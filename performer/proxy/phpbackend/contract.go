// Package phpbackend is the JSON wire contract and HTTP client between the proxy and the PHP backend.
package phpbackend

// ConnectionParams is sent on every request so any PHP worker can serve it statelessly.
// The PHP SDK keys its connection cache on connstr+auth only (PCBC-1056), so ids differing only in Config share a connection.
type ConnectionParams struct {
	Connstr string `json:"connstr"`

	// cluster_username/cluster_password are folded into the "password" case.
	Auth Authenticator `json:"auth"`

	Config *ClusterConfig `json:"clusterConfig,omitempty"`
}

type Authenticator struct {
	Kind string `json:"kind"` // "password" | "certificate" | "jwt"

	Username string `json:"username,omitempty"`
	Password string `json:"password,omitempty"`

	// PEM contents, not paths; the backend writes them to files.
	Cert string `json:"cert,omitempty"`
	Key  string `json:"key,omitempty"`

	Jwt string `json:"jwt,omitempty"`
}

// ClusterConfig mirrors shared.ClusterConfig with the protocol's units preserved (...Secs is seconds) and no SDK mapping.
type ClusterConfig struct {
	UseTls              bool `json:"useTls,omitempty"`
	UseCustomSerializer bool `json:"useCustomSerializer,omitempty"`

	CertPath *string `json:"certPath,omitempty"`
	Cert     *string `json:"cert,omitempty"` // PEM contents
	Insecure *bool   `json:"insecure,omitempty"`

	KvConnectTimeoutSecs   *int32 `json:"kvConnectTimeoutSecs,omitempty"`
	KvTimeoutMillis        *int32 `json:"kvTimeoutMillis,omitempty"`
	KvDurableTimeoutMillis *int32 `json:"kvDurableTimeoutMillis,omitempty"`
	KvScanTimeoutSecs      *int32 `json:"kvScanTimeoutSecs,omitempty"`
	ViewTimeoutSecs        *int32 `json:"viewTimeoutSecs,omitempty"`
	QueryTimeoutSecs       *int32 `json:"queryTimeoutSecs,omitempty"`
	AnalyticsTimeoutSecs   *int32 `json:"analyticsTimeoutSecs,omitempty"`
	SearchTimeoutSecs      *int32 `json:"searchTimeoutSecs,omitempty"`
	ManagementTimeoutSecs  *int32 `json:"managementTimeoutSecs,omitempty"`

	Transcoder *string `json:"transcoder,omitempty"`

	EnableMutationTokens          *bool   `json:"enableMutationTokens,omitempty"`
	TcpKeepAliveTimeMillis        *int32  `json:"tcpKeepAliveTimeMillis,omitempty"`
	EnableTcpKeepAlives           *bool   `json:"enableTcpKeepAlives,omitempty"`
	ForceIpv4                     *bool   `json:"forceIpv4,omitempty"`
	ConfigPollIntervalSecs        *int32  `json:"configPollIntervalSecs,omitempty"`
	ConfigPollFloorIntervalSecs   *int32  `json:"configPollFloorIntervalSecs,omitempty"`
	ConfigIdleRedialTimeoutSecs   *int32  `json:"configIdleRedialTimeoutSecs,omitempty"`
	NumKvConnections              *int32  `json:"numKvConnections,omitempty"`
	MaxHttpConnections            *int32  `json:"maxHttpConnections,omitempty"`
	IdleHttpConnectionTimeoutSecs *int32  `json:"idleHttpConnectionTimeoutSecs,omitempty"`
	PreferredServerGroup          *string `json:"preferredServerGroup,omitempty"`

	EnableAppTelemetry           *bool   `json:"enableAppTelemetry,omitempty"`
	AppTelemetryEndpoint         *string `json:"appTelemetryEndpoint,omitempty"`
	AppTelemetryBackoffSecs      *int32  `json:"appTelemetryBackoffSecs,omitempty"`
	AppTelemetryPingIntervalSecs *int32  `json:"appTelemetryPingIntervalSecs,omitempty"`
	AppTelemetryPingTimeoutSecs  *int32  `json:"appTelemetryPingTimeoutSecs,omitempty"`
}

type DocLocation struct {
	Bucket     string `json:"bucket"`
	Scope      string `json:"scope"`
	Collection string `json:"collection"`
	Id         string `json:"id"`
}

type ContentInput struct {
	Kind string `json:"kind"` // "json" | "passthrough_string" | "byte_array" | "null"

	// JSON text, not an embedded value: PHP's assoc json_decode would turn {} into [].
	Json  string  `json:"json,omitempty"`
	Value *string `json:"value,omitempty"` // base64 for "byte_array"; a pointer so "" is still sent
}

