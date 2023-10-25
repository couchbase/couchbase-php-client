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

    public function getConnectionOptions(array $parsedConnString, array $exported): array
    {
        if (!isset($parsedConnString["query"])) {
            return $exported;
        }
        $arr = [];
        foreach (self::parseURIQuery($parsedConnString["query"]) as $key => $value) {
            if ($key == "kv_timeout" || $key == "key_value_timeout") {
                $arr["keyValueTimeout"] = intval($value);
            } elseif ($key == "kv_durable_timeout" || $key == "key_value_durable_timeout") {
                $arr["keyValueDurableTimeout"] = intval($value);
            } elseif ($key == "view_timeout") {
                $arr["viewTimeout"] = intval($value);
            } elseif ($key == "query_timeout") {
                $arr["queryTimeout"] = intval($value);
            } elseif ($key == "analytics_timeout") {
                $arr["analyticsTimeout"] = intval($value);
            } elseif ($key == "search_timeout") {
                $arr["searchTimeout"] = intval($value);
            } elseif ($key == "management_timeout") {
                $arr["managementTimeout"] = intval($value);
            } elseif ($key == "trust_certificate") {
                $arr["trustCertificate"] = $value;
            }
        }
        return array_merge($arr, array_filter($exported)); //Cluster options overwrite conn string options
    }

    public function getChannelCredentials(array $opts = [])
    {
        if (!isset($opts["trustCertificate"])) {
            return self::getChannelCredentialsInsecure();
        }
        return ['credentials' => ChannelCredentials::createSsl(file_get_contents($opts["trustCertificate"]))];
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
