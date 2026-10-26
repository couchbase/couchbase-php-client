<?php

/**
 * Copyright 2014-Present Couchbase, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

// Usage: php smoke.php LIBRARY_DIR
// Connects to CB_CONNSTR with the library in LIBRARY_DIR and round-trips one document.

declare(strict_types=1);

require_once $argv[1] . '/Couchbase/autoload.php';

use Couchbase\Cluster;
use Couchbase\ClusterOptions;
use Couchbase\Exception\DocumentNotFoundException;

$options = new ClusterOptions();
$options->credentials(getenv('CB_USERNAME') ?: 'Administrator', getenv('CB_PASSWORD') ?: 'password');
$cluster = Cluster::connect(getenv('CB_CONNSTR'), $options);
$collection = $cluster->bucket(getenv('CB_BUCKET') ?: 'default')->defaultCollection();

$key = 'smoke-' . uniqid();
$collection->upsert($key, ['php' => PHP_VERSION]);
if ($collection->get($key)->content() !== ['php' => PHP_VERSION]) {
    fwrite(STDERR, "get returned a different document\n");
    exit(1);
}
$collection->remove($key);
try {
    $collection->get($key);
    fwrite(STDERR, "get of a removed document succeeded\n");
    exit(1);
} catch (DocumentNotFoundException $e) {
}

printf("couchbase %s on PHP %s: ok\n", phpversion('couchbase'), PHP_VERSION);
