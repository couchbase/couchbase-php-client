#!/usr/bin/bash

PROJECT_ROOT="$( cd "$(dirname "$0"/..)" >/dev/null 2>&1 ; pwd -P )"
PHPCBF=${PROJECT_ROOT}/vendor/bin/phpcbf

set -xe

if [ ! -x "${PHPCBF}" ]
then
    composer update
fi

${PHPCBF} --standard=${PROJECT_ROOT}/phpcs.xml ${PROJECT_ROOT}/src ${PROJECT_ROOT}/tests
