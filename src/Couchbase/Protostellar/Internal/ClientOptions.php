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
use Grpc\ChannelCredentials;

class ClientOptions
{
    /**
     * @throws InvalidArgumentException
     */
    public function channelOptions(array $exported): array
    {
        $creds = $this->getCredentials($exported['authenticator']);
        return [
            'update_metadata' => function ($metaData) use ($creds) {
                $metaData['authorization'] = [$creds];
                return $metaData;
            },
        ];
    }

    public function getChannelCredentials(array $parsedConnString, array $exported): array
    {
        $certPath = "";
        if (isset($parsedConnString["query"])) {
            $queryArr = self::parseURIQuery($parsedConnString["query"]);
            $certPath = $queryArr["certpath"] ?? $certPath;
        }
        $certPath = $exported["trustCertificate"] ?? $certPath; //Overwriting certificate path if set in ClusterOptions
        return empty($certPath) ? self::getChannelCredentialsInsecure() : ['credentials' => ChannelCredentials::createSsl(file_get_contents($certPath))];
    }

    public static function getChannelCredentialsInsecure(): array
    {
        return [
            'credentials' => ChannelCredentials::createInsecure(),
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

    private static function parseURIQuery(string $str): array
    {
        $arr = [];
        $pairs = explode('&', $str);
        foreach ($pairs as $i) {
            list($name,$value) = explode('=', $i, 2);
            if (isset($arr[$name])) {
                if (is_array($arr[$name])) {
                    $arr[$name][] = $value;
                } else {
                    $arr[$name] = array($arr[$name], $value);
                }
            } else {
                $arr[$name] = $value;
            }
        }
        return $arr;
    }
}
