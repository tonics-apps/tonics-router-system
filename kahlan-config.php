<?php


use Devsrealm\TonicsRouterSystem\Container\Container;
use Devsrealm\TonicsRouterSystem\Events\OnRequestProcess;
use Devsrealm\TonicsRouterSystem\Handler\Router;
use Devsrealm\TonicsRouterSystem\RequestInput;
use Devsrealm\TonicsRouterSystem\Resolver\RouteResolver;
use Devsrealm\TonicsRouterSystem\Response;
use Devsrealm\TonicsRouterSystem\Route;
use Devsrealm\TonicsRouterSystem\RouteNode;
use Devsrealm\TonicsRouterSystem\RouteTreeGenerator;
use Devsrealm\TonicsRouterSystem\State\RouteTreeGeneratorState;
use Kahlan\Filter\Filters;

Filters::apply($this, 'run', function($next) {
    $scope = $this->suite()->root()->scope(); // The top most describe scope.

    class RouteSetup {

        public function wireRouter(): Router
        {
            ## Router And Request
            $routeTreeGeneratorState = new RouteTreeGeneratorState();
            $routeNode = new RouteNode();
            $onRequestProcess = new OnRequestProcess(new RouteResolver(new Container()), new Route(new RouteTreeGenerator($routeTreeGeneratorState, $routeNode)));

            return new Router($onRequestProcess,
                $onRequestProcess->getRouteObject(),
                new Response($onRequestProcess, new RequestInput()));
        }
    }

    $routeSetup = new RouteSetup();
    $scope->router = $routeSetup;
    return $next();
});