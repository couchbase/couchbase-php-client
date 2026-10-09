package executor

import (
	"errors"
	"fmt"

	"github.com/couchbaselabs/transactions-fit-performer/phpbackend"
	"github.com/couchbaselabs/transactions-fit-performer/protocol/sdk"
	"github.com/couchbaselabs/transactions-fit-performer/protocol/sdk/kv/rangescan"
	"github.com/couchbaselabs/transactions-fit-performer/protocol/shared"
)

func scanType(scan *rangescan.ScanType) (*phpbackend.ScanType, error) {
	switch t := scan.GetType().(type) {
	case *rangescan.ScanType_Sampling:
		sampling := &phpbackend.SamplingScanType{Limit: t.Sampling.GetLimit()}
		if t.Sampling.Seed != nil {
			sampling.Seed = t.Sampling.Seed
		}
		return &phpbackend.ScanType{Sampling: sampling}, nil

	case *rangescan.ScanType_Range:
		switch r := t.Range.GetRange().(type) {
		case *rangescan.RangeScan_DocIdPrefix:
			prefix := r.DocIdPrefix
			return &phpbackend.ScanType{Prefix: &prefix}, nil
		case *rangescan.RangeScan_FromTo:
			from, err := scanTerm(r.FromTo.GetFrom())
			if err != nil {
				return nil, err
			}
			to, err := scanTerm(r.FromTo.GetTo())
			if err != nil {
				return nil, err
			}
			return &phpbackend.ScanType{Range: &phpbackend.RangeScanType{From: from, To: to}}, nil
		default:
			return nil, fmt.Errorf("unsupported range scan type %T", r)
		}

	default:
		return nil, fmt.Errorf("unsupported scan type %T", t)
	}
}

// scanTerm returns nil for the `default` choice (no bound), which the SDK takes as a null term.
func scanTerm(choice *rangescan.ScanTermChoice) (*phpbackend.ScanTerm, error) {
	if choice == nil {
		return nil, nil
	}

	switch c := choice.GetChoice().(type) {
	case nil:
		return nil, nil
	case *rangescan.ScanTermChoice_Default:
		return nil, nil
	case *rangescan.ScanTermChoice_Term:
		switch term := c.Term.GetTerm().(type) {
		case *rangescan.ScanTerm_AsString:
			return &phpbackend.ScanTerm{Term: term.AsString, Exclusive: c.Term.Exclusive}, nil
		case *rangescan.ScanTerm_AsBytes:
			return nil, errors.New("scan terms as bytes are deprecated and not supported")
		default:
			return nil, fmt.Errorf("unsupported scan term %T", term)
		}
	case *rangescan.ScanTermChoice_Minimum, *rangescan.ScanTermChoice_Maximum:
		return nil, errors.New("the minimum and maximum scan term choices are deprecated; use default")
	default:
		return nil, fmt.Errorf("unsupported scan term choice %T", c)
	}
}

func scanOptions(opts *rangescan.ScanOptions) (*phpbackend.Options, error) {
	if opts == nil {
		return nil, nil
	}
	if err := rejectParentSpan(opts.ParentSpanId); err != nil {
		return nil, err
	}

	out := &phpbackend.Options{
		TimeoutMillis:  opts.TimeoutMsecs,
		IdsOnly:        opts.IdsOnly,
		BatchByteLimit: opts.BatchByteLimit,
		BatchItemLimit: opts.BatchItemLimit,
		BatchTimeLimit: opts.BatchTimeLimit,
		Concurrency:    opts.Concurrency,
		ConsistentWith: mutationTokens(opts.ConsistentWith),
	}
	if opts.Sort != nil {
		// Forwarded so the backend rejects it; sorting was removed from the RFC.
		sort := opts.Sort.String()
		out.Sort = &sort
	}

	return out, setTranscoder(out, opts.Transcoder)
}

func mutationTokens(state *shared.MutationState) []phpbackend.MutationToken {
	if state == nil {
		return nil
	}

	tokens := make([]phpbackend.MutationToken, 0, len(state.GetTokens()))
	for _, token := range state.GetTokens() {
		tokens = append(tokens, phpbackend.MutationToken{
			PartitionId:    token.GetPartitionId(),
			PartitionUuid:  casToHex(token.GetPartitionUuid()),
			SequenceNumber: casToHex(token.GetSequenceNumber()),
			BucketName:     token.GetBucketName(),
		})
	}

	return tokens
}

func scanResult(item *phpbackend.ScanItem, streamID string) (*sdk.Result, error) {
	result := &rangescan.ScanResult{
		Id:         item.Id,
		IdOnly:     item.IdOnly,
		StreamId:   streamID,
		ExpiryTime: item.ExpiryTime,
	}

	if !item.IdOnly {
		cas := casFromString(item.Cas)
		result.Cas = &cas

		if item.Content != nil {
			content, err := contentPayloadToContentTypes(item.Content)
			if err != nil {
				return nil, err
			}
			result.Content = content
		}
	}

	return &sdk.Result{Result: &sdk.Result_RangeScanResult{RangeScanResult: result}}, nil
}
