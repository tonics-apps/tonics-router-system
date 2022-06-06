<?php

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
     * @throws Exception
     */
    public function dispatchRequestURL(string $url = '', string $requestMethod = '', bool $store = false)
    {
        // $this->OnRequestProcessingEvent->reset();
        if (empty($url)){
            $url = $this->OnRequestProcessingEvent->getRequestURL();
        }
        if (empty($requestMethod)){
            $requestMethod = $this->OnRequestProcessingEvent->getRequestMethod();
        }
        $requestUrl = $this->removeQueryStringFromPath($url);
        $routeNode = $this->OnRequestProcessingEvent->getRouteObject()->getRouteTreeGenerator()->findURL($requestUrl);
        if ($routeNode->getFoundURLNode() === null){
            throw new URLNotFound("Page Not Found 🙄", 404);
        }

        // Request Method `$requestMethod` is not registered to any known route"
        // throw url not found exception
        if ($routeNode->getFoundURLNode()->requestMethodExist($requestMethod) === false){
            throw new URLNotFound("URL Not Found", 404);
        }

        if ($routeNode->getFoundURLNode()->requestMethodHasRequestInterceptors($requestMethod)){
            $this->dispatchRequestInterceptor($routeNode->getFoundURLNode()->getRequestInterceptors($requestMethod));
        }

        $this->dispatch($routeNode, $requestMethod, $store);
    }

    /**
     * @param TonicsRouterFoundURLMethodsInterface $foundURLMethods
     * @param $requestMethod
     * @param bool $store
     */
    private function dispatch(TonicsRouterFoundURLMethodsInterface $foundURLMethods, $requestMethod, bool $store = false): void
    {
        $params = $foundURLMethods->getFoundURLRequiredParams();
        $rR = $this->getOnRequestProcessingEvent()->getRouteResolver();

        if ($class = $foundURLMethods->getFoundURLNode()->getClass($requestMethod)){
            $class = $rR->resolveClass($class);
            if ($store){
                $this->dispatchResult = $rR->resolveThroughClassMethod($class, $foundURLMethods->getFoundURLNode()->getCallback($requestMethod), $params);
            } else {
                $rR->resolveThroughClassMethod($class, $foundURLMethods->getFoundURLNode()->getCallback($requestMethod), $params);
            }
        }elseif($foundURLMethods->getFoundURLNode()->getCallback($requestMethod) instanceof Closure){
            if ($store){
                $this->dispatchResult = $rR->resolveThroughClosure($foundURLMethods->getFoundURLNode()->getCallback($requestMethod), $params);
            }
            $rR->resolveThroughClosure($foundURLMethods->getFoundURLNode()->getCallback($requestMethod), $params);
        }

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