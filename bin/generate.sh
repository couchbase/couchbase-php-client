#!/usr/bin/bash

PROTOC=${PROTOC:-$(which protoc)}
GRPC_PHP_PLUGIN=${GRPC_PHP_PLUGIN:-$(which grpc_php_plugin)}

PROJECT_ROOT="$( cd "$(dirname "$0"/..)" >/dev/null 2>&1 ; pwd -P )"
STELLAR_NEBULA_PATH="${PROJECT_ROOT}/deps/stellar-nebula"
SRC_PATH="${PROJECT_ROOT}/src"
STUBS_PATH="${SRC_PATH}/Couchbase/StellarNebula/Generated"

set -xe

rm -rf "${STUBS_PATH}"
mkdir -p "${STUBS_PATH}"

for proto in $(find "${STELLAR_NEBULA_PATH}/proto" -type f -name '*.proto')
do
    ${PROTOC} \
        --proto_path="${STELLAR_NEBULA_PATH}/proto" \
        --proto_path="${STELLAR_NEBULA_PATH}/contrib/googleapis" \
        --php_out="${SRC_PATH}" \
        --grpc_out="${SRC_PATH}" \
        --plugin=protoc-gen-grpc="${GRPC_PHP_PLUGIN}" \
        "$proto"
done
    
