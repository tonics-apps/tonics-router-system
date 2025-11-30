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
            // First try getParsedBody() - works for form-urlencoded and multipart/form-data
            $parsedBody = $this->psrRequest->getParsedBody();

            if (is_array($parsedBody)) {
                $data = $parsedBody;
            } else {
                // getParsedBody() returned null - parse the raw body based on Content-Type
                $data = $this->parseRequestBody();
            }
        }
        $clone = clone $this;
        $clone->setCurrentData($data);
        return $clone;
    }

    /**
     * Parse raw request body based on Content-Type
     * Supports: application/json, application/x-www-form-urlencoded, and others
     *
     * @return array
     */
    private function parseRequestBody(): array
    {
        $body = (string) $this->psrRequest->getBody();

        // Empty body
        if (empty($body)) {
            return [];
        }

        $contentType = $this->psrRequest->getHeaderLine('Content-Type');

        // Parse JSON
        if (str_contains($contentType, 'application/json')) {
            try {
                $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
                return is_array($decoded) ? $decoded : [];
            } catch (\JsonException $e) {
                // Invalid JSON - return empty array
                return [];
            }
        }

        // Parse form-urlencoded (fallback if PSR-7 didn't parse it)
        if (str_contains($contentType, 'application/x-www-form-urlencoded')) {
            parse_str($body, $parsed);
            return is_array($parsed) ? $parsed : [];
        }

        // For other content types, try JSON first, then form-urlencoded
        // This allows flexibility for APIs that don't set Content-Type correctly

        // Try JSON
        $decoded = @json_decode($body, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // Try form-urlencoded
        parse_str($body, $parsed);
        if (is_array($parsed) && !empty($parsed)) {
            return $parsed;
        }

        // Unable to parse - return empty array
        return [];
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

