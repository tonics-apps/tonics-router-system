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

use Closure;
use Devsrealm\TonicsRouterSystem\State\RouteTreeGeneratorState;
use InvalidArgumentException;
use JetBrains\PhpStorm\Pure;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;

class Route
{

    private array $groupPrefix = [];
    private array $groupRequestInterceptor = [];
    private array $groupAlias = [];

    private array $routes = [];
    private RouteTreeGenerator $routeTreeGenerator;


    #[Pure] public function __construct(RouteTreeGenerator $routeTreeGenerator)
    {
        $this->routeTreeGenerator = $routeTreeGenerator;
    }


    /**
     * @param string $prefix
     * @param array $requestInterceptor
     * @param Closure $callback
     * @param string $alias
     * An example could be group1 or for a nested group group1.group2
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function group(string $prefix, Closure $callback, array $requestInterceptor = [], string $alias = ''): void
    {
        $alias = trim($alias, '.');
        if (!$this->isCallBackRouteGroup($callback)){
            throw new \Exception("Callback should be an instance of RouteGroup");
        }

        # This stores the group prefix. If the group is of nested type, it would cycle back due to $callback($this)
        # and would keep storing the prefixes of each group until the end of each group closure (the process is same for requestInterceptor).
        #
        # The prepends and marshalling of the route under the group is done in the resolveRouteSettings() method
        #
        $this->addToGroupPrefix($prefix)->addToGroupRequestInterceptor($requestInterceptor)->addToGroupAlias($alias);
        $callback($this); # This callback is what makes it possible to pass the Route Object to the group callback

        # Once we get to the end of the closure, we remove the last prefix from the group to
        # give space for the incoming prefix, the same is true for the requestInterceptor, the cycle repeats
        $this->removeProcessGroup();
    }

    /**
     * @return void
     */
    protected function removeProcessGroup() :void
    {
        $processes = ['groupPrefix', 'groupRequestInterceptor', 'groupAlias'];
        foreach ($processes as $process){
            $gKey = array_key_last($this->{$process});
            if ($gKey !== null){
                unset($this->{$process}[$gKey]);
                $this->{$process} = array_values($this->{$process});
            }
        }
    }

    /**
     * @param string $prefix
     * @return $this
     */
    private function addToGroupPrefix(string $prefix): static
    {
        $this->groupPrefix[] = $prefix . '/';
        return $this;
    }

    /**
     * @param string $alias
     * @return void
     */
    private function addToGroupAlias(string $alias): void
    {
        $this->groupAlias[] = $alias;
    }

    /**
     * @param array $requestInterceptor
     * @return Route
     */
    private function addToGroupRequestInterceptor(array $requestInterceptor): Route
    {
        // $this->groupRequestInterceptor = [...$this->groupRequestInterceptor, ...$requestInterceptor];
        $this->groupRequestInterceptor[] = $requestInterceptor;
        return $this;
    }

    /**
     * @param string $url
     * @param Closure|array $callback
     * @param array $requestInterceptor
     * @param string $alias
     * @param null $moreSettings
     * @return $this
     */
    public function get(string $url, Closure|array $callback, array $requestInterceptor = [], string $alias = '', $moreSettings = null): static
    {
        $this->resolveRouteSettings([RequestMethods::REQUEST_TYPE_GET], $url,  $callback, $requestInterceptor, $alias, $moreSettings);
        return $this;
    }

    /**
     * @param string $url
     * @param Closure|array $callback
     * @param array $requestInterceptor
     * @param string $alias
     * @param null $moreSettings
     * @return $this
     */
    public function head(string $url, Closure|array $callback, array $requestInterceptor = [], string $alias = '', $moreSettings = null): static
    {
        $this->resolveRouteSettings([RequestMethods::REQUEST_TYPE_HEAD], $url,  $callback, $requestInterceptor, $alias, $moreSettings);
        return $this;
    }

    /**
     * @param string $url
     * @param Closure|array $callback
     * @param array $requestInterceptor
     * @param string $alias
     * @return $this
     */
    public function post(string $url, Closure|array $callback, array $requestInterceptor = [], string $alias = ''): static
    {
        $this->resolveRouteSettings([RequestMethods::REQUEST_TYPE_POST], $url,  $callback, $requestInterceptor, $alias);
        return $this;
    }

    /**
     * @param string $url
     * @param Closure|array $callback
     * @param array $requestInterceptor
     * @param string $alias
     * @return $this
     */
    public function put(string $url, Closure|array $callback, array $requestInterceptor = [], string $alias = ''): static
    {
        $this->resolveRouteSettings([RequestMethods::REQUEST_TYPE_PUT], $url,  $callback, $requestInterceptor, $alias);
        return $this;
    }

