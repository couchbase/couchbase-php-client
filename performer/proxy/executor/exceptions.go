package executor

import (
	"github.com/couchbaselabs/transactions-fit-performer/phpbackend"
	"github.com/couchbaselabs/transactions-fit-performer/protocol/shared"
)

// Written out rather than derived from names because a few spellings differ (e.g.
// DocumentIrretrievable/UNRETRIEVABLE). Unlisted classes map to SDK_COUCHBASE_EXCEPTION.
var couchbaseExceptionTypes = map[string]shared.CouchbaseExceptionType{
	"TimeoutException":               shared.CouchbaseExceptionType_SDK_TIMEOUT_EXCEPTION,
	"AmbiguousTimeoutException":      shared.CouchbaseExceptionType_SDK_AMBIGUOUS_TIMEOUT_EXCEPTION,
	"UnambiguousTimeoutException":    shared.CouchbaseExceptionType_SDK_UNAMBIGUOUS_TIMEOUT_EXCEPTION,
	"RequestCanceledException":       shared.CouchbaseExceptionType_SDK_REQUEST_CANCELLED_EXCEPTION,
	"InvalidArgumentException":       shared.CouchbaseExceptionType_SDK_INVALID_ARGUMENT_EXCEPTION,
	"ServiceNotAvailableException":   shared.CouchbaseExceptionType_SDK_SERVICE_NOT_AVAILABLE_EXCEPTION,
	"InternalServerFailureException": shared.CouchbaseExceptionType_SDK_INTERNAL_SERVER_FAILURE_EXCEPTION,
	"AuthenticationFailureException": shared.CouchbaseExceptionType_SDK_AUTHENTICATION_FAILURE_EXCEPTION,
	"TemporaryFailureException":      shared.CouchbaseExceptionType_SDK_TEMPORARY_FAILURE_EXCEPTION,
	"ParsingFailureException":        shared.CouchbaseExceptionType_SDK_PARSING_FAILURE_EXCEPTION,
	"CasMismatchException":           shared.CouchbaseExceptionType_SDK_CAS_MISMATCH_EXCEPTION,
	"BucketNotFoundException":        shared.CouchbaseExceptionType_SDK_BUCKET_NOT_FOUND_EXCEPTION,
	"CollectionNotFoundException":    shared.CouchbaseExceptionType_SDK_COLLECTION_NOT_FOUND_EXCEPTION,
	"ScopeNotFoundException":         shared.CouchbaseExceptionType_SDK_SCOPE_NOT_FOUND_EXCEPTION,
	"UnsupportedOperationException":  shared.CouchbaseExceptionType_SDK_UNSUPPORTED_OPERATION_EXCEPTION,
	"FeatureNotAvailableException":   shared.CouchbaseExceptionType_SDK_FEATURE_NOT_AVAILABLE_EXCEPTION,
	"IndexNotFoundException":         shared.CouchbaseExceptionType_SDK_INDEX_NOT_FOUND_EXCEPTION,
	"IndexExistsException":           shared.CouchbaseExceptionType_SDK_INDEX_EXISTS_EXCEPTION,
	"EncodingFailureException":       shared.CouchbaseExceptionType_SDK_ENCODING_FAILURE_EXCEPTION,
	"DecodingFailureException":       shared.CouchbaseExceptionType_SDK_DECODING_FAILURE_EXCEPTION,

	// Key-value
	"DocumentNotFoundException":                  shared.CouchbaseExceptionType_SDK_DOCUMENT_NOT_FOUND_EXCEPTION,
	"DocumentNotFoundOnReplicaException":         shared.CouchbaseExceptionType_SDK_DOCUMENT_NOT_FOUND_ON_REPLICA_EXCEPTION,
	"DocumentIrretrievableException":             shared.CouchbaseExceptionType_SDK_DOCUMENT_UNRETRIEVABLE_EXCEPTION,
	"DocumentLockedException":                    shared.CouchbaseExceptionType_SDK_DOCUMENT_LOCKED_EXCEPTION,
	"DocumentNotLockedException":                 shared.CouchbaseExceptionType_SDK_DOCUMENT_NOT_LOCKED_EXCEPTION,
	"ValueTooLargeException":                     shared.CouchbaseExceptionType_SDK_VALUE_TOO_LARGE_EXCEPTION,
	"DocumentExistsException":                    shared.CouchbaseExceptionType_SDK_DOCUMENT_EXISTS_EXCEPTION,
	"DurabilityLevelNotAvailableException":       shared.CouchbaseExceptionType_SDK_DURABILITY_LEVEL_NOT_AVAILABLE_EXCEPTION,
	"DurabilityImpossibleException":              shared.CouchbaseExceptionType_SDK_DURABILITY_IMPOSSIBLE_EXCEPTION,
	"DurabilityAmbiguousException":               shared.CouchbaseExceptionType_SDK_DURABILITY_AMBIGUOUS_EXCEPTION,
	"DurableWriteInProgressException":            shared.CouchbaseExceptionType_SDK_DURABLE_WRITE_IN_PROGRESS_EXCEPTION,
	"DurableWriteReCommitInProgressException":    shared.CouchbaseExceptionType_SDK_DURABLE_WRITE_RECOMMIT_IN_PROGRESS_EXCEPTION,
	"PathNotFoundException":                      shared.CouchbaseExceptionType_SDK_PATH_NOT_FOUND_EXCEPTION,
	"PathMismatchException":                      shared.CouchbaseExceptionType_SDK_PATH_MISMATCH_EXCEPTION,
	"PathInvalidException":                       shared.CouchbaseExceptionType_SDK_PATH_INVALID_EXCEPTION,
	"PathTooBigException":                        shared.CouchbaseExceptionType_SDK_PATH_TOO_BIG_EXCEPTION,
	"PathTooDeepException":                       shared.CouchbaseExceptionType_SDK_PATH_TOO_DEEP_EXCEPTION,
	"PathExistsException":                        shared.CouchbaseExceptionType_SDK_PATH_EXISTS_EXCEPTION,
	"ValueTooDeepException":                      shared.CouchbaseExceptionType_SDK_VALUE_TOO_DEEP_EXCEPTION,
	"ValueInvalidException":                      shared.CouchbaseExceptionType_SDK_VALUE_INVALID_EXCEPTION,
	"DocumentNotJsonException":                   shared.CouchbaseExceptionType_SDK_DOCUMENT_NOT_JSON_EXCEPTION,
	"NumberTooBigException":                      shared.CouchbaseExceptionType_SDK_NUMBER_TOO_BIG_EXCEPTION,
	"DeltaInvalidException":                      shared.CouchbaseExceptionType_SDK_DELTA_INVALID_EXCEPTION,
	"XattrUnknownMacroException":                 shared.CouchbaseExceptionType_SDK_XATTR_UNKNOWN_MACRO_EXCEPTION,
	"XattrInvalidKeyComboException":              shared.CouchbaseExceptionType_SDK_XATTR_INVALID_KEY_COMBO_EXCEPTION,
	"XattrUnknownVirtualAttributeException":      shared.CouchbaseExceptionType_SDK_XATTR_UNKNOWN_VIRTUAL_ATTRIBUTE_EXCEPTION,
	"XattrCannotModifyVirtualAttributeException": shared.CouchbaseExceptionType_SDK_XATTR_CANNOT_MODIFY_VIRTUAL_ATTRIBUTE_EXCEPTION,

	// Query
	"PlanningFailureException":          shared.CouchbaseExceptionType_SDK_PLANNING_FAILURE_EXCEPTION,
	"IndexFailureException":             shared.CouchbaseExceptionType_SDK_INDEX_FAILURE_EXCEPTION,
	"PreparedStatementFailureException": shared.CouchbaseExceptionType_SDK_PREPARED_STATEMENT_FAILURE_EXCEPTION,

	// Analytics
	"CompilationFailureException": shared.CouchbaseExceptionType_SDK_COMPILATION_FAILURE_EXCEPTION,
	"JobQueueFullException":       shared.CouchbaseExceptionType_SDK_JOB_QUEUE_FULL_EXCEPTION,
	"DatasetNotFoundException":    shared.CouchbaseExceptionType_SDK_DATASET_NOT_FOUND_EXCEPTION,
	"DataverseNotFoundException":  shared.CouchbaseExceptionType_SDK_DATAVERSE_NOT_FOUND_EXCEPTION,
	"DatasetExistsException":      shared.CouchbaseExceptionType_SDK_DATASET_EXISTS_EXCEPTION,
	"DataverseExistsException":    shared.CouchbaseExceptionType_SDK_DATAVERSE_EXISTS_EXCEPTION,
	"LinkNotFoundException":       shared.CouchbaseExceptionType_SDK_LINK_NOT_FOUND_EXCEPTION,

	// View
	"ViewNotFoundException":           shared.CouchbaseExceptionType_SDK_VIEW_NOT_FOUND_EXCEPTION,
	"DesignDocumentNotFoundException": shared.CouchbaseExceptionType_SDK_DESIGN_DOCUMENT_NOT_FOUND_EXCEPTION,

	// Management
	"CollectionExistsException":   shared.CouchbaseExceptionType_SDK_COLLECTION_EXISTS_EXCEPTION,
	"ScopeExistsException":        shared.CouchbaseExceptionType_SDK_SCOPE_EXISTS_EXCEPTION,
	"UserNotFoundException":       shared.CouchbaseExceptionType_SDK_USER_NOT_FOUND_EXCEPTION,
	"GroupNotFoundException":      shared.CouchbaseExceptionType_SDK_GROUP_NOT_FOUND_EXCEPTION,
	"BucketExistsException":       shared.CouchbaseExceptionType_SDK_BUCKET_EXISTS_EXCEPTION,
	"UserExistsException":         shared.CouchbaseExceptionType_SDK_USER_EXISTS_EXCEPTION,
	"BucketNotFlushableException": shared.CouchbaseExceptionType_SDK_BUCKET_NOT_FLUSHABLE_EXCEPTION,
}

// performerException reports a performer-side failure, distinct from any SDK error.
func performerException(err error) *shared.Exception {
	return &shared.Exception{
		Exception: &shared.Exception_Other{
			Other: &shared.ExceptionOther{
				Name:       "PerformerError",
				Serialized: err.Error(),
			},
		},
	}
}

func exceptionToProto(exc *phpbackend.Exception) *shared.Exception {
	name := exc.GetName()

	if !exc.IsCouchbase() {
		return &shared.Exception{
			Exception: &shared.Exception_Other{
				Other: &shared.ExceptionOther{
					Name:       name,
					Serialized: exc.GetSerialized(),
				},
			},
		}
	}

	exceptionType, ok := couchbaseExceptionTypes[name]
	if !ok {
		exceptionType = shared.CouchbaseExceptionType_SDK_COUCHBASE_EXCEPTION
	}

	return &shared.Exception{
		Exception: &shared.Exception_Couchbase{
			Couchbase: &shared.CouchbaseExceptionEx{
				Name:       name,
				Type:       exceptionType,
				Serialized: exc.GetSerialized(),
			},
		},
	}
}
