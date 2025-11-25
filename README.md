# Tonics Router System

A Trie based PHP Router System For Tonics Projects. 

This would serve as a base router for tonics web apps, the router is different from most PHP Router 
in the sense that it doesn't use regex for matching urls, it instead uses a tree data structure where every path is
hierarchically organized making it faster for finding both static or dynamic url.

Additionally, I came up with a concept called Node Teleporting which can further enhance and speed up searching dynamic routes, in the best case, dynamic route would be matched directly just like the static routes, and in the worse case, it would teleport a couple of times which is also faster than mere traversing.

You can learn more about the teleporting in the part 2 of how the router works.

## Features

* **Fast Trie-based routing** - Uses a tree data structure instead of regex for faster URL matching
* **Node Teleporting** - Advanced optimization for dynamic routes
* **PSR-7 Support** - Full PSR-7 HTTP message interface compatibility
* **Backward Compatible** - Works with traditional PHP globals or PSR-7 objects
* **Request Interceptors** - Middleware-like functionality for request processing
* **Route Groups** - Organize routes hierarchically with shared attributes
* **Dependency Injection** - Built-in container for automatic dependency resolution

## Requirements
* PHP 8.0 and above
* PHP mbstring extension enabled.

## Installation

```
composer require devsrealm/tonics-router-system
```

If you don't want to use composer, go-to the release section and download the zip file that has a postfix of `composer-no-required` e.g `tonics-router-system-v1.0.0-composer-no-required.zip`

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

// Once your route is set up, dispatch it (don't forget to do this once all your route is set-up, otherwise, it won't work):
try {
    $router->dispatchRequestURL();
} catch (Exception $e) {
    // handle error or 404
}
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

## PSR-7 Support

The Tonics Router System now has full PSR-7 support while maintaining 100% backward compatibility. You can choose to use PSR-7 HTTP message interfaces or continue using the traditional approach.

### Why Use PSR-7?

PSR-7 provides standardized HTTP message interfaces that:
- Work seamlessly with modern PHP frameworks and libraries
- Enable better testing with mock request/response objects
- Provide immutable request/response objects for safer code
- Follow PHP-FIG standards for better interoperability

### Using PSR-7 Router (Recommended for new projects)

The easiest way to use PSR-7 is with the `Psr7Router` class:

```php
use Devsrealm\TonicsRouterSystem\Handler\Psr7Router;

// Create router from PHP globals
$router = Psr7Router::create();

// Define your routes
$router->getRoute()->get('/', function() {
    return 'Welcome to PSR-7!';
});

$router->getRoute()->get('/user/:id', function($id) {
    return "User ID: $id";
});

// Handle request and emit response
$router->run();
```

**Important PSR-7 Best Practice:** Controllers should **return** content, not `echo` it:

```php
// ✅ GOOD: Return content (PSR-7 compliant)
$router->getRoute()->get('/api/users', function() {
    return json_encode(['users' => []]);
});

// ❌ BAD: Echo content (not PSR-7 compliant)
$router->getRoute()->get('/api/users', function() {
    echo json_encode(['users' => []]);  // Don't do this!
});
```

If you have legacy code that uses `echo`, you can use `handleWithOutputBuffering()` temporarily:

```php
// For legacy code only (not recommended)
$response = $router->handleWithOutputBuffering($request);
$router->emit($response);
```

However, **refactoring to return values is strongly recommended** for proper PSR-7 compliance.

### Manual PSR-7 Usage (Advanced)

For more control, you can manually create PSR-7 objects:

```php
use Devsrealm\TonicsRouterSystem\Handler\Psr7Router;
use Devsrealm\TonicsRouterSystem\Psr7Factory;

// Create a PSR-7 request from globals
$psrRequest = Psr7Factory::createServerRequestFromGlobals();

// Create the router with the PSR-7 request
$router = new Psr7Router($psrRequest);

// Define routes
$router->getRoute()->get('/api/data', function() {
    return json_encode(['status' => 'success', 'data' => []]);
});

// Handle the request
$psrResponse = $router->handle($psrRequest);

// Emit the response
$router->emit($psrResponse);
```


### Creating Router with Custom Container

