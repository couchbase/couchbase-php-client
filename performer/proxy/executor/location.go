package executor

import (
	"errors"
	"fmt"
	"math/rand"

	"github.com/google/uuid"

	"github.com/couchbaselabs/transactions-fit-performer/counter"
	"github.com/couchbaselabs/transactions-fit-performer/phpbackend"
	"github.com/couchbaselabs/transactions-fit-performer/protocol/shared"
)

// docLocation is a shared.DocLocation with the pool/counter/uuid strategy already applied.
type docLocation struct {
	collection *shared.Collection
	id         string
}

func (loc *docLocation) ID() string         { return loc.id }
func (loc *docLocation) Bucket() string     { return loc.collection.BucketName }
func (loc *docLocation) Scope() string      { return loc.collection.ScopeName }
func (loc *docLocation) Collection() string { return loc.collection.CollectionName }

func toPHPLocation(loc *docLocation) *phpbackend.DocLocation {
	return &phpbackend.DocLocation{
		Bucket:     loc.Bucket(),
		Scope:      loc.Scope(),
		Collection: loc.Collection(),
		Id:         loc.ID(),
	}
}

func location(loc *shared.DocLocation, counters *counter.Counters) (*docLocation, error) {
	switch l := loc.Location.(type) {
	case *shared.DocLocation_Specific:
		return &docLocation{
			collection: l.Specific.Collection,
			id:         l.Specific.Id,
		}, nil
	case *shared.DocLocation_Pool:
		var next int
		switch strat := l.Pool.PoolSelectionStrategy.(type) {
		case *shared.DocLocationPool_Random:
			if strat.Random.Distribution == shared.RandomDistribution_RANDOM_DISTRIBUTION_UNIFORM {
				next = rand.Intn(int(l.Pool.PoolSize))
			} else {
				return nil, errors.New("unrecognised random distribution")
			}
		case *shared.DocLocationPool_Counter:
			c, err := counters.Get(strat.Counter.Counter)
			if err != nil {
				return nil, err
			}
			next = int(c.GetAndIncrement()) % int(l.Pool.PoolSize)
		default:
			return nil, errors.New("unrecognised pool selection strategy")
		}

		return &docLocation{
			collection: l.Pool.Collection,
			id:         fmt.Sprintf("%s%d", l.Pool.IdPreface, next),
		}, nil
	case *shared.DocLocation_Uuid:
		return &docLocation{
			collection: l.Uuid.Collection,
			id:         uuid.NewString(),
		}, nil
	default:
		return nil, errors.New("command had no valid location")
	}
}

func collectionLocation(collection *shared.Collection) *phpbackend.DocLocation {
	return &phpbackend.DocLocation{
		Bucket:     collection.GetBucketName(),
		Scope:      collection.GetScopeName(),
		Collection: collection.GetCollectionName(),
	}
}
