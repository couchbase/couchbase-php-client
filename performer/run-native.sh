#!/usr/bin/env bash
# Runs the PHP backend (php -S) and the Go gRPC proxy in front of it. Also the Docker entrypoint;
# prefer the host for local runs, since a container can't reliably reach the driver's fault proxy.
# Prereqs: couchbase extension, protoc, (cd backend/src && composer install) && (cd proxy && make proto)
#
#   PERFORMER_PORT          gRPC port the driver connects to (8060)
#   BACKEND_PORT            PHP backend port (8000)
#   PHP_CLI_SERVER_WORKERS  backend worker processes (64)
#   PHP_MAX_PERSISTENT      sets couchbase.max_persistent; 0 for fault-injection proxy tests
#   PROXY_BIN               prebuilt proxy binary; otherwise built with go build
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")"

PERFORMER_PORT="${PERFORMER_PORT:-8060}"
BACKEND_PORT="${BACKEND_PORT:-8000}"

# The backend's total concurrency. Keep >= driver concurrency and <= maxBackendConns in the proxy.
export PHP_CLI_SERVER_WORKERS="${PHP_CLI_SERVER_WORKERS:-64}"

php_modules="$(php -m)"
if ! grep -qi '^couchbase$' <<<"${php_modules}"; then
  echo "error: the couchbase PHP extension is not loaded. Build it with ./bin/build.rb and add modules/couchbase.so to php.ini (see README)" >&2
  exit 1
fi
library_version="$(php -r 'echo phpversion("couchbase");')"

if [[ ! -f backend/src/vendor/autoload.php ]]; then
  echo "error: composer dependencies missing. Run: (cd backend/src && composer install)" >&2
  exit 1
fi

# PHP_MAX_PERSISTENT=0: the connection cache keys only on connstr, so it would reuse a connection
# whose sockets died with a previous fault-injection proxy.
php_ini_args=()
if [[ -n "${PHP_MAX_PERSISTENT:-}" ]]; then
  php_ini_args+=(-d "couchbase.max_persistent=${PHP_MAX_PERSISTENT}")
  echo "couchbase.max_persistent=${PHP_MAX_PERSISTENT} (connection caching altered; expect slower ops)"
fi

backend_up() { (exec 3<>"/dev/tcp/127.0.0.1/${BACKEND_PORT}") 2>/dev/null; }

if backend_up; then
  echo "error: something is already listening on port ${BACKEND_PORT}; stop it or set BACKEND_PORT" >&2
  exit 1
fi

# Own process group (set -m) so cleanup also kills the worker processes.
# index.php is the router script; without it php -S 404s /execute.
set -m
php ${php_ini_args[@]+"${php_ini_args[@]}"} -S "127.0.0.1:${BACKEND_PORT}" -t backend/src/public backend/src/public/index.php &
backend_pid=$!
set +m

cleanup() {
  kill -- "-${backend_pid}" 2>/dev/null || true
  if [[ -n "${proxy_pid:-}" ]]; then kill "${proxy_pid}" 2>/dev/null || true; fi
}
trap cleanup EXIT
trap 'exit 130' INT
trap 'exit 143' TERM

for _ in $(seq 50); do
  if ! kill -0 "${backend_pid}" 2>/dev/null; then
    echo "error: the PHP backend exited on startup" >&2
    exit 1
  fi
  if backend_up; then
    break
  fi
  sleep 0.1
done

echo "PHP backend on http://127.0.0.1:${BACKEND_PORT} (pid ${backend_pid}, ${PHP_CLI_SERVER_WORKERS} workers)"

if [[ -z "${PROXY_BIN:-}" ]]; then
  (cd proxy && go build -o ./php-performer .)
  PROXY_BIN=proxy/php-performer
fi

"${PROXY_BIN}" \
  -php-backend-url "http://127.0.0.1:${BACKEND_PORT}" \
  -port "${PERFORMER_PORT}" \
  -library-version "${library_version}" &
proxy_pid=$!
wait "${proxy_pid}"
