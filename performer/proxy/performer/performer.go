package frontend

import (
	"context"
	"sync"

	"github.com/google/uuid"
	"google.golang.org/grpc/codes"
	"google.golang.org/grpc/status"

	"github.com/sirupsen/logrus"

	"github.com/couchbaselabs/transactions-fit-performer/common"
	"github.com/couchbaselabs/transactions-fit-performer/connections"
	"github.com/couchbaselabs/transactions-fit-performer/counter"
	"github.com/couchbaselabs/transactions-fit-performer/executor"
	"github.com/couchbaselabs/transactions-fit-performer/phpbackend"
	"github.com/couchbaselabs/transactions-fit-performer/protocol/run"

	"github.com/couchbaselabs/transactions-fit-performer/protocol"
	"github.com/couchbaselabs/transactions-fit-performer/protocol/performer"
	"github.com/couchbaselabs/transactions-fit-performer/protocol/shared"

	protoSDK "github.com/couchbaselabs/transactions-fit-performer/protocol/sdk"
	protoStreams "github.com/couchbaselabs/transactions-fit-performer/protocol/streams"
	protoTransactions "github.com/couchbaselabs/transactions-fit-performer/protocol/transactions"
	"github.com/couchbaselabs/transactions-fit-performer/sender"
	fitStreams "github.com/couchbaselabs/transactions-fit-performer/streams"
)

type Performer struct {
	lock             sync.Mutex
	logger           *logrus.Logger
	performerVersion string
	streams          *fitStreams.StreamOwner
	phpClient        *phpbackend.Client
	connections      *connections.Registry
	// Outlives any single Run: SetCounter/ClearAllCounters arrive between runs.
	counters *counter.Counters
	protocol.UnimplementedPerformerServiceServer
}

func NewPerformer(logger *logrus.Logger, version string, phpBackendURL string) *Performer {
	return &Performer{
		logger:           logger,
		performerVersion: version,
		streams:          fitStreams.NewStreamOwner(logger),
		phpClient:        phpbackend.New(phpBackendURL),
		connections:      connections.NewRegistry(),
		counters:         counter.NewCounters(),
	}
}

func (ts *Performer) PerformerCapsFetch(context.Context, *performer.PerformerCapsFetchRequest) (*performer.PerformerCapsFetchResponse, error) {
	ts.logger.Log(logrus.InfoLevel, "PerformerCapsFetch called")

	sdkCaps := []protoSDK.Caps{
		protoSDK.Caps_SDK_KV,
		protoSDK.Caps_SUPPORTS_AUTHENTICATOR,
		protoSDK.Caps_SDK_DOCUMENT_NOT_LOCKED,
		protoSDK.Caps_SDK_LOOKUP_IN,
		protoSDK.Caps_SDK_LOOKUP_IN_REPLICAS,
		// Not ..._SELECTED_SERVER_GROUP_OR_ALL_AVAILABLE: the PHP SDK has no such mode.
		protoSDK.Caps_SDK_ZONE_AWARE_READ_FROM_REPLICA,
		protoSDK.Caps_SDK_KV_RANGE_SCAN,
	}

	// Not CLUSTER_CONFIG_1: PHP ClusterOptions lacks transcoder and num_kv_connections.
	performerCaps := []performer.Caps{
		performer.Caps_KV_SUPPORT_1,
		performer.Caps_CLUSTER_CONFIG_CERT,
		performer.Caps_CLUSTER_CONFIG_INSECURE,
		performer.Caps_TIMING_ON_FAILED_OPS,
	}

	return &performer.PerformerCapsFetchResponse{
		TransactionImplementationsCaps: []protoTransactions.Caps{},
		PerformerUserAgent:             "php",
		PerformerCaps:                  performerCaps,
		LibraryVersion:                 ts.performerVersion,
		SupportedApis:                  []shared.API{shared.API_DEFAULT},
		SdkImplementationCaps:          sdkCaps,
	}, nil
}

