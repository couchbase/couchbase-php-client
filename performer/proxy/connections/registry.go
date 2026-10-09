// Package connections maps ClusterConnectionId to the params re-sent on every backend request.
package connections

import (
	"fmt"
	"sync"

	"github.com/couchbaselabs/transactions-fit-performer/phpbackend"
)

type Registry struct {
	lock  sync.Mutex
	conns map[string]phpbackend.ConnectionParams
}

func NewRegistry() *Registry {
	return &Registry{
		conns: make(map[string]phpbackend.ConnectionParams),
	}
}

func (r *Registry) Add(id string, params phpbackend.ConnectionParams) int {
	r.lock.Lock()
	defer r.lock.Unlock()

	r.conns[id] = params
	return len(r.conns)
}

func (r *Registry) Get(id string) (phpbackend.ConnectionParams, error) {
	r.lock.Lock()
	defer r.lock.Unlock()

	params, ok := r.conns[id]
	if !ok {
		return phpbackend.ConnectionParams{}, fmt.Errorf("connection not known for %s", id)
	}
	return params, nil
}

func (r *Registry) Remove(id string) int {
	r.lock.Lock()
	defer r.lock.Unlock()

	delete(r.conns, id)
	return len(r.conns)
}

// All returns a copy.
func (r *Registry) All() map[string]phpbackend.ConnectionParams {
	r.lock.Lock()
	defer r.lock.Unlock()

	out := make(map[string]phpbackend.ConnectionParams, len(r.conns))
	for k, v := range r.conns {
		out[k] = v
	}
	return out
}
