package streams

import (
	"sync"

	"github.com/couchbaselabs/transactions-fit-performer/sender"
	"github.com/sirupsen/logrus"
)

type StreamSender struct {
	Stream Stream
	Sender sender.ResultSender
}

// Locked (unlike the Go performer's copy): streams are added by runners but read by RPC handler goroutines.
type StreamOwner struct {
	lock    sync.Mutex
	streams map[string]*StreamSender
	logger  *logrus.Logger
}

func NewStreamOwner(logger *logrus.Logger) *StreamOwner {
	return &StreamOwner{
		streams: make(map[string]*StreamSender),
		logger:  logger,
	}
}

func (so *StreamOwner) Add(streamID string, stream *StreamSender) {
	so.lock.Lock()
	defer so.lock.Unlock()

	so.streams[streamID] = stream
}

func (so *StreamOwner) Get(streamID string) *StreamSender {
	so.lock.Lock()
	defer so.lock.Unlock()

	if stream, ok := so.streams[streamID]; ok {
		return stream
	}

	return nil
}

func (so *StreamOwner) WaitForCompletion(runID string) {
	streams := so.forRun(runID)

	so.logger.Logf(logrus.InfoLevel, "Waiting for %d streams to complete", len(streams))

	// Wait outside the lock: completion depends on StreamRequestItems calling Get.
	for streamID, stream := range streams {
		<-stream.Stream.Completed()
		so.remove(streamID)
	}
}

func (so *StreamOwner) forRun(runID string) map[string]*StreamSender {
	so.lock.Lock()
	defer so.lock.Unlock()

	streams := make(map[string]*StreamSender)
	for streamID, stream := range so.streams {
		if stream.Stream.RunID() == runID {
			streams[streamID] = stream
		}
	}
	return streams
}

func (so *StreamOwner) remove(streamID string) {
	so.lock.Lock()
	defer so.lock.Unlock()

	delete(so.streams, streamID)
}
