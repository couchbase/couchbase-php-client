package executor

import (
	"errors"
	"fmt"

	"github.com/sirupsen/logrus"
	"google.golang.org/grpc/codes"
	"google.golang.org/grpc/status"
	"google.golang.org/protobuf/types/known/timestamppb"

	"github.com/couchbaselabs/transactions-fit-performer/counter"
	"github.com/couchbaselabs/transactions-fit-performer/phpbackend"
	"github.com/couchbaselabs/transactions-fit-performer/sender"
	fitStreams "github.com/couchbaselabs/transactions-fit-performer/streams"

	"github.com/couchbaselabs/transactions-fit-performer/protocol/run"
	"github.com/couchbaselabs/transactions-fit-performer/protocol/sdk"
	"github.com/couchbaselabs/transactions-fit-performer/protocol/sdk/kv"
	"github.com/couchbaselabs/transactions-fit-performer/protocol/sdk/kv/lookupin"
	"github.com/couchbaselabs/transactions-fit-performer/protocol/sdk/kv/rangescan"
	"github.com/couchbaselabs/transactions-fit-performer/protocol/shared"
	protoStreams "github.com/couchbaselabs/transactions-fit-performer/protocol/streams"
)

// Executor forwards sdk.Command workloads to the PHP backend; it holds no SDK connection itself.
type Executor struct {
	counters    *counter.Counters
	runID       string
	streamOwner *fitStreams.StreamOwner
	logger      *logrus.Logger
	phpClient   *phpbackend.Client
	connection  phpbackend.ConnectionParams
}

func NewExecutor(counters *counter.Counters, runID string, streamOwner *fitStreams.StreamOwner,
	logger *logrus.Logger, phpClient *phpbackend.Client, connection phpbackend.ConnectionParams) *Executor {
	return &Executor{
		counters:    counters,
		runID:       runID,
		streamOwner: streamOwner,
		logger:      logger,
		phpClient:   phpClient,
		connection:  connection,
	}
}

func (e *Executor) PerformOperation(command *sdk.Command, sender sender.ResultSender) bool {
	return e.performOperation(command, sender)
}

func (e *Executor) performOperation(command *sdk.Command, sender sender.ResultSender) bool {
	switch op := command.Command.(type) {
	case *sdk.Command_Insert:
		opts, err := insertOptions(op.Insert.Options)
		if err != nil {
			e.sendSDKError(err, sender)
			return false
		}
		return e.performMutation("insert", op.Insert.Location, op.Insert.Content, opts, command.ReturnResult, sender)
	case *sdk.Command_Replace:
		opts, err := replaceOptions(op.Replace.Options)
		if err != nil {
			e.sendSDKError(err, sender)
			return false
		}
		return e.performMutation("replace", op.Replace.Location, op.Replace.Content, opts, command.ReturnResult, sender)
	case *sdk.Command_Upsert:
		opts, err := upsertOptions(op.Upsert.Options)
		if err != nil {
			e.sendSDKError(err, sender)
			return false
		}
		return e.performMutation("upsert", op.Upsert.Location, op.Upsert.Content, opts, command.ReturnResult, sender)
	case *sdk.Command_Remove:
		opts, err := removeOptions(op.Remove.Options)
		if err != nil {
			e.sendSDKError(err, sender)
			return false
		}
		return e.performMutation("remove", op.Remove.Location, nil, opts, command.ReturnResult, sender)
	case *sdk.Command_Get:
		return e.performGet(op.Get, command.ReturnResult, sender)
	case *sdk.Command_ClusterCommand:
		e.sendUnimplemented("cluster", sender)
		return false
	case *sdk.Command_BucketCommand:
		e.sendUnimplemented("bucket", sender)
		return false
	case *sdk.Command_CollectionCommand:
		return e.performCollectionCommand(op.CollectionCommand, command.ReturnResult, sender)
	case *sdk.Command_ScopeCommand:
		e.sendUnimplemented("scope", sender)
		return false
	case *sdk.Command_RangeScan:
		return e.performScan(op.RangeScan, sender)
	default:
		e.sendUnimplemented("unknown", sender)
		return false
	}
}

