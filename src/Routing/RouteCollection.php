<?php

namespace JetFire\Routing;


/**
 * Class RouteCollection
 * @package JetFire\Routing
 */
class RouteCollection
{

    /**
     * @var array
     */
    private $routes = [];
    /**
     * @var array
     */
    public $routesByName = [];
    /**
     * @var int
     */
    public $countRoutes = 0;
    /**
     * @var
     */
    public $middleware;

    /**
     * @param array $routes
     * @param null $prefix
     * @throws \Exception
     */
    public function __construct($routes = null, $prefix = null)
    {
        if (!is_null($routes)) $this->addRoutes($routes, $prefix);
    }

    /**
     * @param array $routes
     * @param null $prefix
     * @throws \Exception
     */
    public function addRoutes($routes, $prefix = null)
    {
        if (is_string($routes) && strpos($routes, '.php') === false) $routes = trim($routes, '/') . '/';
        if (is_array($routes)) $routes = ['', $routes];
        elseif (is_file($routes . '/routes.php') && is_array($routesFile = include $routes . '/routes.php')) $routes = [$routes, $routesFile];
        elseif (is_file($routes) && is_array($routesFile = include $routes)) $routes = [str_replace(basename($routes), '', $routes), $routesFile];
        else throw new \InvalidArgumentException('Argument for "' . get_called_class() . '" constructor is not recognized. Accepted argument array and file containing array');
        $this->routes['path_' . $this->countRoutes] = $routes[0];
        if (!is_null($prefix)) $this->routes['prefix_' . $this->countRoutes] = '/' . trim($prefix, '/');
        $this->routes['routes_' . $this->countRoutes] = $routes[1];
        $this->countRoutes++;
    }

    /**
     * @param null $key
     * @return array
     */
    public function getRoutes($key = null)
    {
        if(!is_null($key))
            return isset($this->routes[$key])?$this->routes[$key]:'';
        return $this->routes;
    }

    /**
     * @param null $name
     * @param array $params
     * @return mixed
     */
    public function getRoutePath($name, $params = [])
    {
        foreach ($this->routesByName as $key => $route) {
            $param = explode('@', $key);
            foreach ($params as $key2 => $value) $route = str_replace(':' . $key2, $value, $route);
            if ($param[0] == trim($name, '/')) return $route;
            else if (isset($param[1]) && $param[1] == $name) return $route;
        }
        return null;
    }

    /**
     * @param $args
     */
    public function setPrefix($args)
    {
        if (is_array($args)) {
            $nbrArgs = count($args);
            for ($i = 0; $i < $nbrArgs; ++$i)
                $this->routes['prefix_' . $i] = '/' . trim($args[$i], '/');
        } elseif (is_string($args))
            for ($i = 0; $i < $this->countRoutes; ++$i)
                $this->routes['prefix_' . $i] = '/' . trim($args, '/');

    }

    /**
     * @param $middleware
     * @throws \Exception
     */
    public function setMiddleware($middleware)
    {
        if (is_string($middleware)) $middleware = trim($middleware, '/');
        if (is_file($middleware) && is_array($mid = include $middleware))
            $this->middleware = $mid;
        elseif (is_array($middleware))
            $this->middleware = $middleware;
        else throw new \InvalidArgumentException('Accepted argument for setMiddleware are array and array file');
    }

    /**
     * @return bool
     */
    public function generateRoutesPath()
    {
        $root = 'http://' . $_SERVER['SERVER_NAME'] . str_replace('/index.php', '', $_SERVER['SCRIPT_NAME']);
        $count = 0;
        for ($i = 0; $i < $this->countRoutes; ++$i) {
            $prefix = (isset($this->routes['prefix_' . $i])) ? $this->routes['prefix_' . $i] : '';
            if (isset($this->routes['routes_' . $i]))
                foreach ($this->routes['routes_' . $i] as $route => $dependencies) {
                    if (is_array($dependencies) && isset($dependencies['use']))
                        $use = (is_object($dependencies['use'])) ? 'closure-' . $count : trim($dependencies['use'], '/');
                    else
                        $use = trim($dependencies, '/');
                    isset($dependencies['name']) ? $this->routesByName[$use . '@' . $dependencies['name']] = $root . $prefix . $route : $this->routesByName[$use] = $root . $prefix . $route;
                    $count++;
                }
        }
        return true;
    }

} 