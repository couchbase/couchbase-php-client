<?php

declare(strict_types=1);

/*
 *   Copyright 2020-2021 Couchbase, Inc.
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

namespace Couchbase\Datastructures\Options;

use Couchbase\GetOptions;
use Couchbase\LookupInOptions;
use Couchbase\MutateInOptions;
use Couchbase\RemoveOptions;

/**
 * Aggregate of the options for {@link \Couchbase\Datastructures\CouchbaseList}
 */
class CouchbaseMap
{
    private GetOptions $getOptions;
    private RemoveOptions $removeOptions;
    private LookupInOptions $lookupInOptions;
    private MutateInOptions $mutateInOptions;

    /**
     * CouchbaseMap constructor.
     *
     * @param GetOptions|null $get options for get operations
     * @param RemoveOptions|null $remove options for get remove operations
     * @param LookupInOptions|null $lookupIn options for lookupIn operations
     * @param MutateInOptions|null $mutateIn options for mutateIn operations
     */
    public function __construct(?GetOptions $get, ?RemoveOptions $remove, ?LookupInOptions $lookupIn, ?MutateInOptions $mutateIn)
    {
        $this->getOptions = $get ?: new GetOptions();
        $this->removeOptions = $remove ?: new RemoveOptions();
        $this->lookupInOptions = $lookupIn ?: new LookupInOptions();
        $this->mutateInOptions = $mutateIn ?: new MutateInOptions();
    }

    public function getOptions(): GetOptions
    {
        return $this->getOptions;
    }

    public function removeOptions(): RemoveOptions
    {
        return $this->removeOptions;
    }

    public function lookupInOptions(): LookupInOptions
    {
        return $this->lookupInOptions;
    }

    public function mutateInOptions(): MutateInOptions
    {
        return $this->mutateInOptions;
    }
}
