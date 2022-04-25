# Couchbase PHP Extension

[![license](https://img.shields.io/github/license/couchbaselabs/couchbase-php-client?color=brightgreen)](https://opensource.org/licenses/Apache-2.0)

This repository contains source code of the Couchbase PHP SDK. 

## Support and Feedback

If you find an issue, please file it in [our JIRA issue tracker](https://couchbase.com/issues/browse/PCBC). Also you are
always welcome on [our forum](https://forums.couchbase.com/c/php-sdk) and [Discord](https://discord.com/invite/sQ5qbPZuTh).


## Getting the Source Code

This repo uses several git submodules. If you are fetching the repo for the first time by command line, the
`--recurse-submodules` option will init the submodules recursively as well:
```shell
git clone --recurse-submodules https://github.com/couchbaselabs/couchbase-php-client.git
```

However, if you fetched using a simple clone command (or another IDE or tool), or if you are pulling the latest and need to update, then **you must also perform** the following command to recursively update and initialize the submodules:
```shell
git submodule update --init --recursive
```


## Building the Extension

This project is built with `CMake` and packaged with `pecl` so everything should build easily once the basic dev dependencies are satisfied. However, we have helper scripts in the [/bin](./bin) directory to help automate tasks even further.

### Dev Dependencies

The following dependencies must be installed before the project can be built. We recommend using OS specific utilities
such as `brew`, `apt-get`, and similar package management utilities (depending on your environment).
- **cmake >= 3.20.0+** (e.g., `brew install cmake`)
- **c++ compiler >= std_17** (e.g., `xcode-select --install`)
- **openssl >= 1.1+** (e.g., `brew install openssl`)

**IMPORTANT:** On macOS, the **OpenSSL** `brew` install command mentioned above is not sufficient to be able to build. The easiest way to fix this is to add the `OPENSSL_ROOT_DIR` env variable to your exports (e.g., `.zshenv`). If this is not sufficient, see the other tips mentioned when you run `brew info openssl`.
```shell
export OPENSSL_ROOT_DIR=/usr/local/opt/openssl/
```

### Building (with shell script)
```shell
cd couchbase-php-client
./bin/build
./bin/package
```


## Running Tests

The tests are located in the (/tests)[./tests] directory. More tests may be added and this directory will be organized more in the near future to differentiate between the common tests types that might be used for different types of testing (e.g., `unit tests`, `integration tests`, `system tests`).

### Testing (command-line)
```shell
./bin/test
```
