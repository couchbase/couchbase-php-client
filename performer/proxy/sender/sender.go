package sender

import "github.com/couchbaselabs/transactions-fit-performer/protocol/run"

type ResultSender interface {
	Send(*run.Result)
}
