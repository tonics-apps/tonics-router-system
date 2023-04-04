<?php
/*
 * Copyright 2023 Ahmed Olayemi F. <olayemi@tonics.app or devsrealmer@gmail.com>
 *    Licensed under the Apache License, Version 2.0 (the "License");
 *    you may not use this file except in compliance with the License.
 *    You may obtain a copy of the License at
 *
 *        http://www.apache.org/licenses/LICENSE-2.0
 *
 *    Unless required by applicable law or agreed to in writing, software
 *    distributed under the License is distributed on an "AS IS" BASIS,
 *    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *    See the License for the specific language governing permissions and
 *    limitations under the License.
 */

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