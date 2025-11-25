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

namespace Devsrealm\TonicsRouterSystem\Handler;

use Devsrealm\TonicsRouterSystem\Adapter\Psr7OnRequestProcessAdapter;
use Devsrealm\TonicsRouterSystem\Adapter\Psr7RequestAdapter;
use Devsrealm\TonicsRouterSystem\Adapter\Psr7ResponseAdapter;
use Devsrealm\TonicsRouterSystem\Container\Container;
use Devsrealm\TonicsRouterSystem\Psr7Factory;
use Devsrealm\TonicsRouterSystem\Resolver\RouteResolver;
use Devsrealm\TonicsRouterSystem\Response;
use Devsrealm\TonicsRouterSystem\Route;
use Devsrealm\TonicsRouterSystem\RouteNode;
use Devsrealm\TonicsRouterSystem\RouteTreeGenerator;
use Devsrealm\TonicsRouterSystem\State\RouteTreeGeneratorState;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * PSR-7 Router
 *
 * A PSR-7 compliant router that wraps the standard Tonics Router.
 * This allows you to use PSR-7 request and response objects with the Tonics Router System.
 *
 * Example usage:
 * ```php
 * $router = Psr7Router::create();
 *
 * $router->getRoute()->get('/', function() {
 *     return 'Hello World';
 * });
 *
 * $request = Psr7Factory::createServerRequestFromGlobals();
 * $response = $router->handle($request);
 *
 * // Emit the response
 * $router->emit($response);
 * ```
 */
class Psr7Router
{
    private Router $router;
    private Psr7OnRequestProcessAdapter $onRequestProcess;
    private Psr7ResponseAdapter $responseAdapter;
    private ServerRequestInterface $psrRequest;

    /**
     * @param ServerRequestInterface $psrRequest
     * @param RouteResolver|null $routeResolver
     * @param Route|null $routeObject
     */
    public function __construct(
        ServerRequestInterface $psrRequest,
        RouteResolver $routeResolver = null,
        Route $routeObject = null
    ) {
        $this->psrRequest = $psrRequest;

        // Create route resolver if not provided
        if ($routeResolver === null) {
            $routeResolver = new RouteResolver(new Container());
        }

        // Create route object if not provided
        if ($routeObject === null) {
            $routeObject = new Route(
                new RouteTreeGenerator(
                    new RouteTreeGeneratorState(),
                    new RouteNode()
                )
            );
        }

        // Create PSR-7 adapted OnRequestProcess
        $this->onRequestProcess = new Psr7OnRequestProcessAdapter(
            $psrRequest,
            $routeResolver,
            $routeObject
        );

        // Create PSR-7 response adapter
        $psrResponse = Psr7Factory::createResponse();
        $this->responseAdapter = new Psr7ResponseAdapter($psrResponse);

        // Create standard Response for backward compatibility
        $requestAdapter = new Psr7RequestAdapter($psrRequest);
        $response = new Response($this->onRequestProcess, $requestAdapter);

        // Create the router
        $this->router = new Router(
            $this->onRequestProcess,
            $routeObject,
            $response
        );
    }

    /**
     * Static factory method to create a PSR-7 router from globals
     * @return static
     */
    public static function create(): self
    {
        $request = Psr7Factory::createServerRequestFromGlobals();
        return new self($request);
    }

    /**
     * Static factory method with custom dependencies
     * @param ServerRequestInterface|null $psrRequest
     * @param RouteResolver|null $routeResolver
     * @param Route|null $routeObject
     * @return static
     */
    public static function createWithDependencies(
        ?ServerRequestInterface $psrRequest = null,
        ?RouteResolver $routeResolver = null,
        ?Route $routeObject = null
    ): self {
        if ($psrRequest === null) {
            $psrRequest = Psr7Factory::createServerRequestFromGlobals();
        }
        return new self($psrRequest, $routeResolver, $routeObject);
    }

