# Running the SDK in containers

A PHP process runs one version of the SDK: the extension and the `couchbase/couchbase` Composer
package from the same release. Applications that need different SDK versions run in separate
processes. A container per application is one way to keep them apart.

## One version per process

From 4.6.0, release archives contain only the module `couchbase`. PHP refuses a second module with
that name, whatever its file is called:

```text
Warning: Module "couchbase" is already loaded in Unknown on line 0
```

The library checks only that the `couchbase` module is loaded. It does not compare its own release
with the extension's. Pin the Composer package to the release of the installed extension:

```json
{
    "require": {
        "couchbase/couchbase": "4.6.0"
    }
}
```

Released `-abi` archives of 4.5.0 and earlier have distinct module names, such as `couchbase_4_5_0`,
and load side by side. That is not supported, because the modules share one process:

- **Settings.** Only the module loaded first registers the `couchbase.*` INI entries. The second
  ignores them without a warning.
- **C++ runtime state.** Both modules bundle the C++ core and export its symbols.
  - musl, the C library of the Alpine images, binds them to the copy loaded first. The module loaded
    second crashes with a segmentation fault.
  - glibc keeps each module's functions apart. It still binds the core's template statics, asio's
    thread-local call stacks among them, to the copy loaded first. With two asio versions, one
    module's code would then run on the other's objects.
- **Security fixes.** A fix in the newer release does not reach requests served by the older module.
  Its network and TLS code stays in the process.

## Building an image

[`docker/Dockerfile`](docker/Dockerfile) installs a release archive into an
[official PHP image](https://hub.docker.com/_/php). The archive must match the PHP version of the
image and its C library:

| Image | Archive |
|---|---|
| `php:8.4-cli`, `php:8.4-fpm`, `php:8.4-apache` | `couchbase-4.6.0-php8.4-nts-linux-x86_64.tgz` |
| `php:8.4-cli-alpine`, `php:8.4-fpm-alpine` | `couchbase-4.6.0-php8.4-nts-linux-musl-x86_64.tgz` |

The archives are attached to each
[release](https://github.com/couchbase/couchbase-php-client/releases). They are built for x86_64, so
on an arm64 host the image needs `--platform linux/amd64`. Put the Dockerfile and one archive in a
directory and build from it:

```console
$ curl -LO https://github.com/couchbase/couchbase-php-client/releases/download/4.6.0/couchbase-4.6.0-php8.4-nts-linux-x86_64.tgz
$ docker build --build-arg PHP_IMAGE=php:8.4-fpm -t app-php .
```

The build fails if the module does not load in the image. The application image then starts
`FROM app-php` and installs its Composer dependencies.

## Without containers

Run one php-fpm master per application. Each master gets its own `php.ini` (`php-fpm -c`) and
`PHP_INI_SCAN_DIR`. It then loads only the extension of its application's release.
