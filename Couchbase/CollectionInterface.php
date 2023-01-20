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

declare(strict_types=1);

namespace Couchbase;

interface CollectionInterface
{
    public function bucketName(): string;

    public function scopeName(): string;

    public function name(): string;

    public function get(string $id, GetOptions $options = null): GetResult;

    public function exists(string $id, ExistsOptions $options = null): ExistsResult;

    public function getAndLock(string $id, int $lockTimeSeconds, GetAndLockOptions $options = null): GetResult;

    public function getAndTouch(string $id, $expiry, GetAndTouchOptions $options = null): GetResult;

    public function getAnyReplica(string $id, GetAnyReplicaOptions $options = null): GetReplicaResult;

    public function getAllReplicas(string $id, GetAllReplicasOptions $options = null): array;

    public function upsert(string $id, $value, UpsertOptions $options = null): MutationResult;

    public function insert(string $id, $value, InsertOptions $options = null): MutationResult;

    public function replace(string $id, $value, ReplaceOptions $options = null): MutationResult;

    public function remove(string $id, RemoveOptions $options = null): MutationResult;

    public function unlock(string $id, string $cas, UnlockOptions $options = null): Result;

    public function touch(string $id, $expiry, TouchOptions $options = null): MutationResult;

    public function lookupIn(string $id, array $specs, LookupInOptions $options = null): LookupInResult;

    public function mutateIn(string $id, array $specs, MutateInOptions $options = null): MutateInResult;

    public function binary(): BinaryCollectionInterface;
}
