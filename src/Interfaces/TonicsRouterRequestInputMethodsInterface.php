<?php

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