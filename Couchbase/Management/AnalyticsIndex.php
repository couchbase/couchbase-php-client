<?php

namespace Couchbase\Management;

class AnalyticsIndex
{
    private string $name;
    private string $datasetName;
    private string $dataverseName;
    private bool $isPrimary;

    /**
     * @param string $name
     * @param string $datasetName
     * @param string $dataverseName
     * @param bool $isPrimary
     */
    public function __construct(string $name, string $datasetName, string $dataverseName, bool $isPrimary)
    {
        $this->name = $name;
        $this->datasetName = $datasetName;
        $this->dataverseName = $dataverseName;
        $this->isPrimary = $isPrimary;
    }

    /**
     * Static helper to keep code more readable
     *
     * @param string $name
     * @param string $datasetName
     * @param string $dataverseName
     * @param bool $isPrimary
     *
     * @return AnalyticsIndex
     * @since 4.2.4
     */
    public static function build(string $name, string $datasetName, string $dataverseName, bool $isPrimary)
    {
        return new AnalyticsIndex($name, $datasetName, $dataverseName, $isPrimary);
    }

    /**
     * Gets the name of this index
     *
     * @return string
     * @since 4.2.4
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Gets the name of the analytics dataset in which this index exists
     *
     * @return string
     * @since 4.2.4
     */
    public function datasetName(): string
    {
        return $this->datasetName;
    }

    /**
     * Gets the name of the dataverse in which this index exists.
     *
     * @return string
     * @since 4.2.4
     */
    public function dataverseName(): string
    {
        return $this->dataverseName;
    }

    /**
     * Returns true if this index is a primary index.
     *
     * @return bool
     * @since 4.2.4
     */
    public function isPrimary(): bool
    {
        return $this->isPrimary;
    }

    /**
     * @internal
     */
    public static function import(array $data): AnalyticsIndex
    {
        return AnalyticsIndex::build(
            $data["name"] ?? "",
            $data["datasetName"] ?? "",
            $data["dataverseName"] ?? "",
            $data["isPrimary"] ?? false
        );
    }
}
