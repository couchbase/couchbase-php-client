# Couchbase\StellarNebula

## Development

Clone repository and switch to branch `stellar-nebula`:

    git clone https://github.com/couchbase/couchbase-php-client.git --branch stellar-nebula

Install development dependencies:

    composer install

Run the tests:

    composer exec ./vendor/bin/phpunit tests

## Regenerate gRPC Stubs

Ensure that submodules are synchronized:

    git submodule update --init --recursive

Regenerate gRPC stubs:

    ./bin/generate.sh
