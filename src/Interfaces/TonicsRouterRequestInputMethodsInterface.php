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

interface TonicsRouterRequestInputMethodsInterface
{
    /**
     * @return array
     */
    public function all(): array;

    /**
     * Check if a $key is present,
     * use hasValue to check if the key is present and value is not empty
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;


    /**
     * Check if $key is present and has value
     * @param string $key
     * @return bool
     */
    public function hasValue(string $key): bool;

    /**
     * @param string $key
     * @param null $default
     * Return default if $key value is empty, note if key doesn't exist, you should get an empty string.
     * Use the has() method to check if a key exist
     * @return mixed
     */
    public function retrieve(string $key, $default = null): mixed;
}