```php
use Devsrealm\TonicsRouterSystem\Handler\Psr7Router;
use Devsrealm\TonicsRouterSystem\Container\Container;
use Devsrealm\TonicsRouterSystem\Resolver\RouteResolver;
use Devsrealm\TonicsRouterSystem\Psr7Factory;

// Create and configure your container
$container = new Container();

// Register all your dependencies
$container->set(DatabaseInterface::class, fn() => new MySQLDatabase());
$container->set(UserRepository::class, fn($c) => new UserRepository($c->get(DatabaseInterface::class)));
// ... more registrations

// Create route resolver with your configured container
$routeResolver = new RouteResolver($container);

// Create PSR-7 request
$psrRequest = Psr7Factory::createServerRequestFromGlobals();

// Create router with custom resolver
$router = new Psr7Router($psrRequest, $routeResolver);

// Now define routes
$router->getRoute()->get('/', [HomeController::class, 'index']);
$router->run();
```

### Auto-wiring Example

The container can automatically resolve dependencies if they're type-hinted:

```php
$router = Psr7Router::create();

// Register only what can't be auto-resolved (primitives, interfaces, etc.)
$router->getContainer()->set(DatabaseInterface::class, function() {
    return new MySQLDatabase('localhost', 'mydb', 'user', 'pass');
});

// These classes will be auto-resolved
class EmailService {
    // No dependencies - will be auto-created
}

class UserRepository {
    // DatabaseInterface must be registered (interface)
    public function __construct(private DatabaseInterface $db) {}
}

class UserService {
    // UserRepository will be auto-created, EmailService will be auto-created
    public function __construct(
        private UserRepository $repo,
        private EmailService $email
    ) {}
}

class UserController {
    // UserService and all its dependencies will be auto-resolved!
    public function __construct(private UserService $service) {}
    
    public function show($id) {
        return json_encode($this->service->findById($id));
    }
}

// Just register the route - everything else is automatic!
$router->getRoute()->get('/users/:id', [UserController::class, 'show']);
```

### Using PSR-7 Request Adapter

You can also use PSR-7 requests with individual components:

```php
use Devsrealm\TonicsRouterSystem\Adapter\Psr7RequestAdapter;
use Devsrealm\TonicsRouterSystem\Psr7Factory;

$psrRequest = Psr7Factory::createServerRequestFromGlobals();
$requestAdapter = new Psr7RequestAdapter($psrRequest);

// Use it like the traditional RequestInput
$postData = $requestAdapter->fromPost()->all();
$userId = $requestAdapter->fromGet()->retrieve('user_id');
```

### Using PSR-7 Response Adapter

For PSR-7 compliant responses:

```php
use Devsrealm\TonicsRouterSystem\Adapter\Psr7ResponseAdapter;
use Devsrealm\TonicsRouterSystem\Psr7Factory;

$psrResponse = Psr7Factory::createResponse(200);
$responseAdapter = new Psr7ResponseAdapter($psrResponse);

// Use Tonics-style methods
$responseAdapter->json(['status' => 'success']);
// or
$responseAdapter->redirect('/dashboard');
```

### Backward Compatibility

**All existing code continues to work!** The traditional approach still works exactly as before:

```php
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

$onRequestProcess = new OnRequestProcess(
    new RouteResolver(new Container()),
    new Route(
        new RouteTreeGenerator(
            new RouteTreeGeneratorState(), 
            new RouteNode()
        )
    )
);

$router = new Router(
    $onRequestProcess,
    $onRequestProcess->getRouteObject(),
    new Response($onRequestProcess, new RequestInput())
);

// Traditional usage works as always
$route = $router->getRoute();
$route->get('/', function() {
    return 'Hello World';
});

$router->dispatchRequestURL();
```

### PSR-7 in Controllers

When using PSR-7, you can type-hint PSR-7 interfaces in your controllers:

```php
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class UserController
{
    public function show(ServerRequestInterface $request, string $id)
    {
        // Access PSR-7 request
        $queryParams = $request->getQueryParams();
        $headers = $request->getHeaders();
        
        return "User: $id";
    }
}
```

### Testing with PSR-7

PSR-7 makes testing much easier:

