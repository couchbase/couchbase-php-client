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

namespace Helpers;

include_once __DIR__ . "/Caves.php";
include_once __DIR__ . "/ServerVersion.php";

use Couchbase\PasswordAuthenticator;
use Exception;
use RuntimeException;

class TestEnvironment
{
    private string $clusterId;
    private ?string $connectionString;
    private ?Caves $caves = null;
    private string $username;
    private string $password;
    private string $bucketName;
    private ServerVersion $version;

    private static function checkExtension(string $name)
    {
        if (!extension_loaded($name)) {
            $extension = "couchbase";
            $moduleDirectory = realpath(__DIR__ . '/../../modules');
            if ($moduleDirectory) {
                $modulePath = $moduleDirectory . "/" . $extension . "." . PHP_SHLIB_SUFFIX;
                if (file_exists($modulePath)) {
                    $extension = $modulePath;
                }
            }
            throw new RuntimeException(
                sprintf(
                    "extension '%s' is not loaded. Check your INI file, or add '-d extension=%s' to the interpreter arguments",
                    $name,
                    $extension
                )
            );
        }
    }

    public function __construct()
    {
        self::checkExtension("json");
        self::checkExtension("couchbase");

        $this->clusterId = self::randomId();

        $this->connectionString = getenv("TEST_CONNECTION_STRING") ?: null;
        if ($this->connectionString == null) {
            $this->caves = new Caves();
        }
        $this->username = getenv("TEST_USERNAME") ?: "Administrator";
        $this->password = getenv("TEST_PASSWORD") ?: "password";
        $this->bucketName = getenv("TEST_BUCKET") ?: "default";
    }

    public function bucketName(): string
    {
        return $this->bucketName;
    }

    public function useCaves(): bool
    {
        return $this->caves != null;
    }

    public function useCouchbase(): bool
    {
        return !$this->useCaves();
    }

    public function useProtostellar(): bool
    {
        if ($this->useCaves()) {
            return false;
        }
        if (
            preg_match("/^protostellar:\/\//", $this->connectionString) ||
            preg_match("/^couchbase2:\/\//", $this->connectionString)
        ) {
            return true;
        }
        return false;
    }

    public function start()
    {
        if ($this->useCaves()) {
            $this->caves->start();
        }
    }

    public function stop()
    {
        if ($this->useCaves()) {
            $this->caves->stop();
            $this->connectionString = null;
        }
    }

    public function connectionString(): string
    {
        if ($this->useCaves() && $this->connectionString == null) {
            $this->connectionString = $this->caves->createCluster($this->clusterId);
        }
        return $this->connectionString;
    }

    public function version(): ?ServerVersion
    {
        if (isset($this->version)) {
            return $this->version;
        }
        return null;
    }

    public function setVersion(string $versionString): ServerVersion
    {
        if (isset($this->version)) {
            return $this->version;
        }

        $this->version = ServerVersion::parse($versionString);
        return $this->version;
    }

    public function buildPasswordAuthenticator(): PasswordAuthenticator
    {
        return new PasswordAuthenticator($this->username, $this->password);
    }

    public static function randomId(): string
    {
        try {
            return bin2hex(random_bytes(16));
        } catch (Exception $e) {
            return sprintf("%s_%s", time(), rand());
        }
    }
}
