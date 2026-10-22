package executor

import (
	"errors"

	"github.com/couchbaselabs/transactions-fit-performer/phpbackend"
	"github.com/couchbaselabs/transactions-fit-performer/protocol/sdk"
	"github.com/couchbaselabs/transactions-fit-performer/protocol/sdk/collection/mutatein"
	"github.com/couchbaselabs/transactions-fit-performer/protocol/sdk/kv"
	"github.com/couchbaselabs/transactions-fit-performer/protocol/shared"
)

func mutationResult(resp *phpbackend.ExecuteResponse) (*sdk.Result, error) {
	return &sdk.Result{
		Result: &sdk.Result_MutationResult{
			MutationResult: &kv.MutationResult{
				Cas:           casFromString(resp.Cas),
				MutationToken: mutationToken(resp.MutationToken),
			},
		},
	}, nil
}

func getResult(resp *phpbackend.ExecuteResponse) (*sdk.Result, error) {
	content, err := contentPayloadToContentTypes(resp.Content)
	if err != nil {
		return nil, err
	}

	return &sdk.Result{
		Result: &sdk.Result_GetResult{
			GetResult: &kv.GetResult{
				Cas:        casFromString(resp.Cas),
				Content:    content,
				ExpiryTime: resp.ExpiryTime,
			},
		},
	}, nil
}

func counterResult(resp *phpbackend.ExecuteResponse) (*sdk.Result, error) {
	if resp.Counter == nil {
		return nil, errors.New("php backend did not return the counter value")
	}

	return &sdk.Result{
		Result: &sdk.Result_CounterResult{
			CounterResult: &kv.CounterResult{
				Cas:           casFromString(resp.Cas),
				MutationToken: mutationToken(resp.MutationToken),
				Content:       *resp.Counter,
			},
		},
	}, nil
}

func getReplicaResult(resp *phpbackend.ExecuteResponse) (*sdk.Result, error) {
	content, err := contentPayloadToContentTypes(resp.Content)
	if err != nil {
		return nil, err
	}
	if resp.IsReplica == nil {
		return nil, errors.New("php backend did not say whether the result came from a replica")
	}

	return &sdk.Result{
		Result: &sdk.Result_GetReplicaResult{
			GetReplicaResult: &kv.GetReplicaResult{
				Cas:        casFromString(resp.Cas),
				Content:    content,
				IsReplica:  *resp.IsReplica,
				ExpiryTime: resp.ExpiryTime,
			},
		},
	}, nil
}

func replicaResults(resp *phpbackend.ExecuteResponse, streamID string) ([]*sdk.Result, error) {
	if len(resp.Replicas) == 0 {
		return nil, errors.New("php backend returned no replica results")
	}

	results := make([]*sdk.Result, 0, len(resp.Replicas))
	for _, replica := range resp.Replicas {
		content, err := contentPayloadToContentTypes(replica.Content)
		if err != nil {
			return nil, err
		}

		results = append(results, &sdk.Result{
			Result: &sdk.Result_GetReplicaResult{
				GetReplicaResult: &kv.GetReplicaResult{
					Cas:       casFromString(replica.Cas),
					Content:   content,
					IsReplica: replica.IsReplica,
					StreamId:  &streamID,
				},
			},
		})
	}

	return results, nil
}

func existsResult(resp *phpbackend.ExecuteResponse) (*sdk.Result, error) {
	if resp.Exists == nil {
		return nil, errors.New("php backend did not return whether the document exists")
	}

	return &sdk.Result{
		Result: &sdk.Result_ExistsResult{
			ExistsResult: &kv.ExistsResult{
				Cas:    casFromString(resp.Cas),
				Exists: *resp.Exists,
			},
		},
	}, nil
}

func mutateInResult(resp *phpbackend.ExecuteResponse) (*sdk.Result, error) {
	// An entry with neither content nor exception is the protocol's "empty value at this index".
	results := make([]*mutatein.MutateInSpecResult, 0, len(resp.SpecResults))
	for _, specResult := range resp.SpecResults {
		out := &mutatein.MutateInSpecResult{}

		switch {
		case specResult.Exception != nil:
			out.ContentAsResult = &shared.ContentOrError{
				Result: &shared.ContentOrError_Exception{
					Exception: exceptionToProto(specResult.Exception),
				},
			}
		case specResult.Content != nil:
			content, err := contentPayloadToContentTypes(specResult.Content)
			if err != nil {
				return nil, err
			}
			out.ContentAsResult = &shared.ContentOrError{
				Result: &shared.ContentOrError_Content{Content: content},
			}
		}

		results = append(results, out)
	}

	return &sdk.Result{
		Result: &sdk.Result_MutateInResult{
			MutateInResult: &mutatein.MutateInResult{
				Cas:           casFromString(resp.Cas),
				MutationToken: mutationToken(resp.MutationToken),
				Results:       results,
			},
		},
	}, nil
}

func successResult(_ *phpbackend.ExecuteResponse) (*sdk.Result, error) {
	return &sdk.Result{Result: &sdk.Result_Success{Success: true}}, nil
}

// A nil token is legitimate: tokens are only present when enabled in the cluster config.
func mutationToken(token *phpbackend.MutationToken) *shared.MutationToken {
	if token == nil {
		return nil
	}

	return &shared.MutationToken{
		PartitionId:    token.PartitionId,
		PartitionUuid:  hexToInt64(token.PartitionUuid),
		SequenceNumber: hexToInt64(token.SequenceNumber),
		BucketName:     token.BucketName,
	}
}