```php
use Devsrealm\TonicsRouterSystem\Handler\Psr7Router;
use Nyholm\Psr7\Factory\Psr17Factory;

// Create a test request
$factory = new Psr17Factory();
$testRequest = $factory->createServerRequest('GET', '/api/user/123');

// Create router and handle
$router = new Psr7Router($testRequest);
$router->getRoute()->get('/api/user/:id', function($id) {
    return json_encode(['id' => $id]);
});

$response = $router->handle($testRequest);

// Assert response
assert($response->getStatusCode() === 200);
assert($response->getHeaderLine('Content-Type') === 'application/json');
```

## Working with Controllers

Controllers help organize your application logic. Here are practical examples:

### Basic Controller Example

```php
namespace App\Controllers;

class HomeController
{
    public function index()
    {
        return 'Welcome to the homepage!';
    }
    
    public function about()
    {
        return 'About us page';
    }
}

// Register routes
$route->get('/', [HomeController::class, 'index']);
$route->get('/about', [HomeController::class, 'about']);
```

### Controller with Route Parameters

```php
namespace App\Controllers;

class PostController
{
    public function show($slug)
    {
        // Fetch post from database
        $post = Post::findBySlug($slug);
        
        if (!$post) {
            http_response_code(404);
            return 'Post not found';
        }
        
        return json_encode($post);
    }
    
    public function showById($id)
    {
        $post = Post::find($id);
        return json_encode($post);
    }
}

// Register routes
$route->get('/posts/:slug', [PostController::class, 'show']);
$route->get('/posts/id/:id', [PostController::class, 'showById']);
```

### Controller with Dependency Injection

The router automatically resolves dependencies through the container:

```php
namespace App\Controllers;

use App\Services\UserService;
use App\Services\EmailService;

class UserController
{
    private UserService $userService;
    private EmailService $emailService;
    
    // Dependencies are automatically injected
    public function __construct(UserService $userService, EmailService $emailService)
    {
        $this->userService = $userService;
        $this->emailService = $emailService;
    }
    
    public function show($id)
    {
        $user = $this->userService->findById($id);
        return json_encode($user);
    }
    
    public function sendWelcomeEmail($id)
    {
        $user = $this->userService->findById($id);
        $this->emailService->sendWelcome($user->email);
        return json_encode(['message' => 'Email sent']);
    }
}

// Register routes
$route->get('/user/:id', [UserController::class, 'show']);
$route->post('/user/:id/welcome', [UserController::class, 'sendWelcomeEmail']);
```

### RESTful Controller Example

```php
namespace App\Controllers;

use Devsrealm\TonicsRouterSystem\RequestInput;

class ApiUserController
{
    private RequestInput $input;
    
    public function __construct(RequestInput $input)
    {
        $this->input = $input;
    }
    
    // GET /api/users
    public function index()
    {
        $users = User::all();
        return json_encode(['users' => $users]);
    }
    
    // GET /api/users/:id
    public function show($id)
    {
        $user = User::find($id);
        return json_encode(['user' => $user]);
    }
    
    // POST /api/users
    public function store()
    {
        $data = $this->input->fromPost();
        
        $name = $data->retrieve('name');
        $email = $data->retrieve('email');
        
        $user = User::create(['name' => $name, 'email' => $email]);
        
        http_response_code(201);
        return json_encode(['user' => $user]);
    }
    
    // PUT /api/users/:id
    public function update($id)
    {
        $data = $this->input->fromPost();
        
        $user = User::find($id);
        $user->name = $data->retrieve('name', $user->name);
        $user->email = $data->retrieve('email', $user->email);
        $user->save();
        
        return json_encode(['user' => $user]);
    }
    
    // DELETE /api/users/:id
    public function destroy($id)
    {
        User::find($id)->delete();
        return json_encode(['message' => 'User deleted']);
    }
}

// Register RESTful routes
$route->get('/api/users', [ApiUserController::class, 'index']);
$route->get('/api/users/:id', [ApiUserController::class, 'show']);
$route->post('/api/users', [ApiUserController::class, 'store']);
$route->put('/api/users/:id', [ApiUserController::class, 'update']);
$route->delete('/api/users/:id', [ApiUserController::class, 'destroy']);
```

### PSR-7 Controller Example