    /**
     * @param string $url
     * @param Closure|array $callback
     * @param array $requestInterceptor
     * @param string $alias
     * @return $this
     */
    public function delete(string $url, Closure|array $callback, array $requestInterceptor = [], string $alias = ''): static
    {
        $this->resolveRouteSettings([RequestMethods::REQUEST_TYPE_DELETE], $url,  $callback, $requestInterceptor, $alias);
        return $this;
    }

    /**
     * @param string $url
     * @param Closure|array $callback
     * @param array $requestInterceptor
     * @param string $alias
     * @return $this
     */
    public function patch(string $url, Closure|array $callback, array $requestInterceptor = [], string $alias = ''): static
    {
        $this->resolveRouteSettings([RequestMethods::REQUEST_TYPE_PATCH], $url,  $callback, $requestInterceptor, $alias);
        return $this;
    }

    /**
     * @param array $method
     * @param string $url
     * @param \Closure|array $callback
     * @param array $requestInterceptor
     * @param string $alias
     * @return Route
     * @throws \Exception
     */
    public function match(array $method, string $url, \Closure|array $callback, array $requestInterceptor = [], string $alias = ''): static
    {
        $method = array_flip(array_change_key_case(array_flip($method), CASE_UPPER));
        # If $method contains methods not recognize by Request::$requestMethods
        if (array_diff($method, RequestMethods::$requestMethods)) {
            throw new \Exception("$url contains an unknown method");
        }
        $this->resolveRouteSettings($method, $url, $callback, $requestInterceptor, $alias);
        return $this;
    }

    /**
     * @param Closure $callback
     * @return bool
     * @throws \ReflectionException
     */
    protected function isCallBackRouteGroup(Closure $callback): bool
    {
        $param = new \ReflectionFunction($callback);
        if ($param->getNumberOfParameters() !== 1){
            return false;
        }
        $param = $param->getParameters()[0]->getType()->getName();

        return get_class($this) === $param;
    }

    /**
     * @param $callable
     * @return bool
     */
    protected function isClosure($callable): bool
    {
        return $callable instanceof Closure;
    }

    /**
     * @param $callback
     * @return array
     */
    private function generateRouteSettings($callback): array
    {

        if ($this->isClosure($callback)) {
            return ['Class' => null, 'Callback' => $callback, 'RequestInterceptors' => $this->groupRequestInterceptor];
        }

        if (is_array($callback)) {
            if (count($callback) !== 2) {
                throw new InvalidArgumentException("Invalid Route Arguments");
            }
            return ['Class' => $callback[0], 'Callback' => $callback[1], 'RequestInterceptors' => $this->groupRequestInterceptor];
        }

        return [];
    }

    /**
     * @param array $methods
     * @param $url
     * @param $callback
     * @param array $requestInterceptors
     * @param string $alias
     * @param null $moreSettings
     */
    private function resolveRouteSettings(array $methods, $url, $callback, array $requestInterceptors, string $alias = '', $moreSettings = null):void
    {
        $alias = trim($alias, '.');
        $settings = $this->generateRouteSettings($callback);
        $url =  trim(implode('', $this->groupPrefix) . $url, '/');
        // the preg_replace change multiple slashes to one
        $url = preg_replace("#//+#", "\\1/", $url);

        ## Alias would only be applied if route has an alios, it won't be applied if its only given to group
        if ($alias){
            $alias = implode('.', $this->groupAlias) . '.' . $alias;
            $alias = preg_replace("#\.\.+#", ".", $alias);
            $alias = trim($alias, '.');
        }

        if (!empty($settings)){
            foreach ($requestInterceptors as $requestInterceptor){
                array_unshift($settings['RequestInterceptors'], $requestInterceptor);
               // $settings['RequestInterceptors'][] = $requestInterceptor;
            }

            ## Convert $requestInterceptors multi-dimensional array to array
            $requestInterceptors = [];
            foreach ($settings['RequestInterceptors'] as $requestInterceptor){
                if (is_array($requestInterceptor)){
                    array_push($requestInterceptors, ...$requestInterceptor);
                    continue;
                }
                $requestInterceptors[] = $requestInterceptor;
            }

            $routeSettings = [
                'url' => $url,
                'methods' => $methods,
                'requestInterceptors' =>  $requestInterceptors,
                'class' => $settings['Class'],
                'callback' => $settings['Callback'],
                'alias' => $alias,
                'moreSettings' => $moreSettings
            ];

           $this->routeTreeGenerator->initRouteTreeGeneratorState($routeSettings);
        }

    }

    /**
     * @return RouteTreeGenerator
     */
    public function getRouteTreeGenerator(): RouteTreeGenerator
    {
        return $this->routeTreeGenerator;
    }
}