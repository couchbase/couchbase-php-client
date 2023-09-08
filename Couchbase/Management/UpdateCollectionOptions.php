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

namespace Couchbase\Management;

class UpdateCollectionOptions
{
    private ?int $timeoutMilliseconds;

    /**
     *  Static helper to keep code more readable
     *
     * @return UpdateCollectionOptions
     * @since 4.1.6
     */
    public static function build(): UpdateCollectionOptions
    {
        return new UpdateCollectionOptions();
    }

    /**
     * Sets the operation timeout in milliseconds
     *
     * @param int $milliseconds the operation timeout to apply
     *
     * @return UpdateCollectionOptions
     * @since 4.1.6
     */
    public function timeout(int $milliseconds): UpdateCollectionOptions
    {
        $this->timeoutMilliseconds = $milliseconds;
        return $this;
    }

    /**
     * @param UpdateCollectionOptions|null $options
     * @return array
     * @internal
     * @since 4.1.3
     */

    public static function export(?UpdateCollectionOptions $options): array
    {
        if ($options == null) {
            return [];
        }
        return [
            'timeoutMilliseconds' => $options->timeoutMilliseconds,
        ];
    }
}
