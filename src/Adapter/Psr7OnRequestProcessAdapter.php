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

use Devsrealm\TonicsRouterSystem\Events\OnRequestProcess;
use Devsrealm\TonicsRouterSystem\Interfaces\TonicsRequestInterface;
use Devsrealm\TonicsRouterSystem\Interfaces\TonicsRouterResolverInterface;
use Devsrealm\TonicsRouterSystem\Route;
use Psr\Http\Message\ServerRequestInterface;

/**
 * PSR-7 OnRequestProcess Adapter
 *
 * Adapts PSR-7 ServerRequestInterface to work with OnRequestProcess.
 * This allows the router to work with PSR-7 request objects.
 */
class Psr7OnRequestProcessAdapter extends OnRequestProcess
{
    private ServerRequestInterface $psrRequest;

    public function __construct(
        ServerRequestInterface $psrRequest,
        TonicsRouterResolverInterface $routeResolver = null,
        Route $routeObject = null
    ) {
        $this->psrRequest = $psrRequest;
        parent::__construct($routeResolver, $routeObject);
        $this->initializeFromPsr7Request();
    }

    /**
     * Initialize the OnRequestProcess from PSR-7 request
     */
    protected function initializeFromPsr7Request(): void
    {
        $serverParams = $this->psrRequest->getServerParams();

        // Set headers from server params
        $this->setHeaders($serverParams);

        // Set host
        $uri = $this->psrRequest->getUri();
        $this->setHost($uri->getHost() ?: ($serverParams['HTTP_HOST'] ?? 'localhost'));

        // Set URL from URI path
        $this->setUrl($uri->getPath());

        // Set method
        $this->setMethod($this->psrRequest->getMethod());

        // Set query params
        $queryParams = $this->psrRequest->getQueryParams();
        if (!empty($queryParams)) {
            $this->setParams($queryParams);
        }
    }

    /**
     * Get the underlying PSR-7 request
     * @return ServerRequestInterface
     */
    public function getPsrRequest(): ServerRequestInterface
    {
        return $this->psrRequest;
    }

    /**
     * Override reset to use PSR-7 request
     */
    public function reset(): void
    {
        $this->initializeFromPsr7Request();
    }

    /**
     * Override getEntityBody to use PSR-7 request body
     */
    public function getEntityBody(): string|bool
    {
        $body = $this->psrRequest->getBody();
        $body->rewind();
        return $body->getContents();
    }

    /**
     * Override getHeaderByKey to work with PSR-7 headers
     */
    public function getHeaderByKey(array|string $key): mixed
    {
        if (is_array($key)) {
            $result = [];
            foreach ($key as $header) {
                // Try PSR-7 headers first
                if ($this->psrRequest->hasHeader($header)) {
                    $result[$header] = $this->psrRequest->getHeaderLine($header);
                } else {
                    // Fallback to server params
                    $headerKey = 'HTTP_' . strtoupper(str_replace('-', '_', $header));
                    $serverParams = $this->psrRequest->getServerParams();
                    $result[$header] = $serverParams[$headerKey] ?? '';
                }
            }
            return $result;
        }

        // Try PSR-7 headers first
        if ($this->psrRequest->hasHeader($key)) {
            return $this->psrRequest->getHeaderLine($key);
        }

        // Try server params
        $headerKey = strtoupper($key);
        $serverParams = $this->psrRequest->getServerParams();

        // Try with HTTP_ prefix
        if (isset($serverParams['HTTP_' . $headerKey])) {
            return $serverParams['HTTP_' . $headerKey];
        }

        // Try without prefix (for things like REQUEST_METHOD, etc.)
        if (isset($serverParams[$headerKey])) {
            return $serverParams[$headerKey];
        }

        return '';
    }

    /**
     * Override getBearerToken to work with PSR-7 headers
     */
    public function getBearerToken(): bool|string
    {
        if ($this->psrRequest->hasHeader('Authorization')) {
            $authHeader = $this->psrRequest->getHeaderLine('Authorization');
            if (str_starts_with(strtolower($authHeader), 'bearer ')) {
                return trim(substr($authHeader, 7));
            }
            return '';
        }

        return '';
    }

    /**
     * Override isSecure to work with PSR-7 URI
     */
    public function isSecure(): bool
    {
        $scheme = $this->psrRequest->getUri()->getScheme();
        if ($scheme === 'https') {
            return true;
        }

        // Fallback to checking server params
        $serverParams = $this->psrRequest->getServerParams();
        if (!empty($serverParams['HTTPS']) && $serverParams['HTTPS'] !== 'off') {
            return true;
        }

        if (!empty($serverParams['HTTP_X_FORWARDED_PROTO'])
            && $serverParams['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        }

        return false;
    }

    /**
     * Override getUserAgent to work with PSR-7 headers
     */
    public function getUserAgent(): string
    {
        return $this->psrRequest->getHeaderLine('User-Agent');
    }

    /**
     * Override getReferer to work with PSR-7 headers
     */
    public function getReferer(): string
    {
        return $this->psrRequest->getHeaderLine('Referer');
    }

    /**
     * Override isAjax to work with PSR-7 headers
     */
    public function isAjax(): bool
    {
        return strtolower($this->psrRequest->getHeaderLine('X-Requested-With')) === 'xmlhttprequest';
    }
}