    /**
     * Handle a PSR-7 request and return a PSR-7 response
     *
     * In PSR-7, controllers should return content, not echo it.
     * The $store parameter in dispatchRequestURL captures returned values.
     *
     * Note: If you have legacy code that uses echo, you may need to refactor it
     * to return values instead when using PSR-7.
     *
     * @param ServerRequestInterface|null $request Optional request to handle (defaults to the one provided in constructor)
     * @return ResponseInterface
     * @throws \Exception
     */
    public function handle(?ServerRequestInterface $request = null): ResponseInterface
    {
        if ($request !== null) {
            $this->psrRequest = $request;
            $this->onRequestProcess = new Psr7OnRequestProcessAdapter(
                $request,
                $this->onRequestProcess->getRouteResolver(),
                $this->onRequestProcess->getRouteObject()
            );
            $this->router->setOnRequestProcessingEvent($this->onRequestProcess);
        }

        // Store the dispatch result (PSR-7 encourages returning content, not echoing)
        $this->router->dispatchRequestURL('', '', true);
        $result = $this->router->getDispatchResult();

        // Write the returned content to the PSR-7 response body
        if (!empty($result)) {
            $this->responseAdapter->write($result);
        }

        return $this->responseAdapter->getPsrResponse();
    }

    /**
     * Handle a PSR-7 request with output buffering for backward compatibility
     *
     * ⚠️ WARNING: This method uses output buffering which is NOT recommended for PSR-7.
     * It's provided only for backward compatibility with legacy code that uses echo.
     *
     * PSR-7 Best Practice: Controllers should return content, not echo it.
     * Please refactor your controllers to return values instead of echoing.
     *
     * @param ServerRequestInterface|null $request Optional request to handle
     * @return ResponseInterface
     * @throws \Exception
     * @deprecated Use handle() instead and refactor controllers to return values
     */
    public function handleWithOutputBuffering(?ServerRequestInterface $request = null): ResponseInterface
    {
        if ($request !== null) {
            $this->psrRequest = $request;
            $this->onRequestProcess = new Psr7OnRequestProcessAdapter(
                $request,
                $this->onRequestProcess->getRouteResolver(),
                $this->onRequestProcess->getRouteObject()
            );
            $this->router->setOnRequestProcessingEvent($this->onRequestProcess);
        }

        // Start output buffering to capture any echoed content (not PSR-7 compliant)
        ob_start();

        try {
            // Store the dispatch result
            $this->router->dispatchRequestURL('', '', true);
            $result = $this->router->getDispatchResult();

            // Get any output that was echoed
            $output = ob_get_clean();

            // Combine stored result and output
            $content = $output . $result;

            if (!empty($content)) {
                $this->responseAdapter->write($content);
            }
        } catch (\Exception $e) {
            ob_end_clean();
            throw $e;
        }

        return $this->responseAdapter->getPsrResponse();
    }

    /**
     * Get the underlying standard Router
     * @return Router
     */
    public function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * Get the Route object for defining routes
     * @return Route
     */
    public function getRoute(): Route
    {
        return $this->router->getRoute();
    }

    /**
     * Get the PSR-7 response adapter
     * @return Psr7ResponseAdapter
     */
    public function getResponseAdapter(): Psr7ResponseAdapter
    {
        return $this->responseAdapter;
    }

    /**
     * Get the OnRequestProcess adapter
     * @return Psr7OnRequestProcessAdapter
     */
    public function getOnRequestProcess(): Psr7OnRequestProcessAdapter
    {
        return $this->onRequestProcess;
    }

    /**
     * Emit a PSR-7 response (send headers and output body)
     * @param ResponseInterface $response
     */
    public function emit(ResponseInterface $response): void
    {
        // Send status line
        if (!headers_sent()) {
            header(sprintf(
                'HTTP/%s %s %s',
                $response->getProtocolVersion(),
                $response->getStatusCode(),
                $response->getReasonPhrase()
            ), true, $response->getStatusCode());

            // Send headers
            foreach ($response->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    header(sprintf('%s: %s', $name, $value), false);
                }
            }
        }

        // Send body
        echo $response->getBody();
    }

    /**
     * Convenience method to handle request from globals and emit response
     * @throws \Exception
     */
    public function run(): void
    {
        $response = $this->handle();
        $this->emit($response);
    }
}
