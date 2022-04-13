# Couchbase PHP Extension

[![license](https://img.shields.io/github/license/couchbaselabs/couchbase-php-client?color=brightgreen)](https://opensource.org/licenses/Apache-2.0)

This repository contains source code of the Couchbase PHP Extension. 

The extension provides low-level APIs for the Couchbase PHP Client SDK.

This repo is under active development and is not yet ready for release as a public SDK.


## Getting the Source Code

Clone this repo as usual, but this repo also uses several git submodules. However, the build helper scripts (see below) should take care of fetching the required submodules for you.


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
