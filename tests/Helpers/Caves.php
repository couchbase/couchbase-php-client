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

use UnexpectedValueException;

class Caves
{
    private ?string $logPrefix = null;
    private $cavesProcess = null;
    private $cavesSocket = null;
    private $controlSocket = null;
    private $controlPort = 0;

    public function start()
    {
        $this->logPrefix = sprintf("%d-%d", time(), $this->controlPort());
        $started = false;
        $env = [];
        while (!$started) {
            $proc = proc_open(
                [
                    $this->executablePath(),
                    sprintf("--control-port=%d", $this->controlPort()),
                ],
                [
                    1 => ["file", sprintf("%s/%s.out.txt", $this->logsDirectory(), $this->logPrefix), "a"],
                    2 => ["file", sprintf("%s/%s.err.txt", $this->logsDirectory(), $this->logPrefix), "a"],
                ],
                $pipes,
                $this->buildDirectory(),
                null,
                ['suppress_errors' => true]
            );
            if (is_resource($proc)) {
                $started = true;
                $this->cavesProcess = $proc;
            } else {
                fprintf(STDERR, "--- %s, unable to start the process\n", $this->executablePath());
            }
        }
        $this->cavesSocket = socket_accept($this->controlSocket);
        if (getenv("CAVES_VERBOSE")) {
            printf(
                "--- %s, control_port: %d, logs: %s\n",
                $this->executablePath(),
                $this->controlPort(),
                sprintf("%s/%s.{out,err}.txt", $this->logsDirectory(), $this->logPrefix)
            );
        }
        $helloCommand = $this->readCommand();
        if ($helloCommand["type"] != "hello") {
            throw new UnexpectedValueException("CAVES didn't greet us, something happened: " . var_export($helloCommand));
        }
    }

    public function stop()
    {
        if ($this->cavesProcess != null) {
            proc_terminate($this->cavesProcess, 9);
            $this->cavesProcess = null;
        }
    }

    /**
     * @param string $clusterId
     *
     * @return string connection string
     */
    public function createCluster(string $clusterId): string
    {
        $resp = $this->roundTripCommand(["type" => "createcluster", "id" => $clusterId]);
        return $resp["connstr"];
    }

    /**
     * @param string $clusterId
     * @param int $durationMilliseconds
     *
     * @return void
     */
    public function timeTravelCluster(string $clusterId, int $durationMilliseconds)
    {
        $this->roundTripCommand(["type" => "timetravel", "cluster" => $clusterId, "amount_ms" => $durationMilliseconds]);
    }

    private function executablePath(): string
    {
        return $this->buildDirectory() . "/gocaves";
    }

    private function projectDirectory(): string
    {
        return realpath(__DIR__ . "/../..");
    }

    private function buildDirectory(): string
    {
        return $this->projectDirectory() . "/build";
    }

    private function logsDirectory(): string
    {
        return $this->projectDirectory() . "/logs";
    }

    private function controlPort(): int
    {
        if ($this->controlSocket == null) {
            $this->openControlSocket();
        }
        return $this->controlPort;
    }

    private function openControlSocket()
    {
        $this->controlSocket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_bind($this->controlSocket, "127.0.0.1");
        socket_listen($this->controlSocket);
        socket_getsockname($this->controlSocket, $address, $this->controlPort);
        fprintf(STDERR, "address=%s, port=%d\n", $address, $this->controlPort);
    }

    private function roundTripCommand($cmd)
    {
        $this->writeCommand($cmd);
        return $this->readCommand();
    }

    private function writeCommand($cmd)
    {
        socket_write($this->cavesSocket, json_encode($cmd) . "\0");
    }

    private function readCommand()
    {
        $response = "";
        do {
            $byte = socket_read($this->cavesSocket, 1);
            if ($byte === "\0") {
                break;
            }
            $response .= $byte;
        } while (true);
        return json_decode(trim($response), true);
    }
}
