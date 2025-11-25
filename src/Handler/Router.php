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

namespace Devsrealm\TonicsRouterSystem\Handler;

use Closure;
use Devsrealm\TonicsRouterSystem\Events\OnRequestProcess;
use Devsrealm\TonicsRouterSystem\Exceptions\URLNotFound;
use Devsrealm\TonicsRouterSystem\Interfaces\TonicsRouterFoundURLMethodsInterface;
use Devsrealm\TonicsRouterSystem\Interfaces\TonicsRouterRequestInterceptorInterface;
use Devsrealm\TonicsRouterSystem\Response;
use Devsrealm\TonicsRouterSystem\Route;
use Exception;

class Router
{
    private OnRequestProcess $OnRequestProcessingEvent;
    private Route $route;
    private Response $response;

    private string $dispatchResult = '';

    /**
     * @param OnRequestProcess $onRequestProcess
     * @param Route $route
     * @param Response $response
     */
    public function __construct(OnRequestProcess $onRequestProcess, Route $route, Response $response)
    {
        $this->OnRequestProcessingEvent = $onRequestProcess;
        $this->route = $route;
        $this->response = $response;
    }

    /**
     * @return OnRequestProcess
     */
    public function getOnRequestProcessingEvent(): OnRequestProcess
    {
        return $this->OnRequestProcessingEvent;
    }

    /**
     * @param $getRequestURL
     * @return bool|string
     */
    private function removeQueryStringFromPath($getRequestURL): bool|string
    {
        $path = strtok($getRequestURL, '?'); // Remove the query string
        strtok('', ''); // free token from memory I guess
        return $path;
    }


    /**
     * Dispatches a request to the appropriate route handler.
     *
     * This method performs the following operations:
     * 1. Resolves the URL and request method (uses defaults from OnRequestProcessingEvent if not provided)
     * 2. Strips query strings from the URL path
     * 3. Finds the matching route in the route tree
     * 4. Validates that the route exists and supports the requested HTTP method
     * 5. Executes any registered request interceptors for the route
     * 6. Dispatches to the final route handler
     *
     * @param string $url The URL to dispatch. If empty, uses the URL from OnRequestProcessingEvent.
     * @param string $requestMethod The HTTP request method (GET, POST, etc.). If empty, uses the method from OnRequestProcessingEvent.
     * @param bool $store When true, stores the return value from the route handler in $dispatchResult property.
     *                    NOTE: The store parameter only works if the route handler (controller method or closure)
     *                    explicitly returns a value. If the handler doesn't return anything or returns void,
     *                    $dispatchResult will be empty even when $store is true.
     *
     * @return void
     * @throws URLNotFound If the URL is not found or the request method is not registered for the route.
     * @throws Exception If there are issues with request interceptors or route resolution.
     */
    public function dispatchRequestURL(string $url = '', string $requestMethod = '', bool $store = false): void
    {
        // Use defaults from OnRequestProcessingEvent if parameters are not provided
        if (empty($url)) {
            $url = $this->OnRequestProcessingEvent->getRequestURL();
        }
        if (empty($requestMethod)) {
            $requestMethod = $this->OnRequestProcessingEvent->getRequestMethod();
        }

        // Strip query string from the URL path
        $requestUrl = $this->removeQueryStringFromPath($url);

        // Find the matching route in the route tree
        $routeNode = $this->OnRequestProcessingEvent->getRouteObject()->getRouteTreeGenerator()->findURL($requestUrl);

        // Validate that a matching route was found
        if ($routeNode->getFoundURLNode() === null) {
            throw new URLNotFound("Page Not Found 🙄", 404);
        }

        // Validate that the route supports the requested HTTP method
        if ($routeNode->getFoundURLNode()->requestMethodExist($requestMethod) === false) {
            throw new URLNotFound("URL Not Found", 404);
        }

        // Execute request interceptors if any are registered for this route method
        if ($routeNode->getFoundURLNode()->requestMethodHasRequestInterceptors($requestMethod)) {
            $this->dispatchRequestInterceptor($routeNode->getFoundURLNode()->getRequestInterceptors($requestMethod));
        }

        // Dispatch to the final route handler
        $this->dispatch($routeNode, $requestMethod, $store);
    }

