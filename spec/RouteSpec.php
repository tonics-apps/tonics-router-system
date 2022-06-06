<?php

use Devsrealm\TonicsRouterSystem\Events\OnRequestProcess;
use Devsrealm\TonicsRouterSystem\Exceptions\URLNotFound;
use Devsrealm\TonicsRouterSystem\Handler\Router;
use Devsrealm\TonicsRouterSystem\Interfaces\TonicsRouterRequestInterceptorInterface;
use Devsrealm\TonicsRouterSystem\Route;

class FamilyTreeIntercept implements TonicsRouterRequestInterceptorInterface
{

    public function handle(OnRequestProcess $request): void
    {
        // TODO: Implement handle() method.
    }
}

class YouIntercept implements TonicsRouterRequestInterceptorInterface
{

    public function handle(OnRequestProcess $request): void
    {
        // TODO: Implement handle() method.
    }
}

class SiblingOne implements TonicsRouterRequestInterceptorInterface
{

    public function handle(OnRequestProcess $request): void
    {
        // TODO: Implement handle() method.
    }
}

class SiblingTwo implements TonicsRouterRequestInterceptorInterface
{

    public function handle(OnRequestProcess $request): void
    {
        // TODO: Implement handle() method.
    }
}

class FatherIntercept implements TonicsRouterRequestInterceptorInterface
{

    public function handle(OnRequestProcess $request): void
    {
        // TODO: Implement handle() method.
    }
}

class MotherIntercept implements TonicsRouterRequestInterceptorInterface
{

    public function handle(OnRequestProcess $request): void
    {
        // TODO: Implement handle() method.
    }
}

