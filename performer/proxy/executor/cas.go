package executor

import "strconv"

// CAS crosses the wire as hex in both directions; casFromString and casToHex must stay inverses.

func casFromString(s string) int64 {
	return hexToInt64(s)
}

// casToString drops CAS 0, which the protocol uses for "unset".
func casToString(cas *int64) *string {
	if cas == nil || *cas == 0 {
		return nil
	}

	s := casToHex(*cas)
	return &s
}

// casToHex keeps 0: the driver unlocks with CAS 0 to check the SDK rejects it.
func casToHex(cas int64) string {
	return strconv.FormatUint(uint64(cas), 16)
}

// hexToInt64 parses the SDK's hex-encoded uint64s (CAS, partition uuid, seqno); bad input yields 0.
func hexToInt64(s string) int64 {
	v, _ := strconv.ParseUint(s, 16, 64)
	return int64(v)
}
