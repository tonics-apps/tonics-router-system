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

namespace Devsrealm\TonicsRouterSystem;

use Devsrealm\TonicsRouterSystem\Interfaces\TonicsRouterRequestInputInterface;
use Devsrealm\TonicsRouterSystem\Interfaces\TonicsRouterRequestInputMethodsInterface;

class RequestInput implements TonicsRouterRequestInputInterface, TonicsRouterRequestInputMethodsInterface
{


    private ?array $currentHttpGlobalVariable = null;

    /**
     * @return array
     */
    protected function getCurrentHttpGlobalVariable(): array
    {
        return $this->currentHttpGlobalVariable;
    }

    /**
     * @param mixed $currentHttpGlobalVariable
     */
    protected function setCurrentHttpGlobalVariable(array|\stdClass $currentHttpGlobalVariable): void
    {
        $this->currentHttpGlobalVariable = (array)$currentHttpGlobalVariable;
    }

    /**
     * @param $data
     *
     * @return TonicsRouterRequestInputMethodsInterface
     */
    public function fromPost($data = []): TonicsRouterRequestInputMethodsInterface
    {
        if (empty($data)){
            $data = $_POST;
        }
        $this->setCurrentHttpGlobalVariable($data);
        return clone $this;
    }

    /**
     * @param array $data
     *
     * @return TonicsRouterRequestInputMethodsInterface
     */
    public function fromGet(array $data = []): TonicsRouterRequestInputMethodsInterface
    {
        if (empty($data)){
            $data = $_GET;
        }
        $this->setCurrentHttpGlobalVariable($data);
        return clone $this;
    }

    /**
     * @param $data
     *
     * @return TonicsRouterRequestInputMethodsInterface
     */
    public function fromFile($data = []): TonicsRouterRequestInputMethodsInterface
    {
        if (empty($data)){
            $data = $_FILES;
        }
        $this->setCurrentHttpGlobalVariable($data);
        return clone $this;
    }

    /**
     * @param $data
     *
     * @return TonicsRouterRequestInputMethodsInterface
     */
    public function fromServer($data = []): TonicsRouterRequestInputMethodsInterface
    {
        if (empty($data)){
            $data = $_SERVER;
        }
        $this->setCurrentHttpGlobalVariable($data);
        return clone $this;
    }

    /**
     * @param $data
     *
     * @return TonicsRouterRequestInputMethodsInterface
     */
    public function fromCookie($data = []): TonicsRouterRequestInputMethodsInterface
    {
        if (empty($data)){
            $data = $_COOKIE;
        }
        $this->setCurrentHttpGlobalVariable($data);
        return clone $this;
    }

    /**
     * @throws \Exception
     */
    protected function checkHttpGlobalVariableIsNotNull(): void
    {
        if ($this->getCurrentHttpGlobalVariable() === null) {
            throw new \Exception("No http global variable set");
        }
    }

    /**
     * @throws \Exception
     */
    public function all(): array
    {
        $this->checkHttpGlobalVariableIsNotNull();
        return $this->getCurrentHttpGlobalVariable();
    }


    /**
     * @throws \Exception
     */
    public function has(string $key): bool
    {
        $this->checkHttpGlobalVariableIsNotNull();
        $httpVar = $this->getCurrentHttpGlobalVariable();

        $splitName = explode('.', $key); $result = false;
        foreach ($splitName as $value) {
            if ( is_array($httpVar) && key_exists($value, $httpVar)) {
                $result = true;
                $httpVar = $httpVar[$value];
            } else {
                $result = false;
            }
        }
        return $result;
    }


    /**
     * @throws \Exception
     */
    public function hasValue(string $key): bool
    {
        $this->checkHttpGlobalVariableIsNotNull();
        $httpVar = $this->getCurrentHttpGlobalVariable();
        $result = $this->getKeyHttpVar($key, $httpVar);

        if(empty($result)){
            return false;
        }

        return true;
    }


    /**
     * @param string $key
     * You can alo use dot-notation to access nested data type, e.g
     * data.nested.nested2
     * @param null $default
     * Returns default if $key value is empty
     * @return mixed
     * @throws \Exception
     */
    public function retrieve(string $key, $default = null): mixed
    {
        $this->checkHttpGlobalVariableIsNotNull();
        $httpVar = $this->getCurrentHttpGlobalVariable();
        $result = $this->getKeyHttpVar($key, $httpVar);

        if(empty($result)){
            if ($default) {
                return $default;
            }
        }

        return $result;
    }

    /**
     * Check if $data is empty, 0, 0.0, o, NULL, false is not considered empty
     * @param array|string $data
     * @return bool
     */
    protected function isNotEmpty(array|string $data): bool
    {
        if (is_string($data)) {
            if (mb_strlen($data, 'UTF-8') === 0) {
                return false;
            }
            return mb_strlen(trim($data), 'UTF-8') > 0;
        }

        if (is_array($data)) {
            return count($data) > 0;
        }

        if (is_numeric($data)) {
            return true;
        }

        return false;
    }

    /**
     * Get key in HTTP var, if key does exist, you get the data (could be an array or a string or even null), else an empty string.
     *
     * @param string $key
     * key to get, to get a nested array key, you can delimit it with the $sep, the default is a dot notation, e.g
     * data.info.age would move in the array data to get the age value in the nested element
     * @param array $data
     * Data should be PHP HTTP GLOBAL VAR, i.e $_POST, $_GET, etc
     * @param string $sep
     * Default is a dot-notation
     * @return array|string
     */
    public function getKeyHttpVar(string $key, array $data, string $sep = '.'): array|string|null
    {
        $splitName = explode($sep, $key);
        foreach ($splitName as $value) {
            if (key_exists($value, $data)) {
                $data = $data[$value];
            } else {
                return '';
            }
        }
        return $data;
    }
}