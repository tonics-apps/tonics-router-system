<?php
/*
 * Copyright 2025 Ahmed Olayemi F. <olayemi@tonics.app or devsrealmer@gmail.com>
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

namespace Devsrealm\TonicsRouterSystem\Adapter;

use Devsrealm\TonicsRouterSystem\Interfaces\TonicsRouterRequestInputInterface;
use Devsrealm\TonicsRouterSystem\Interfaces\TonicsRouterRequestInputMethodsInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * PSR-7 Request Adapter
 *
 * Adapts PSR-7 ServerRequestInterface to work with Tonics Router System's RequestInput interface.
 * This allows you to use PSR-7 compliant request objects with the router.
 */
class Psr7RequestAdapter implements TonicsRouterRequestInputInterface, TonicsRouterRequestInputMethodsInterface
{
    private ?array $currentData = null;
    private ServerRequestInterface $psrRequest;

    public function __construct(ServerRequestInterface $psrRequest)
    {
        $this->psrRequest = $psrRequest;
    }

    /**
     * @return ServerRequestInterface
     */
    public function getPsrRequest(): ServerRequestInterface
    {
        return $this->psrRequest;
    }

    /**
     * @param ServerRequestInterface $psrRequest
     */
    public function setPsrRequest(ServerRequestInterface $psrRequest): void
    {
        $this->psrRequest = $psrRequest;
    }

    /**
     * @return array
     */
    protected function getCurrentData(): array
    {
        return $this->currentData ?? [];
    }

    /**
     * @param array $currentData
     */
    protected function setCurrentData(array $currentData): void
    {
        $this->currentData = $currentData;
    }

    /**
     * @param $data
     * @return TonicsRouterRequestInputMethodsInterface
     */
    public function fromPost($data = []): TonicsRouterRequestInputMethodsInterface
    {
        if (empty($data)){
            $parsedBody = $this->psrRequest->getParsedBody();
            $data = is_array($parsedBody) ? $parsedBody : [];
        }
        $clone = clone $this;
        $clone->setCurrentData($data);
        return $clone;
    }

    /**
     * @param array $data
     * @return TonicsRouterRequestInputMethodsInterface
     */
    public function fromGet(array $data = []): TonicsRouterRequestInputMethodsInterface
    {
        if (empty($data)){
            $data = $this->psrRequest->getQueryParams();
        }
        $clone = clone $this;
        $clone->setCurrentData($data);
        return $clone;
    }

    /**
     * @param $data
     * @return TonicsRouterRequestInputMethodsInterface
     */
    public function fromFile($data = []): TonicsRouterRequestInputMethodsInterface
    {
        if (empty($data)){
            $data = $this->psrRequest->getUploadedFiles();
        }
        $clone = clone $this;
        $clone->setCurrentData($data);
        return $clone;
    }

    /**
     * @param $data
     * @return TonicsRouterRequestInputMethodsInterface
     */
    public function fromServer($data = []): TonicsRouterRequestInputMethodsInterface
    {
        if (empty($data)){
            $data = $this->psrRequest->getServerParams();
        }
        $clone = clone $this;
        $clone->setCurrentData($data);
        return $clone;
    }

    /**
     * @param $data
     * @return TonicsRouterRequestInputMethodsInterface
     */
    public function fromCookie($data = []): TonicsRouterRequestInputMethodsInterface
    {
        if (empty($data)){
            $data = $this->psrRequest->getCookieParams();
        }
        $clone = clone $this;
        $clone->setCurrentData($data);
        return $clone;
    }

    /**
     * @throws \Exception
     */
    protected function checkDataIsNotNull(): void
    {
        if ($this->currentData === null) {
            throw new \Exception("No data set. Call fromPost(), fromGet(), etc. first");
        }
    }

    /**
     * @throws \Exception
     */
    public function all(): array
    {
        $this->checkDataIsNotNull();
        return $this->getCurrentData();
    }

    /**
     * @throws \Exception
     */
    public function has(string $key): bool
    {
        $this->checkDataIsNotNull();
        $data = $this->getCurrentData();

        $splitName = explode('.', $key);
        $result = false;
        foreach ($splitName as $value) {
            if (is_array($data) && key_exists($value, $data)) {
                $result = true;
                $data = $data[$value];
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
        $this->checkDataIsNotNull();
        $data = $this->getCurrentData();
        $result = $this->getKeyData($key, $data);

        if(empty($result)){
            return false;
        }

        return true;
    }

    /**
     * @param string $key
     * @param null $default
     * @return mixed
     * @throws \Exception
     */
    public function retrieve(string $key, $default = null): mixed
    {
        $this->checkDataIsNotNull();
        $data = $this->getCurrentData();
        $result = $this->getKeyData($key, $data);

        if(empty($result)){
            if ($default) {
                return $default;
            }
        }

        return $result;
    }

    /**
     * Get key in data array
     * @param string $key
     * @param array $data
     * @param string $sep
     * @return array|string|null
     */
    public function getKeyData(string $key, array $data, string $sep = '.'): array|string|null
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