type ContentPayload struct {
	Kind   string  `json:"kind"` // "bytes" | "string" | "int64" | "double" | "bool"
	Bytes  string  `json:"bytes,omitempty"`
	String string  `json:"string,omitempty"`
	Int64  int64   `json:"int64,omitempty"`
	Double float64 `json:"double,omitempty"`
	Bool   bool    `json:"bool,omitempty"`
}

type Expiry struct {
	RelativeSecs      *int64 `json:"relativeSecs,omitempty"`
	AbsoluteEpochSecs *int64 `json:"absoluteEpochSecs,omitempty"`
}

type ObserveBased struct {
	PersistTo   string `json:"persistTo"`
	ReplicateTo string `json:"replicateTo"`
}

// Durability carries protobuf enum names unmapped; mapping is the backend's job.
type Durability struct {
	Level   *string       `json:"level,omitempty"`
	Observe *ObserveBased `json:"observe,omitempty"`
}

// Options is the union of all forwarded KV options; nil means the driver sent no options block.
type Options struct {
	TimeoutMillis *int32      `json:"timeoutMillis,omitempty"`
	Expiry        *Expiry     `json:"expiry,omitempty"`
	Durability    *Durability `json:"durability,omitempty"`

	// Hex: the PHP SDK's cas() option rejects decimal.
	Cas *string `json:"cas,omitempty"`

	PreserveExpiry *bool `json:"preserveExpiry,omitempty"`
	WithExpiry     *bool `json:"withExpiry,omitempty"`

	Projections []string `json:"projections,omitempty"`

	Transcoder *string `json:"transcoder,omitempty"`

	ReadPreference *string `json:"readPreference,omitempty"`

	StoreSemantics  *string `json:"storeSemantics,omitempty"`
	AccessDeleted   *bool   `json:"accessDeleted,omitempty"`
	CreateAsDeleted *bool   `json:"createAsDeleted,omitempty"`

	// Delta is always positive; direction is the op.
	Delta   *int64 `json:"delta,omitempty"`
	Initial *int64 `json:"initial,omitempty"`

	// Sort and BatchTimeLimit are unsupported by PHP; forwarded so the backend rejects them.
	IdsOnly        *bool           `json:"idsOnly,omitempty"`
	BatchByteLimit *uint32         `json:"batchByteLimit,omitempty"`
	BatchItemLimit *uint32         `json:"batchItemLimit,omitempty"`
	BatchTimeLimit *uint32         `json:"batchTimeLimit,omitempty"`
	Concurrency    *uint32         `json:"concurrency,omitempty"`
	Sort           *string         `json:"sort,omitempty"`
	ConsistentWith []MutationToken `json:"consistentWith,omitempty"`
}

// ScanType lifts the protocol's RangeScan.doc_id_prefix out into its own Prefix case.
type ScanType struct {
	Range    *RangeScanType    `json:"range,omitempty"`
	Sampling *SamplingScanType `json:"sampling,omitempty"`
	Prefix   *string           `json:"prefix,omitempty"`
}

// A nil term means unbounded, distinct from an empty term.
type RangeScanType struct {
	From *ScanTerm `json:"from,omitempty"`
	To   *ScanTerm `json:"to,omitempty"`
}

type ScanTerm struct {
	Term      string `json:"term"`
	Exclusive *bool  `json:"exclusive,omitempty"` // explicit false must reach the SDK
}

type SamplingScanType struct {
	Limit uint64  `json:"limit"`
	Seed  *uint64 `json:"seed,omitempty"`
}

type ScanItem struct {
	Id         string          `json:"id"`
	IdOnly     bool            `json:"idOnly"`
	Cas        string          `json:"cas,omitempty"`
	ExpiryTime *int64          `json:"expiryTime,omitempty"`
	Content    *ContentPayload `json:"content,omitempty"`
}

// StreamLine is one NDJSON line from /execute/stream; see handleExecuteStream in index.php.
type StreamLine struct {
	// After Created, failures belong to the stream rather than the command.
	Created bool `json:"created,omitempty"`

	Item *ScanItem `json:"item,omitempty"`

	Error *Exception `json:"error,omitempty"`

	// A failure converting one item; reported as streams.Error in its place, not terminal.
	ItemError *Exception `json:"itemError,omitempty"`

	// Explicit so a truncated body isn't mistaken for completion.
	Complete bool `json:"complete,omitempty"`

	// Only on a failure before the stream exists.
	OK        *bool      `json:"ok,omitempty"`
	Exception *Exception `json:"exception,omitempty"`
}

type MutateInSpec struct {
	Kind string `json:"kind"` // "upsert" | "insert" | "replace" | "remove" | "array_append" | "array_prepend" | "array_insert" | "array_add_unique" | "increment" | "decrement"
	Path string `json:"path"`

	Content  *ContentOrMacro  `json:"content,omitempty"`
	Contents []ContentOrMacro `json:"contents,omitempty"`

	Delta *int64 `json:"delta,omitempty"` // always positive

	Xattr      *bool `json:"xattr,omitempty"`
	CreatePath *bool `json:"createPath,omitempty"`

	// Empty means don't read back this spec's result.
	ContentAs string `json:"contentAs,omitempty"`
}

