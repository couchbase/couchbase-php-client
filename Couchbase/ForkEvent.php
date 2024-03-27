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
 * ForkEvent defines types of events, that can happen when forking the process.
 *
 * @see \Couchbase\Cluster::notifyFork()
 * @since 4.2.1
 */
interface ForkEvent
{
    /**
     * Prepare the library for fork() call. This event should be used in the parent process before
     * invoking `pcntl_fork()`. Once \Couchbase\Cluster::notifyFork() the library reaches the safe
     * state when it is ready for fork() syscall (i.e. no background threads running, all operations
     * completed, etc.)
     */
    public const PREPARE = "prepare";

    /**
     * Resume progress of the child process. This usually gives the library the chance to open new
     * connections, and restart IO threads.
     */
    public const CHILD = "child";

    /**
     * Resume progress of the parent process. Typically parent process could continue using all
     * descriptors that were open before fork process, and also the library will restart background
     * IO threads.
     */
    public const PARENT = "parent";
}