func (e *Executor) performCollectionCommand(cmd *sdk.CollectionLevelCommand, returnResult bool,
	sender sender.ResultSender) bool {

	switch op := cmd.Command.(type) {
	case *sdk.CollectionLevelCommand_Exists:
		opts, err := existsOptions(op.Exists.Options)
		if err != nil {
			e.sendSDKError(err, sender)
			return false
		}
		return e.perform(phpbackend.ExecuteRequest{Op: "exists", Options: opts},
			op.Exists.Location, existsResult, returnResult, sender)

	case *sdk.CollectionLevelCommand_Touch:
		opts, err := touchOptions(op.Touch.Options)
		if err != nil {
			e.sendSDKError(err, sender)
			return false
		}
		expiry, err := expiryToWire(op.Touch.Expiry)
		if err != nil {
			e.sendSDKError(err, sender)
			return false
		}
		return e.perform(phpbackend.ExecuteRequest{Op: "touch", Options: opts, Expiry: expiry},
			op.Touch.Location, mutationResult, returnResult, sender)

	case *sdk.CollectionLevelCommand_GetAndTouch:
		opts, err := getAndTouchOptions(op.GetAndTouch.Options)
		if err != nil {
			e.sendSDKError(err, sender)
			return false
		}
		expiry, err := expiryToWire(op.GetAndTouch.Expiry)
		if err != nil {
			e.sendSDKError(err, sender)
			return false
		}
		return e.perform(phpbackend.ExecuteRequest{
			Op:        "get_and_touch",
			Options:   opts,
			Expiry:    expiry,
			ContentAs: contentAsToString(op.GetAndTouch.ContentAs),
		}, op.GetAndTouch.Location, getResult, returnResult, sender)

	case *sdk.CollectionLevelCommand_GetAndLock:
		opts, err := getAndLockOptions(op.GetAndLock.Options)
		if err != nil {
			e.sendSDKError(err, sender)
			return false
		}
		lockSeconds := int32(op.GetAndLock.Duration.AsDuration().Seconds())
		return e.perform(phpbackend.ExecuteRequest{
			Op:          "get_and_lock",
			Options:     opts,
			LockSeconds: &lockSeconds,
			ContentAs:   contentAsToString(op.GetAndLock.ContentAs),
		}, op.GetAndLock.Location, getResult, returnResult, sender)

	case *sdk.CollectionLevelCommand_Unlock:
		opts, err := unlockOptions(op.Unlock.Options)
		if err != nil {
			e.sendSDKError(err, sender)
			return false
		}
		// casToHex, not casToString: the driver sends CAS 0 on purpose.
		cas := casToHex(op.Unlock.Cas)
		return e.perform(phpbackend.ExecuteRequest{
			Op:      "unlock",
			Options: opts,
			Cas:     &cas,
		}, op.Unlock.Location, successResult, returnResult, sender)

	case *sdk.CollectionLevelCommand_MutateIn:
		opts, err := mutateInOptions(op.MutateIn.Options)
		if err != nil {
			e.sendSDKError(err, sender)
			return false
		}
		specs, err := mutateInSpecs(op.MutateIn.Spec)
		if err != nil {
			e.sendSDKError(err, sender)
			return false
		}
		return e.perform(phpbackend.ExecuteRequest{Op: "mutate_in", Options: opts, Specs: specs},
			op.MutateIn.Location, mutateInResult, returnResult, sender)

	case *sdk.CollectionLevelCommand_GetAnyReplica:
		opts, err := getAnyReplicaOptions(op.GetAnyReplica.Options)
		if err != nil {
			e.sendSDKError(err, sender)
			return false
		}
		return e.perform(phpbackend.ExecuteRequest{
			Op:        "get_any_replica",
			Options:   opts,
			ContentAs: contentAsToString(op.GetAnyReplica.ContentAs),
		}, op.GetAnyReplica.Location, getReplicaResult, returnResult, sender)

	case *sdk.CollectionLevelCommand_LookupIn:
		opts, err := lookupInOptions(op.LookupIn.Options)
		if err != nil {
			e.sendSDKError(err, sender)
			return false
		}
		specs, err := lookupInSpecs(op.LookupIn.Spec)
		if err != nil {
			e.sendSDKError(err, sender)
			return false
		}
		return e.perform(phpbackend.ExecuteRequest{Op: "lookup_in", Options: opts, LookupSpecs: specs},
			op.LookupIn.Location, lookupInResult, returnResult, sender)

	case *sdk.CollectionLevelCommand_LookupInAnyReplica:
		opts, err := lookupInAnyReplicaOptions(op.LookupInAnyReplica.Options)
		if err != nil {
			e.sendSDKError(err, sender)
			return false
		}
		specs, err := lookupInSpecs(op.LookupInAnyReplica.Spec)
		if err != nil {
			e.sendSDKError(err, sender)
			return false
		}
		return e.perform(phpbackend.ExecuteRequest{
			Op:          "lookup_in_any_replica",
			Options:     opts,
			LookupSpecs: specs,
		}, op.LookupInAnyReplica.Location, lookupInAnyReplicaResult, returnResult, sender)

	case *sdk.CollectionLevelCommand_LookupInAllReplicas:
		return e.performLookupInAllReplicas(op.LookupInAllReplicas, sender)

	case *sdk.CollectionLevelCommand_GetAllReplicas:
		return e.performGetAllReplicas(op.GetAllReplicas, sender)

	case *sdk.CollectionLevelCommand_Binary:
		return e.performBinaryCommand(op.Binary, returnResult, sender)

	default:
		e.sendUnimplemented(fmt.Sprintf("collection-level %T", op), sender)
		return false
	}
}

