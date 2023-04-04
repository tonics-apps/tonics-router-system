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

namespace Devsrealm\TonicsRouterSystem\Container;

use Closure;
use Exception;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;

/**
 * The only different between the container shipped in the Router System and the one of `Devsrealm\TonicsContainer` is
 * that this one doesn't have a ServiceProvider method, so, feel free to use this one if you don't want the hassle of yet installing `Devsrealm\TonicsContainer`
 */
class Container
{

    /**
     * This holds all the id of the instance passed to the container
     * @var array
     */
    private array $instances = [];

    /**
     * This is the equivalent of set method except it is called...
     * from the constructor
     */
    public function __construct(array $entries = [])
    {
        $this->instances = $entries;
    }

    /**
     * @param $id
     * @param null $concrete
     */
    public function set($id, $concrete = NULL): void
    {
        if (is_null($concrete)){
            $concrete = $id;
        }
        $this->instances[$id] = $concrete;
    }

    /**
     * For resolving multiple dependencies in one fell swoop
     * @throws ReflectionException
     */
    public function resolveMany(array $id): array
    {
        $resolved = [];
        foreach ($id as $d){
            $resolved[] = $this->get($d);
        }

        return $resolved;
    }

    /**
     * @inheritDoc
     * @param bool $autowire
     * this would determine if the container should resolve dependencies or return the $id instance
     * which is particularly useful if you just want to set and get simple key => value pair
     * @throws ReflectionException
     */
    public function get($id, $parameters = [], bool $autowire = true)
    {
        # if we don't have the identifier, just set it anyway
        if (!isset($this->instances[$id])) {
            $this->set($id);
        }

        if (!$autowire) return $this->instances[$id];

        return $this->resolve($this->instances[$id], $parameters);
    }

    /**
     * This can be used to resolve dependencies on class methods,
     * If you wanna resolve dependencies on class instance constructor use the resolve method
     * @param callable $callable - e.g [PagesController::class, 'showHomepage']
     * @param array $args - These are usually URL parameters, but it is optional
     * @param bool $mergeArgsRecurse - Set to true if you want to merge route $args with resolved dependency parameter
     * <br>
     * e.g <b>$route->get('/:faruq/:boy', [Test::class, 'index']);</b> would merge both <b>:faruq, :boy</b> alongside any dependencies in index method
     * meaning, you can use it like so: <b>index(Model $model, $arg1, arg2)</b>, $arg1 references <b>:faruq</b>, and <b>$arg2</b> references <b>:boy</b>
     * <br>
     * <br>
     * If <b>$mergeArgsRecurse</b> is false, the args would be passed as an array instead of been merged, you would be able to access it like so:
     * <br>
     * <b>index(Model $model, $argArray)</b>, <b>$argArray[0]</b> references <b>:faruq</b>, and  <b>$argArray[1]</b> references <b>:boy</b>
     * <br>
     * Note: when $mergeArgsRecurse is false, then prepare your function to hold all the args in a single variable
     *
     * @throws ReflectionException
     */
    public function call(callable $callable, array $args = [], bool $mergeArgsRecurse = true){
        $callableReflection = $this->whichCallableReflection($callable);
        # This gives us the parameters from the reflected method or closure
        $parameters = $callableReflection->getParameters();
        # This is were we store the resolvedDependableParams
        $resolveDependableMethodParam = $this->resolveDependencies($parameters);

        # Finally we call the method in the class by passing it the $resolveDependableMethodParam
        # it might look like so Class::foobar($resolveDependableMethodParam);
        # The $resolveDependableMethodParam would contain array of instantiated dependencies, if in doubt, dump it to Debug how it works
        if (!empty($args)){
            if (!$mergeArgsRecurse){
                $resolveDependableMethodParam[] = $args;
                return call_user_func_array($callable, $resolveDependableMethodParam);
            }

            $resolveDependableMethodParam = array_values(array_merge_recursive($resolveDependableMethodParam, $args));
            return call_user_func_array($callable, $resolveDependableMethodParam);
        }

        return call_user_func_array($callable, $resolveDependableMethodParam);

    }

    /**
     * @inheritDoc
     */
    public function has(string $id): bool
    {
        # simple return a bool if we have an $id in the instance array or not
        return array_key_exists($id, $this->instances);
    }

    /**
     * The resolve method would iterate over the instances that has been...
     * passed to the container, and resolve it
     * @param $id
     * @param $parameters
     * @return object
     * @throws ReflectionException
     * @throws Exception
     */
    public function resolve($id, $parameters): object
    {
        $reflector = new \ReflectionClass($id);

        # get class constructor
        $__construct = $reflector->getConstructor();

        # If there is no constructor in class, just return the object
        # by returning the object, you can think of it has (new Class())
        if($__construct === null) {
            return $reflector->newInstance();
        }

        # Checks if the function is an anonymous function
        # if so, we return it return $id($this, $parameters);
        # $this is the current class instance, so, it contains info about this container or whatever
        # properties are in this container
        # $parameters are the object parameter if any is set
        if ($reflector->isAnonymous()) return $id($this, $parameters);


        # if class is not instantiatable we throw exception
        if (!$reflector->isInstantiable()) throw new Exception("Class $id not instantiable.");

        $dependencies = $this->resolveDependencies($__construct->getParameters());

        # This creates a new class instance from $dependencies retrieved
        return $reflector->newInstanceArgs($dependencies);
    }

    /**
     * @throws ReflectionException
     */
    public function resolveDependencies($parameters): array
    {

        $dependencies = [];
        /**
         * @var ReflectionParameter $param
         */
        foreach ($parameters as $param) {
            # Checks if the parameter has a type associated with it e.g.
            # if the class constructor has __construct(Post $post) then the type hinted class is Post
            # or if its is a method e.g public function index(PostModel $post){}, then the type hinted is PostModel
            if ($param->hasType() && $param->getType()->isBuiltin() === false){
                # This returns a string of the fully qualified hinted class
                # e.g. Post would be "App\Modules\Page\Models\PostModel"
                if($paramType = $param->getType()->getName()) {
                    # recursively gets and instantiate dependencies
                    $dependencies[] = $this->get($paramType);
                }
            }
        }
        # return resolved dependencies
        return $dependencies;
    }

    /**
     * Determine which reflection to return i.e. method or for anonymous function
     * @param callable $callable
     * @return ReflectionFunction|ReflectionMethod
     * @throws ReflectionException
     * @throws Exception
     */
    protected function whichCallableReflection(callable $callable): \ReflectionMethod|ReflectionFunction
    {

        if ($callable instanceof Closure) {
            # reflection on function  or anonymous function to be exact
            return new ReflectionFunction($callable);
        }

        # This reflects on the class method, this is so, we can better examine the method
        if (is_array($callable) && method_exists($callable[0], $callable[1]) ) {
            return new ReflectionMethod($callable[0], $callable[1]);
        }

        throw new Exception("$callable[1] doesnt exist in $callable[0]");
    }

}