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
     * @var
     */
    public $middleware;
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
        $this->response->setStatusCode(404);
        $this->route = is_null($route) ? new Route() : $route;
        $this->middleware = new Middleware($this);
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
     * @param object|array $matcher
     */
    public function setMatcher($matcher)
    {
        if (is_object($matcher))
            $matcher = [$matcher];
        $this->matcher = $matcher;
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
        if ($this->match()) $this->callTarget();
        $this->callResponse();
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
     *
     */
    public function callTarget()
    {
        $target = is_array($this->route->getTarget('dispatcher')) ? $this->route->getTarget('dispatcher') : [$this->route->getTarget('dispatcher')];
        if (!empty($target)) {
            foreach ($target as $call) {
                $this->dispatcher = new $call($this->route, $this->response);
                call_user_func([$this->dispatcher, 'call']);
            }
        }
    }

    /**
     * @param array $responses
     */
    public function setResponses($responses = [])
    {
        $this->route->addDetail('response_templates', $responses);
    }

    /**
     * @description set response code
     */
    public function callResponse()
    {
        if (isset($this->route->getDetail()['response_templates']) && isset($this->route->getDetail()['response_templates'][$code = $this->response->getStatusCode()])) {
            $this->route->setCallback($this->route->getDetail()['response_templates'][$code]);
            $matcher = null;
            foreach ($this->matcher as $instance) if ($instance instanceof ArrayMatcher) $matcher = $instance;
            if (is_null($matcher)) $matcher = new ArrayMatcher($this);
            foreach (call_user_func([$matcher, 'getResolver']) as $match)
                if (is_array($target = call_user_func_array([$matcher, $match], [$this->route->getCallback()]))) {
                    call_user_func_array([$matcher, 'setTarget'], [$target]);
                    $this->callTarget();
                    break;
                }
            $this->response->setStatusCode($code);
        }
        if (isset($this->collection->middleware['before_render'])){
            foreach ($this->collection->middleware['before_render'] as $callback){
                $call = explode('@', $callback);
                if(isset($call[1])){
                    $classes = ['JetFire\Routing\Router' => $this];
                    $args = ['router' => $this, 'route' => $this->route, 'response' => $this->response];
                    $this->callMethod($call[0], $call[1], $args, $args, $classes);
                }
            }
        }

        $this->response->send();
    }

    /**
     * @param $controller
     * @param $method
     * @param array $methodArgs
     * @param array $ctrlArgs
     * @param array $classInstance
     * @return mixed|null
     */
    public function callMethod($controller, $method, $methodArgs = [], $ctrlArgs = [], $classInstance = [])
    {
        if (class_exists($controller) && method_exists($controller, $method)) {
            $reflectionMethod = new ReflectionMethod($controller, $method);
            $dependencies = [];
            foreach ($reflectionMethod->getParameters() as $arg) {
                if (isset($methodArgs[$arg->name]))
                    array_push($dependencies, $methodArgs[$arg->name]);
                else if (!is_null($arg->getClass())) {
                    array_push($dependencies, call_user_func_array($this->getConfig()['di'], [$arg->getClass()->name]));
                }
            }
            $dependencies = array_merge($dependencies, $methodArgs);
            return $reflectionMethod->invokeArgs($this->callClass($controller, $ctrlArgs, $classInstance), $dependencies);
        }
        return null;
    }

    /**
     * @param $controller
     * @param array $ctrlArgs
     * @param array $classInstance
     * @return object
     * @throws \Exception
     */
    public function callClass($controller, $ctrlArgs = [], $classInstance = [])
    {
        $reflector = new ReflectionClass($controller);
        if (!$reflector->isInstantiable())
            throw new \Exception('Controller [' . $controller . '] is not instantiable.');
        $constructor = $reflector->getConstructor();
        if (is_null($constructor))
            return call_user_func_array($this->getConfig()['di'], [$controller]);
        $dependencies = [];
        foreach ($constructor->getParameters() as $arg) {
            if (isset($ctrlArgs[$arg->name]))
                array_push($dependencies, $ctrlArgs[$arg->name]);
            else if (isset($classInstance[$arg->getClass()->name]))
                array_push($dependencies, $classInstance[$arg->getClass()->name]);
            else if (!is_null($arg->getClass())) {
                array_push($dependencies, call_user_func_array($this->getConfig()['di'], [$arg->getClass()->name]));
            }
        }
        $dependencies = array_merge($dependencies, $ctrlArgs);
        return $reflector->newInstanceArgs($dependencies);
    }

}
