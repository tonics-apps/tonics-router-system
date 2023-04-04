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

namespace Devsrealm\TonicsRouterSystem\Events;

use Devsrealm\TonicsRouterSystem\Interfaces\TonicsRequestInterface;
use Devsrealm\TonicsRouterSystem\Interfaces\TonicsRouterResolverInterface;
use Devsrealm\TonicsRouterSystem\Route;

class OnRequestProcess implements TonicsRequestInterface
{
    /**
     * @var Route
     */
    private Route $routeObject;

    /**
     * Server Request Headers
     * @var array
     */
    private array $headers = [];

    /**
     * Request host
     * @var string
     */
    private string $host;

    /**
     * Current request url
     * @var string
     */
    private string $url;

    /**
     * Request method
     * @var string
     */
    private string $method;

    /**
     * @var TonicsRouterResolverInterface
     */
    private TonicsRouterResolverInterface $routeResolver;

    /**
     * @var array
     */
    private array $params = [];

    public function event(): static
    {
        return $this;
    }

    /**
     * OnRequestProcessing constructor.
     * @param TonicsRouterResolverInterface|null $routeResolver
     */
    public function __construct(TonicsRouterResolverInterface $routeResolver = null, Route $routeObject = null) {

        if ($routeResolver){
            $this->routeResolver = $routeResolver;
        }

        if ($routeObject){
            $this->routeObject = $routeObject;
        }

        $this->reset();
    }

    /**
     * Reset Request Global Vars, This shouldn't be cached cos we wouldn't wanna cache a single url for all request
     */
    public function reset(){
        $this->removeAllQueryString();
        $this->setHeaders($_SERVER);
        $this->setHost($this->getHeaderByKey('HTTP_HOST'));
        $this->setUrl($this->getHeaderByKey('REQUEST_URI'));
        $this->setMethod($this->getHeaderByKey('REQUEST_METHOD'));
        $this->setQueryString($this->getHeaderByKey('QUERY_STRING'));
    }

    /**
     * @inheritDoc
     */
    public function getRequestMethod(): string
    {
        return $this->method;
    }

    /**
     * @inheritDoc
     */
    public function getRequestURL(): string
    {
        $url = strtok($this->url, '?');
        return $this->cleanUrl($url);
    }

    public function getFullURL(): string
    {
        $http = ($this->isSecure()) ? 'https://': 'http://';
        return $http . $this->getHost() . $this->getRequestURL();
    }

    public function getRequestURLWithQueryString(): string
    {
        $queryString = http_build_query($this->getParams());
        $urlPathWithoutQueryString = $this->getRequestURL();
        return $urlPathWithoutQueryString . '?' . $queryString;
    }

    public function cleanUrl(string $url): string
    {
        ## D preg_replace converts multiple slashes to one.
        ## FILTER_SANITIZE_URL remove illegal chars from the url
        ## rtrim remove slash from the end e.g /name/book/ becomes  /name/book
        return rtrim(filter_var(preg_replace("#//+#", "\\1/", $url), FILTER_SANITIZE_URL), '/');
    }

    /**
     * @inheritDoc
     */
    public function getEntityBody(): string|bool
    {
        return file_get_contents('php://input');
    }

    /**
     * Find header key, you could either find one key or multiple keys
     * @param array|string $key
     * @return mixed
     */
    public function getHeaderByKey(array|string $key): mixed
    {
        if (is_array($key)) {
            $result = [];
            foreach ($key as $header) {
                $headerToUpper = strtoupper($header);
                if (key_exists('HTTP_'.$headerToUpper, $this->getHeaders())){
                    $result[$header] = $this->getHeaders()['HTTP_'.$headerToUpper];
                    continue;
                }
                if (!key_exists($header, $this->getHeaders())) {
                    return '';
                }
                $result[$header] = $this->getHeaders()[$header];
            }

            return $result;
        }

        $headerToUpper = strtoupper($key);
        if (key_exists('HTTP_'.$headerToUpper, $this->getHeaders())){
            $headerToUpper = 'HTTP_'.$headerToUpper;
        }
        if (!key_exists($headerToUpper, $this->getHeaders())){
            return '';
        }

        return $this->getHeaders()[$headerToUpper];
    }

    /*
     * Alias of `getHeaderByKey()` method
     */
    public function getAPIHeaderKey(array|string $key): mixed
    {
        return $this->getHeaderByKey($key);
    }

    protected function setHeaders(array $headers){
        $this->headers = $headers;
    }

    public function addToHeader($key, $data)
    {
        $this->headers[$key] = $data;
        return $this;
    }

