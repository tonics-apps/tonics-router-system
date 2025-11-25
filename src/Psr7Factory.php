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

namespace Devsrealm\TonicsRouterSystem;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * PSR-7 Factory Helper
 *
 * Provides convenient factory methods for creating PSR-7 request and response objects
 * using the Nyholm PSR-7 implementation.
 */
class Psr7Factory
{
    private static ?Psr17Factory $factory = null;

    /**
     * Get or create the PSR-17 factory instance
     * @return Psr17Factory
     */
    public static function getFactory(): Psr17Factory
    {
        if (self::$factory === null) {
            self::$factory = new Psr17Factory();
        }
        return self::$factory;
    }

    /**
     * Create a PSR-7 ServerRequest from PHP globals
     * @return ServerRequestInterface
     */
    public static function createServerRequestFromGlobals(): ServerRequestInterface
    {
        $factory = self::getFactory();

        // Get request method
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // Build URI
        $uri = self::createUriFromGlobals();

        // Create server request
        $serverRequest = new ServerRequest(
            $method,
            $uri,
            self::getHeadersFromGlobals(),
            'php://input',
            '1.1',
            $_SERVER
        );

        // Add parsed body (POST data)
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

            if (str_contains($contentType, 'application/json')) {
                $body = file_get_contents('php://input');
                $parsed = json_decode($body, true);
                if (is_array($parsed)) {
                    $serverRequest = $serverRequest->withParsedBody($parsed);
                }
            } else {
                $serverRequest = $serverRequest->withParsedBody($_POST);
            }
        }

        // Add query params
        $serverRequest = $serverRequest->withQueryParams($_GET);

        // Add cookie params
        $serverRequest = $serverRequest->withCookieParams($_COOKIE);

        // Add uploaded files
        $serverRequest = $serverRequest->withUploadedFiles(self::normalizeFiles($_FILES));

        return $serverRequest;
    }

    /**
     * Create a URI from PHP globals
     * @return \Psr\Http\Message\UriInterface
     */
    protected static function createUriFromGlobals(): \Psr\Http\Message\UriInterface
    {
        $factory = self::getFactory();

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        $port = (int)($_SERVER['SERVER_PORT'] ?? 80);
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        $query = $_SERVER['QUERY_STRING'] ?? '';

        $uri = $factory->createUri()
            ->withScheme($scheme)
            ->withHost($host)
            ->withPath($path);

        // Only add port if it's non-standard
        if (($scheme === 'http' && $port !== 80) || ($scheme === 'https' && $port !== 443)) {
            $uri = $uri->withPort($port);
        }

        if ($query !== '') {
            $uri = $uri->withQuery($query);
        }

        return $uri;
    }

    /**
     * Get HTTP headers from PHP globals
     * @return array
     */
    protected static function getHeadersFromGlobals(): array
    {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                // Remove HTTP_ prefix and convert to header name format
                $headerName = str_replace('_', '-', substr($key, 5));
                $headers[$headerName] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                // Special case for content headers
                $headerName = str_replace('_', '-', $key);
                $headers[$headerName] = $value;
            }
        }

        return $headers;
    }

    /**
     * Normalize PHP's $_FILES array to PSR-7 UploadedFile objects
     * @param array $files
     * @return array
     */
    protected static function normalizeFiles(array $files): array
    {
        $factory = self::getFactory();
        $normalized = [];

        foreach ($files as $key => $value) {
            if ($value instanceof \Psr\Http\Message\UploadedFileInterface) {
                $normalized[$key] = $value;
            } elseif (is_array($value) && isset($value['tmp_name'])) {
                $normalized[$key] = self::createUploadedFile($value);
            } elseif (is_array($value)) {
                $normalized[$key] = self::normalizeFiles($value);
            }
        }

        return $normalized;
    }

    /**
     * Create an UploadedFile from a $_FILES array element
     * @param array $file
     * @return \Psr\Http\Message\UploadedFileInterface|array
     */
    protected static function createUploadedFile(array $file)
    {
        $factory = self::getFactory();

        if (is_array($file['tmp_name'])) {
            $normalized = [];
            foreach (array_keys($file['tmp_name']) as $key) {
                $normalized[$key] = self::createUploadedFile([
                    'tmp_name' => $file['tmp_name'][$key],
                    'size' => $file['size'][$key],
                    'error' => $file['error'][$key],
                    'name' => $file['name'][$key],
                    'type' => $file['type'][$key],
                ]);
            }
            return $normalized;
        }

        return $factory->createUploadedFile(
            $factory->createStreamFromFile($file['tmp_name'] ?? ''),
            $file['size'] ?? null,
            $file['error'] ?? UPLOAD_ERR_OK,
            $file['name'] ?? null,
            $file['type'] ?? null
        );
    }

    /**
     * Create a new PSR-7 Response
     * @param int $status
     * @param array $headers
     * @param string $body
     * @return ResponseInterface
     */
    public static function createResponse(int $status = 200, array $headers = [], string $body = ''): ResponseInterface
    {
        $factory = self::getFactory();
        $response = $factory->createResponse($status);

        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        if ($body !== '') {
            $response->getBody()->write($body);
        }

        return $response;
    }

    /**
     * Create a JSON response
     * @param mixed $data
     * @param int $status
     * @param int $options
     * @return ResponseInterface
     */
    public static function createJsonResponse($data, int $status = 200, int $options = 0): ResponseInterface
    {
        $json = json_encode($data, $options);
        return self::createResponse($status, ['Content-Type' => 'application/json'], $json);
    }
}