// performGetAllReplicas ignores return_result: the driver reads the items off the stream.
func (e *Executor) performGetAllReplicas(cmd *kv.GetAllReplicas, sender sender.ResultSender) bool {
	opts, err := getAllReplicasOptions(cmd.Options)
	if err != nil {
		e.sendSDKError(err, sender)
		return false
	}

	return e.performStreamed(phpbackend.ExecuteRequest{
		Op:        "get_all_replicas",
		Options:   opts,
		ContentAs: contentAsToString(cmd.ContentAs),
	}, cmd.Location, cmd.StreamConfig, protoStreams.Type_STREAM_KV_GET_ALL_REPLICAS,
		replicaResults, sender)
}

func (e *Executor) performLookupInAllReplicas(cmd *lookupin.LookupInAllReplicas, sender sender.ResultSender) bool {
	opts, err := lookupInAllReplicasOptions(cmd.Options)
	if err != nil {
		e.sendSDKError(err, sender)
		return false
	}
	specs, err := lookupInSpecs(cmd.Spec)
	if err != nil {
		e.sendSDKError(err, sender)
		return false
	}

	return e.performStreamed(phpbackend.ExecuteRequest{
		Op:          "lookup_in_all_replicas",
		Options:     opts,
		LookupSpecs: specs,
	}, cmd.Location, cmd.StreamConfig, protoStreams.Type_STREAM_LOOKUP_IN_ALL_REPLICAS,
		lookupInReplicaResults, sender)
}

type streamShaper func(resp *phpbackend.ExecuteResponse, streamID string) ([]*sdk.Result, error)

// performStreamed ignores return_result: the driver reads the items off the stream.
func (e *Executor) performStreamed(req phpbackend.ExecuteRequest, docLoc *shared.DocLocation,
	config *protoStreams.Config, streamType protoStreams.Type, shaper streamShaper,
	sender sender.ResultSender) bool {

	if config == nil {
		e.sendSDKError(fmt.Errorf("%s arrived with no stream config, so there is no stream id to "+
			"report its results under", req.Op), sender)
		return false
	}
	streamID := config.StreamId

	resp, result := e.send(req, docLoc, sender)
	if resp == nil {
		return false
	}

	items, err := shaper(resp, streamID)
	if err != nil {
		e.sendSDKError(err, sender)
		return false
	}

	return e.startStream(newSliceStream(streamID, e.runID, items), config, streamType, result, sender)
}

