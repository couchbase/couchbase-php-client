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

class Origin
{
    private string $type;
    private string $name;

    /**
     * Gets the type of the origin.
     *
     * @return string
     * @since 4.0.0
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * Gets the name of the origin.
     *
     * @return string
     * @since 4.0.0
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @internal
     * @since 4.0.0
     */
    public static function import(array $origin): Origin
    {
        $settings = new Origin();
        if (array_key_exists('name', $origin)) {
            $settings->name = $origin['name'];
        }
        $settings->type = $origin['type'];

        return $settings;
    }
}