func (ts *Performer) Echo(_ctx context.Context, req *shared.EchoRequest) (*shared.EchoResponse, error) {
	ts.logger.Logf(logrus.InfoLevel, "================ %s : %s ================", req.TestName, req.Message)
	return &shared.EchoResponse{}, nil
}

func (ts *Performer) ClusterConnectionCreate(_ctx context.Context, req *shared.ClusterConnectionCreateRequest) (*shared.ClusterConnectionCreateResponse, error) {
	ts.logger.Log(logrus.InfoLevel, "ClusterConnectionCreate called")

	params, err := executor.ConnectionParams(req)
	if err != nil {
		return nil, status.Errorf(codes.InvalidArgument, "cannot apply the requested cluster options for %s: %v", req.ClusterConnectionId, err)
	}

	// Don't log config contents: they hold credentials.
	ts.logger.Logf(logrus.InfoLevel, "Creating connection %s to %s with %s auth, cluster config present: %t",
		req.ClusterConnectionId, params.Connstr, params.Auth.Kind, params.Config != nil)

	if err := ts.phpClient.CheckConnection(params); err != nil {
		return nil, status.Errorf(codes.Unknown, "failed to create the connection %s: %v", req.ClusterConnectionId, err)
	}

	count := ts.connections.Add(req.ClusterConnectionId, params)

	return &shared.ClusterConnectionCreateResponse{
		ClusterConnectionCount: int32(count),
	}, nil
}

func (ts *Performer) ClusterConnectionClose(_ctx context.Context, req *shared.ClusterConnectionCloseRequest) (*shared.ClusterConnectionCloseResponse, error) {
	ts.logger.Log(logrus.InfoLevel, "ClusterConnectionClose called")

	params, err := ts.connections.Get(req.ClusterConnectionId)
	if err != nil {
		return nil, status.Errorf(codes.InvalidArgument, "connection not known for %s", req.ClusterConnectionId)
	}

	if err := ts.phpClient.CloseConnection(params); err != nil {
		ts.logger.Logf(logrus.WarnLevel, "failed to close connection %s on php backend: %v", req.ClusterConnectionId, err)
	}

	count := ts.connections.Remove(req.ClusterConnectionId)

	return &shared.ClusterConnectionCloseResponse{
		ClusterConnectionCount: int32(count),
	}, nil
}

func (ts *Performer) DisconnectConnections(_ctx context.Context,
	_req *shared.DisconnectConnectionsRequest) (*shared.DisconnectConnectionsResponse, error) {
	ts.logger.Log(logrus.InfoLevel, "DisconnectConnections called")

	for id, params := range ts.connections.All() {
		if err := ts.phpClient.CloseConnection(params); err != nil {
			ts.logger.Logf(logrus.WarnLevel, "failed to close connection %s on php backend: %v", id, err)
		}
		ts.connections.Remove(id)
	}

	return &shared.DisconnectConnectionsResponse{}, nil
}

// StreamCancel stops the stream; how far the cancel reaches depends on its source (see executor/stream.go).
func (ts *Performer) StreamCancel(_ctx context.Context, req *protoStreams.CancelRequest) (*protoStreams.CancelResponse, error) {
	ts.logger.Logf(logrus.InfoLevel, "StreamCancel called for %s", req.StreamId)

	stream := ts.streams.Get(req.StreamId)
	if stream == nil {
		return nil, status.Errorf(codes.Unknown, "stream id %s not known", req.StreamId)
	}

	if err := stream.Stream.Cancel(); err != nil {
		return nil, status.Errorf(codes.Aborted, "failed to cancel stream %s: %v", req.StreamId, err)
	}

	// Cancelled replaces Complete.
	stream.Sender.Send(executor.StreamCancelled(req.StreamId))
	stream.Stream.Finish()

	return &protoStreams.CancelResponse{}, nil
}