func (e *Executor) performScan(cmd *rangescan.Scan, sender sender.ResultSender) bool {
	if cmd.StreamConfig == nil {
		e.sendSDKError(errors.New("scan arrived with no stream config, so there is no stream id to "+
			"report its results under"), sender)
		return false
	}
	streamID := cmd.StreamConfig.StreamId

	scanType, err := scanType(cmd.ScanType)
	if err != nil {
		e.sendSDKError(err, sender)
		return false
	}
	opts, err := scanOptions(cmd.Options)
	if err != nil {
		e.sendSDKError(err, sender)
		return false
	}

	result := &run.Result{Initiated: timestamppb.Now()}
	backendStream, err := e.phpClient.ExecuteStream(phpbackend.ExecuteRequest{
		Connection: e.connection,
		Op:         "scan",
		Location:   collectionLocation(cmd.Collection),
		ScanType:   scanType,
		Options:    opts,
		ContentAs:  contentAsToString(cmd.ContentAs),
	})
	if err != nil {
		e.sendSDKError(err, sender)
		return false
	}

	// Fail the command, not the stream, until the backend confirms the scan was created.
	line, err := backendStream.Next()
	if err != nil {
		_ = backendStream.Close()
		e.sendSDKError(fmt.Errorf("reading the scan's first line: %w", err), sender)
		return false
	}
	if !line.Created {
		_ = backendStream.Close()
		if line.Exception != nil {
			e.sendPHPException(&phpbackend.ExecuteResponse{Exception: line.Exception}, result, sender)
		} else {
			e.sendSDKError(errors.New("php backend did not report the scan as created"), sender)
		}
		return false
	}

	source := &scanSource{stream: backendStream, streamID: streamID}
	return e.startStream(newStream(streamID, e.runID, source), cmd.StreamConfig,
		protoStreams.Type_STREAM_KV_RANGE_SCAN, result, sender)
}

func (e *Executor) startStream(stream *stream, config *protoStreams.Config,
	streamType protoStreams.Type, result *run.Result, sender sender.ResultSender) bool {

	// Created must reach the driver before the stream is registered and can be driven.
	result.Result = streamCreated(stream.streamID, streamType)
	sender.Send(result)

	e.streamOwner.Add(stream.streamID, &fitStreams.StreamSender{Stream: stream, Sender: sender})

	switch config.StreamWhen.(type) {
	case *protoStreams.Config_OnDemand:
		e.logger.Logf(logrus.InfoLevel, "Stream %s is on demand, waiting for the driver", stream.streamID)
		return true
	case *protoStreams.Config_Automatically:
		stream.SendAll(sender)
		return true
	default:
		e.sendSDKError(fmt.Errorf("unsupported stream_when %T", config.StreamWhen), sender)
		stream.Finish()
		return false
	}
}

func (e *Executor) performBinaryCommand(cmd *sdk.BinaryCollectionLevelCommand, returnResult bool,
	sender sender.ResultSender) bool {

	switch op := cmd.Command.(type) {
	case *sdk.BinaryCollectionLevelCommand_Append:
		opts, err := appendOptions(op.Append.Options)
		if err != nil {
			e.sendSDKError(err, sender)
			return false
		}
		return e.perform(phpbackend.ExecuteRequest{
			Op:      "append",
			Options: opts,
			Content: rawBytes(op.Append.Content),
		}, op.Append.Location, mutationResult, returnResult, sender)

	case *sdk.BinaryCollectionLevelCommand_Prepend:
		opts, err := prependOptions(op.Prepend.Options)
		if err != nil {
			e.sendSDKError(err, sender)
			return false
		}
		return e.perform(phpbackend.ExecuteRequest{
			Op:      "prepend",
			Options: opts,
			Content: rawBytes(op.Prepend.Content),
		}, op.Prepend.Location, mutationResult, returnResult, sender)

	case *sdk.BinaryCollectionLevelCommand_Increment:
		opts, err := incrementOptions(op.Increment.Options)
		if err != nil {
			e.sendSDKError(err, sender)
			return false
		}
		return e.perform(phpbackend.ExecuteRequest{Op: "increment", Options: opts},
			op.Increment.Location, counterResult, returnResult, sender)

	case *sdk.BinaryCollectionLevelCommand_Decrement:
		opts, err := decrementOptions(op.Decrement.Options)
		if err != nil {
			e.sendSDKError(err, sender)
			return false
		}
		return e.perform(phpbackend.ExecuteRequest{Op: "decrement", Options: opts},
			op.Decrement.Location, counterResult, returnResult, sender)

	default:
		e.sendUnimplemented(fmt.Sprintf("binary %T", op), sender)
		return false
	}
}

