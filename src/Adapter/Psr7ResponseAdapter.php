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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use InvalidArgumentException;
use JsonSerializable;

/**
 * PSR-7 Response Adapter
 *
 * Wraps a PSR-7 ResponseInterface to provide Tonics-style response methods
 * while maintaining PSR-7 immutability and compatibility.
 */
class Psr7ResponseAdapter
{
    private ResponseInterface $psrResponse;

    public function __construct(ResponseInterface $psrResponse)
    {
        $this->psrResponse = $psrResponse;
    }

    /**
     * Get the underlying PSR-7 response
     * @return ResponseInterface
     */
    public function getPsrResponse(): ResponseInterface
    {
        return $this->psrResponse;
    }

    /**
     * Set or update the PSR-7 response (useful for immutable operations)
     * @param ResponseInterface $psrResponse
     */
    public function setPsrResponse(ResponseInterface $psrResponse): void
    {
        $this->psrResponse = $psrResponse;
    }

    /**
     * Set the HTTP response code
     * @param int $code
     * @return $this
     */
    public function httpResponseCode(int $code): self
    {
        $this->psrResponse = $this->psrResponse->withStatus($code);
        return $this;
    }

    /**
     * Redirect the response
     * @param string $url
     * @param ?int $httpCode
     */
    public function redirect(string $url, ?int $httpCode = null): void
    {
        if ($httpCode !== null) {
            $this->psrResponse = $this->psrResponse->withStatus($httpCode);
        } else {
            $this->psrResponse = $this->psrResponse->withStatus(302);
        }

        $this->psrResponse = $this->psrResponse->withHeader('Location', $url);
        $this->emit();
        exit(0);
    }

    /**
     * Add http authorisation
     * @param string $name
     * @return static
     */
    public function auth(string $name = ''): self
    {
        $this->psrResponse = $this->psrResponse
            ->withStatus(401)
            ->withHeader('WWW-Authenticate', 'Basic realm="' . $name . '"');

        return $this;
    }

    /**
     * Json encode
     * @param array|JsonSerializable $value
     * @param ?int $options JSON options
     * @param int $dept JSON depth
     * @throws InvalidArgumentException
     */
    public function json(array|JsonSerializable $value, ?int $options = null, int $dept = 512): void
    {
        if (!$value instanceof JsonSerializable && !is_array($value)){
            throw new InvalidArgumentException('`value` must be of type array or object that is an instance of JsonSerializable Interface.');
        }

        $json = json_encode($value, $options ?? 0, $dept);

        $this->psrResponse = $this->psrResponse
            ->withHeader('Content-Type', 'application/json; charset=utf-8');

        $this->psrResponse->getBody()->write($json);
        $this->emit();
        exit(0);
    }

    /**
     * @param $data
     * @param string $message
     * @param int $code
     * @param null $more
     */
    public function onSuccess($data, string $message = '', int $code = 200, $more = null): void
    {
        $this->httpResponseCode($code)->json([
            'status' => $code,
            'message' => $message,
            'data' => $data,
            'more' => $more
        ], JSON_PRETTY_PRINT);
    }

    /**
     * @param int $code
     * @param string $message
     */
    public function onError(int $code, string $message = ''): void
    {
        $this->httpResponseCode($code)->json([
            'status' => $code,
            'message' => $message,
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Add Raw header to response
     * @param string $value
     * @return static
     */
    public function header(string $value): self
    {
        // Parse header string (format: "Header-Name: value")
        $parts = explode(':', $value, 2);
        if (count($parts) === 2) {
            $name = trim($parts[0]);
            $val = trim($parts[1]);
            $this->psrResponse = $this->psrResponse->withHeader($name, $val);
        }

        return $this;
    }

    /**
     * Add multiple Raw headers to response
     * @param array $headers
     * @return static
     */
    public function headers(array $headers): self
    {
        foreach ($headers as $header) {
            $this->header($header);
        }

        return $this;
    }

    /**
     * Write content to the response body
     * @param string $content
     * @return $this
     */
    public function write(string $content): self
    {
        $this->psrResponse->getBody()->write($content);
        return $this;
    }

    /**
     * Emit the PSR-7 response (send headers and body)
     * This is typically called automatically by json() and redirect()
     */
    public function emit(): void
    {
        // Emit status line
        if (!headers_sent()) {
            header(sprintf(
                'HTTP/%s %s %s',
                $this->psrResponse->getProtocolVersion(),
                $this->psrResponse->getStatusCode(),
                $this->psrResponse->getReasonPhrase()
            ), true, $this->psrResponse->getStatusCode());

            // Emit headers
            foreach ($this->psrResponse->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    header(sprintf('%s: %s', $name, $value), false);
                }
            }
        }

        // Emit body
        echo $this->psrResponse->getBody();
    }
}

