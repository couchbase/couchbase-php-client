# Build steps for Windows

This document describes steps to build module for Windows OS.

Download Windows VM image (or use another method to get running machine with Windows OS):

https://developer.microsoft.com/en-us/windows/downloads/virtual-machines/

Install scoop package manager (https://scoop.sh/):

    Set-ExecutionPolicy RemoteSigned -scope CurrentUser
    iwr -useb get.scoop.sh | iex

Install tools and dependencies:

    scoop install git openssl cmake

Clone Couchbase SDK source code (note `--recurse-submodules`):

    git clone --recurse-submodules https://github.com/couchbaselabs/couchbase-php-client c:\users\user\couchbase-php-client

Clone PHP interpreter source code (switch to necessary branch):

    git clone https://github.com/php/php-src c:\users\user\php-src
    cd c:\users\user\php-src
    git checkout PHP-8.1

Clone PHP SDK tools

    git clone https://github.com/php/php-sdk-binary-tools c:\php-sdk

Open "Visual Studio Installer" application and make sure that "Desktop development with C++" workload is installed.

Navigate to PHP SDK tools and load build environment (we assume Visual Studio 2022, aka VS17 here):

    cd c:\php-sdk
    .\phpsdk-vs17-x64.bat

Inside the shell with build environment, navigate to PHP interpreter sources

    cd c:\users\user\php-src

Generate configuration script, note that we pass directory, where Couchbase PHP SDK respository is located:

    .\buildconf.bat --add-modules-dir=c:\users\user

Trigger build configuration:

    .\configure.bat --disable-all --enable-cli --enable-couchbase

Build the module:

    nmake

# Verification steps Windows

The aforementioned build process will generade command-line version of `php.exe` and DLLs for PHP extension:

    c:\users\user\php-src\x64\Release_TS\php.exe
    c:\users\user\php-src\x64\Release_TS\php8ts.dll
    c:\users\user\php-src\x64\Release_TS\php_couchbase.dll
    c:\users\user\php-src\x64\Release_TS\couchbase_php_core.dll

Lets create another directory and copy these three files so that the following file tree will be in result:

    c:\users\user\php-verification\
    |- php.exe
    |- php8ts.dll
    |- php_couchbase.dll
    +- couchbase_php_core.dll

Now navigate to verification directory and check that it can load the extension successfully:

    cd c:\users\user\php-verification\

Display list of modules:

    PS> php -d extension=c:\users\user\php-verification\php_couchbase.dll -m
    [PHP Modules]
    Core
    couchbase
    date
    hash
    json
    pcre
    Reflection
    SPL
    standard

Dump runtime configuration to the terminal (equivalent of `<?php phpinfo(); php?>`:

    PS> php -d extension=c:\users\user\php-verification\php_couchbase.dll -i
    ...
    couchbase => enabled
    couchbase_extension_version => 4.0.0
    couchbase_extension_revision => 82377e66c3b8f1d4798cd9860adbd32f00983541
    couchbase_client_revision => ee002352870f1d623d81868b80a5ef70c0acfcee
    couchbase_transactions_revision => ee002352870f1d623d81868b80a5ef70c0acfcee
    ...


Display service information, that was recorded during the build:

    PS> php -d extension=c:\Users\User\php-verification\php_couchbase.dll -r "print_r(Couchbase\Extension\version());"
    Array
    (
        [extension_revision] => 82377e66c3b8f1d4798cd9860adbd32f00983541
        [cxx_client_revision] => ee002352870f1d623d81868b80a5ef70c0acfcee
        [cxx_transactions_revision] => ee002352870f1d623d81868b80a5ef70c0acfcee
        [asio] => 1.21.0
        [build_timestamp] => 2022-04-18 15:12:13
        [cc] => MSVC 19.30.30709.0
        [cmake_build_type] => RelWithDebInfo
        [cmake_version] => 3.23.1
        [compile_definitions] => FMT_LOCALE;SPDLOG_COMPILED_LIB;SPDLOG_FMT_EXTERNAL
        [compile_features] => cxx_std_17;cxx_variadic_templates
        [compile_flags] =>
        [compile_options] =>
        [cpu] => AMD64
        [cxx] => MSVC 19.30.30709.0
        [fmt] => 8.800.1
        [http_parser] => 2.9.4
        [link_depends] =>
        [link_flags] =>
        [link_libraries] => project_options;project_warnings;fmt::fmt;spdlog::spdlog;couchbase_backtrace;couchbase_logger;couchbase_platform;couchbase_io;couchbase_meta;couchbase_crypto;couchbase_sasl;couchbase_topology;couchbase_utils;couchbase_protocol;couchbase_management;couchbase_operations;couchbase_operations_management;couchbase_tracing;couchbase_metrics;OpenSSL::SSL;OpenSSL::Crypto
        [link_options] =>
        [openssl_headers] => OpenSSL 3.0.2 15 Mar 2022
        [openssl_runtime] => OpenSSL 3.0.2 15 Mar 2022
        [platform] => Windows-10.0.22000
        [post_linked_openssl] => OFF
        [revision] => b5800042a227065cfa8bc4dfc0bcaf04fd859fc7
        [snappy] => 1.1.8
        [snapshot] =>
        [spdlog] => 1.9.2
        [static_openssl] =>
        [static_stdlib] =>
        [version] => 1.0.0
        [version_build] => 0
        [version_major] => 1
        [version_minor] => 0
        [version_patch] => 0
    )
