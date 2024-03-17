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

class ServerVersion
{
    public const EDITION_ENTERPRISE = "enterprise";
    public const EDITION_COMMUNITY = "community";

    private int $major;
    private int $minor;
    private int $micro;
    private int $build;
    private string $edition;
    private bool $developerPreview;

    public function __construct(
        int $major,
        int $minor,
        int $micro = 0,
        int $build = 0,
        string $edition = self::EDITION_ENTERPRISE,
        bool $developerPreview = false
    )
    {
        $this->major = $major;
        $this->minor = $minor;
        $this->micro = $micro;
        $this->build = $build;
        $this->edition = $edition;
        $this->developerPreview = $developerPreview;
    }

    public static function parse(string $versionString): ServerVersion
    {
        if (preg_match("/(\d+)\.(\d+)(\.(\d+)(-(\d+))?(-(.+))?)?/", $versionString, $match)) {
            $numberOfCaptures = count($match) - 1;
            $major = intval($match[1]);
            $minor = intval($match[2]);
            $micro = 0;
            if ($numberOfCaptures > 3) {
                $micro = intval($match[4]);
            }
            $build = 0;
            if ($numberOfCaptures > 5) {
                $build = intval($match[6]);
            }
            $edition = self::EDITION_ENTERPRISE;
            if ($numberOfCaptures > 7) {
                $edition = $match[8];
            }
            return new ServerVersion($major, $minor, $micro, $build, $edition);
        }
        return new ServerVersion(7, 0);
    }

    public function __toString()
    {
        return sprintf(
            "%d.%d.%d-%d-%s%s",
            $this->major,
            $this->minor,
            $this->micro,
            $this->build,
            $this->edition,
            $this->developerPreview ? " (developer preview)" : ""
        );
    }

    public function isCommunity(): bool
    {
        return $this->edition == self::EDITION_COMMUNITY;
    }

    public function isEnterprise(): bool
    {
        return $this->edition == self::EDITION_ENTERPRISE;
    }

    public function isAlice(): bool
    {
        // [6.0.0, 6.5.0)
        return $this->major == 6 && $this->minor < 5;
    }

    public function isMadHatter(): bool
    {
        // [6.5.0, 7.0.0)
        return $this->major == 6 && $this->minor >= 5;
    }

    public function is66(): bool
    {
        return $this->major == 6 && $this->minor == 6;
    }

    public function is72(): bool
    {
        return $this->major == 7 && $this->minor == 2;
    }

    public function is75(): bool
    {
        return $this->major == 7 && $this->minor == 5;
    }

    public function is76(): bool
    {
        return $this->major == 7 && $this->minor == 6;
    }

    public function is80(): bool
    {
        return $this->major == 8 && $this->minor = 0;
    }

    public function isCheshireCat(): bool
    {
        // [7.0.0, 7.1.0)
        return $this->major == 7 && $this->minor < 1;
    }

    public function isNeo(): bool
    {
        // [7.1.0, inf)
        return ($this->major == 7 && $this->minor >= 1) || $this->major > 7;
    }

    public function supportsGcccp(): bool
    {
        return $this->isMadHatter() || $this->isCheshireCat() || $this->isNeo();
    }

    public function supportsSyncReplication(): bool
    {
        return $this->isMadHatter() || $this->isCheshireCat() || $this->isNeo();
    }

    public function supportsEnhancedDurability(): bool
    {
        return $this->isMadHatter() || $this->isCheshireCat() || $this->isNeo();
    }

    public function supportsScopedQueries(): bool
    {
        return $this->isCheshireCat() || $this->isNeo();
    }

    public function supportsCollections(): bool
    {
        return ($this->isMadHatter() && $this->isDeveloperPreview()) || $this->isCheshireCat() || $this->isNeo();
    }

    public function supportsStorageBackend(): bool
    {
        return $this->isNeo();
    }

    public function supportsPreserveExpiry(): bool
    {
        return $this->isCheshireCat() || $this->isNeo();
    }

