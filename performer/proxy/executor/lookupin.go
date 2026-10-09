package executor

import (
	"errors"
	"fmt"

	"github.com/couchbaselabs/transactions-fit-performer/phpbackend"
	"github.com/couchbaselabs/transactions-fit-performer/protocol/sdk"
	"github.com/couchbaselabs/transactions-fit-performer/protocol/sdk/kv/lookupin"
	"github.com/couchbaselabs/transactions-fit-performer/protocol/shared"
)

// Each spec reports content and exists independently; either can fail while the other succeeds
// (a get on a missing path is exists=false plus a PathNotFound content exception).

func lookupInSpecs(specs []*lookupin.LookupInSpec) ([]phpbackend.LookupSpec, error) {
	out := make([]phpbackend.LookupSpec, 0, len(specs))

	for _, spec := range specs {
		wire := phpbackend.LookupSpec{ContentAs: contentAsToString(spec.ContentAs)}

		switch op := spec.Operation.(type) {
		case *lookupin.LookupInSpec_Get:
			wire.Kind = "get"
			wire.Path = op.Get.Path
			wire.Xattr = op.Get.Xattr
		case *lookupin.LookupInSpec_Exists:
			wire.Kind = "exists"
			wire.Path = op.Exists.Path
			wire.Xattr = op.Exists.Xattr
		case *lookupin.LookupInSpec_Count:
			wire.Kind = "count"
			wire.Path = op.Count.Path
			wire.Xattr = op.Count.Xattr
		default:
			return nil, fmt.Errorf("unsupported lookup-in spec %T", op)
		}

		out = append(out, wire)
	}

	return out, nil
}

func lookupInOptions(opts *lookupin.LookupInOptions) (*phpbackend.Options, error) {
	if opts == nil {
		return nil, nil
	}
	if err := rejectParentSpan(opts.ParentSpanId); err != nil {
		return nil, err
	}

	return &phpbackend.Options{
		TimeoutMillis: timeoutFromUint32(opts.TimeoutMillis),
		AccessDeleted: opts.AccessDeleted,
	}, nil
}

func lookupInAllReplicasOptions(opts *lookupin.LookupInAllReplicasOptions) (*phpbackend.Options, error) {
	if opts == nil {
		return nil, nil
	}
	if err := rejectParentSpan(opts.ParentSpanId); err != nil {
		return nil, err
	}

	out := &phpbackend.Options{TimeoutMillis: timeoutFromUint32(opts.TimeoutMillis)}
	setReadPreference(out, opts.ReadPreference)
	return out, nil
}

func lookupInAnyReplicaOptions(opts *lookupin.LookupInAnyReplicaOptions) (*phpbackend.Options, error) {
	if opts == nil {
		return nil, nil
	}
	if err := rejectParentSpan(opts.ParentSpanId); err != nil {
		return nil, err
	}

	out := &phpbackend.Options{TimeoutMillis: timeoutFromUint32(opts.TimeoutMillis)}
	setReadPreference(out, opts.ReadPreference)
	return out, nil
}

// timeoutFromUint32: lookup-in is the only options family with a uint32 timeout.
func timeoutFromUint32(millis *uint32) *int32 {
	if millis == nil {
		return nil
	}

	converted := int32(*millis)
	return &converted
}

func lookupInResult(resp *phpbackend.ExecuteResponse) (*sdk.Result, error) {
	return &sdk.Result{
		Result: &sdk.Result_LookupInResult{
			LookupInResult: &lookupin.LookupInResult{
				Cas:     casFromString(resp.Cas),
				Results: lookupSpecResults(resp.LookupResults),
			},
		},
	}, nil
}

func lookupInAnyReplicaResult(resp *phpbackend.ExecuteResponse) (*sdk.Result, error) {
	if resp.IsReplica == nil {
		return nil, errors.New("php backend did not say whether the lookup came from a replica")
	}

	return &sdk.Result{
		Result: &sdk.Result_LookupInAnyReplicaResult{
			LookupInAnyReplicaResult: &lookupin.LookupInReplicaResult{
				Cas:       casFromString(resp.Cas),
				IsReplica: *resp.IsReplica,
				Results:   lookupSpecResults(resp.LookupResults),
			},
		},
	}, nil
}

func lookupInReplicaResults(resp *phpbackend.ExecuteResponse, streamID string) ([]*sdk.Result, error) {
	if len(resp.LookupReplicas) == 0 {
		return nil, errors.New("php backend returned no replica lookup results")
	}

	results := make([]*sdk.Result, 0, len(resp.LookupReplicas))
	for _, replica := range resp.LookupReplicas {
		results = append(results, &sdk.Result{
			Result: &sdk.Result_LookupInAllReplicasResult{
				LookupInAllReplicasResult: &lookupin.LookupInAllReplicasResult{
					StreamId: streamID,
					LookupInReplicaResult: &lookupin.LookupInReplicaResult{
						Cas:       casFromString(replica.Cas),
						IsReplica: replica.IsReplica,
						Results:   lookupSpecResults(replica.Results),
					},
				},
			},
		})
	}

	return results, nil
}

// A content payload that fails to decode becomes that spec's exception, not a command failure.
func lookupSpecResults(results []phpbackend.LookupSpecResult) []*lookupin.LookupInSpecResult {
	out := make([]*lookupin.LookupInSpecResult, 0, len(results))

	for _, result := range results {
		specResult := &lookupin.LookupInSpecResult{}

		switch {
		case result.ContentException != nil:
			specResult.ContentAsResult = &shared.ContentOrError{
				Result: &shared.ContentOrError_Exception{
					Exception: exceptionToProto(result.ContentException),
				},
			}
		case result.Content != nil:
			content, err := contentPayloadToContentTypes(result.Content)
			if err != nil {
				specResult.ContentAsResult = &shared.ContentOrError{
					Result: &shared.ContentOrError_Exception{
						Exception: performerException(err),
					},
				}
				break
			}
			specResult.ContentAsResult = &shared.ContentOrError{
				Result: &shared.ContentOrError_Content{Content: content},
			}
		}

		switch {
		case result.ExistsException != nil:
			specResult.ExistsResult = &lookupin.BooleanOrError{
				Result: &lookupin.BooleanOrError_Exception{
					Exception: exceptionToProto(result.ExistsException),
				},
			}
		case result.Exists != nil:
			specResult.ExistsResult = &lookupin.BooleanOrError{
				Result: &lookupin.BooleanOrError_Value{Value: *result.Exists},
			}
		}

		out = append(out, specResult)
	}

	return out
}
