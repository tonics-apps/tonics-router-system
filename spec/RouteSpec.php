<?php

use Devsrealm\TonicsRouterSystem\Events\OnRequestProcess;
use Devsrealm\TonicsRouterSystem\Exceptions\URLNotFound;
use Devsrealm\TonicsRouterSystem\Handler\Router;
use Devsrealm\TonicsRouterSystem\Interfaces\TonicsRouterRequestInterceptorInterface;
use Devsrealm\TonicsRouterSystem\Route;

class FamilyTreeIntercept implements TonicsRouterRequestInterceptorInterface
{

    public function handle(OnRequestProcess $request): void {}
}

class YouIntercept implements TonicsRouterRequestInterceptorInterface
{

    public function handle(OnRequestProcess $request): void {}
}

class SiblingOne implements TonicsRouterRequestInterceptorInterface
{

    public function handle(OnRequestProcess $request): void {}
}

class SiblingTwo implements TonicsRouterRequestInterceptorInterface
{

    public function handle(OnRequestProcess $request): void {}
}

class FatherIntercept implements TonicsRouterRequestInterceptorInterface
{

    public function handle(OnRequestProcess $request): void {}
}

class MotherIntercept implements TonicsRouterRequestInterceptorInterface
{

    public function handle(OnRequestProcess $request): void {}
}

