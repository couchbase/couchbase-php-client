# Build steps for Windows

This document describes steps to build module for Windows OS.

Download Windows VM image (or use another method to get running machine with Windows OS):

https://developer.microsoft.com/en-us/windows/downloads/virtual-machines/

Install scoop package manager (https://scoop.sh/):

Install tools and dependencies:

    winget install Git.Git
    winget install Kitware.CMake
    winget install NASM.NASM

Clone Couchbase SDK source code (note `--recurse-submodules`):

    git clone --recurse-submodules https://github.com/couchbaselabs/couchbase-php-client c:\users\user\couchbase-php-client

NOTE: it is important that path to the extension source will not have spaces, otherwise PHP build
system will not be able to work properly.

Clone PHP interpreter source code (switch to necessary branch):

    git clone https://github.com/php/php-src c:\users\user\php-src
    cd c:\users\user\php-src
    git checkout PHP-8.1

Clone PHP SDK tools

    git clone https://github.com/php/php-sdk-binary-tools c:\php-sdk

Open "Visual Studio Installer" application and make sure that "Desktop development with C++" workload is installed.

Navigate to PHP SDK tools and load build environment (we assume Visual Studio 2019, aka VS16 here):

    cd c:\php-sdk
    .\phpsdk-vs16-x64.bat

Inside the shell with build environment, navigate to PHP interpreter sources

    cd c:\users\user\php-src

Generate configuration script, note that we pass directory, where Couchbase PHP SDK respository is located:

    .\buildconf.bat --add-modules-dir=c:\users\user

Trigger build configuration:

    .\configure.bat --disable-all --enable-cli --enable-couchbase

Build the module:

    nmake

# Verification steps Windows

The aforementioned build process will generate a command-line version of `php.exe` and DLLs for PHP extension:

    c:\users\user\php-src\x64\Release_TS\php.exe
    c:\users\user\php-src\x64\Release_TS\php8ts.dll
    c:\users\user\php-src\x64\Release_TS\php_couchbase.dll

Lets create another directory and copy these three files so that the following file tree will be in result:

    c:\users\user\php-verification\
    |- php.exe
    |- php8ts.dll
    +- php_couchbase.dll

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
        [extension_revision] => 5d98471f9651e2157a160443990d303a0eb1cbc3
        [cxx_client_revision] => 291ed3da17a0a0d68a599dd25306dbb8eb7febac
        [_MSC_VER] => 1929
        [__cplusplus] => 199711
        [asio] => 1.29.0
        [boringssl_sha] => 2ff4b968a7e0cfee66d9f151cb95635b43dc1d5b
        [build_timestamp] => 2024-04-05 00:34:10
        [cc] => MSVC 19.29.30154.0
        [cmake_build_type] => RelWithDebInfo
        [cmake_version] => 3.29.1
        [compile_definitions] => SPDLOG_COMPILED_LIB;SPDLOG_FMT_EXTERNAL;ASIO_STANDALONE;ASIO_NO_DEPRECATED;_WIN32_WINNT=0x0A00;WIN32_LEAN_AND_MEAN
        [compile_features] => cxx_std_17;cxx_std_11;cxx_std_17;cxx_std_17
        [compile_flags] =>
        [compile_options] => /bigobj
        [cpu] => AMD64
        [cxx] => MSVC 19.29.30154.0
        [fmt] => 10.2.1
        [hdr_histogram_c] => 0.11.8
        [link_depends] =>
        [link_flags] =>
        [link_libraries] => project_options;project_warnings;fmt::fmt;spdlog::spdlog;couchbase_backtrace;couchbase_logger;couchbase_platform;couchbase_meta;couchbase_crypto;couchbase_sasl;couchbase_tracing;couchbase_metrics;Microsoft.GSL::GSL;asio;llhttp::llhttp;taocpp::json;snappy;jsonsl;hdr_histogram_static;iphlpapi;OpenSSL::SSL;OpenSSL::Crypto
        [link_options] =>
        [llhttp] => 9.2.0
        [mozilla_ca_bundle_date] => Mon Mar 11 15:25:27 2024 GMT
        [mozilla_ca_bundle_embedded] => 1
        [mozilla_ca_bundle_sha256] => 1794c1d4f7055b7d02c2170337b61b48a2ef6c90d77e95444fd2596f4cac609f
        [mozilla_ca_bundle_size] => 147
        [openssl_config_dir] => OPENSSLDIR: n/a
        [openssl_crypto_interface_imported_location] =>
        [openssl_crypto_interface_include_directories] => C:/Users/user/.cache/CPM/boringssl/e31ea00c1ea52052d2d78d44006cc88c80fa24a9/boringssl/src/include
        [openssl_crypto_interface_link_libraries] => crypto
        [openssl_default_cert_dir] => /etc/ssl/certs
        [openssl_default_cert_dir_env] => SSL_CERT_DIR
        [openssl_default_cert_file] => /etc/ssl/cert.pem
        [openssl_default_cert_file_env] => SSL_CERT_FILE
        [openssl_headers] => OpenSSL 1.1.1 (compatible; BoringSSL)
        [openssl_pkg_config_interface_include_directories] =>
        [openssl_pkg_config_interface_link_libraries] =>
        [openssl_runtime] => BoringSSL
        [openssl_ssl_imported_location] =>
        [openssl_ssl_interface_include_directories] => C:/Users/user/.cache/CPM/boringssl/e31ea00c1ea52052d2d78d44006cc88c80fa24a9/boringssl/src/include
        [openssl_ssl_interface_link_libraries] => crypto
        [platform] => Windows-10.0.26100
        [platform_name] => Windows
        [platform_version] => 10.0.26100
        [post_linked_openssl] => OFF
        [revision] => 291ed3da17a0a0d68a599dd25306dbb8eb7febac
        [semver] => 1.0.0-dp.14+8.291ed3d
        [snappy] => 1.1.10
        [snapshot] =>
        [spdlog] => 1.13.0
        [static_boringssl] => true
        [static_openssl] =>
        [static_stdlib] =>
        [txns_forward_compat_extensions] => TI,MO,BM,QU,SD,BF3787,BF3705,BF3838,RC,UA,CO,BF3791,CM,SI,QC,IX,TS,PU
        [txns_forward_compat_protocol_version] => 2.0
        [version] => 1.0.0
        [version_build] => 0
        [version_major] => 1
        [version_minor] => 0
        [version_patch] => 0
    )
