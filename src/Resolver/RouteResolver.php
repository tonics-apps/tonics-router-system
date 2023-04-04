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

namespace Devsrealm\TonicsRouterSystem\Resolver;

use Devsrealm\TonicsRouterSystem\Container\Container;
use Devsrealm\TonicsRouterSystem\Interfaces\TonicsRouterResolverInterface;
use Exception;

class RouteResolver implements TonicsRouterResolverInterface
{

    private ?Container $container = null;

    public function __construct(Container $container = null)
    {
        if ($container){
            $this->container = $container;
        }
    }

    /**
     * @throws Exception
     */
    public function resolveClass(string $class): mixed
    {

        if (class_exists($class) === false) {
            throw new Exception("Class $class does not exist", 404);
        }

        try {
            return $this->container->get($class);
        } catch (\Exception $e) {
            throw new Exception($e->getMessage(), (int)$e->getCode(), $e->getPrevious());
        }
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function resolveThroughClassMethod($class, string $method, array $parameters): mixed
    {
        try {
            return $this->container->call([$class, $method], $parameters, true);
        } catch (\Exception $e) {
            throw new Exception($e->getMessage(), (int)$e->getCode(), $e->getPrevious());
        }
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function resolveThroughClosure(callable $closure, array $parameters): mixed
    {
        try {
            return $this->getContainer()->call($closure, $parameters);
        } catch (\Exception $e) {
            throw new Exception($e->getMessage(), (int)$e->getCode(), $e->getPrevious());
        }
    }

    /**
     * @return Container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }
}