    public function removeFromHeader($key)
    {
        unset($this->headers[$key]);
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    protected function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    public function setUrl($urlPath)
    {
        $this->url = $urlPath;
    }

    /**
     * @param mixed $requestMethod
     */
    protected function setMethod(mixed $requestMethod)
    {
        $this->method = $requestMethod;
    }

    /**
     * This would override existing params be-careful
     * @param string $queryString
     * e.g "path=uploads&id=1&page=3"
     */
    public function setQueryString(string $queryString)
    {
        $params = [];
        parse_str($queryString, $params);
        if(count($params) > 0) {
            $this->setParams($params);
        }
    }

    public function removeAllQueryString()
    {
        $this->setParams([]);
    }

    /**
     * @param string $queryString
     * e.g "page=55" or "page=55&id=600" (you use the & symbol to add more query key and value)
     */
    public function appendQueryString(string $queryString): static
    {
        $params = [];
        parse_str($queryString, $params);
        if(count($params) > 0) {
            $this->setParams(array_merge($this->getParams(), $params));
        }
        return $this;
    }

    /**
     * Get URL QUERY_STRING
     *
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Get parameter by name.
     * @param string $name
     * Parameter or query key name
     * @param string|null $defaultValue
     * Default value if query parameter value is empty
     * @param \Closure|null $callback
     * Callback would be called on value if the param key value is not empty
     * @return string|array|null
     */
    public function getParam(string $name, ?string $defaultValue = null, \Closure $callback = null): string|array|null
    {
        $paramVal = (isset($this->getParams()[$name])) ? $this->getParams()[$name] : $defaultValue;
        if ($callback && $paramVal){
            return $callback($paramVal);
        }
        return $paramVal;
    }

    /**
     * Check if URL contains parameter or query string key.
     *
     * @param string $name
     * @return bool
     */
    public function hasParam(string $name): bool
    {
        return array_key_exists($name, $this->getParams());
    }

    /**
     * Check if URL contains parameter or query string key and a value.
     *
     * Note: String '0' is not considered empty
     * @param string $name
     * @return bool
     */
    public function hasParamAndValue(string $name): bool
    {
        if (!$this->hasParam($name)){
            return false;
        }

        $value = $this->getParam($name);
        if ($value === '0'){
            return true;
        }
        return !empty($this->getParam($name));
    }

    /**
     * Removes multiple parameters from the query-string
     *
     * @param array ...$names
     * @return OnRequestProcess
     */
    public function removeParams(...$names): OnRequestProcess
    {
        $params = array_diff_key($this->getParams(), array_flip(...$names));
        $this->setParams($params);
        return $this;
    }

    /**
     * Removes parameter from the query-string
     * Use $this->removeParams(...$names) to remove multiple params/queries string
     * @param string $name
     * @return OnRequestProcess
     */
    public function removeParam(string $name): OnRequestProcess
    {
        $params = $this->getParams();
        unset($params[$name]);
        $this->setParams($params);

        return $this;
    }

    /**
     * Set the url params
     *
     * @param array $params
     * @return OnRequestProcess
     */
    public function setParams(array $params): OnRequestProcess
    {
        $this->params = $params;
        return $this;
    }

    /**
     * Get referer
     * @return string
     */
    public function getReferer(): string
    {
        return $this->getHeaderByKey('HTTP_REFERER');
    }

    /**
     * This would return an empty string if the bearer token can't be found,
     * otherwise, it would send the bearer token
     */
    public function getBearerToken(): bool|string
    {
        if (!key_exists('Authorization', getallheaders())){
            return '';
        }

        $auth = getallheaders()['Authorization'];
        if (!str_contains($auth, 'Bearer ')){
            return '';
        }
        return trim(substr($auth, 7));
    }

    /**
     *  Returns true if bearerToken is empty
     * @return bool
     */
    public function isBearerTokenEmpty(): bool
    {
        return !$this->getBearerToken();
    }

    /**
     * Get user agent
     * @return string
     */
    public function getUserAgent(): string
    {
        return $this->getHeaderByKey('HTTP_USER_AGENT');
    }

    /**
     * @return string
     */
    public function getQueryString(): string
    {
        return $this->getHeaderByKey('QUERY_STRING');
    }

    /**
     * Get accept formats
     * @return array
     */
    public function getRequestAcceptFormats(): array
    {
        return explode(',', $this->getHeaderByKey('HTTP_ACCEPT'));
    }

    /**
     * Returns true if the request is made through Ajax/Fetch API
     *
     * Note: There isn't a 100% secure way to know if a request is an Ajax or not cos it can easily be spoofed,
     * use at your own risk.
     *
     * @return bool
     */
    public function isAjax(): bool
    {
        return (strtolower($this->getHeaderByKey('HTTP_X-REQUESTED_WITH')) === 'xmlhttprequest');
    }

    /**
     * Check if url is secure
     * @return bool
     */
    public function isSecure(): bool
    {
        if( !empty($this->getHeaderByKey('HTTPS'))) { return true; }

        if( !empty( $this->getHeaderByKey('HTTP_X_FORWARDED_PROTO') )
            && $this->getHeaderByKey('HTTP_X_FORWARDED_PROTO') == 'https' ) { return true; }

        return false;
    }

    /**
     * @param TonicsRouterResolverInterface $routeResolver
     * @return OnRequestProcess
     */
    public function setRouteResolver(TonicsRouterResolverInterface $routeResolver): self
    {
        $this->routeResolver = $routeResolver;
        return $this;
    }

    /**
     * @return TonicsRouterResolverInterface
     */
    public function getRouteResolver(): TonicsRouterResolverInterface
    {
        return $this->routeResolver;
    }

    /**
     * @return Route
     */
    public function getRouteObject(): Route
    {
        return $this->routeObject;
    }

    /**
     * @param Route $routeObject
     * @return OnRequestProcess
     */
    public function setRouteObject(Route $routeObject): self
    {
        $this->routeObject = $routeObject;
        return $this;
    }
}