```php
namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface;
use Devsrealm\TonicsRouterSystem\Adapter\Psr7ResponseAdapter;

class Psr7UserController
{
    // Type-hint PSR-7 interfaces
    public function show(ServerRequestInterface $request, $id)
    {
        // Access query parameters
        $queryParams = $request->getQueryParams();
        $includeEmail = $queryParams['include_email'] ?? false;
        
        // Access headers
        $authToken = $request->getHeaderLine('Authorization');
        
        // Get request body
        $body = $request->getBody()->getContents();
        
        $user = User::find($id);
        
        if ($includeEmail === 'true') {
            return json_encode(['id' => $user->id, 'name' => $user->name, 'email' => $user->email]);
        }
        
        return json_encode(['id' => $user->id, 'name' => $user->name]);
    }
}
```

### Complete Example with Container + PSR-7

Here's a full real-world example showing how everything works together:

```php
use Devsrealm\TonicsRouterSystem\Handler\Psr7Router;

// 1. Create router
$router = Psr7Router::create();

// 2. Configure container with your dependencies
$container = $router->getContainer();

// Register database
$container->singleton(PDO::class, function() {
    return new PDO('mysql:host=localhost;dbname=myapp', 'user', 'pass');
});

// Register repositories
$container->set(UserRepository::class, function($c) {
    return new UserRepository($c->get(PDO::class));
});

$container->set(PostRepository::class, function($c) {
    return new PostRepository($c->get(PDO::class));
});

// Register services
$container->set(UserService::class, function($c) {
    return new UserService(
        $c->get(UserRepository::class),
        $c->get(EmailService::class)
    );
});

$container->set(EmailService::class, function() {
    return new EmailService(getenv('SMTP_HOST'), getenv('SMTP_PORT'));
});

// 3. Define your routes
$router->getRoute()->get('/', [HomeController::class, 'index']);
$router->getRoute()->get('/users/:id', [UserController::class, 'show']);
$router->getRoute()->post('/users', [UserController::class, 'store']);
$router->getRoute()->get('/posts/:slug', [PostController::class, 'show']);

// 4. Run the application
$router->run();

// Controller examples
class HomeController {
    public function index() {
        return json_encode(['message' => 'Welcome to our API']);
    }
}

class UserController {
    // Dependencies auto-injected via container
    public function __construct(
        private UserService $userService,
        private UserRepository $userRepo
    ) {}
    
    public function show($id) {
        try {
            $user = $this->userService->findById($id);
            return json_encode(['user' => $user]);
        } catch (NotFoundException $e) {
            http_response_code(404);
            return json_encode(['error' => 'User not found']);
        }
    }
    
    public function store() {
        // Using PSR-7 request in constructor
        $data = json_decode(file_get_contents('php://input'), true);
        
        $user = $this->userService->createUser(
            $data['name'] ?? '',
            $data['email'] ?? ''
        );
        
        http_response_code(201);
        return json_encode(['user' => $user]);
    }
}

class PostController {
    public function __construct(private PostRepository $postRepo) {}
    
    public function show($slug) {
        $post = $this->postRepo->findBySlug($slug);
        
        if (!$post) {
            http_response_code(404);
            return json_encode(['error' => 'Post not found']);
        }
        
        return json_encode(['post' => $post]);
    }
}

// Service layer
class UserService {
    public function __construct(
        private UserRepository $userRepo,
        private EmailService $emailService
    ) {}
    
    public function findById($id) {
        return $this->userRepo->find($id);
    }
    
    public function createUser(string $name, string $email) {
        $user = $this->userRepo->create(['name' => $name, 'email' => $email]);
        $this->emailService->sendWelcome($user->email);
        return $user;
    }
}

// Repository layer
class UserRepository {
    public function __construct(private PDO $db) {}
    
    public function find($id) {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }
    
    public function create(array $data) {
        $stmt = $this->db->prepare('INSERT INTO users (name, email) VALUES (?, ?)');
        $stmt->execute([$data['name'], $data['email']]);
        return $this->find($this->db->lastInsertId());
    }
}
```

## Request Interceptors (Middleware) Examples

Request Interceptors act as middleware to process requests before they reach your controllers.