    /**
     * Dispatches the request to the appropriate handler (class method or closure).
     *
     * This method resolves and executes the route handler in one of two ways:
     * 1. Class-based handler: Resolves the class via dependency injection and calls the specified method
     * 2. Closure-based handler: Directly invokes the closure with resolved dependencies
     *
     * The method extracts URL parameters from the route and passes them to the handler along with
     * any other dependencies resolved through the RouteResolver.
     *
     * @param TonicsRouterFoundURLMethodsInterface $foundURLMethods The found route containing handler information and URL parameters.
     * @param string $requestMethod The HTTP request method (GET, POST, PUT, DELETE, etc.).
     * @param bool $store When true, attempts to store the return value from the route handler.
     *                    IMPORTANT: The store functionality depends on the handler's return behavior:
     *                    - If the handler returns a value (string, array, object, etc.), it will be stored in $dispatchResult
     *                    - If the handler returns void or null, $dispatchResult will be empty/null
     *                    - If the handler echoes output but doesn't return anything, nothing will be stored
     *                    Example working handler: public function index() { return "Hello World"; }
     *                    Example non-working handler: public function index(): void { echo "Hello World"; }
     *
     * @return void
     */
    private function dispatch(TonicsRouterFoundURLMethodsInterface $foundURLMethods, string $requestMethod, bool $store = false): void
    {
        // Extract URL parameters (e.g., {id}, {slug}) from the matched route
        $params = $foundURLMethods->getFoundURLRequiredParams();

        // Get the route resolver for dependency injection
        $rR = $this->getOnRequestProcessingEvent()->getRouteResolver();

        // Get the route node for accessing handler information
        $foundURLNode = $foundURLMethods->getFoundURLNode();

        // Check if handler is a class-based controller
        $class = $foundURLNode->getClass($requestMethod);
        if ($class) {
            // Resolve the class through dependency injection
            $resolvedClass = $rR->resolveClass($class);

            // Get the callback method name
            $callback = $foundURLNode->getCallback($requestMethod);

            // Execute the class method and optionally store the result
            if ($store) {
                $this->dispatchResult = $rR->resolveThroughClassMethod($resolvedClass, $callback, $params);
            } else {
                $rR->resolveThroughClassMethod($resolvedClass, $callback, $params);
            }

            return;
        }

        // Check if handler is a closure
        $callback = $foundURLNode->getCallback($requestMethod);
        if ($callback instanceof Closure) {
            // Execute the closure and optionally store the result
            if ($store) {
                $this->dispatchResult = $rR->resolveThroughClosure($callback, $params);
            } else {
                $rR->resolveThroughClosure($callback, $params);
            }

            return;
        }

        // If we reach here, no valid handler was found (edge case that shouldn't normally occur)
        // The route tree validation should prevent this, but we handle it defensively
    }

    /**
     * @param array $requestInterceptors
     * @throws Exception
     */
    private function dispatchRequestInterceptor(array $requestInterceptors)
    {
        foreach ($requestInterceptors as $interceptor) {
            $interceptor = $this->getOnRequestProcessingEvent()->getRouteResolver()->resolveClass($interceptor);
            if (!$interceptor instanceof TonicsRouterRequestInterceptorInterface) {
                throw new Exception("`$interceptor` is not an instance of TonicsRouterRequestInterceptorInterface");
            }
            $interceptor->handle($this->getOnRequestProcessingEvent());
        }
    }

    /**
     * @param OnRequestProcess $OnRequestProcessingEvent
     * @return Router
     */
    public function setOnRequestProcessingEvent(OnRequestProcess $OnRequestProcessingEvent): Router
    {
        $this->OnRequestProcessingEvent = $OnRequestProcessingEvent;
        return $this;
    }

    /**
     * @return Route
     */
    public function getRoute(): Route
    {
        return $this->route;
    }

    /**
     * @return Response
     */
    public function getResponse(): Response
    {
        return $this->response;
    }

    /**
     * @param Response $response
     * @return Router
     */
    public function setResponse(Response $response): Router
    {
        $this->response = $response;
        return $this;
    }

    /**
     * @param Route $route
     * @return Router
     */
    public function setRoute(Route $route): Router
    {
        $this->route = $route;
        return $this;
    }

    /**
     * @return string
     */
    public function getDispatchResult(): string
    {
        return $this->dispatchResult;
    }

    /**
     * @param string $dispatchResult
     * @return Router
     */
    public function setDispatchResult(string $dispatchResult): Router
    {
        $this->dispatchResult = $dispatchResult;
        return $this;
    }
}