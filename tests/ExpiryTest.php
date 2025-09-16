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

use Couchbase\Utilities\ExpiryHelper;
use Couchbase\Exception\InvalidArgumentException;

include_once __DIR__ . "/Helpers/CouchbaseTestCase.php";

class ExpiryTest extends Helpers\CouchbaseTestCase
{
    public function testNullExpiryReturnsZero()
    {
        $this->assertEquals(0, ExpiryHelper::parseExpiry(null));
    }

    public function testZeroExpiryReturnsZero()
    {
        $this->assertEquals(0, ExpiryHelper::parseExpiry(0));
        $this->assertEquals(0, ExpiryHelper::parseExpiry('0'));
    }

    public function testNegativeExpiryThrows()
    {
        $this->expectException(InvalidArgumentException::class);
        ExpiryHelper::parseExpiry(-1);
    }

    public function testExpiryGreaterThanMaxThrows()
    {
        $this->expectException(InvalidArgumentException::class);
        ExpiryHelper::parseExpiry(4294967296); // MAX_EXPIRY + 1
    }

    public function testRelativeExpiryUnderThirtyDays()
    {
        $expiry = 60; // 1 minute
        $result = ExpiryHelper::parseExpiry($expiry);
        $this->assertEquals($expiry, $result);
    }

    public function testRelativeExpiryOverThirtyDaysIsConverted()
    {
        $expiry = 2592000 + 1; // 30 days + 1 second
        $before = time();
        $result = ExpiryHelper::parseExpiry($expiry);
        $after = time();
        $this->assertGreaterThanOrEqual($before + $expiry, $result);
        $this->assertLessThanOrEqual($after + $expiry, $result);
    }

    public function testAbsoluteDateWithinRangeReturnsTimestamp()
    {
        $dt = new DateTimeImmutable('2025-01-01T00:00:00Z');
        $result = ExpiryHelper::parseExpiry($dt);
        $this->assertEquals($dt->getTimestamp(), $result);
    }

    public function testAbsoluteDateBelowMinThrows()
    {
        $dt = new DateTimeImmutable('1969-12-31T23:59:59Z');
        $this->expectException(InvalidArgumentException::class);
        ExpiryHelper::parseExpiry($dt);
    }

    public function testAbsoluteDateAboveMaxThrows()
    {
        $dt = new DateTimeImmutable('2200-01-01T00:00:00Z');
        $this->expectException(InvalidArgumentException::class);
        ExpiryHelper::parseExpiry($dt);
    }

    public function testZeroSecondDateReturnsZero()
    {
        $dt = new DateTimeImmutable('1970-01-31T00:00:00Z');
        $this->assertEquals(0, ExpiryHelper::parseExpiry($dt));
    }

    public function testInvalidTypeThrows()
    {
        $this->expectException(InvalidArgumentException::class);
        ExpiryHelper::parseExpiry('foo');
    }
}
