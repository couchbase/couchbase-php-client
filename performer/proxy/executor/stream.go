package executor

import (
	"errors"
	"io"
	"sync"
	"sync/atomic"

	"google.golang.org/protobuf/types/known/timestamppb"

	"github.com/couchbaselabs/transactions-fit-performer/phpbackend"
	"github.com/couchbaselabs/transactions-fit-performer/protocol/run"
	"github.com/couchbaselabs/transactions-fit-performer/protocol/sdk"
	"github.com/couchbaselabs/transactions-fit-performer/protocol/shared"
	protoStreams "github.com/couchbaselabs/transactions-fit-performer/protocol/streams"
	"github.com/couchbaselabs/transactions-fit-performer/sender"
	fitStreams "github.com/couchbaselabs/transactions-fit-performer/streams"
)

// Replica reads return arrays from the PHP SDK, so they are fetched eagerly and served from a slice;
// cancel cannot reach the SDK. Scan is a lazy generator read over a long-lived HTTP response, so
// closing it abandons the scan; decoder read-ahead makes pacing approximate.

type itemSource interface {
	// next returns nil, nil when exhausted; an error becomes streams.Error rather than Complete.
	next() (*sdk.Result, error)

	close() error
}

type stream struct {
	streamID string
	runID    string
	source   itemSource

	lock      sync.Mutex
	cancelled bool
	finished  bool

	completed chan struct{}
}

func newStream(streamID, runID string, source itemSource) *stream {
	return &stream{
		streamID:  streamID,
		runID:     runID,
		source:    source,
		completed: make(chan struct{}),
	}
}

func newSliceStream(streamID, runID string, items []*sdk.Result) *stream {
	return newStream(streamID, runID, &sliceSource{items: items})
}

func (s *stream) RunID() string { return s.runID }

func (s *stream) Completed() <-chan struct{} { return s.completed }

func (s *stream) Next() fitStreams.StreamItem {
	result, err := s.nextResult()
	if err != nil || result == nil {
		return nil
	}
	return &streamItem{result: result}
}

func (s *stream) nextResult() (*sdk.Result, error) {
	if s.isCancelled() {
		return nil, nil
	}

	return s.source.next()
}

func (s *stream) Cancel() error {
	s.lock.Lock()
	s.cancelled = true
	s.lock.Unlock()

	return s.source.close()
}

// Err is unused: failures are sent from SendItems as they happen.
func (s *stream) Err() error { return nil }

// Finish may be called by both completion and the cancel RPC; closing completed twice would panic.
func (s *stream) Finish() {
	s.lock.Lock()
	alreadyFinished := s.finished
	s.finished = true
	if !alreadyFinished {
		close(s.completed)
	}
	s.lock.Unlock()

	if !alreadyFinished {
		_ = s.source.close()
	}
}

// SendItems sends up to numItems results, then Complete if the source ran out.
func (s *stream) SendItems(numItems int32, resultSender sender.ResultSender) {
	for i := int32(0); i < numItems; i++ {
		result, err := s.nextResult()

		var failedItem *itemFailure
		if errors.As(err, &failedItem) {
			resultSender.Send(streamError(s.streamID, failedItem.exception))
			continue
		}
		if err != nil {
			resultSender.Send(streamErrorResult(s.streamID, err))
			s.Finish()
			return
		}

		if result == nil {
			// A cancelled stream must not also send Complete; the cancel RPC sends Cancelled.
			if !s.isCancelled() {
				resultSender.Send(streamComplete(s.streamID))
				s.Finish()
			}
			return
		}

		resultSender.Send(&run.Result{
			Initiated: timestamppb.Now(),
			Result:    &run.Result_Sdk{Sdk: result},
		})
	}
}

func (s *stream) SendAll(resultSender sender.ResultSender) {
	s.SendItems(int32(1)<<30, resultSender)
}

func (s *stream) isCancelled() bool {
	s.lock.Lock()
	defer s.lock.Unlock()

	return s.cancelled
}