func (e *Executor) performMutation(op string, docLoc *shared.DocLocation, content *shared.Content,
	opts *phpbackend.Options, returnResult bool, sender sender.ResultSender) bool {

	contentInput, err := contentToInput(content)
	if err != nil {
		e.sendSDKError(err, sender)
		return false
	}

	return e.perform(phpbackend.ExecuteRequest{Op: op, Content: contentInput, Options: opts},
		docLoc, mutationResult, returnResult, sender)
}

func (e *Executor) performGet(get *kv.Get, returnResult bool, sender sender.ResultSender) bool {
	opts, err := getOptions(get.Options)
	if err != nil {
		e.sendSDKError(err, sender)
		return false
	}

	return e.perform(phpbackend.ExecuteRequest{
		Op:        "get",
		Options:   opts,
		ContentAs: contentAsToString(get.ContentAs),
	}, get.Location, getResult, returnResult, sender)
}

type resultShaper func(resp *phpbackend.ExecuteResponse) (*sdk.Result, error)

// perform reports every failure as a result rather than returning it; workloads keep running.
func (e *Executor) perform(req phpbackend.ExecuteRequest, docLoc *shared.DocLocation,
	shaper resultShaper, returnResult bool, sender sender.ResultSender) bool {

	resp, result := e.send(req, docLoc, sender)
	if resp == nil {
		return false
	}

	if returnResult {
		sdkResult, err := shaper(resp)
		if err != nil {
			e.sendSDKError(err, sender)
			return false
		}
		result.Result = &run.Result_Sdk{Sdk: sdkResult}
	} else {
		result.Result = e.makeSuccessResult()
	}

	sender.Send(result)
	return true
}

// send returns a nil response if it already reported a failure.
func (e *Executor) send(req phpbackend.ExecuteRequest, docLoc *shared.DocLocation,
	sender sender.ResultSender) (*phpbackend.ExecuteResponse, *run.Result) {

	loc, err := location(docLoc, e.counters)
	if err != nil {
		e.sendSDKError(err, sender)
		return nil, nil
	}

	req.Connection = e.connection
	req.Location = toPHPLocation(loc)

	// Taken before the call: the protocol wants initiation time, even for a timeout.
	result := &run.Result{Initiated: timestamppb.Now()}

	resp, err := e.phpClient.Execute(req)
	if err != nil {
		e.sendSDKError(err, sender)
		return nil, nil
	}
	if !resp.OK {
		e.sendPHPException(resp, result, sender)
		return nil, nil
	}

	result.ElapsedNanos = resp.ElapsedMicros * 1000
	return resp, result
}

func (e *Executor) makeSuccessResult() *run.Result_Sdk {
	return &run.Result_Sdk{
		Sdk: &sdk.Result{
			Result: &sdk.Result_Success{Success: true},
		},
	}
}

func (e *Executor) sendSDKError(err error, sender sender.ResultSender) {
	sender.Send(&run.Result{
		Result: &run.Result_Sdk{
			Sdk: &sdk.Result{
				Result: &sdk.Result_Exception{
					Exception: &shared.Exception{
						Exception: &shared.Exception_Other{
							Other: &shared.ExceptionOther{
								Name:       "PerformerError",
								Serialized: err.Error(),
							},
						},
					},
				},
			},
		},
	})
}

func (e *Executor) sendPHPException(resp *phpbackend.ExecuteResponse, result *run.Result,
	sender sender.ResultSender) {

	result.ElapsedNanos = resp.ElapsedMicros * 1000
	result.Result = &run.Result_Sdk{
		Sdk: &sdk.Result{
			Result: &sdk.Result_Exception{
				Exception: exceptionToProto(resp.Exception),
			},
		},
	}
	sender.Send(result)
}

func (e *Executor) sendUnimplemented(kind string, sender sender.ResultSender) {
	e.sendSDKError(status.Errorf(codes.Unimplemented, "%s command is unimplemented in performer", kind), sender)
}
