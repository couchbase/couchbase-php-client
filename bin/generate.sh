#!/usr/bin/bash

PROTOC=${PROTOC:-$(which protoc)}
GRPC_PHP_PLUGIN=${GRPC_PHP_PLUGIN:-$(which grpc_php_plugin)}

PROJECT_ROOT="$( cd "$(dirname "$0"/..)" >/dev/null 2>&1 ; pwd -P )"
PROTOSTELLAR_PATH="${PROJECT_ROOT}/src/deps/protostellar"
GOOGLE_APIS_PATH="${PROJECT_ROOT}/src/deps/googleapis"
SRC_PATH="${PROJECT_ROOT}"
STUBS_PATH="${SRC_PATH}/Couchbase/Protostellar/Generated"

set -xe

rm -rf "${STUBS_PATH}"
mkdir -p "${STUBS_PATH}"

for proto in $(find "${PROTOSTELLAR_PATH}" -type f -name '*.proto')
do
    ${PROTOC} \
        --proto_path="${PROTOSTELLAR_PATH}" \
        --proto_path="${GOOGLE_APIS_PATH}" \
        --php_out="${SRC_PATH}" \
        --grpc_out="${SRC_PATH}" \
        --plugin=protoc-gen-grpc="${GRPC_PHP_PLUGIN}" \
        "$proto"
done
    
