package common

import (
	"github.com/couchbaselabs/transactions-fit-performer/counter"
	"time"
)

type boundsExecutor interface {
	CanExecute() bool
}

type counterBoundsExecutor struct {
	counter *counter.Counter
}

func newCounterBoundsExecutor(counter *counter.Counter) *counterBoundsExecutor {
	return &counterBoundsExecutor{
		counter: counter,
	}
}

func (executor *counterBoundsExecutor) CanExecute() bool {
	return executor.counter.GetAndDecrement() >= 0
}

// counterEqualityBoundsExecutor runs while the counter holds its start value; something external changes it.
type counterEqualityBoundsExecutor struct {
	initialValue int32
	counter      *counter.Counter
}

func newCounterEqualityBoundsExecutor(counter *counter.Counter) *counterEqualityBoundsExecutor {
	return &counterEqualityBoundsExecutor{
		initialValue: counter.Get(),
		counter:      counter,
	}
}

func (executor *counterEqualityBoundsExecutor) CanExecute() bool {
	return executor.counter.Get() == executor.initialValue
}

type timeBoundsExecutor struct {
	deadline time.Time
}

func newTimeBoundsExecutor(deadline time.Time) *timeBoundsExecutor {
	return &timeBoundsExecutor{
		deadline: deadline,
	}
}

func (executor *timeBoundsExecutor) CanExecute() bool {
	return time.Now().Before(executor.deadline)
}
