A Trie based PHP Router System For Tonics Projects. 

This would serve as a base router for tonics web apps, the router is different from most PHP Router 
in the sense that it doesn't use regex for matching urls, it instead uses a tree data structure where every path is
hierarchically organized making it faster for finding both static or dynamic url.

## Requirements
* PHP 8.0 and above
* PHP mbstring extension enabled.

## Installation

```
composer require devsrealm/tonics-router-system
```

If you don't want to use composer, go-to the release section and download the zip file that has a postfix of composer-no-required e.g tonics-router-system-v1.0.0-composer-no-required.zip

Unzip it and require it like so:

```
require 'path/to/tonics-router-system/vendor/autoload.php';
```

## How The Router Works

1. [A Faster Router System in PHP (Part 1)](https://tonics.app/posts/ff9af70984746b91/faster-router-php)
2. [A Faster Router System in PHP (Part 2) (Improvement & Benchmarks)](https://tonics.app/posts/409a745fcbf15371/faster-router-system-in-php-part-2-improvement-and-benchmarks)

## Documentation

Before you get started, wire up the Router dependencies:

```php
$onRequestProcess = new OnRequestProcess(
                        new RouteResolver(
                            new Container()
                        ),
                        new Route(
                            new RouteTreeGenerator(
                                new RouteTreeGeneratorState(), new RouteNode()
                                )
                        )
                );

$router = new Router(
    $onRequestProcess,
    $onRequestProcess->getRouteObject(),
    new Response(
        $onRequestProcess, new RequestInput()
        )
    );
```

### Basic routing

First parameter is the url paths which you want the route to match, and the second parameter
could be a closure or a callback function that the route would call once the route matches.

```php
$route = $router->getRoute();

$route->get('/', function() {
    return 'Welcome To My Home Page';
});
```

If you want to keep things organized, you can also resolve through a class method, like so:

```php
$route->get('/', [HomePage::class, 'methodName']);
```

### Request Interceptors
Some call it middleware, `requestInterceptor` sounds plain and simple to me. 
RequestInterceptors can be used to intercept a request before it moves to the next life cycle or to other request interceptors.

For example, if you have an admin url path: /admin, and you want to check if a user is logged in before processing the request, you use the request interceptor. Let's see an example:

```php
$route->get('admin', [AdminController::class, 'adminDashboard'], [IsAuthenticated::class]);
```

in `isAuthenticated()` class you can have something as such:

```php
class Authenticated implements TonicsRouterRequestInterceptorInterface
{
    /**
     * @inheritDoc
     */
    public function handle(OnRequestProcess $request): void
    {
       if (UserData::isAuthenticated() === false){
           # If this is for admin, then redirect to admin login
           if (str_starts_with($request->getRequestURL(), '/admin')){
               redirect(route('admin.login'));
           }

           # If this is for customer, then redirect to customer login
           if (str_starts_with($request->getRequestURL(), '/customer')){
               redirect(route('customer.login'));
           }

           # Else...
           SimpleState::displayUnauthorizedErrorMessage();
       }
    }
}
```

We implemented the `TonicsRouterRequestInterceptorInterface` (it is a must to implement the interface to use the request interceptor) which provides a handle method with the $request object.
Inside the handle method, I am checking if user is not authenticated, and thus redirecting them to their proper destination. 

However, if user is authenticated, the interceptor would move to the next life cycle in the route state, the next life cycle could be a new request interceptor or a class method or a callback delegation.

To add more request interceptors, simply do:

```php
$route->get('admin', 
    [AdminController::class, 'adminDashboard'], 
    [IsAuthenticated::class, MoreInterceptor::class, EvenMoreInterceptor::class]
    );
```

### Route Required parameters
To match a dynamic url parameter you do:

```php
$route->get('posts/:slug', function($slug) {
    return "Post with slug: $slug";
});
```

where you capture the slug from the url, for example, if user visits /posts/blog-post-title, you get access 
to `blog-post-title`.

Alternatively you can do

```php
$route->get('/posts/:slug', [PostsController::class, 'viewPost']);
```

where `PostsController could look like:
```php
class PostsController
{
    viewPost($slug)
    {
        return "Post with slug: $slug";
    }
}
```

### Route Groups

With the route group you could organize route in a tree like fashion, the good thing about this approach is  
you can share route attributes, such as route interceptors, parent url paths, etc. across a large number of routes without needing to define those attributes on each individual route.

instead of doing this:

```php
$route->get('admin/login', [LoginController::class, 'showLoginForm'], [SpecialInterceptor::class, RedirectAuthenticated::class]);
$route->post('admin/login', [LoginController::class, 'login'], [SpecialInterceptor::class]);
$route->post('admin/logout', [LoginController::class, 'logout'], [SpecialInterceptor::class]);
```

do this:

```php
$route->group('admin', function (Route $route){
    $route->get('login', [LoginController::class, 'showLoginForm'], [RedirectAuthenticated::class]);
    $route->post('login', [LoginController::class, 'login']);
    $route->post('logout', [LoginController::class, 'logout']);
}, [SpecialInterceptor::class]);
```
The end goal is identical to the above one but this is better organized.

You could also nest a group:

```php
$route->group('/admin/posts', function (Route $route){
            #---------------------------------
        # POST CATEGORIES...
    #---------------------------------
    $route->group('/category', function (Route $route){
        $route->get('', [PostCategoryController::class, 'index']);
        $route->get(':category/edit', [PostCategoryController::class, 'edit']);
        $route->get('create', [PostCategoryController::class, 'create']);
        $route->post('store', [PostCategoryController::class, 'store']);
        $route->post(':category/trash', [PostCategoryController::class, 'trash']);
        $route->post( '/trash/multiple', [PostCategoryController::class, 'trashMultiple']);
        $route->match(['post', 'put', 'patch'], ':category/update', [PostCategoryController::class, 'update']);
        $route->match(['post', 'delete'], ':category/delete', [PostCategoryController::class, 'delete']);
    }, alias: 'category');

}, [StartSession::class, CSRFGuard::class, Authenticated::class, PostAccess::class]);
```

### Route HTTP Verbs

* `$route->get(string $url, array|Closure $callback, array $requestInterceptor = [])`
* `$route->post(string $url, array|Closure $callback, array $requestInterceptor = [])`
* `$route->put(string $url, array|Closure $callback, array $requestInterceptor = [])`
* `$route->patch(string $url, array|Closure $callback, array $requestInterceptor = [])`
* `$route->delete(string $url, array|Closure $callback, array $requestInterceptor = [])`
* `$route->match(array $method, string $url, \Closure|array $callback, array $requestInterceptor = [])`

With match, you can match multiple HTTP verbs in one fell swoop.

More Documentation...
