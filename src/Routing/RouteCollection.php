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
    public $matcher;

    /**
     * @param array $routes
     * @param array $options
     */
    public function __construct($routes = null, $options = [])
    {
        if (!is_null($routes) || !empty($options)) $this->addRoutes($routes, $options);
    }

    /**
     * @param array|string $routes
     * @param array $options
     */
    public function addRoutes($routes = null, $options = [])
    {
        if (!is_null($routes) && !is_array($routes)) {
            if (strpos($routes, '.php') === false) $routes = trim($routes, '/') . '/';
            if (is_file($routes . '/routes.php') && is_array($routesFile = include $routes . '/routes.php')) $routes = $routesFile;
            elseif (is_file($routes) && is_array($routesFile = include $routes)) $routes = $routesFile;
            else throw new \InvalidArgumentException('Argument for "' . get_called_class() . '" constructor is not recognized. Expected argument array or file containing array but "' . $routes . '" given');
        }
        $this->routes['routes_' . $this->countRoutes] = is_array($routes) ? $routes : [];
        $this->setRoutes($options, $this->countRoutes);
        $this->countRoutes++;
    }

    /**
     * @param null $key
     * @return array
     */
    public function getRoutes($key = null)
    {
        if (!is_null($key))
            return isset($this->routes[$key]) ? $this->routes[$key] : '';
        return $this->routes;
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
        if ($this->countRoutes == 0) $this->countRoutes++;
    }

    /**
     * @param $args
     */
    public function setOption($args = [])
    {
        $nbrArgs = count($args);
        for ($i = 0; $i < $nbrArgs; ++$i) {
            if (is_array($args[$i])) {
                $this->setRoutes($args[$i], $i);
                if (!isset($this->routes['routes_' . $i])) $this->routes['routes_' . $i] = [];
            }
        }
        if ($this->countRoutes == 0) $this->countRoutes++;
    }

    /**
     * @param array $args
     * @param $i
     */
    private function setRoutes($args = [], $i)
    {
        $this->routes['block_' . $i] = (isset($args['block']) && !empty($args['block'])) ? rtrim($args['block'], '/') . '/' : '';
        $this->routes['view_dir_' . $i] = (isset($args['view_dir']) && !empty($args['view_dir'])) ? rtrim($args['view_dir'], '/') . '/' : '';
        $this->routes['ctrl_namespace_' . $i] = (isset($args['ctrl_namespace']) && !empty($args['ctrl_namespace'])) ? trim($args['ctrl_namespace'], '\\') . '\\' : '';
        $this->routes['prefix_' . $i] = (isset($args['prefix']) && !empty($args['prefix'])) ? '/' . trim($args['prefix'], '/') : '';
        $this->routes['subdomain_' . $i] = (isset($args['subdomain'])) ? $args['subdomain'] : '';
    }

    /**
     * @param string $root
     * @param string $script_file
     * @param string $protocol
     * @return bool
     */
    public function generateRoutesPath($root = null, $script_file = 'index.php', $protocol = 'http')
    {
        $protocol = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : $protocol;
        $domain = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : null;
        $root = (is_null($root))
            ? $protocol . '://' . $domain . ((!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != 80) ? ':' . $_SERVER['SERVER_PORT'] : '') . str_replace('/' . $script_file, '', $_SERVER['SCRIPT_NAME'])
            : $root;
        $new_domain = $this->getDomain($root);
        if (!is_null($domain) && strpos($domain, $new_domain) !== false) {
            $root = str_replace($domain, $new_domain, $root);
        }
        $count = 0;
        for ($i = 0; $i < $this->countRoutes; ++$i) {
            $prefix = (isset($this->routes['prefix_' . $i])) ? $this->routes['prefix_' . $i] : '';
            $subdomain = (isset($this->routes['subdomain_' . $i])) ? $this->routes['subdomain_' . $i] : '';
            $url = (!empty($subdomain)) ? str_replace($protocol.'://',$protocol.'://'.$subdomain.'.' ,$root) : $root;
            if (isset($this->routes['routes_' . $i]))
                foreach ($this->routes['routes_' . $i] as $route => $dependencies) {
                    if (is_array($dependencies) && isset($dependencies['use']) && !is_array($dependencies['use'])) {
                        $use = (is_callable($dependencies['use'])) ? 'closure-' . $count : trim($dependencies['use'], '/');
                    } elseif (!is_array($dependencies)) {
                        $use = (is_callable($dependencies)) ? 'closure-' . $count : trim($dependencies, '/');
                    } else {
                        $use = $route;
                    }
                    if (isset($route[0]) && $route[0] == '/') {
                        $full_url = rtrim($url, '/') . '/' . trim($prefix, '/') . '/' . ltrim($route, '/');
                        (!is_callable($dependencies) && isset($dependencies['name']))
                            ? $this->routesByName[$use . '#' . $dependencies['name']] = $full_url
                            : $this->routesByName[$use] = $full_url;
                    } else {
                        (!is_callable($dependencies) && isset($dependencies['name']))
                            ? $this->routesByName[$use . '#' . $dependencies['name']] = $protocol . '://' . str_replace('{host}', $new_domain, $route) . $prefix
                            : $this->routesByName[$use] = $protocol . '://' . str_replace('{host}', $new_domain, $route) . $prefix;
                    }
                    $count++;
                }
        }
        return true;
    }

    /**
     * @param $url
     * @return string
     */
    public function getDomain($url)
    {
        $url = parse_url($url);
        $domain = $url['host'];
        if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs)) {
            return $regs['domain'];
        }
        return $domain;
    }

    /**
     * @param null $name
     * @param array $params
     * @param string $subdomain
     * @return mixed
     */
    public function getRoutePath($name, $params = [], $subdomain = '')
    {
        foreach ($this->routesByName as $key => $route) {
            $param = explode('#', $key);
            $route = str_replace('{subdomain}', $subdomain, $route);
            foreach ($params as $key2 => $value) $route = str_replace(':' . $key2, $value, $route);
            if ($param[0] == trim($name, '/')) return $route;
            else if (isset($param[1]) && $param[1] == $name) return $route;
        }
        return null;
    }
}
