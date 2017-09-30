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
    protected $callbacks = [
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
     */
    public function globalMiddleware($action)
    {
        if (isset($this->middleware[$action]['global_middleware'])) {
            $this->callHandlers($this->middleware[$action]['global_middleware']);
        }
    }

    /**
     * @description block middleware
     * @param $action
     */
    public function blockMiddleware($action)
    {
        if (isset($this->middleware[$action]['block_middleware'])) {
            if (isset($this->middleware[$action]['block_middleware'][$this->router->route->getTarget('block')])) {
                $blocks = $this->middleware[$action]['block_middleware'][$this->router->route->getTarget('block')];
                $this->callHandlers($blocks);
            }
        }
    }

    /**
     * @description controller middleware
     * @param $action
     */
    public function classMiddleware($action)
    {
        if (isset($this->middleware[$action]['class_middleware'])) {
            $ctrl = str_replace('\\', '/', $this->router->route->getTarget('controller'));
            if (isset($this->middleware[$action]['class_middleware'][$ctrl]) && class_exists($this->router->route->getTarget('controller'))) {
                $classes = $this->middleware[$action]['class_middleware'][$ctrl];
                $this->callHandlers($classes);
            }
        }
    }

    /**
     * @description route middleware
     * @param $action
     */
    public function routeMiddleware($action)
    {
        if (isset($this->middleware[$action]['route_middleware'])) {
            if (isset($this->router->route->getPath()['middleware']) && class_exists($this->middleware[$action]['route_middleware'][$this->router->route->getPath()['middleware']])) {
                $classes = $this->middleware[$action]['route_middleware'][$this->router->route->getPath()['middleware']];
                $this->callHandlers($classes);
            }
        }
    }

    /**
     * @param $handlers
     */
    private function callHandlers($handlers){
        $handlers = is_array($handlers) ? $handlers : [$handlers];
        foreach ($handlers as $handler) {
            if($this->handle($handler) !== true){
                break;
            }
        }
    }

    /**
     * @param $callback
     * @return mixed
     */
    private function handle($callback)
    {
        $callback = explode('@', $callback);
        $response = true;
        $method = isset($callback[1]) ? $callback[1] : 'handle';
        if (class_exists($callback[0])) {
            $instance = call_user_func($this->router->getConfig()['di'], $callback[0]);
            if (method_exists($instance, $method)) {
                $reflectionMethod = new ReflectionMethod($instance, $method);
                $dependencies = [];
                foreach ($reflectionMethod->getParameters() as $arg) {
                    if (!is_null($arg->getClass())) {
                        $dependencies[] = $this->getClass($arg->getClass()->name);
                    }
                }
                $dependencies = array_merge($dependencies, [$this->router->route]);
                $response = $reflectionMethod->invokeArgs($instance, $dependencies);
                if ($response instanceof ResponseInterface) {
                    $this->router->response = $response;
                }
            }
        }
        return $response;
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
            case ResponseInterface::class:
                return $this->router->response;
            default:
                return call_user_func_array($this->router->getConfig()['di'], [$class]);
        }
    }
}
