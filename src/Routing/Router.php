<?php

namespace JetFire\Routing;

use JetFire\Routing\Matcher\ArrayMatcher;
use ReflectionClass;
use ReflectionMethod;

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
     * @param RouteCollection $collection
     * @param ResponseInterface $response
     * @param Route $route
     */
    public function __construct(RouteCollection $collection, ResponseInterface $response = null, Route $route = null)
    {
        $this->collection = $collection;
        $this->response = is_null($response) ? new Response() : $response;
        $this->route = is_null($route) ? new Route() : $route;
        $this->config['di'] = function ($class) {
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
        if ($this->config['generateRoutesPath']) $this->collection->generateRoutesPath();
        if ($this->match() === true) {
            $this->callMiddleware('before');
            if (!in_array(substr($this->response->getStatusCode(), 0, 1), [3,4,5])) {
                $this->callTarget();
            }
        }else{
            $this->response->setStatusCode(404);
        }
        $this->callMiddleware('after');
        return $this->response->send();
    }

    /**
     * @description call the middleware before and after the target
     * @param $action
     */
    private function callMiddleware($action)
    {
        foreach ($this->middlewareCollection as $middleware) {
            if ($middleware instanceof MiddlewareInterface) {
                foreach ($middleware->getCallbacks($action) as $callback) {
                    if (method_exists($middleware, $callback)) {
                        call_user_func_array([$middleware, $callback], [$action]);
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
        if (is_null($url))
            $url = (isset($_GET['url'])) ? $_GET['url'] : substr(str_replace(str_replace('/index.php', '', $_SERVER['SCRIPT_NAME']), '', $_SERVER['REQUEST_URI']), 1);
        $this->route->setUrl('/' . trim(explode('?', $url)[0], '/'));
    }

    /**
     * @return bool
     */
    public function match()
    {
        foreach ($this->matcher as $key => $matcher) {
            if (call_user_func([$this->matcher[$key], 'match'])) return true;
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
}
