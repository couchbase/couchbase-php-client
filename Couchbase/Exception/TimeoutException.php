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

namespace Couchbase\Exception;

/**
 * The {Timeout} signals that an operation timed out before it could be completed.
 *
 * It is important to understand that the timeout itself is always just the effect an underlying cause, never the
 * issue itself. The root cause might not even be on the application side, also the network and server need to be
 * taken into account.
 *
 * Right now the SDK can throw two different implementations of this class:
 *
 * {AmbiguousTimeout}::
 *   The operation might have caused a side effect on the server and should not be retried without
 *   actions and checks.
 *
 * {UnambiguousTimeout}::
 *   The operation has not caused a side effect on the server and is safe to retry. This is always the case for
 *   idempotent operations. For non-idempotent operations it depends on the state the operation was in at the time of
 *   cancellation.
 */
class TimeoutException extends CouchbaseException
{
}