type streamItem struct {
	result *sdk.Result
}

func (i *streamItem) IsStreamItem() {}

type sliceSource struct {
	items  []*sdk.Result
	cursor int
}

func (s *sliceSource) next() (*sdk.Result, error) {
	if s.cursor >= len(s.items) {
		return nil, nil
	}

	item := s.items[s.cursor]
	s.cursor++
	return item, nil
}

func (s *sliceSource) close() error { return nil }

type scanSource struct {
	stream   *phpbackend.Stream
	streamID string
	done     bool
	closed   atomic.Bool
}

func (s *scanSource) next() (*sdk.Result, error) {
	// Once cancelled, closing the body makes the read fail; that is the end of the scan, not an error.
	if s.done || s.closed.Load() {
		return nil, nil
	}

	line, err := s.stream.Next()
	if err != nil {
		s.done = true
		if errors.Is(err, io.EOF) {
			// EOF without a Complete line means the backend died mid-scan.
			return nil, errors.New("php backend closed the scan stream without completing it")
		}
		return nil, err
	}

	switch {
	case line.Complete:
		s.done = true
		return nil, nil
	case line.Error != nil:
		s.done = true
		return nil, &streamFailure{exception: exceptionToProto(line.Error)}
	case line.ItemError != nil:
		return nil, &itemFailure{exception: exceptionToProto(line.ItemError)}
	case line.Item != nil:
		return scanResult(line.Item, s.streamID)
	default:
		s.done = true
		return nil, errors.New("php backend sent a scan line with no item, error or completion")
	}
}

func (s *scanSource) close() error {
	s.closed.Store(true)
	return s.stream.Close()
}

// streamFailure carries a backend-reported SDK exception so it isn't wrapped as a performer error.
type streamFailure struct {
	exception *shared.Exception
}

func (f *streamFailure) Error() string {
	if couchbase := f.exception.GetCouchbase(); couchbase != nil {
		return couchbase.GetName()
	}
	return f.exception.GetOther().GetName()
}

// itemFailure is one item whose contentAs failed: streams.Error in its place, and the stream carries on.
type itemFailure struct {
	exception *shared.Exception
}

func (f *itemFailure) Error() string { return (&streamFailure{exception: f.exception}).Error() }

func streamCreated(streamID string, streamType protoStreams.Type) *run.Result_Stream {
	return &run.Result_Stream{
		Stream: &protoStreams.Signal{
			Signal: &protoStreams.Signal_Created{
				Created: &protoStreams.Created{StreamId: streamID, Type: streamType},
			},
		},
	}
}

func streamComplete(streamID string) *run.Result {
	return &run.Result{
		Initiated: timestamppb.Now(),
		Result: &run.Result_Stream{
			Stream: &protoStreams.Signal{
				Signal: &protoStreams.Signal_Complete{
					Complete: &protoStreams.Complete{StreamId: streamID},
				},
			},
		},
	}
}

func streamErrorResult(streamID string, err error) *run.Result {
	exception := performerException(err)

	var failure *streamFailure
	if errors.As(err, &failure) {
		exception = failure.exception
	}

	return streamError(streamID, exception)
}

func streamError(streamID string, exception *shared.Exception) *run.Result {
	return &run.Result{
		Initiated: timestamppb.Now(),
		Result: &run.Result_Stream{
			Stream: &protoStreams.Signal{
				Signal: &protoStreams.Signal_Error{
					Error: &protoStreams.Error{StreamId: streamID, Exception: exception},
				},
			},
		},
	}
}

func StreamCancelled(streamID string) *run.Result {
	return &run.Result{
		Initiated: timestamppb.Now(),
		Result: &run.Result_Stream{
			Stream: &protoStreams.Signal{
				Signal: &protoStreams.Signal_Cancelled{
					Cancelled: &protoStreams.Cancelled{StreamId: streamID},
				},
			},
		},
	}
}