### Authentication Interceptor

```php
namespace App\Middleware;

use Devsrealm\TonicsRouterSystem\Events\OnRequestProcess;
use Devsrealm\TonicsRouterSystem\Interfaces\TonicsRouterRequestInterceptorInterface;

class AuthenticationMiddleware implements TonicsRouterRequestInterceptorInterface
{
    public function handle(OnRequestProcess $request): void
    {
        // Check if user is authenticated
        if (!isset($_SESSION['user_id'])) {
            // Redirect to login page
            http_response_code(401);
            header('Location: /login');
            exit;
        }
        
        // If authenticated, continue to next interceptor or controller
    }
}

// Usage
$route->get('/dashboard', [DashboardController::class, 'index'], [AuthenticationMiddleware::class]);
```

### CORS Interceptor

```php
namespace App\Middleware;

use Devsrealm\TonicsRouterSystem\Events\OnRequestProcess;
use Devsrealm\TonicsRouterSystem\Interfaces\TonicsRouterRequestInterceptorInterface;

class CorsMiddleware implements TonicsRouterRequestInterceptorInterface
{
    public function handle(OnRequestProcess $request): void
    {
        // Add CORS headers
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        // Handle preflight requests
        if ($request->getRequestMethod() === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
        
        // Continue to next interceptor or controller
    }
}

// Usage on API routes
$route->group('/api', function (Route $route) {
    $route->get('/users', [ApiUserController::class, 'index']);
    $route->post('/users', [ApiUserController::class, 'store']);
}, [CorsMiddleware::class]);
```

### JSON Content-Type Validator

```php
namespace App\Middleware;

use Devsrealm\TonicsRouterSystem\Events\OnRequestProcess;
use Devsrealm\TonicsRouterSystem\Interfaces\TonicsRouterRequestInterceptorInterface;

class JsonContentTypeMiddleware implements TonicsRouterRequestInterceptorInterface
{
    public function handle(OnRequestProcess $request): void
    {
        $method = $request->getRequestMethod();
        
        // Check Content-Type for POST, PUT, PATCH requests
        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $contentType = $request->getHeaderByKey('CONTENT_TYPE');
            
            if (strpos($contentType, 'application/json') === false) {
                http_response_code(415);
                echo json_encode(['error' => 'Content-Type must be application/json']);
                exit;
            }
        }
        
        // Continue to next interceptor or controller
    }
}
```

### Rate Limiting Interceptor

```php
namespace App\Middleware;

use Devsrealm\TonicsRouterSystem\Events\OnRequestProcess;
use Devsrealm\TonicsRouterSystem\Interfaces\TonicsRouterRequestInterceptorInterface;

class RateLimitMiddleware implements TonicsRouterRequestInterceptorInterface
{
    private int $maxRequests = 100;
    private int $perMinutes = 1;
    
    public function handle(OnRequestProcess $request): void
    {
        $ip = $request->getHeaderByKey('REMOTE_ADDR');
        $key = "rate_limit:$ip";
        
        // Get current count from cache (Redis, Memcached, etc.)
        $count = Cache::get($key, 0);
        
        if ($count >= $this->maxRequests) {
            http_response_code(429);
            echo json_encode(['error' => 'Too many requests. Please try again later.']);
            exit;
        }
        
        // Increment counter
        Cache::increment($key);
        Cache::expire($key, $this->perMinutes * 60);
        
        // Continue to next interceptor or controller
    }
}
```

### Logging Interceptor

```php
namespace App\Middleware;

use Devsrealm\TonicsRouterSystem\Events\OnRequestProcess;
use Devsrealm\TonicsRouterSystem\Interfaces\TonicsRouterRequestInterceptorInterface;

class LoggingMiddleware implements TonicsRouterRequestInterceptorInterface
{
    public function handle(OnRequestProcess $request): void
    {
        $method = $request->getRequestMethod();
        $url = $request->getRequestURL();
        $ip = $request->getHeaderByKey('REMOTE_ADDR');
        $userAgent = $request->getUserAgent();
        
        // Log the request
        error_log(sprintf(
            "[%s] %s %s from %s - %s",
            date('Y-m-d H:i:s'),
            $method,
            $url,
            $ip,
            $userAgent
        ));
        
        // Continue to next interceptor or controller
    }
}

// Apply to all routes
$route->group('/', function (Route $route) {
    // All your routes here
}, [LoggingMiddleware::class]);
```

