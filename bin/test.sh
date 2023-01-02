#!/usr/bin/bash

PROJECT_ROOT="$( cd "$(dirname "$0"/..)" >/dev/null 2>&1 ; pwd -P )"
PHPUNIT=${PROJECT_ROOT}/vendor/bin/phpunit

set -xe

if [ ! -x "${PHPCBF}" ]
then
    composer update
fi

${PHPUNIT} \
    -d extenion=protobuf \
    -d extension=grpc \
    --bootstrap ${PROJECT_ROOT}/vendor/autoload.php \
    --log-junit ${PROJECT_ROOT}/results.xml \
    --color \
    --testdox \
    ${PROJECT_ROOT}/tests 
