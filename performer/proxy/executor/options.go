package executor

import (
	"errors"

	"github.com/couchbaselabs/transactions-fit-performer/phpbackend"
	"github.com/couchbaselabs/transactions-fit-performer/protocol/sdk/collection/mutatein"
	"github.com/couchbaselabs/transactions-fit-performer/protocol/sdk/kv"
	"github.com/couchbaselabs/transactions-fit-performer/protocol/shared"
)

// parent_span_id is rejected: span RPCs are unimplemented, so forwarding it would false-pass.

func insertOptions(opts *kv.InsertOptions) (*phpbackend.Options, error) {
	if opts == nil {
		return nil, nil
	}
	if err := rejectParentSpan(opts.ParentSpanId); err != nil {
		return nil, err
	}

	out := &phpbackend.Options{
		TimeoutMillis: opts.TimeoutMsecs,
	}
	if err := setExpiry(out, opts.Expiry); err != nil {
		return nil, err
	}
	if err := setDurability(out, opts.Durability); err != nil {
		return nil, err
	}
	return out, setTranscoder(out, opts.Transcoder)
}

func replaceOptions(opts *kv.ReplaceOptions) (*phpbackend.Options, error) {
	if opts == nil {
		return nil, nil
	}
	if err := rejectParentSpan(opts.ParentSpanId); err != nil {
		return nil, err
	}

	out := &phpbackend.Options{
		TimeoutMillis:  opts.TimeoutMsecs,
		PreserveExpiry: opts.PreserveExpiry,
		Cas:            casToString(opts.Cas),
	}
	if err := setExpiry(out, opts.Expiry); err != nil {
		return nil, err
	}
	if err := setDurability(out, opts.Durability); err != nil {
		return nil, err
	}
	return out, setTranscoder(out, opts.Transcoder)
}

func upsertOptions(opts *kv.UpsertOptions) (*phpbackend.Options, error) {
	if opts == nil {
		return nil, nil
	}
	if err := rejectParentSpan(opts.ParentSpanId); err != nil {
		return nil, err
	}

	out := &phpbackend.Options{
		TimeoutMillis:  opts.TimeoutMsecs,
		PreserveExpiry: opts.PreserveExpiry,
	}
	if err := setExpiry(out, opts.Expiry); err != nil {
		return nil, err
	}
	if err := setDurability(out, opts.Durability); err != nil {
		return nil, err
	}
	return out, setTranscoder(out, opts.Transcoder)
}

func removeOptions(opts *kv.RemoveOptions) (*phpbackend.Options, error) {
	if opts == nil {
		return nil, nil
	}
	if err := rejectParentSpan(opts.ParentSpanId); err != nil {
		return nil, err
	}

	out := &phpbackend.Options{
		TimeoutMillis: opts.TimeoutMsecs,
		Cas:           casToString(opts.Cas),
	}
	return out, setDurability(out, opts.Durability)
}

func appendOptions(opts *kv.AppendOptions) (*phpbackend.Options, error) {
	if opts == nil {
		return nil, nil
	}
	if err := rejectParentSpan(opts.ParentSpanId); err != nil {
		return nil, err
	}

	out := &phpbackend.Options{
		TimeoutMillis: opts.TimeoutMsecs,
		Cas:           casToString(opts.Cas),
	}
	return out, setDurability(out, opts.Durability)
}

func prependOptions(opts *kv.PrependOptions) (*phpbackend.Options, error) {
	if opts == nil {
		return nil, nil
	}
	if err := rejectParentSpan(opts.ParentSpanId); err != nil {
		return nil, err
	}

	out := &phpbackend.Options{
		TimeoutMillis: opts.TimeoutMsecs,
		Cas:           casToString(opts.Cas),
	}
	return out, setDurability(out, opts.Durability)
}

