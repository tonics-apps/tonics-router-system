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

//$commandLine = $this->commandLine();
// Disable Monkey Patching
//$commandLine->commandLine()->set('include', []);

Filters::apply($this, 'run', function($next) {
    $scope = $this->suite()->root()->scope(); // The top most describe scope.

    ## Router And Request
    $routeTreeGeneratorState = new RouteTreeGeneratorState();
    $routeNode = new RouteNode();
    $onRequestProcess = new OnRequestProcess(new RouteResolver(new Container()), new Route(new RouteTreeGenerator($routeTreeGeneratorState, $routeNode)));

    $router = new Router($onRequestProcess,
        $onRequestProcess->getRouteObject(),
        new Response($onRequestProcess, new RequestInput()));
    $scope->router = $router;
    return $next();
});