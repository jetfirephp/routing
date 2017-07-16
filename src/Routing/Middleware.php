<?php

namespace JetFire\Routing;

use ReflectionMethod;


/**
 * Class Middleware
 * @package JetFire\Routing
 */
class Middleware implements MiddlewareInterface
{

    /**
     * @var Router
     */
    private $router;

    /**
     * @var array
     */
    private $callbacks = [
        'globalMiddleware',
        'blockMiddleware',
        'classMiddleware',
        'routeMiddleware',
    ];

    /**
     * @var array
     */
    private $middleware = [];

    /**
     * @param Router $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * @return array
     */
    public function getMiddleware()
    {
        return $this->middleware;
    }

    /**
     * @param $middleware
     * @return mixed|void
     */
    public function setBeforeCallback($middleware)
    {
        $this->setMiddleware('before', $middleware);
    }

    /**
     * @param $middleware
     * @return mixed|void
     */
    public function setAfterCallback($middleware)
    {
        $this->setMiddleware('after', $middleware);
    }

    /**
     * @param $action
     * @param $middleware
     */
    private function setMiddleware($action, $middleware)
    {
        if (is_string($middleware)) {
            $middleware = rtrim($middleware, '/');
        }
        if (is_array($middleware)) {
            $this->middleware[$action] = $middleware;
        } elseif (is_file($middleware) && is_array($mid = include $middleware)) {
            $this->middleware[$action] = $mid;
        } else {
            throw new \InvalidArgumentException('Accepted argument for setMiddleware are array and array file');
        }
    }

    /**
     * @return Router
     */
    public function getCallbacks()
    {
        return $this->callbacks;
    }

    /**
     * @description global middleware
     * @param $action
     * @return bool|mixed
     */
    public function globalMiddleware($action)
    {
        if (isset($this->middleware[$action]['global_middleware'])) {
            foreach ($this->middleware[$action]['global_middleware'] as $class) {
                if (class_exists($class)) return $this->callHandler($class);
            }
        }
        return true;
    }

    /**
     * @description block middleware
     * @param $action
     * @return bool|mixed
     */
    public function blockMiddleware($action)
    {
        if (isset($this->middleware[$action]['block_middleware'])) {
            if (isset($this->middleware[$action]['block_middleware'][$this->router->route->getTarget('block')])) {
                $blocks = $this->middleware[$action]['block_middleware'][$this->router->route->getTarget('block')];
                if (is_array($blocks)) {
                    foreach ($blocks as $block) {
                        if (class_exists($block)) {
                            if ($this->callHandler($block) === false) return false;
                        }
                    }
                } elseif (is_string($blocks) && class_exists($blocks)) {
                    return $this->callHandler($blocks);
                }
            }
        }
        return true;
    }

    /**
     * @description controller middleware
     * @param $action
     * @return bool|mixed
     */
    public function classMiddleware($action)
    {
        if (isset($this->middleware[$action]['class_middleware'])) {
            $ctrl = str_replace('\\', '/', $this->router->route->getTarget('controller'));
            if (isset($this->middleware[$action]['class_middleware'][$ctrl]) && class_exists($this->router->route->getTarget('controller'))) {
                $classes = $this->middleware[$action]['class_middleware'][$ctrl];
                if (is_array($classes)) {
                    foreach ($classes as $class) {
                        if ($this->callHandler($class) === false) return false;
                    }
                } elseif (is_string($classes)) {
                    return $this->callHandler($classes);
                }
            }
        }
        return true;
    }

    /**
     * @description route middleware
     * @param $action
     * @return bool|mixed
     */
    public function routeMiddleware($action)
    {
        if (isset($this->middleware[$action]['route_middleware'])) {
            if (isset($this->router->route->getPath()['middleware']) && class_exists($this->middleware[$action]['route_middleware'][$this->router->route->getPath()['middleware']])) {
                $classes = $this->middleware[$action]['route_middleware'][$this->router->route->getPath()['middleware']];
                if (is_array($classes)) {
                    foreach ($classes as $class) {
                        if ($this->callHandler($class) === false) return false;
                    }
                } elseif (is_string($classes)) {
                    return $this->callHandler($classes);
                }
            }
        }
        return true;
    }

    /**
     * @param $class
     * @return mixed
     */
    private function callHandler($class)
    {
        $class = explode('@', $class);
        $method = isset($class[1]) ? $class[1] : 'handle';
        $instance = call_user_func($this->router->getConfig()['di'], $class[0]);
        if (method_exists($instance, $method)) {
            $reflectionMethod = new ReflectionMethod($instance, $method);
            $dependencies = [];
            foreach ($reflectionMethod->getParameters() as $arg) {
                if (!is_null($arg->getClass())) {
                    $dependencies[] = $this->getClass($arg->getClass()->name);
                }
            }
            $dependencies = array_merge($dependencies, [$this->router->route]);
            return $reflectionMethod->invokeArgs($instance, $dependencies);
        }
        return true;
    }

    /**
     * @param $class
     * @return Route|RouteCollection|Router|mixed
     */
    private function getClass($class)
    {
        switch ($class) {
            case Route::class:
                return $this->router->route;
            case Router::class:
                return $this->router;
            case RouteCollection::class:
                return $this->router->collection;
            case Response::class:
                return $this->router->response;
            default:
                return call_user_func_array($this->router->getConfig()['di'], [$class]);
        }
    }
}