func incrementOptions(opts *kv.IncrementOptions) (*phpbackend.Options, error) {
	if opts == nil {
		return nil, nil
	}
	if err := rejectParentSpan(opts.ParentSpanId); err != nil {
		return nil, err
	}

	out := &phpbackend.Options{
		TimeoutMillis: opts.TimeoutMsecs,
		Delta:         opts.Delta,
		Initial:       opts.Initial,
	}
	if err := setExpiry(out, opts.Expiry); err != nil {
		return nil, err
	}
	return out, setDurability(out, opts.Durability)
}

func decrementOptions(opts *kv.DecrementOptions) (*phpbackend.Options, error) {
	if opts == nil {
		return nil, nil
	}
	if err := rejectParentSpan(opts.ParentSpanId); err != nil {
		return nil, err
	}

	out := &phpbackend.Options{
		TimeoutMillis: opts.TimeoutMsecs,
		Delta:         opts.Delta,
		Initial:       opts.Initial,
	}
	if err := setExpiry(out, opts.Expiry); err != nil {
		return nil, err
	}
	return out, setDurability(out, opts.Durability)
}

func mutateInOptions(opts *mutatein.MutateInOptions) (*phpbackend.Options, error) {
	if opts == nil {
		return nil, nil
	}
	if err := rejectParentSpan(opts.ParentSpanId); err != nil {
		return nil, err
	}

	out := &phpbackend.Options{
		TimeoutMillis:   opts.TimeoutMillis,
		Cas:             casToString(opts.Cas),
		PreserveExpiry:  opts.PreserveExpiry,
		AccessDeleted:   opts.AccessDeleted,
		CreateAsDeleted: opts.CreateAsDeleted,
	}
	if opts.StoreSemantics != nil {
		semantics := opts.StoreSemantics.String()
		out.StoreSemantics = &semantics
	}
	if err := setExpiry(out, opts.Expiry); err != nil {
		return nil, err
	}
	return out, setDurability(out, opts.Durability)
}

func getAndLockOptions(opts *kv.GetAndLockOptions) (*phpbackend.Options, error) {
	if opts == nil {
		return nil, nil
	}
	if err := rejectParentSpan(opts.ParentSpanId); err != nil {
		return nil, err
	}

	out := &phpbackend.Options{TimeoutMillis: opts.TimeoutMsecs}
	return out, setTranscoder(out, opts.Transcoder)
}

func getAndTouchOptions(opts *kv.GetAndTouchOptions) (*phpbackend.Options, error) {
	if opts == nil {
		return nil, nil
	}
	if err := rejectParentSpan(opts.ParentSpanId); err != nil {
		return nil, err
	}

	out := &phpbackend.Options{TimeoutMillis: opts.TimeoutMsecs}
	return out, setTranscoder(out, opts.Transcoder)
}

func getAllReplicasOptions(opts *kv.GetAllReplicasOptions) (*phpbackend.Options, error) {
	if opts == nil {
		return nil, nil
	}
	if err := rejectParentSpan(opts.ParentSpanId); err != nil {
		return nil, err
	}

	out := &phpbackend.Options{TimeoutMillis: opts.TimeoutMsecs}
	setReadPreference(out, opts.ReadPreference)
	return out, setTranscoder(out, opts.Transcoder)
}

func getAnyReplicaOptions(opts *kv.GetAnyReplicaOptions) (*phpbackend.Options, error) {
	if opts == nil {
		return nil, nil
	}
	if err := rejectParentSpan(opts.ParentSpanId); err != nil {
		return nil, err
	}

	out := &phpbackend.Options{TimeoutMillis: opts.TimeoutMsecs}
	setReadPreference(out, opts.ReadPreference)
	return out, setTranscoder(out, opts.Transcoder)
}

func touchOptions(opts *kv.TouchOptions) (*phpbackend.Options, error) {
	if opts == nil {
		return nil, nil
	}
	if err := rejectParentSpan(opts.ParentSpanId); err != nil {
		return nil, err
	}

	return &phpbackend.Options{TimeoutMillis: opts.TimeoutMsecs}, nil
}