describe( "Route Simple", function () {

    /*** @var Router $router */
    $router = $this->router->wireRouter();
    $route = $router->getRoute();

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

describe( "Routes", function () {

    /*** @var Router $router */
    $router = $this->router->wireRouter();
    $route = $router->getRoute();
    $routeTree = $route->getRouteTreeGenerator();

    defineRoutes([
        "/a/hi/1/2/3", "/a/hi/1/2/:i",  "/a/hii/1/2/:i",
        "/:i/hi/1/2/:i", "/c/hi/1/2/:i", "/d/hi/1/2/:i",
        "/e/hi/1/2/:i", "/post/:id/:id/:id", "/post/:id/:cat/3",
    ], $route);

    context("Must Match URLs ", function () use ($routeTree) {

        $urls = [
            "/a/hi/1/2/3" => "/a/hi/1/2/3",
            "/ee/hi/1/2/hello" => "/:i/hi/1/2/:i",
            "/e/hi/1/2/hello" => "/e/hi/1/2/:i",
            "/a/hi/1/2/hello" => "/a/hi/1/2/:i",
            "/a/hii/1/2/hello" => "/a/hii/1/2/:i",
            "/d/hi/1/2/hello" => "/d/hi/1/2/:i",
            "/post/hi/hi/3" => "/post/:id/:cat/3",
        ];

        matchURLS($urls, $routeTree);
    });

    context("Must Not Match URLs ", function () use ($routeTree) {
        $urls = [
            "/a/hi/1/2/hello/hello",
            "/a/hi/1/1/hello"
        ];
        mustNotMatchURLAndBe($urls, $routeTree, null);
    });

});

describe( "Routes 2", function () {
    // THis is for testing if the route would work in the teleporting scenario
    /*** @var Router $router */
    $router = $this->router->wireRouter();
    $route = $router->getRoute();
    $routeTree = $route->getRouteTreeGenerator();

    defineRoutes([
        '/r', '/r/:w', '/r/:w/:r'
    ], $route);

    context("Must Match URLs ", function () use ($routeTree) {

        $urls = [
            '/r' => '/r',
            '/r/w' => '/r/:w',
            '/r/w/r' => '/r/:w/:r',
        ];

        matchURLS($urls, $routeTree);
    });

    context("Must Not Match URLs ", function () use ($routeTree) {
        $urls = [
            '/b',
            '/r/w/r/r'
        ];
        mustNotMatchURLAndBe($urls, $routeTree, null);
    });

    

});

describe( "Routes 3", function () {
    /*** @var Router $router */
    $router = $this->router->wireRouter();
    $route = $router->getRoute();
    $routeTree = $route->getRouteTreeGenerator();

    defineRoutes([
        '/c', '/c/:s', '/c/:s/:s/:s/:s', '/c/:s/:s/d/:s',
        '/users/:selected_user', '/users/:selected_user/search/code',
        '/users/:selected_user/ssh-keys', '/users/:selected_user/ssh-keys/:key_id',
    ], $route);

    context("Must Match URLs ", function () use ($routeTree) {

        $urls = [
            "/users/tonics-user/ssh-keys" => "/users/:selected_user/ssh-keys",
            "/users/tonics-user/search/code" => "/users/:selected_user/search/code"
        ];

        matchURLS($urls, $routeTree);

    });

    context("Must Not Match URLs and Be Empty", function () use ($routeTree) {

        $urls = [
            "/c/s/s/d",
            "/c/s/s",
            "/users/tonics-user/search"
        ];

        mustNotMatchURLAndBe($urls, $routeTree, '');
    });
});

describe( "Routes BITBUCKET", function () {
    /*** @var Router $router */
    $router = $this->router->wireRouter();
    $route = $router->getRoute();
    $routeTree = $route->getRouteTreeGenerator();


    context("Must Match URLs ", function () use ($route, $routeTree) {
        $urls = json_decode(<<<JSO
{"\/addon":"\/addon","\/addon\/linkers":"\/addon\/linkers","\/addon\/linkers\/tonics-linker_key":"\/addon\/linkers\/:linker_key","\/addon\/linkers\/tonics-linker_key\/values":"\/addon\/linkers\/:linker_key\/values","\/addon\/linkers\/tonics-linker_key\/values\/tonics-value_id":"\/addon\/linkers\/:linker_key\/values\/:value_id","\/hook_events":"\/hook_events","\/hook_events\/tonics-subject_type":"\/hook_events\/:subject_type","\/pullrequests\/tonics-selected_user":"\/pullrequests\/:selected_user","\/repositories":"\/repositories","\/repositories\/tonics-workspace":"\/repositories\/:workspace","\/repositories\/tonics-workspace\/tonics-repo_slug":"\/repositories\/:workspace\/:repo_slug","\/repositories\/tonics-workspace\/tonics-repo_slug\/branch-restrictions":"\/repositories\/:workspace\/:repo_slug\/branch-restrictions","\/repositories\/tonics-workspace\/tonics-repo_slug\/branch-restrictions\/tonics-id":"\/repositories\/:workspace\/:repo_slug\/branch-restrictions\/:id","\/repositories\/tonics-workspace\/tonics-repo_slug\/branching-model":"\/repositories\/:workspace\/:repo_slug\/branching-model","\/repositories\/tonics-workspace\/tonics-repo_slug\/branching-model\/settings":"\/repositories\/:workspace\/:repo_slug\/branching-model\/settings","\/repositories\/tonics-workspace\/tonics-repo_slug\/commit\/tonics-commit":"\/repositories\/:workspace\/:repo_slug\/commit\/:commit","\/repositories\/tonics-workspace\/tonics-repo_slug\/commit\/tonics-commit\/approve":"\/repositories\/:workspace\/:repo_slug\/commit\/:commit\/approve","\/repositories\/tonics-workspace\/tonics-repo_slug\/commit\/tonics-commit\/comments":"\/repositories\/:workspace\/:repo_slug\/commit\/:commit\/comments","\/repositories\/tonics-workspace\/tonics-repo_slug\/commit\/tonics-commit\/comments\/tonics-comment_id":"\/repositories\/:workspace\/:repo_slug\/commit\/:commit\/comments\/:comment_id","\/repositories\/tonics-workspace\/tonics-repo_slug\/commit\/tonics-commit\/properties\/tonics-app_key\/tonics-property_name":"\/repositories\/:workspace\/:repo_slug\/commit\/:commit\/properties\/:app_key\/:property_name","\/repositories\/tonics-workspace\/tonics-repo_slug\/commit\/tonics-commit\/pullrequests":"\/repositories\/:workspace\/:repo_slug\/commit\/:commit\/pullrequests","\/repositories\/tonics-workspace\/tonics-repo_slug\/commit\/tonics-commit\/reports":"\/repositories\/:workspace\/:repo_slug\/commit\/:commit\/reports","\/repositories\/tonics-workspace\/tonics-repo_slug\/commit\/tonics-commit\/reports\/tonics-reportId":"\/repositories\/:workspace\/:repo_slug\/commit\/:commit\/reports\/:reportId","\/repositories\/tonics-workspace\/tonics-repo_slug\/commit\/tonics-commit\/reports\/tonics-reportId\/annotations":"\/repositories\/:workspace\/:repo_slug\/commit\/:commit\/reports\/:reportId\/annotations","\/repositories\/tonics-workspace\/tonics-repo_slug\/commit\/tonics-commit\/reports\/tonics-reportId\/annotations\/tonics-annotationId":"\/repositories\/:workspace\/:repo_slug\/commit\/:commit\/reports\/:reportId\/annotations\/:annotationId","\/repositories\/tonics-workspace\/tonics-repo_slug\/commit\/tonics-commit\/statuses":"\/repositories\/:workspace\/:repo_slug\/commit\/:commit\/statuses","\/repositories\/tonics-workspace\/tonics-repo_slug\/commit\/tonics-commit\/statuses\/build":"\/repositories\/:workspace\/:repo_slug\/commit\/:commit\/statuses\/build","\/repositories\/tonics-workspace\/tonics-repo_slug\/commit\/tonics-commit\/statuses\/build\/tonics-key":"\/repositories\/:workspace\/:repo_slug\/commit\/:commit\/statuses\/build\/:key","\/repositories\/tonics-workspace\/tonics-repo_slug\/commits":"\/repositories\/:workspace\/:repo_slug\/commits","\/repositories\/tonics-workspace\/tonics-repo_slug\/commits\/tonics-revision":"\/repositories\/:workspace\/:repo_slug\/commits\/:revision","\/repositories\/tonics-workspace\/tonics-repo_slug\/components":"\/repositories\/:workspace\/:repo_slug\/components","\/repositories\/tonics-workspace\/tonics-repo_slug\/components\/tonics-component_id":"\/repositories\/:workspace\/:repo_slug\/components\/:component_id","\/repositories\/tonics-workspace\/tonics-repo_slug\/default-reviewers":"\/repositories\/:workspace\/:repo_slug\/default-reviewers","\/repositories\/tonics-workspace\/tonics-repo_slug\/default-reviewers\/tonics-target_username":"\/repositories\/:workspace\/:repo_slug\/default-reviewers\/:target_username","\/repositories\/tonics-workspace\/tonics-repo_slug\/deploy-keys":"\/repositories\/:workspace\/:repo_slug\/deploy-keys","\/repositories\/tonics-workspace\/tonics-repo_slug\/deploy-keys\/tonics-key_id":"\/repositories\/:workspace\/:repo_slug\/deploy-keys\/:key_id","\/repositories\/tonics-workspace\/tonics-repo_slug\/deployments":"\/repositories\/:workspace\/:repo_slug\/deployments","\/repositories\/tonics-workspace\/tonics-repo_slug\/deployments\/tonics-deployment_uuid":"\/repositories\/:workspace\/:repo_slug\/deployments\/:deployment_uuid","\/repositories\/tonics-workspace\/tonics-repo_slug\/deployments_config\/environments\/tonics-environment_uuid\/variables":"\/repositories\/:workspace\/:repo_slug\/deployments_config\/environments\/:environment_uuid\/variables","\/repositories\/tonics-workspace\/tonics-repo_slug\/deployments_config\/environments\/tonics-environment_uuid\/variables\/tonics-variable_uuid":"\/repositories\/:workspace\/:repo_slug\/deployments_config\/environments\/:environment_uuid\/variables\/:variable_uuid","\/repositories\/tonics-workspace\/tonics-repo_slug\/diff\/tonics-spec":"\/repositories\/:workspace\/:repo_slug\/diff\/:spec","\/repositories\/tonics-workspace\/tonics-repo_slug\/diffstat\/tonics-spec":"\/repositories\/:workspace\/:repo_slug\/diffstat\/:spec","\/repositories\/tonics-workspace\/tonics-repo_slug\/downloads":"\/repositories\/:workspace\/:repo_slug\/downloads","\/repositories\/tonics-workspace\/tonics-repo_slug\/downloads\/tonics-filename":"\/repositories\/:workspace\/:repo_slug\/downloads\/:filename","\/repositories\/tonics-workspace\/tonics-repo_slug\/environments":"\/repositories\/:workspace\/:repo_slug\/environments","\/repositories\/tonics-workspace\/tonics-repo_slug\/environments\/tonics-environment_uuid":"\/repositories\/:workspace\/:repo_slug\/environments\/:environment_uuid","\/repositories\/tonics-workspace\/tonics-repo_slug\/environments\/tonics-environment_uuid\/changes":"\/repositories\/:workspace\/:repo_slug\/environments\/:environment_uuid\/changes","\/repositories\/tonics-workspace\/tonics-repo_slug\/filehistory\/tonics-commit\/tonics-path":"\/repositories\/:workspace\/:repo_slug\/filehistory\/:commit\/:path","\/repositories\/tonics-workspace\/tonics-repo_slug\/forks":"\/repositories\/:workspace\/:repo_slug\/forks","\/repositories\/tonics-workspace\/tonics-repo_slug\/hooks":"\/repositories\/:workspace\/:repo_slug\/hooks","\/repositories\/tonics-workspace\/tonics-repo_slug\/hooks\/tonics-uid":"\/repositories\/:workspace\/:repo_slug\/hooks\/:uid","\/repositories\/tonics-workspace\/tonics-repo_slug\/issues":"\/repositories\/:workspace\/:repo_slug\/issues","\/repositories\/tonics-workspace\/tonics-repo_slug\/issues\/export":"\/repositories\/:workspace\/:repo_slug\/issues\/export","\/repositories\/tonics-workspace\/tonics-repo_slug\/issues\/export\/tonics-repo_name-issues-tonics-task_id.zip":"\/repositories\/:workspace\/:repo_slug\/issues\/export\/:repo_name-issues-task_id.zip","\/repositories\/tonics-workspace\/tonics-repo_slug\/issues\/import":"\/repositories\/:workspace\/:repo_slug\/issues\/import","\/repositories\/tonics-workspace\/tonics-repo_slug\/issues\/tonics-issue_id":"\/repositories\/:workspace\/:repo_slug\/issues\/:issue_id","\/repositories\/tonics-workspace\/tonics-repo_slug\/issues\/tonics-issue_id\/attachments":"\/repositories\/:workspace\/:repo_slug\/issues\/:issue_id\/attachments","\/repositories\/tonics-workspace\/tonics-repo_slug\/issues\/tonics-issue_id\/attachments\/tonics-path":"\/repositories\/:workspace\/:repo_slug\/issues\/:issue_id\/attachments\/:path","\/repositories\/tonics-workspace\/tonics-repo_slug\/issues\/tonics-issue_id\/changes":"\/repositories\/:workspace\/:repo_slug\/issues\/:issue_id\/changes","\/repositories\/tonics-workspace\/tonics-repo_slug\/issues\/tonics-issue_id\/changes\/tonics-change_id":"\/repositories\/:workspace\/:repo_slug\/issues\/:issue_id\/changes\/:change_id","\/repositories\/tonics-workspace\/tonics-repo_slug\/issues\/tonics-issue_id\/comments":"\/repositories\/:workspace\/:repo_slug\/issues\/:issue_id\/comments","\/repositories\/tonics-workspace\/tonics-repo_slug\/issues\/tonics-issue_id\/comments\/tonics-comment_id":"\/repositories\/:workspace\/:repo_slug\/issues\/:issue_id\/comments\/:comment_id","\/repositories\/tonics-workspace\/tonics-repo_slug\/issues\/tonics-issue_id\/vote":"\/repositories\/:workspace\/:repo_slug\/issues\/:issue_id\/vote","\/repositories\/tonics-workspace\/tonics-repo_slug\/issues\/tonics-issue_id\/watch":"\/repositories\/:workspace\/:repo_slug\/issues\/:issue_id\/watch","\/repositories\/tonics-workspace\/tonics-repo_slug\/merge-base\/tonics-revspec":"\/repositories\/:workspace\/:repo_slug\/merge-base\/:revspec","\/repositories\/tonics-workspace\/tonics-repo_slug\/milestones":"\/repositories\/:workspace\/:repo_slug\/milestones","\/repositories\/tonics-workspace\/tonics-repo_slug\/milestones\/tonics-milestone_id":"\/repositories\/:workspace\/:repo_slug\/milestones\/:milestone_id","\/repositories\/tonics-workspace\/tonics-repo_slug\/patch\/tonics-spec":"\/repositories\/:workspace\/:repo_slug\/patch\/:spec","\/repositories\/tonics-workspace\/tonics-repo_slug\/pipelines-config\/caches":"\/repositories\/:workspace\/:repo_slug\/pipelines-config\/caches","\/repositories\/tonics-workspace\/tonics-repo_slug\/pipelines-config\/caches\/tonics-cache_uuid":"\/repositories\/:workspace\/:repo_slug\/pipelines-config\/caches\/:cache_uuid","\/repositories\/tonics-workspace\/tonics-repo_slug\/pipelines-config\/caches\/tonics-cache_uuid\/content-uri":"\/repositories\/:workspace\/:repo_slug\/pipelines-config\/caches\/:cache_uuid\/content-uri","\/repositories\/tonics-workspace\/tonics-repo_slug\/pipelines":"\/repositories\/:workspace\/:repo_slug\/pipelines","\/repositories\/tonics-workspace\/tonics-repo_slug\/pipelines\/tonics-pipeline_uuid":"\/repositories\/:workspace\/:repo_slug\/pipelines\/:pipeline_uuid","\/repositories\/tonics-workspace\/tonics-repo_slug\/pipelines\/tonics-pipeline_uuid\/steps":"\/repositories\/:workspace\/:repo_slug\/pipelines\/:pipeline_uuid\/steps","\/repositories\/tonics-workspace\/tonics-repo_slug\/pipelines\/tonics-pipeline_uuid\/steps\/tonics-step_uuid":"\/repositories\/:workspace\/:repo_slug\/pipelines\/:pipeline_uuid\/steps\/:step_uuid","\/repositories\/tonics-workspace\/tonics-repo_slug\/pipelines\/tonics-pipeline_uuid\/steps\/tonics-step_uuid\/log":"\/repositories\/:workspace\/:repo_slug\/pipelines\/:pipeline_uuid\/steps\/:step_uuid\/log","\/repositories\/tonics-workspace\/tonics-repo_slug\/pipelines\/tonics-pipeline_uuid\/steps\/tonics-step_uuid\/logs\/tonics-log_uuid":"\/repositories\/:workspace\/:repo_slug\/pipelines\/:pipeline_uuid\/steps\/:step_uuid\/logs\/:log_uuid","\/repositories\/tonics-workspace\/tonics-repo_slug\/pipelines\/tonics-pipeline_uuid\/steps\/tonics-step_uuid\/test_reports":"\/repositories\/:workspace\/:repo_slug\/pipelines\/:pipeline_uuid\/steps\/:step_uuid\/test_reports","\/repositories\/tonics-workspace\/tonics-repo_slug\/pipelines\/tonics-pipeline_uuid\/steps\/tonics-step_uuid\/test_reports\/test_cases":"\/repositories\/:workspace\/:repo_slug\/pipelines\/:pipeline_uuid\/steps\/:step_uuid\/test_reports\/test_cases","\/repositories\/tonics-workspace\/tonics-repo_slug\/pipelines\/tonics-pipeline_uuid\/steps\/tonics-step_uuid\/test_reports\/test_cases\/tonics-test_case_uuid\/test_case_reasons":"\/repositories\/:workspace\/:repo_slug\/pipelines\/:pipeline_uuid\/steps\/:step_uuid\/test_reports\/test_cases\/:test_case_uuid\/test_case_reasons","\/repositories\/tonics-workspace\/tonics-repo_slug\/pipelines\/tonics-pipeline_uuid\/stopPipeline":"\/repositories\/:workspace\/:repo_slug\/pipelines\/:pipeline_uuid\/stopPipeline","\/repositories\/tonics-workspace\/tonics-repo_slug\/pipelines_config":"\/repositories\/:workspace\/:repo_slug\/pipelines_config","\/repositories\/tonics-workspace\/tonics-repo_slug\/pipelines_config\/build_number":"\/repositories\/:workspace\/:repo_slug\/pipelines_config\/build_number","\/repositories\/tonics-workspace\/tonics-repo_slug\/pipelines_config\/schedules":"\/repositories\/:workspace\/:repo_slug\/pipelines_config\/schedules","\/repositories\/tonics-workspace\/tonics-repo_slug\/pipelines_config\/schedules\/tonics-schedule_uuid":"\/repositories\/:workspace\/:repo_slug\/pipelines_config\/schedules\/:schedule_uuid","\/repositories\/tonics-workspace\/tonics-repo_slug\/pipelines_config\/schedules\/tonics-schedule_uuid\/executions":"\/repositories\/:workspace\/:repo_slug\/pipelines_config\/schedules\/:schedule_uuid\/executions","\/repositories\/tonics-workspace\/tonics-repo_slug\/pipelines_config\/ssh\/key_pair":"\/repositories\/:workspace\/:repo_slug\/pipelines_config\/ssh\/key_pair","\/repositories\/tonics-workspace\/tonics-repo_slug\/pipelines_config\/ssh\/known_hosts":"\/repositories\/:workspace\/:repo_slug\/pipelines_config\/ssh\/known_hosts","\/repositories\/tonics-workspace\/tonics-repo_slug\/pipelines_config\/ssh\/known_hosts\/tonics-known_host_uuid":"\/repositories\/:workspace\/:repo_slug\/pipelines_config\/ssh\/known_hosts\/:known_host_uuid","\/repositories\/tonics-workspace\/tonics-repo_slug\/pipelines_config\/variables":"\/repositories\/:workspace\/:repo_slug\/pipelines_config\/variables","\/repositories\/tonics-workspace\/tonics-repo_slug\/pipelines_config\/variables\/tonics-variable_uuid":"\/repositories\/:workspace\/:repo_slug\/pipelines_config\/variables\/:variable_uuid","\/repositories\/tonics-workspace\/tonics-repo_slug\/properties\/tonics-app_key\/tonics-property_name":"\/repositories\/:workspace\/:repo_slug\/properties\/:app_key\/:property_name","\/repositories\/tonics-workspace\/tonics-repo_slug\/pullrequests":"\/repositories\/:workspace\/:repo_slug\/pullrequests","\/repositories\/tonics-workspace\/tonics-repo_slug\/pullrequests\/activity":"\/repositories\/:workspace\/:repo_slug\/pullrequests\/activity","\/repositories\/tonics-workspace\/tonics-repo_slug\/pullrequests\/tonics-pull_request_id":"\/repositories\/:workspace\/:repo_slug\/pullrequests\/:pull_request_id","\/repositories\/tonics-workspace\/tonics-repo_slug\/pullrequests\/tonics-pull_request_id\/activity":"\/repositories\/:workspace\/:repo_slug\/pullrequests\/:pull_request_id\/activity","\/repositories\/tonics-workspace\/tonics-repo_slug\/pullrequests\/tonics-pull_request_id\/approve":"\/repositories\/:workspace\/:repo_slug\/pullrequests\/:pull_request_id\/approve","\/repositories\/tonics-workspace\/tonics-repo_slug\/pullrequests\/tonics-pull_request_id\/comments":"\/repositories\/:workspace\/:repo_slug\/pullrequests\/:pull_request_id\/comments","\/repositories\/tonics-workspace\/tonics-repo_slug\/pullrequests\/tonics-pull_request_id\/comments\/tonics-comment_id":"\/repositories\/:workspace\/:repo_slug\/pullrequests\/:pull_request_id\/comments\/:comment_id","\/repositories\/tonics-workspace\/tonics-repo_slug\/pullrequests\/tonics-pull_request_id\/commits":"\/repositories\/:workspace\/:repo_slug\/pullrequests\/:pull_request_id\/commits","\/repositories\/tonics-workspace\/tonics-repo_slug\/pullrequests\/tonics-pull_request_id\/decline":"\/repositories\/:workspace\/:repo_slug\/pullrequests\/:pull_request_id\/decline","\/repositories\/tonics-workspace\/tonics-repo_slug\/pullrequests\/tonics-pull_request_id\/diff":"\/repositories\/:workspace\/:repo_slug\/pullrequests\/:pull_request_id\/diff","\/repositories\/tonics-workspace\/tonics-repo_slug\/pullrequests\/tonics-pull_request_id\/diffstat":"\/repositories\/:workspace\/:repo_slug\/pullrequests\/:pull_request_id\/diffstat","\/repositories\/tonics-workspace\/tonics-repo_slug\/pullrequests\/tonics-pull_request_id\/merge":"\/repositories\/:workspace\/:repo_slug\/pullrequests\/:pull_request_id\/merge","\/repositories\/tonics-workspace\/tonics-repo_slug\/pullrequests\/tonics-pull_request_id\/merge\/task-status\/tonics-task_id":"\/repositories\/:workspace\/:repo_slug\/pullrequests\/:pull_request_id\/merge\/task-status\/:task_id","\/repositories\/tonics-workspace\/tonics-repo_slug\/pullrequests\/tonics-pull_request_id\/patch":"\/repositories\/:workspace\/:repo_slug\/pullrequests\/:pull_request_id\/patch","\/repositories\/tonics-workspace\/tonics-repo_slug\/pullrequests\/tonics-pull_request_id\/request-changes":"\/repositories\/:workspace\/:repo_slug\/pullrequests\/:pull_request_id\/request-changes","\/repositories\/tonics-workspace\/tonics-repo_slug\/pullrequests\/tonics-pull_request_id\/statuses":"\/repositories\/:workspace\/:repo_slug\/pullrequests\/:pull_request_id\/statuses","\/repositories\/tonics-workspace\/tonics-repo_slug\/pullrequests\/tonics-pullrequest_id\/properties\/tonics-app_key\/tonics-property_name":"\/repositories\/:workspace\/:repo_slug\/pullrequests\/:pullrequest_id\/properties\/:app_key\/:property_name","\/repositories\/tonics-workspace\/tonics-repo_slug\/refs":"\/repositories\/:workspace\/:repo_slug\/refs","\/repositories\/tonics-workspace\/tonics-repo_slug\/refs\/branches":"\/repositories\/:workspace\/:repo_slug\/refs\/branches","\/repositories\/tonics-workspace\/tonics-repo_slug\/refs\/branches\/tonics-name":"\/repositories\/:workspace\/:repo_slug\/refs\/branches\/:name","\/repositories\/tonics-workspace\/tonics-repo_slug\/refs\/tags":"\/repositories\/:workspace\/:repo_slug\/refs\/tags","\/repositories\/tonics-workspace\/tonics-repo_slug\/refs\/tags\/tonics-name":"\/repositories\/:workspace\/:repo_slug\/refs\/tags\/:name","\/repositories\/tonics-workspace\/tonics-repo_slug\/src":"\/repositories\/:workspace\/:repo_slug\/src","\/repositories\/tonics-workspace\/tonics-repo_slug\/src\/tonics-commit\/tonics-path":"\/repositories\/:workspace\/:repo_slug\/src\/:commit\/:path","\/repositories\/tonics-workspace\/tonics-repo_slug\/versions":"\/repositories\/:workspace\/:repo_slug\/versions","\/repositories\/tonics-workspace\/tonics-repo_slug\/versions\/tonics-version_id":"\/repositories\/:workspace\/:repo_slug\/versions\/:version_id","\/repositories\/tonics-workspace\/tonics-repo_slug\/watchers":"\/repositories\/:workspace\/:repo_slug\/watchers","\/snippets":"\/snippets","\/snippets\/tonics-workspace":"\/snippets\/:workspace","\/snippets\/tonics-workspace\/tonics-encoded_id":"\/snippets\/:workspace\/:encoded_id","\/snippets\/tonics-workspace\/tonics-encoded_id\/comments":"\/snippets\/:workspace\/:encoded_id\/comments","\/snippets\/tonics-workspace\/tonics-encoded_id\/comments\/tonics-comment_id":"\/snippets\/:workspace\/:encoded_id\/comments\/:comment_id","\/snippets\/tonics-workspace\/tonics-encoded_id\/commits":"\/snippets\/:workspace\/:encoded_id\/commits","\/snippets\/tonics-workspace\/tonics-encoded_id\/commits\/tonics-revision":"\/snippets\/:workspace\/:encoded_id\/commits\/:revision","\/snippets\/tonics-workspace\/tonics-encoded_id\/files\/tonics-path":"\/snippets\/:workspace\/:encoded_id\/files\/:path","\/snippets\/tonics-workspace\/tonics-encoded_id\/watch":"\/snippets\/:workspace\/:encoded_id\/watch","\/snippets\/tonics-workspace\/tonics-encoded_id\/watchers":"\/snippets\/:workspace\/:encoded_id\/watchers","\/snippets\/tonics-workspace\/tonics-encoded_id\/tonics-node_id":"\/snippets\/:workspace\/:encoded_id\/:node_id","\/snippets\/tonics-workspace\/tonics-encoded_id\/tonics-node_id\/files\/tonics-path":"\/snippets\/:workspace\/:encoded_id\/:node_id\/files\/:path","\/snippets\/tonics-workspace\/tonics-encoded_id\/tonics-revision\/diff":"\/snippets\/:workspace\/:encoded_id\/:revision\/diff","\/snippets\/tonics-workspace\/tonics-encoded_id\/tonics-revision\/patch":"\/snippets\/:workspace\/:encoded_id\/:revision\/patch","\/teams":"\/teams","\/teams\/tonics-username":"\/teams\/:username","\/teams\/tonics-username\/followers":"\/teams\/:username\/followers","\/teams\/tonics-username\/following":"\/teams\/:username\/following","\/teams\/tonics-username\/members":"\/teams\/:username\/members","\/teams\/tonics-username\/permissions":"\/teams\/:username\/permissions","\/teams\/tonics-username\/permissions\/repositories":"\/teams\/:username\/permissions\/repositories","\/teams\/tonics-username\/permissions\/repositories\/tonics-repo_slug":"\/teams\/:username\/permissions\/repositories\/:repo_slug","\/teams\/tonics-username\/pipelines_config\/variables":"\/teams\/:username\/pipelines_config\/variables","\/teams\/tonics-username\/pipelines_config\/variables\/tonics-variable_uuid":"\/teams\/:username\/pipelines_config\/variables\/:variable_uuid","\/teams\/tonics-username\/projects":"\/teams\/:username\/projects","\/teams\/tonics-username\/projects\/tonics-project_key":"\/teams\/:username\/projects\/:project_key","\/teams\/tonics-username\/search\/code":"\/teams\/:username\/search\/code","\/teams\/tonics-workspace\/repositories":"\/teams\/:workspace\/repositories","\/user":"\/user","\/user\/emails":"\/user\/emails","\/user\/emails\/tonics-email":"\/user\/emails\/:email","\/user\/permissions\/repositories":"\/user\/permissions\/repositories","\/user\/permissions\/teams":"\/user\/permissions\/teams","\/user\/permissions\/workspaces":"\/user\/permissions\/workspaces","\/users\/tonics-selected_user":"\/users\/:selected_user","\/users\/tonics-selected_user\/pipelines_config\/variables":"\/users\/:selected_user\/pipelines_config\/variables","\/users\/tonics-selected_user\/pipelines_config\/variables\/tonics-variable_uuid":"\/users\/:selected_user\/pipelines_config\/variables\/:variable_uuid","\/users\/tonics-selected_user\/properties\/tonics-app_key\/tonics-property_name":"\/users\/:selected_user\/properties\/:app_key\/:property_name","\/users\/tonics-selected_user\/search\/code":"\/users\/:selected_user\/search\/code","\/users\/tonics-selected_user\/ssh-keys":"\/users\/:selected_user\/ssh-keys","\/users\/tonics-selected_user\/ssh-keys\/tonics-key_id":"\/users\/:selected_user\/ssh-keys\/:key_id","\/users\/tonics-username\/members":"\/users\/:username\/members","\/users\/tonics-workspace\/repositories":"\/users\/:workspace\/repositories","\/workspaces":"\/workspaces","\/workspaces\/tonics-workspace":"\/workspaces\/:workspace","\/workspaces\/tonics-workspace\/hooks":"\/workspaces\/:workspace\/hooks","\/workspaces\/tonics-workspace\/hooks\/tonics-uid":"\/workspaces\/:workspace\/hooks\/:uid","\/workspaces\/tonics-workspace\/members":"\/workspaces\/:workspace\/members","\/workspaces\/tonics-workspace\/members\/tonics-member":"\/workspaces\/:workspace\/members\/:member","\/workspaces\/tonics-workspace\/permissions":"\/workspaces\/:workspace\/permissions","\/workspaces\/tonics-workspace\/permissions\/repositories":"\/workspaces\/:workspace\/permissions\/repositories","\/workspaces\/tonics-workspace\/permissions\/repositories\/tonics-repo_slug":"\/workspaces\/:workspace\/permissions\/repositories\/:repo_slug","\/workspaces\/tonics-workspace\/pipelines-config\/identity\/oidc\/keys.json":"\/workspaces\/:workspace\/pipelines-config\/identity\/oidc\/keys.json","\/workspaces\/tonics-workspace\/pipelines-config\/variables":"\/workspaces\/:workspace\/pipelines-config\/variables","\/workspaces\/tonics-workspace\/pipelines-config\/variables\/tonics-variable_uuid":"\/workspaces\/:workspace\/pipelines-config\/variables\/:variable_uuid","\/workspaces\/tonics-workspace\/projects":"\/workspaces\/:workspace\/projects","\/workspaces\/tonics-workspace\/projects\/tonics-project_key":"\/workspaces\/:workspace\/projects\/:project_key","\/workspaces\/tonics-workspace\/search\/code":"\/workspaces\/:workspace\/search\/code"}
JSO, true
        );

        defineRoutes(array_values($urls), $route);
        matchURLS($urls, $routeTree);
    });
});

function defineRoutes(array $routes, Route $route): void
{
    foreach ($routes as $r){
        $route->get( $r, function (){});
    }
}

function matchURLS($urls, $routeTree): void
{
    foreach ($urls as $url => $toReturn){
        it("expect $url to match: $toReturn", function () use ($url, $routeTree, $toReturn) {
            expect($routeTree->findURL($url)?->getFoundURLNode()?->getFullRoutePath())->toEqual($toReturn);
        });
    }
}

function mustNotMatchURLAndBe($urls, $routeTree, $andBe = null): void
{
    foreach ($urls as $url){
        it("expect $url to be null", function () use ($andBe, $url, $routeTree) {
            expect($routeTree->findURL($url)?->getFoundURLNode()?->getFullRoutePath())->toEqual($andBe);
        });
    }
}