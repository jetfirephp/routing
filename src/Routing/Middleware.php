<?php

namespace JetFire\Routing;


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
            foreach ($this->router->collection->middleware['global_middleware'] as $mid) {
                if (class_exists($mid)) {
                    $mid_global = new $mid;
                    if (method_exists($mid_global, 'handle')) $mid_global->handle($this->router->route);
                }
            }
    }

    /**
     * @description block middleware
     */
    public function blockMiddleware()
    {
        if (isset($this->router->collection->middleware['block_middleware']))
            if (isset($this->router->collection->middleware['block_middleware'][$this->router->route->getBlock()]) && class_exists($this->router->collection->middleware['block_middleware'][$this->router->route->getBlock()])) {
                $class = $this->router->collection->middleware['block_middleware'][$this->router->route->getBlock()];
                $mid_block = new $class;
                if (method_exists($mid_block, 'handle')) $mid_block->handle($this->router->route);
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
                $mid_class = new $class;
                if (method_exists($mid_class, 'handle')) $mid_class->handle($this->router->route);
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
                $mid_route = new $class;
                if (method_exists($mid_route, 'handle')) $mid_route->handle($this->router->route);
            }
    }

} 