### Multiple Interceptors Example

```php
namespace App\Middleware;

// Chain multiple interceptors
$route->group('/admin', function (Route $route) {
    $route->get('/dashboard', [AdminController::class, 'dashboard']);
    $route->get('/users', [AdminController::class, 'users']);
    $route->post('/users', [AdminController::class, 'createUser']);
}, [
    LoggingMiddleware::class,           // First: Log the request
    AuthenticationMiddleware::class,     // Second: Check if user is logged in
    AdminAuthorizationMiddleware::class, // Third: Check if user is admin
    CsrfMiddleware::class               // Fourth: Validate CSRF token
]);
```

## Best Practices

### 1. Controller Organization

**✅ DO:** Keep controllers focused and single-purpose
```php
// Good: Focused controller
class UserController {
    public function show($id) { /* ... */ }
    public function update($id) { /* ... */ }
}

class UserProfileController {
    public function show($id) { /* ... */ }
    public function updateAvatar($id) { /* ... */ }
}
```

**❌ DON'T:** Create god controllers
```php
// Bad: Too many responsibilities
class UserController {
    public function show($id) { /* ... */ }
    public function updateProfile($id) { /* ... */ }
    public function uploadAvatar($id) { /* ... */ }
    public function sendEmail($id) { /* ... */ }
    public function generateReport($id) { /* ... */ }
    // ... 50 more methods
}
```

### 2. Return Values vs Echo

**✅ DO:** Return values from controllers (especially for PSR-7)
```php
public function show($id) {
    $user = User::find($id);
    return json_encode($user);  // ✅ Return
}
```

**❌ DON'T:** Echo directly in controllers
```php
public function show($id) {
    $user = User::find($id);
    echo json_encode($user);  // ❌ Echo (not PSR-7 compliant)
}
```

### 3. Use Dependency Injection

**✅ DO:** Inject dependencies via constructor
```php
class UserController {
    private UserRepository $userRepo;
    
    public function __construct(UserRepository $userRepo) {
        $this->userRepo = $userRepo;  // ✅ Injected
    }
    
    public function show($id) {
        return json_encode($this->userRepo->find($id));
    }
}
```

**❌ DON'T:** Create dependencies inside methods
```php
class UserController {
    public function show($id) {
        $userRepo = new UserRepository();  // ❌ Tight coupling
        return json_encode($userRepo->find($id));
    }
}
```

### 4. Request Interceptor Best Practices

**✅ DO:** Keep interceptors focused on one concern
```php
// Good: Single responsibility
class AuthenticationMiddleware implements TonicsRouterRequestInterceptorInterface {
    public function handle(OnRequestProcess $request): void {
        // Only handles authentication
        if (!$this->isAuthenticated()) {
            $this->redirectToLogin();
        }
    }
}
```

**✅ DO:** Chain interceptors for multiple checks
```php
$route->group('/admin', function (Route $route) {
    // Routes here
}, [
    AuthenticationMiddleware::class,    // Check if logged in
    AdminAuthorizationMiddleware::class, // Check if admin
    CsrfMiddleware::class               // Validate CSRF
]);
```

**❌ DON'T:** Create monolithic interceptors
```php
// Bad: Does too much
class MegaMiddleware implements TonicsRouterRequestInterceptorInterface {
    public function handle(OnRequestProcess $request): void {
        // Authentication
        // Authorization
        // CSRF validation
        // Rate limiting
        // Logging
        // ... everything in one class
    }
}
```

### 5. Route Organization

**✅ DO:** Group related routes
```php
// Good: Organized by feature
$route->group('/api/v1', function (Route $route) {
    $route->group('/users', function (Route $route) {
        $route->get('', [UserController::class, 'index']);
        $route->get(':id', [UserController::class, 'show']);
        $route->post('', [UserController::class, 'store']);
    });
    
    $route->group('/posts', function (Route $route) {
        $route->get('', [PostController::class, 'index']);
        $route->get(':id', [PostController::class, 'show']);
    });
}, [CorsMiddleware::class, AuthMiddleware::class]);
```

