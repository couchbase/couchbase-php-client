# Installs a prebuilt Couchbase PHP SDK extension into an official PHP image.
#
# The build context holds exactly one x86_64 release archive. It must match the PHP version and the
# C library of the image:
#
#   Debian images:  couchbase-X.Y.Z-phpA.B-nts-linux-x86_64.tgz
#   -alpine images: couchbase-X.Y.Z-phpA.B-nts-linux-musl-x86_64.tgz
#
#   docker build --build-arg PHP_IMAGE=php:8.4-fpm -t app-php .

ARG PHP_IMAGE=php:8.4-cli
FROM ${PHP_IMAGE}

COPY couchbase-*.tgz /tmp/
RUN set -eux; \
    if command -v apk > /dev/null; then apk add --no-cache libgcc libstdc++; fi; \
    tar -xzf /tmp/couchbase-*.tgz -C /tmp; \
    cp /tmp/couchbase-*/couchbase.so "$(php-config --extension-dir)/"; \
    cp /tmp/couchbase-*/90-couchbase.ini "$PHP_INI_DIR/conf.d/"; \
    rm -rf /tmp/couchbase-*; \
    php -m | grep -qx couchbase
