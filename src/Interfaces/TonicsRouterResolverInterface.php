<?php

namespace Devsrealm\TonicsRouterSystem\Interfaces;

interface TonicsRouterResolverInterface
{
    /**
     * @param string $class
     * @return mixed
     */
    public function resolveClass(string $class): mixed;

    /**
     * @param $class
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function resolveThroughClassMethod($class, string $method, array $parameters): mixed;


    /**
     * @param callable $closure
     * @param array $parameters
     * @return mixed
     */
    public function resolveThroughClosure(Callable $closure, array $parameters): mixed;

}