func unlockOptions(opts *kv.UnlockOptions) (*phpbackend.Options, error) {
	if opts == nil {
		return nil, nil
	}
	if err := rejectParentSpan(opts.ParentSpanId); err != nil {
		return nil, err
	}

	return &phpbackend.Options{TimeoutMillis: opts.TimeoutMsecs}, nil
}

func existsOptions(opts *kv.ExistsOptions) (*phpbackend.Options, error) {
	if opts == nil {
		return nil, nil
	}
	if err := rejectParentSpan(opts.ParentSpanId); err != nil {
		return nil, err
	}

	return &phpbackend.Options{TimeoutMillis: opts.TimeoutMsecs}, nil
}

func getOptions(opts *kv.GetOptions) (*phpbackend.Options, error) {
	if opts == nil {
		return nil, nil
	}
	if err := rejectParentSpan(opts.ParentSpanId); err != nil {
		return nil, err
	}

	out := &phpbackend.Options{
		TimeoutMillis: opts.TimeoutMsecs,
		WithExpiry:    opts.WithExpiry,
		Projections:   opts.Projection,
	}
	return out, setTranscoder(out, opts.Transcoder)
}

func rejectParentSpan(id *string) error {
	if id != nil {
		return errors.New("parent_span_id was set but the performer implements no span registry " +
			"(SpanCreate/SpanFinish are unimplemented)")
	}
	return nil
}

func setExpiry(out *phpbackend.Options, expiry *shared.Expiry) error {
	wire, err := expiryToWire(expiry)
	if err != nil {
		return err
	}

	out.Expiry = wire
	return nil
}

// expiryToWire is for touch/getAndTouch, where expiry is an argument rather than an option.
func expiryToWire(expiry *shared.Expiry) (*phpbackend.Expiry, error) {
	if expiry == nil {
		return nil, nil
	}

	switch e := expiry.ExpiryType.(type) {
	case *shared.Expiry_RelativeSecs:
		secs := e.RelativeSecs
		return &phpbackend.Expiry{RelativeSecs: &secs}, nil
	case *shared.Expiry_AbsoluteEpochSecs:
		secs := e.AbsoluteEpochSecs
		return &phpbackend.Expiry{AbsoluteEpochSecs: &secs}, nil
	default:
		return nil, errors.New("unsupported expiry type")
	}
}

func setDurability(out *phpbackend.Options, durability *shared.DurabilityType) error {
	if durability == nil {
		return nil
	}

	switch d := durability.Durability.(type) {
	case *shared.DurabilityType_DurabilityLevel:
		level := d.DurabilityLevel.String()
		out.Durability = &phpbackend.Durability{Level: &level}
	case *shared.DurabilityType_Observe:
		out.Durability = &phpbackend.Durability{
			Observe: &phpbackend.ObserveBased{
				PersistTo:   d.Observe.PersistTo.String(),
				ReplicateTo: d.Observe.ReplicateTo.String(),
			},
		}
	default:
		return errors.New("unsupported durability type")
	}
	return nil
}

func setReadPreference(out *phpbackend.Options, preference *shared.ReadPreference) {
	if preference == nil {
		return
	}

	name := preference.String()
	out.ReadPreference = &name
}

func setTranscoder(out *phpbackend.Options, transcoder *shared.Transcoder) error {
	if transcoder == nil {
		return nil
	}

	var name string
	switch transcoder.Transcoder.(type) {
	case *shared.Transcoder_Legacy:
		name = "legacy"
	case *shared.Transcoder_Json:
		name = "json"
	case *shared.Transcoder_RawJson:
		name = "raw_json"
	case *shared.Transcoder_RawString:
		name = "raw_string"
	case *shared.Transcoder_RawBinary:
		name = "raw_binary"
	default:
		return errors.New("unsupported transcoder type")
	}

	out.Transcoder = &name
	return nil
}