// Asking for more items than remain is not an error.
func (ts *Performer) StreamRequestItems(_ctx context.Context, req *protoStreams.RequestItemsRequest) (*protoStreams.RequestItemsResponse, error) {
	ts.logger.Logf(logrus.InfoLevel, "StreamRequestItems called for %s, requesting %d items", req.StreamId, req.NumItems)

	stream := ts.streams.Get(req.StreamId)
	if stream == nil {
		return nil, status.Errorf(codes.Unknown, "stream id %s not known", req.StreamId)
	}

	sendable, ok := stream.Stream.(itemSender)
	if !ok {
		return nil, status.Errorf(codes.Internal, "stream %s cannot deliver items on demand (%T)", req.StreamId, stream.Stream)
	}

	sendable.SendItems(req.NumItems, stream.Sender)

	return &protoStreams.RequestItemsResponse{}, nil
}

type itemSender interface {
	SendItems(numItems int32, sender sender.ResultSender)
}

// SetCounter is how the driver stops counter_eq-bounded workloads from outside.
func (ts *Performer) SetCounter(_ctx context.Context, req *shared.Counter) (*shared.SetCounterResponse, error) {
	c, err := ts.counters.Get(req)
	if err != nil {
		return nil, status.Errorf(codes.InvalidArgument, "failed to set counter with id %s: %v", req.GetCounterId(), err)
	}

	newValue := req.GetGlobal().GetCount()
	c.Set(newValue)

	ts.logger.Logf(logrus.InfoLevel, "Set counter with id %s to %d", req.GetCounterId(), newValue)

	return &shared.SetCounterResponse{}, nil
}

func (ts *Performer) ClearAllCounters(_ctx context.Context,
	_req *shared.ClearAllCountersRequest) (*shared.ClearAllCountersResponse, error) {
	ts.counters.Clear()

	ts.logger.Log(logrus.InfoLevel, "Cleared all counters")

	return &shared.ClearAllCountersResponse{}, nil
}

func (p *Performer) Run(request *run.Request, server protocol.PerformerService_RunServer) error {
	p.logger.Log(logrus.InfoLevel, "Run called")

	workloads, ok := request.Request.(*run.Request_Workloads)
	if !ok {
		return status.Errorf(codes.Aborted, "request not workloads")
	}

	connParams, err := p.connections.Get(workloads.Workloads.ClusterConnectionId)
	if err != nil {
		return status.Errorf(codes.InvalidArgument, "connection not known for %s", workloads.Workloads.ClusterConnectionId)
	}

	var batchSize int32
	if request.Config != nil && request.Config.StreamingConfig != nil {
		batchSize = request.Config.StreamingConfig.GetBatchSize()
	}

	onError := make(chan error)
	doneCh := make(chan struct{})
	go func() {
		select {
		case <-onError:
			return
		case <-doneCh:
			return
		}
	}()

	runID := uuid.NewString()

	exec := executor.NewExecutor(p.counters, runID, p.streams, p.logger, p.phpClient, connParams)

	batchHandler := common.NewBatcher(server, p.logger, onError, batchSize)

	batchHandler.Run()

	var scaleRunners []*common.HorizontalScaleRunner
	for i, scaling := range workloads.Workloads.HorizontalScaling {
		r := common.NewHorizontalScaleRunner(p.logger, exec, p.counters, common.PerHorizontalRunner{
			Sender:      batchHandler,
			RunnerIndex: i,
			Workloads:   scaling.Workloads,
		})
		scaleRunners = append(scaleRunners, r)
	}

	for _, r := range scaleRunners {
		go r.Run()
	}

	p.logger.Logf(logrus.InfoLevel, "Started %d runners", len(scaleRunners))

	for _, r := range scaleRunners {
		r.Wait()
	}

	p.logger.Logf(logrus.InfoLevel, "All %d runners completed", len(scaleRunners))

	p.streams.WaitForCompletion(runID)

	p.logger.Logf(logrus.InfoLevel, "All streams completed")

	<-batchHandler.Stop()

	close(doneCh)

	return nil
}
