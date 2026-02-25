<?php

namespace Couchbase\Utilities;

use Couchbase\Exception\InvalidArgumentException;
use DateTimeImmutable;
use DateTimeInterface;

class ExpiryHelper
{
    private const THIRTY_DAYS_IN_SECONDS = 2592000;
    private const FIFTY_YEARS_IN_SECONDS = 1576800000;
    private const MAX_EXPIRY = 4294967295;

    /**
     * @throws InvalidArgumentException
     */
    public static function parseExpiry($expiry): int
    {
        if ($expiry === null || $expiry === 0 || $expiry === '0') {
            return 0;
        }
        if (!is_int($expiry) && !($expiry instanceof DateTimeInterface)) {
            throw new InvalidArgumentException(
                "Expected expiry to be an int or DateTimeInterface."
            );
        }

        if ($expiry instanceof DateTimeInterface) {
            $timestamp = $expiry->getTimestamp();

            if ($timestamp === self::zeroSecondDate()->getTimestamp()) {
                return 0;
            }

            if (
                $timestamp < self::minExpiryDate()->getTimestamp() ||
                $timestamp > self::maxExpiryDate()->getTimestamp()
            ) {
                throw new InvalidArgumentException(
                    "Expiry date is out of range. Must be between " .
                    self::minExpiryDate()->format(DateTimeInterface::ATOM) . " and " .
                    self::maxExpiryDate()->format(DateTimeInterface::ATOM) . " But got " .
                    $expiry->format(DateTimeInterface::ATOM)
                );
            }
            return $timestamp;
        }

        if ($expiry < 0) {
            throw new InvalidArgumentException("Expiry cannot be negative, got $expiry");
        }

        if ($expiry > self::MAX_EXPIRY) {
            throw new InvalidArgumentException("Expiry cannot be greater than " . self::MAX_EXPIRY . ", got $expiry");
        }

        if ($expiry > self::FIFTY_YEARS_IN_SECONDS) {
            trigger_error(
                sprintf(
                    "The specified expiry (%d) is greater than 50 years in seconds. "
                    . "Unix timestamps passed directly as a number are not supported. "
                    . "If you want an absolute expiry, construct a DateTime from the timestamp.",
                    $expiry
                ),
                E_USER_WARNING
            );
        }

        if ($expiry < self::THIRTY_DAYS_IN_SECONDS) {
            return $expiry;
        }

        // Relative expiry >= 30 days, convert to absolute expiry
        $unixTimeSecs = time();
        $maxExpiryDuration = self::MAX_EXPIRY - $unixTimeSecs;
        if ($expiry > $maxExpiryDuration) {
            throw new InvalidArgumentException(
                "Expected expiry duration to be less than " . $maxExpiryDuration .
                " but got $expiry"
            );
        }
        return $expiry + $unixTimeSecs;
    }

    // The server treats values <= 259200 (30 days) as relative to the current time.
    // So, the minimum expiry date is 259201 which corresponds to 1970-01-31T00:00:01Z
    private static function minExpiryDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('1970-01-31T00:00:01Z');
    }

    private static function maxExpiryDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2106-02-07T06:28:15Z');
    }

    private static function zeroSecondDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('1970-01-31T00:00:00Z');
    }
}
