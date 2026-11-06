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

/**
 * The built-in strategies a key/value operation can use to retry a request, for example one on a
 * locked document. Not-my-vbucket and outdated-collection responses are retried under either.
 *
 * @since 4.6.0
 */
interface RetryStrategy
{
    /**
     * Retry with backoff until the operation times out. Requests without a strategy use this one.
     */
    public const BEST_EFFORT = "bestEffort";

    /**
     * Fail on the first retryable response. A mutation on a locked document throws
     * DocumentLockedException at once. Any other declined retry throws the exception the response
     * maps to over couchbase://, such as TemporaryFailureException, and RequestCanceledException over
     * couchbase2://.
     */
    public const FAIL_FAST = "failFast";
}
