# PHP FIT performer

FIT performer for the Couchbase PHP SDK. It builds the extension and the `Couchbase\` classes from
this checkout, so it always tests the SDK it ships with.

```
 FIT driver ──gRPC(8060)──▶ Go proxy ──HTTP/JSON(8000)──▶ PHP backend ──▶ Couchbase
                            (proxy/)                      (backend/)
```

- **Go proxy** - speaks the FIT protocol (bounds, doc locations, counters, batching, streams) and
  forwards each operation as JSON. Forked from the Go performer; `counter/` and `sender/` are
  identical to it, `streams/` and `common/` have diverged.
- **PHP backend** - makes the SDK calls. Stateless: connection params come with every request, and
  the SDK's persistent-connection cache avoids reconnecting.

## Layout

| Path | |
| --- | --- |
| `proxy/phpbackend/contract.go` | the Go↔PHP wire contract - start here for cross-tier changes |
| `proxy/executor/` | protobuf → wire → `sdk.Result`; `options.go`, `clusterconfig.go`, `content.go` mirror `backend/src/lib/{options,cluster,content}.php` |
| `proxy/executor/stream.go` | stream lifecycle |
| `backend/src/public/index.php` | routes: `/connection/check`, `/connection/close`, `/execute`, `/execute/stream` |
| `proto/` | fit-protocol mirror; refresh with `scripts/update-protobuf.sh` |
| `Dockerfile`, `../.github/workflows/fit-performer-image.yml` | builds and publishes `ghcr.io/couchbase/php-fit-performer` |

## Setup

Needs PHP 8.2+, composer, Go 1.19+, `protoc`, cmake and a C++ toolchain.

```sh
go install google.golang.org/protobuf/cmd/protoc-gen-go@v1.28.1 google.golang.org/grpc/cmd/protoc-gen-go-grpc@v1.2.0
./bin/build.rb
(cd performer/backend/src && composer install)
(cd performer/proxy && make proto)
```

Then load the built extension: add `extension=/path/to/couchbase-php-client/modules/couchbase.so`
to the `php.ini` that `php --ini` reports.

## Running

```sh
./run-native.sh                          # PHP_MAX_PERSISTENT=0 for the fault-injection proxy tests
```

| Env var | Default | |
| --- | --- | --- |
| `PERFORMER_PORT` | 8060 | |
| `BACKEND_PORT` | 8000 | |
| `PHP_CLI_SERVER_WORKERS` | 64 | backend concurrency; keep ≥ driver concurrency, ≤ 128 |
| `PHP_MAX_PERSISTENT` | unset | `0` disables the SDK connection cache (slower ops) |
| `PROXY_BIN` | unset | prebuilt proxy instead of `go build` |

Run natively for local testing: a containerised performer can't reliably reach the driver's
host-side fault-injection proxy. Docker, from the repo root:

```sh
docker build -f performer/Dockerfile -t php-fit-performer .
docker run -p 8060:8060 php-fit-performer
```

CI publishes the image on pushes to `main` and tags, or for any ref via *Run workflow*.

The PHP tier can be poked directly with `POST :8000/execute`.

## Known gaps

- Not implemented: transactions, `SpanCreate`/`SpanFinish`, cluster/bucket/scope commands (query,
  search, analytics, management), reads from a specific replica
- The SDK's connection cache keys only on connstr, auth and tracing (PCBC-1056), so connections
  differing only in `ClusterConfig` are shared, and the fault-injection proxy tests can get a dead
  cached connection. Use `PHP_MAX_PERSISTENT=0` for those.