describe( "Route", function () {

    /*** @var Router $router */
    $router = $this->router;
    /*** @var Route $route */
    $route = $this->router->getRoute();

    ## A SIMPLE FAMILY TREE
    $route->group('/', function (Route $route){

        $route->group('you', function (Route $route){

            $route->group('father', function (Route $route){
                $route->group('paternal-grandmother', function (Route $route){

                    $route->get('paternal-grandmothers-mother', function (){
                        return "this is paternal grandmothers mother";
                    });

                    $route->post('paternal-grandmothers-father', function (){
                        return "this is paternal grandmothers father";
                    });
                });

                $route->group('paternal-grandfather', function (Route $route){

                    $route->get('paternal-grandfathers-mother', function (){
                        return "this is paternal grandfathers mother";
                    });

                    $route->post('paternal-grandfathers-father', function (){
                        return "this is paternal grandfathers father";
                    });

                });
            }, [FatherIntercept::class]);

            $route->group('mother', function (Route $route){
                $route->group('maternal-grandmother', function (Route $route){

                    $route->get('maternal-grandmothers-mother', function (){
                        return "this is maternal grandmothers mother";
                    });

                    $route->post('maternal-grandmothers-father', function (){
                        return "this is maternal grandmothers father";
                    });
                });

                $route->group('maternal-grandfather', function (Route $route){

                    $route->get('maternal-grandfathers-mother', function (){
                        return "this is maternal grandfathers mother";
                    });

                    $route->post('maternal-grandfathers-father', function (){
                        return "this is maternal grandfathers father";
                    });

                });
            }, [MotherIntercept::class]);
        }, [YouIntercept::class]);
        $route->get('sibling-1', function (){
            return 'this is sibling 1';
        }, [SiblingOne::class]);
        $route->post('sibling-2', function (){
            return 'this is sibling 2';
        }, [SiblingTwo::class]);
    }, [FamilyTreeIntercept::class]);

    describe("->dispatchRequestURL(); for valid urls", function () use ($router) {

        $url = "/sibling-1"; $method = 'GET';
        context("if url is $url ", function () use ($method, $url, $router) {
            it("should return this is sibling 1", function () use ($url, $method, $router) {
                $router->dispatchRequestURL($url, $method, true);
                expect($router->getDispatchResult())->toEqual("this is sibling 1");
            });

            it("should have a valid requestInterceptor in accordance with the route tree", function () use ($url, $method, $router) {
                $interceptor = $router->getRoute()->getRouteTreeGenerator()->getFoundURLNode()->getRequestInterceptors($method);
                expect($interceptor)->toBe([FamilyTreeIntercept::class, SiblingOne::class]);
            });
        });


        $url = "/sibling-2"; $method = 'POST';
        context("if url is $url ", function () use ($method, $url, $router) {
            it("should return this is sibling 2", function () use ($url, $method, $router) {
                $router->dispatchRequestURL($url, $method, true);
                expect($router->getDispatchResult())->toEqual("this is sibling 2");
            });

            it("should have a valid requestInterceptor in accordance with the route tree", function () use ($url, $method, $router) {
                $interceptor = $router->getRoute()->getRouteTreeGenerator()->getFoundURLNode()->getRequestInterceptors($method);
                expect($interceptor)->toBe([FamilyTreeIntercept::class, SiblingTwo::class]);
            });
        });

        ##
        ## FOR FATHER
        ##
        $return = "this is paternal grandmothers mother";
        $url = "/you/father/paternal-grandmother/paternal-grandmothers-mother";
        $method = "GET";
        context("if url is $url and method is $method", function () use ($method, $url, $return, $router) {
            it("should return $return", function () use ($method, $url, $return, $router) {
                $router->dispatchRequestURL($url, $method, true);
                expect($router->getDispatchResult())->toEqual($return);
            });

            it("should have a valid requestInterceptor in accordance with the route tree", function () use ($url, $method, $router) {
                $interceptor = $router->getRoute()->getRouteTreeGenerator()->getFoundURLNode()->getRequestInterceptors($method);
                expect($interceptor)->toBe([FamilyTreeIntercept::class, YouIntercept::class, FatherIntercept::class]);
            });
        });

        $return = "this is paternal grandmothers father";
        $url = "/you/father/paternal-grandmother/paternal-grandmothers-father";
        $method = "POST";
        context("if url is $url and method is $method", function () use ($method, $url, $return, $router) {
            it("should return $return", function () use ($method, $url, $return, $router) {
                $router->dispatchRequestURL($url, $method, true);
                expect($router->getDispatchResult())->toEqual($return);
            });

            it("should have a valid requestInterceptor in accordance with the route tree", function () use ($url, $method, $router) {
                $interceptor = $router->getRoute()->getRouteTreeGenerator()->getFoundURLNode()->getRequestInterceptors($method);
                expect($interceptor)->toBe([FamilyTreeIntercept::class, YouIntercept::class, FatherIntercept::class]);
            });
        });


        $return = "this is paternal grandfathers mother";
        $url = "/you/father/paternal-grandfather/paternal-grandfathers-mother";
        $method = "GET";
        context("if url is $url and method is $method", function () use ($method, $url, $return, $router) {
            it("should return $return", function () use ($method, $url, $return, $router) {
                $router->dispatchRequestURL($url, $method, true);
                expect($router->getDispatchResult())->toEqual($return);
            });

            it("should have a valid requestInterceptor in accordance with the route tree", function () use ($url, $method, $router) {
                $interceptor = $router->getRoute()->getRouteTreeGenerator()->getFoundURLNode()->getRequestInterceptors($method);
                expect($interceptor)->toBe([FamilyTreeIntercept::class, YouIntercept::class, FatherIntercept::class]);
            });
        });

        $return = "this is paternal grandfathers father";
        $url = "/you/father/paternal-grandfather/paternal-grandfathers-father";
        $method = "POST";
        context("if url is $url and method is $method", function () use ($method, $url, $return, $router) {
            it("should return $return", function () use ($method, $url, $return, $router) {
                $router->dispatchRequestURL($url, $method, true);
                expect($router->getDispatchResult())->toEqual($return);
            });

            it("should have a valid requestInterceptor in accordance with the route tree", function () use ($url, $method, $router) {
                $interceptor = $router->getRoute()->getRouteTreeGenerator()->getFoundURLNode()->getRequestInterceptors($method);
                expect($interceptor)->toBe([FamilyTreeIntercept::class, YouIntercept::class, FatherIntercept::class]);
            });
        });


        ##
        ## FOR MOTHER
        ##
        $return = "this is maternal grandmothers mother";
        $url = "/you/mother/maternal-grandmother/maternal-grandmothers-mother";
        $method = "GET";
        context("if url is $url and method is $method", function () use ($method, $url, $return, $router) {
            it("should return $return", function () use ($method, $url, $return, $router) {
                $router->dispatchRequestURL($url, $method, true);
                expect($router->getDispatchResult())->toEqual($return);
            });

            it("should have a valid requestInterceptor in accordance with the route tree", function () use ($url, $method, $router) {
                $interceptor = $router->getRoute()->getRouteTreeGenerator()->getFoundURLNode()->getRequestInterceptors($method);
                expect($interceptor)->toBe([FamilyTreeIntercept::class, YouIntercept::class, MotherIntercept::class]);
            });
        });

        $return = "this is maternal grandmothers father";
        $url = "/you/mother/maternal-grandmother/maternal-grandmothers-father";
        $method = "POST";
        context("if url is $url and method is $method", function () use ($method, $url, $return, $router) {
            it("should return $return", function () use ($method, $url, $return, $router) {
                $router->dispatchRequestURL($url, $method, true);
                expect($router->getDispatchResult())->toEqual($return);
            });

            it("should have a valid requestInterceptor in accordance with the route tree", function () use ($url, $method, $router) {
                $interceptor = $router->getRoute()->getRouteTreeGenerator()->getFoundURLNode()->getRequestInterceptors($method);
                expect($interceptor)->toBe([FamilyTreeIntercept::class, YouIntercept::class, MotherIntercept::class]);
            });
        });


        $return = "this is maternal grandfathers mother";
        $url = "/you/mother/maternal-grandfather/maternal-grandfathers-mother";
        $method = "GET";
        context("if url is $url and method is $method", function () use ($method, $url, $return, $router) {
            it("should return $return", function () use ($method, $url, $return, $router) {
                $router->dispatchRequestURL($url, $method, true);
                expect($router->getDispatchResult())->toEqual($return);
            });

            it("should have a valid requestInterceptor in accordance with the route tree", function () use ($url, $method, $router) {
                $interceptor = $router->getRoute()->getRouteTreeGenerator()->getFoundURLNode()->getRequestInterceptors($method);
                expect($interceptor)->toBe([FamilyTreeIntercept::class, YouIntercept::class, MotherIntercept::class]);
            });
        });

        $return = "this is maternal grandfathers father";
        $url = "/you/mother/maternal-grandfather/maternal-grandfathers-father";
        $method = "POST";
        context("if url is $url and method is $method", function () use ($method, $url, $return, $router) {
            it("should return $return", function () use ($method, $url, $return, $router) {
                $router->dispatchRequestURL($url, $method, true);
                expect($router->getDispatchResult())->toEqual($return);
            });

            it("should have a valid requestInterceptor in accordance with the route tree", function () use ($url, $method, $router) {
                $interceptor = $router->getRoute()->getRouteTreeGenerator()->getFoundURLNode()->getRequestInterceptors($method);
                expect($interceptor)->toBe([FamilyTreeIntercept::class, YouIntercept::class, MotherIntercept::class]);
            });
        });
    });

    describe("->dispatchRequestURL(); for invalid urls", function () use ($router) {

        $url = "/olayemi";
        $method = "PUT";
        context("if url is $url and method is $method ", function () use ($url, $method, $router) {
            it("should throw URLNotFound", function () use ($url, $method, $router) {
                $closure = function () use ($url, $method, $router) {
                    $router->dispatchRequestURL($url, $method);
                };
                expect($closure)->toThrow(new URLNotFound("Page Not Found 🙄", 404));
            });
        });

        $url = "/olayemi";
        $method = "POST";
        context("if url is $url and method is $method ", function () use ($url, $method, $router) {
            it("should throw URLNotFound", function () use ($url, $method, $router) {
                $closure = function () use ($url, $method, $router) {
                    $router->dispatchRequestURL($url, $method);
                };
                expect($closure)->toThrow(new URLNotFound("Page Not Found 🙄", 404));
            });
        });

        $url = "/nan/nan/nan/nan/nan";
        $method = "GET";
        context("if url is $url and method is $method ", function () use ($url, $method, $router) {
            it("should throw URLNotFound", function () use ($url, $method, $router) {
                $closure = function () use ($url, $method, $router) {
                    $router->dispatchRequestURL($url, $method);
                };
                expect($closure)->toThrow(new URLNotFound("Page Not Found 🙄", 404));
            });
        });
    });


});