**❌ DON'T:** Mix unrelated routes
```php
// Bad: No organization
$route->get('/api/v1/users', [UserController::class, 'index']);
$route->get('/admin/dashboard', [AdminController::class, 'dashboard']);
$route->get('/api/v1/posts', [PostController::class, 'index']);
$route->get('/public/about', [PageController::class, 'about']);
```

### 6. Error Handling

**✅ DO:** Handle errors gracefully
```php
public function show($id) {
    try {
        $user = User::findOrFail($id);
        return json_encode(['user' => $user]);
    } catch (NotFoundException $e) {
        http_response_code(404);
        return json_encode(['error' => 'User not found']);
    } catch (Exception $e) {
        http_response_code(500);
        return json_encode(['error' => 'Internal server error']);
    }
}
```

**✅ DO:** Use proper HTTP status codes
```php
// 200 - OK
return json_encode(['message' => 'Success']);

// 201 - Created
http_response_code(201);
return json_encode(['user' => $newUser]);

// 400 - Bad Request
http_response_code(400);
return json_encode(['error' => 'Invalid input']);

// 401 - Unauthorized
http_response_code(401);
return json_encode(['error' => 'Authentication required']);

// 403 - Forbidden
http_response_code(403);
return json_encode(['error' => 'Access denied']);

// 404 - Not Found
http_response_code(404);
return json_encode(['error' => 'Resource not found']);

// 500 - Internal Server Error
http_response_code(500);
return json_encode(['error' => 'Server error']);
```

### 7. Input Validation

**✅ DO:** Validate input data
```php
public function store(RequestInput $input) {
    $data = $input->fromPost();
    
    // Validate required fields
    if (!$data->hasValue('name')) {
        http_response_code(400);
        return json_encode(['error' => 'Name is required']);
    }
    
    if (!$data->hasValue('email')) {
        http_response_code(400);
        return json_encode(['error' => 'Email is required']);
    }
    
    // Validate email format
    $email = $data->retrieve('email');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        return json_encode(['error' => 'Invalid email format']);
    }
    
    // Create user
    $user = User::create([
        'name' => $data->retrieve('name'),
        'email' => $email
    ]);
    
    return json_encode(['user' => $user]);
}
```

### 8. Use Response Helper (PSR-7)

**✅ DO:** Use response adapter for clean code
```php
use Devsrealm\TonicsRouterSystem\Adapter\Psr7ResponseAdapter;

public function show(Psr7ResponseAdapter $response, $id) {
    $user = User::find($id);
    
    if (!$user) {
        return $response->httpResponseCode(404)
            ->json(['error' => 'User not found']);
    }
    
    return $response->json(['user' => $user]);
}
```

### 9. Security Best Practices

**✅ DO:** Sanitize user input
```php
public function search(RequestInput $input) {
    $query = $input->fromGet()->retrieve('q', '');
    
    // Sanitize input
    $query = htmlspecialchars($query, ENT_QUOTES, 'UTF-8');
    
    $results = Search::query($query);
    return json_encode(['results' => $results]);
}
```

**✅ DO:** Use HTTPS for sensitive operations
```php
class SecureMiddleware implements TonicsRouterRequestInterceptorInterface {
    public function handle(OnRequestProcess $request): void {
        if (!$request->isSecure()) {
            header('Location: https://' . $request->getHost() . $request->getRequestURL());
            exit;
        }
    }
}
```

**✅ DO:** Validate CSRF tokens
```php
class CsrfMiddleware implements TonicsRouterRequestInterceptorInterface {
    public function handle(OnRequestProcess $request): void {
        if (in_array($request->getRequestMethod(), ['POST', 'PUT', 'DELETE'])) {
            $token = $_POST['csrf_token'] ?? '';
            
            if (!$this->validateCsrfToken($token)) {
                http_response_code(403);
                echo json_encode(['error' => 'Invalid CSRF token']);
                exit;
            }
        }
    }
    
    private function validateCsrfToken(string $token): bool {
        return isset($_SESSION['csrf_token']) && 
               hash_equals($_SESSION['csrf_token'], $token);
    }
}
```