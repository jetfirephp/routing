<?php

namespace JetFire\Routing;

/**
 * Class Router
 * @package JetFire\Routing
 */
class Router
{

    /**
     * @var Route
     */
    public $route;
    /**
     * @var RouteCollection
     */
    public $collection;
    /**
     * @var ResponseInterface
     */
    public $response;
    /**
     * @var array
     */
    public $middlewareCollection = [];
    /**
     * @var array
     */
    public $matcher = [];
    /**
     * @var
     */
    public $dispatcher;
    /**
     * @var array
     */
    private $config = [
        'templateExtension' => ['.html', '.php', '.json', '.xml'],
        'templateCallback' => [],
        'di' => '',
        'generateRoutesPath' => false,
    ];

    /**
     * @var array
     */
    public $server = [];

    /**
     * @param RouteCollection $collection
     * @param ResponseInterface $response
     * @param Route $route
     */
    public function __construct(RouteCollection $collection, ResponseInterface $response = null, Route $route = null)
    {
        $this->collection = $collection;
        $this->response = $response === null ? new Response() : $response;
        $this->route = $route === null ? new Route() : $route;
        $this->config['di'] = static function ($class) {
            return new $class;
        };
    }

    /**
     * @param array $config
     */
    public function setConfig($config)
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param object|array $middleware
     */
    public function setMiddleware($middleware)
    {
        $this->middlewareCollection = is_array($middleware)
            ? $middleware
            : [$middleware];
    }

    /**
     * @param MiddlewareInterface $middleware
     */
    public function addMiddleware(MiddlewareInterface $middleware)
    {
        $this->middlewareCollection[] = $middleware;
    }

    /**
     * @param object|array $matcher
     */
    public function setMatcher($matcher)
    {
        $this->matcher = is_array($matcher)
            ? $matcher
            : [$matcher];
    }

    /**
     * @param string $matcher
     */
    public function addMatcher($matcher)
    {
        $this->matcher[] = $matcher;
    }

    /**
     * @description main function
     */
    public function run()
    {
        $this->setUrl();
        if ($this->config['generateRoutesPath']) {
            $this->collection->generateRoutesPath();
        }
        if ($this->match() === true) {
            $this->callMiddleware('before');
            if (!in_array(substr($this->response->getStatusCode(), 0, 1), [3, 4, 5], true)) {
                $this->callTarget();
            }
        } else {
            $this->response->setStatusCode(404);
        }
        $this->callMiddleware('after');
        return $this->response->send();
    }

    /**
     * @description call the middleware before and after the target
     * @param $action
     */
    public function callMiddleware($action)
    {
        foreach ($this->middlewareCollection as $middleware) {
            if ($middleware instanceof MiddlewareInterface) {
                foreach ($middleware->getCallbacks($action) as $callback) {
                    if (method_exists($middleware, $callback)) {
                        $middleware->$callback($action);
                    }
                }
            }
        }
    }

    /**
     * @param null $url
     */
    public function setUrl($url = null)
    {
        if ($url === null) {
            $url = (isset($_GET['url']) ? $_GET['url'] : substr(str_replace(str_replace('/index.php', '', $_SERVER['SCRIPT_NAME']), '', $_SERVER['REQUEST_URI']), 1));
        }
        $this->server['http_host'] = ($this->server['protocol'] = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http') . '://' . ($host = (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST']));
        $this->server['host'] = explode(':', $host)[0];
        $this->server['domain'] = $this->collection->getDomain($this->server['http_host']);
        $this->server['uri'] = trim(explode('?', $url)[0], '/');
        $this->route->setUrl($this->server['host'] . '/' . $this->server['uri']);
    }

    /**
     * @return bool
     */
    public function match()
    {
        foreach ($this->matcher as $key => $matcher) {
            if (call_user_func([$this->matcher[$key], 'match'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * @description call the target for the request uri
     */
    public function callTarget()
    {
        $target = is_array($this->route->getTarget('dispatcher')) ? $this->route->getTarget('dispatcher') : [$this->route->getTarget('dispatcher')];
        if (!empty($target)) {
            foreach ($target as $call) {
                $this->dispatcher = new $call($this);
                call_user_func([$this->dispatcher, 'call']);
            }
        }
    }

    /**
     * @param null $name
     * @param array $params
     * @return mixed
     */
    public function getRoutePath($name, $params = [])
    {
        foreach ($this->route->getDetail()['keys'] as $key => $data) {
            if (!isset($params[$data['key']]) && $data['required']) {
                $params[$data['key']] = $data['value'];
            }
            if (!$data['required']) {
                if (isset($params[$data['key']])) {
                    if (!empty($params[$data['key']])) {
                        $params[$key] = strtr($key, [':' . $data['key'] => $params[$data['key']], '[' => '', ']' => '']);
                    }
                    unset($params[$data['key']]);
                } else if (!empty($data['value'])) {
                    $params[$key] = strtr($key, [':' . $data['key'] => $data['value'], '[' => '', ']' => '']);
                } else {
                    $params[$key] = '';
                }
            }
        }
        foreach ($params as $key => $param) {
            if ($key[0] !== '[') {
                $params[':' . $key] = $param;
                unset($params[$key]);
            }
        }
        return $this->collection->getRoutePath($name, $params);
    }
}
