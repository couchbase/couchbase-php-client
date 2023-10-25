<?php

namespace Couchbase\Management;

interface CollectionManagerInterface
{
    public function getAllScopes(GetAllScopesOptions $options = null): array;

    public function createScope(string $name, CreateScopeOptions $options = null);

    public function dropScope(string $name, DropScopeOptions $options = null);

    public function createCollection($scopeName, $collectionName = null, $settings = null, $options = null);

    public function dropCollection($scopeName, $collectionName = null, $options = null);

    public function updateCollection(string $scopeName, string $collectionName, UpdateCollectionSettings $settings, UpdateCollectionOptions $options = null);
}
