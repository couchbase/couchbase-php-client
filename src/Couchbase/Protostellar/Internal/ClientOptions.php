<?php

/*
 * Copyright 2022-Present Couchbase, Inc.
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

namespace Couchbase\Protostellar\Internal;

use Couchbase\ClusterOptions;
use Couchbase\Exception\InvalidArgumentException;

class ClientOptions
{
    /**
     * @throws InvalidArgumentException
     */
    public function channelOptions(ClusterOptions $options): array
    {
        $exported = $options->export();
        $creds = $this->getCredentials($exported['authenticator']);
        return [
            'update_metadata' => function ($metaData) use ($creds) {
                $metaData['authorization'] = [$creds];
                return $metaData;
            }
        ];
    }

    /**
     * @throws InvalidArgumentException
     */
    private function getCredentials(array $authenticator): string
    {
        if ($authenticator['type'] == 'password') {
            return "Basic " . base64_encode($authenticator['username'] . ":" . $authenticator['password']);
        } else {
            throw new InvalidArgumentException("Unsupported type of the authenticator: " . $authenticator['type']);
        }
    }
}
