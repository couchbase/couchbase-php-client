# Build from the repository root:
#   docker build -f performer/Dockerfile -t php-fit-performer .

ARG PHP_VERSION=8.3

FROM golang:1.22-bookworm AS proxy

RUN apt-get update && apt-get install -y --no-install-recommends protobuf-compiler libprotobuf-dev \
    && rm -rf /var/lib/apt/lists/*
# Pinned to match the grpc/protobuf runtimes in go.mod.
RUN go install google.golang.org/protobuf/cmd/protoc-gen-go@v1.28.1 \
    && go install google.golang.org/grpc/cmd/protoc-gen-go-grpc@v1.2.0

WORKDIR /src/performer/proxy
COPY performer/proxy/go.mod performer/proxy/go.sum ./
RUN go mod download

COPY performer/proto /src/performer/proto
COPY performer/proxy ./
RUN make proto && CGO_ENABLED=0 go build -o /php-performer .

FROM php:${PHP_VERSION}-cli-bookworm AS extension

RUN apt-get update && apt-get install -y --no-install-recommends $PHPIZE_DEPS cmake git \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /src
COPY config.m4 Makefile.frag ./
COPY src ./src
RUN phpize \
    && ./configure --enable-couchbase \
    && CMAKE_BUILD_PARALLEL_LEVEL="$(nproc)" make -j"$(nproc)" \
    && make install \
    && cp "$(php-config --extension-dir)/couchbase.so" /couchbase.so
RUN strip --strip-all /couchbase.so

FROM php:${PHP_VERSION}-cli-bookworm

ARG SDK=php

LABEL org.opencontainers.image.title="${SDK}-fit-performer" \
      org.opencontainers.image.description="Couchbase PHP SDK FIT performer (gRPC server)"

COPY --from=extension /couchbase.so /tmp/couchbase.so
RUN mv /tmp/couchbase.so "$(php-config --extension-dir)/" && docker-php-ext-enable couchbase

WORKDIR /app
COPY Couchbase ./Couchbase
COPY performer/backend ./performer/backend
COPY performer/run-native.sh ./performer/
RUN --mount=from=composer:2,source=/usr/bin/composer,target=/usr/bin/composer \
    cd performer/backend/src && composer install --no-interaction --no-progress --no-dev

COPY --from=proxy /php-performer /usr/local/bin/php-performer

ENV PROXY_BIN=/usr/local/bin/php-performer
EXPOSE 8060
ENTRYPOINT ["/app/performer/run-native.sh"]