    public function supportsPreserveExpiryForQuery(): bool
    {
        return $this->isNeo();
    }

    public function supportsUserGroups(): bool
    {
        return $this->isMadHatter() || $this->isCheshireCat() || $this->isNeo();
    }

    public function supportsQueryIndexManagement(): bool
    {
        return $this->isMadHatter() || $this->isCheshireCat() || $this->isNeo();
    }

    public function supportsAnalytics(): bool
    {
        return $this->isAlice() || $this->isMadHatter() || $this->isCheshireCat() || $this->isNeo();
    }

    public function supportsAnalyticsPendingMutations(): bool
    {
        return $this->isMadHatter() || $this->isCheshireCat() || $this->isNeo();
    }

    public function supportsAnalyticsLinkAzureBlob(): bool
    {
        return $this->isCheshireCat() && $this->isDeveloperPreview();
    }

    public function supportsSearchAnalyze(): bool
    {
        return $this->isMadHatter() || $this->isCheshireCat() || $this->isNeo();
    }

    public function supportsAnalyticsLinksCertAuth(): bool
    {
        return $this->isNeo();
    }

    public function supportsEventingFunctions(): bool
    {
        return $this->isCheshireCat() || $this->isNeo();
    }

    public function supportsAnalyticsLinks(): bool
    {
        return $this->is66() || $this->isCheshireCat() || $this->isNeo();
    }

    public function supportsMinimumDurabilityLevel(): bool
    {
        return $this->is66() || $this->isCheshireCat() || $this->isNeo();
    }

    public function supportsCustomConflictResolutionType(): bool
    {
        return $this->isNeo() && $this->isDeveloperPreview();
    }

    public function supportsMagmaStorageBackend(): bool
    {
        return $this->isNeo();
    }

    public function supportsTransactions(): bool
    {
        return $this->is66() || $this->isCheshireCat() || $this->isNeo();
    }

    public function supportsSubdocReadReplica(): bool
    {
        return $this->is75() || $this->is76() || $this->is80();
    }

    public function supportsTransactionsQueries(): bool
    {
        return $this->isCheshireCat() || $this->isNeo();
    }

    public function supportsRangeScan(): bool
    {
        return $this->is75() || $this->is76();
    }

    public function supportsBucketDedup(): bool
    {
        return ($this->major == 7 && $this->minor >= 2) || $this->major > 7;
    }

    public function supportsUpdateCollectionMaxExpiry(): bool
    {
        return ($this->major == 7 && $this->minor >= 5) || $this->major > 7;
    }

    public function supportsDocNotLockedException(): bool
    {
        return ($this->major == 7 && $this->minor >= 6) || $this->major > 7;
    }

    public function supportsCollectionMaxTTLNoExpiry(): bool
    {
        return ($this->major == 7 && $this->minor >= 6) || $this->major > 7;
    }

    public function supportsScopeSearchIndexes(): bool
    {
        return ($this->major == 7 && $this->minor >= 6) || $this->major > 7;
    }

    public function supportsVectorSearch(): bool
    {
        return ($this->major == 7 && $this->minor >= 6) || $this->major > 7;
    }

    /**
     * @return int
     */
    public function major(): int
    {
        return $this->major;
    }

    /**
     * @return int
     */
    public function minor(): int
    {
        return $this->minor;
    }

    /**
     * @return int
     */
    public function micro(): int
    {
        return $this->micro;
    }

    /**
     * @return int
     */
    public function build(): int
    {
        return $this->build;
    }

    /**
     * @return string
     */
    public function edition(): string
    {
        return $this->edition;
    }

    /**
     * @return bool
     */
    public function isDeveloperPreview(): bool
    {
        return $this->developerPreview;
    }

    /**
     * @param bool $enabled
     *
     * @return void
     */
    public function setDeveloperPreview(bool $enabled)
    {
        $this->developerPreview = $enabled;
    }
}