type ContentOrMacro struct {
	Content *ContentInput `json:"content,omitempty"`

	Macro *string `json:"macro,omitempty"`
}

// SpecResult: both fields nil when the driver didn't ask for content.
type SpecResult struct {
	Content *ContentPayload `json:"content,omitempty"`

	// Content conversion failure for this spec, not a command failure.
	Exception *Exception `json:"exception,omitempty"`
}

type LookupSpec struct {
	Kind string `json:"kind"` // "get" | "exists" | "count"
	Path string `json:"path"`

	Xattr *bool `json:"xattr,omitempty"`

	// Empty means the natural representation, unlike MutateInSpec.ContentAs.
	ContentAs string `json:"contentAs,omitempty"`
}

// Content and exists can fail independently, so each has its own exception.
type LookupSpecResult struct {
	Content          *ContentPayload `json:"content,omitempty"`
	ContentException *Exception      `json:"contentException,omitempty"`

	Exists          *bool      `json:"exists,omitempty"`
	ExistsException *Exception `json:"existsException,omitempty"`
}

type LookupReplicaResult struct {
	Cas       string             `json:"cas,omitempty"`
	IsReplica bool               `json:"isReplica"`
	Results   []LookupSpecResult `json:"results,omitempty"`
}

type ExecuteRequest struct {
	Connection ConnectionParams `json:"connection"`
	Op         string           `json:"op"`
	Location   *DocLocation     `json:"location,omitempty"`
	Content    *ContentInput    `json:"content,omitempty"`
	ContentAs  string           `json:"contentAs,omitempty"`
	Options    *Options         `json:"options,omitempty"`

	// Positional SDK arguments, not options.
	LockSeconds *int32  `json:"lockSeconds,omitempty"`
	Expiry      *Expiry `json:"expiry,omitempty"`
	Cas         *string `json:"cas,omitempty"`

	Specs       []MutateInSpec `json:"specs,omitempty"`
	LookupSpecs []LookupSpec   `json:"lookupSpecs,omitempty"`

	ScanType *ScanType `json:"scanType,omitempty"`
}

// Exception identifies the PHP exception class; mapping to protocol error types is in executor/exceptions.go.
type Exception struct {
	Name string `json:"name"`

	// Set via instanceof; PHP's own InvalidArgumentException must not pass as the SDK's.
	Couchbase bool `json:"couchbase,omitempty"`

	Serialized string `json:"serialized"`
}

func (e *Exception) GetName() string {
	if e == nil {
		return "MissingException"
	}
	return e.Name
}

func (e *Exception) GetSerialized() string {
	if e == nil {
		return "the php backend reported a failure with no exception attached"
	}
	return e.Serialized
}

func (e *Exception) IsCouchbase() bool {
	if e == nil {
		return false
	}
	return e.Couchbase
}

type ExecuteResponse struct {
	OK            bool            `json:"ok"`
	ElapsedMicros int64           `json:"elapsedMicros"`
	Cas           string          `json:"cas,omitempty"`
	ExpiryTime    *int64          `json:"expiryTime,omitempty"`
	Content       *ContentPayload `json:"content,omitempty"`

	MutationToken *MutationToken `json:"mutationToken,omitempty"`

	Exists *bool `json:"exists,omitempty"`

	IsReplica *bool `json:"isReplica,omitempty"`

	SpecResults []SpecResult `json:"specResults,omitempty"`

	Counter *int64 `json:"counter,omitempty"`

	Replicas []ReplicaResult `json:"replicas,omitempty"`

	LookupResults  []LookupSpecResult    `json:"lookupResults,omitempty"`
	LookupReplicas []LookupReplicaResult `json:"lookupReplicas,omitempty"`

	Exception *Exception `json:"exception,omitempty"`
}

type ReplicaResult struct {
	Cas       string          `json:"cas,omitempty"`
	IsReplica bool            `json:"isReplica"`
	Content   *ContentPayload `json:"content,omitempty"`
}

// PartitionUuid and SequenceNumber are hex strings: PHP has no uint64.
type MutationToken struct {
	PartitionId    int32  `json:"partitionId"`
	PartitionUuid  string `json:"partitionUuid"`
	SequenceNumber string `json:"sequenceNumber"`
	BucketName     string `json:"bucketName"`
}

type connectionRequest struct {
	Connection ConnectionParams `json:"connection"`
}

type connectionResponse struct {
	OK        bool       `json:"ok"`
	Exception *Exception `json:"exception,omitempty"`
}
