<?php

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