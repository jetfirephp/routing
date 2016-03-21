<?php

namespace JetFire\Routing;
use ReflectionMethod;


/**
 * Class Middleware
 * @package JetFire\Routing
 */
class Middleware
{

    /**
     * @var Router
     */
    private $router;

    /**
     * @param Router $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * @description global middleware
     */
    public function globalMiddleware()
    {
        if (isset($this->router->collection->middleware['global_middleware']))
            foreach ($this->router->collection->middleware['global_middleware'] as $class)
                if (class_exists($class)) $this->callHandler($class);
    }

    /**
     * @description block middleware
     */
    public function blockMiddleware()
    {
        if (isset($this->router->collection->middleware['block_middleware']))
            if (isset($this->router->collection->middleware['block_middleware'][$this->router->route->getTarget('block')]) && class_exists($this->router->collection->middleware['block_middleware'][$this->router->route->getTarget('block')])) {
                $class = $this->router->collection->middleware['block_middleware'][$this->router->route->getTarget('block')];
                $this->callHandler($class);
            }
    }

    /**
     * @description controller middleware
     */
    public function classMiddleware()
    {
        if (isset($this->router->collection->middleware['class_middleware'])) {
            $ctrl = str_replace('\\', '/', $this->router->route->getTarget('controller'));
            if (isset($this->router->collection->middleware['class_middleware'][$ctrl]) && class_exists($this->router->route->getTarget('controller'))) {
                $class = $this->router->collection->middleware['class_middleware'][$ctrl];
                $this->callHandler($class);
            }
        }
    }

    /**
     * @description route middleware
     */
    public function routeMiddleware()
    {
        if (isset($this->router->collection->middleware['route_middleware']))
            if (isset($this->router->route->getPath()['middleware']) && class_exists($this->router->collection->middleware['route_middleware'][$this->router->route->getPath()['middleware']])) {
                $class = $this->router->collection->middleware['route_middleware'][$this->router->route->getPath()['middleware']];
                $this->callHandler($class);
            }
    }

    /**
     * @param $class
     * @return mixed
     */
    private function callHandler($class){
        $instance = call_user_func($this->router->getConfig()['di'],$class);
        if (method_exists($instance, 'handle')) {
            $reflectionMethod = new ReflectionMethod($instance, 'handle');
            $dependencies = [];
            foreach ($reflectionMethod->getParameters() as $arg)
                if (!is_null($arg->getClass()))
                    $dependencies[] = $this->getClass($arg->getClass()->name);
            $dependencies = array_merge($dependencies, [$this->router->route]);
            $reflectionMethod->invokeArgs($instance, $dependencies);
        }
    }

    /**
     * @param $class
     * @return Route|RouteCollection|Router|mixed
     */
    private function getClass($class){
        switch($class){
            case 'JetFire\Routing\Route':
                return $this->router->route;
                break;
            case 'JetFire\Routing\Router':
                return $this->router;
                break;
            case 'JetFire\Routing\RouteCollection':
                return $this->router->collection;
                break;
            default:
                return call_user_func_array($this->router->getConfig()['di'],[$class]);
                break;
        }
    }
}
