<?php

namespace Devsrealm\TonicsRouterSystem\Interfaces;

interface TonicsRouterCacheInterface
{

    /**
     * Cache a new variable in the data store or overwrite data if key already exist
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function add(string $key, mixed $value): bool;

    /**
     * Get a stored variable from the cache store
     * @param string $key
     * @return mixed
     */
    public function get(string $key): mixed;

    /**
     * Removes a stored variable from the cache
     * @param string $key
     * @return mixed
     */
    public function delete(string $key): mixed;

    /**
     * Checks if cache key exists
     * @param string $key
     * @return bool
     */
    public function exists(string $key): bool;

    /**
     * Clear all entry in cache store
     * @return bool
     */
    public function clear(): mixed;
}