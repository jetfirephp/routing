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
     * @var
     */
    public $middleware;
    /**
     * @var
     */
    public $dispatcher;

    /**
     * @var array
     */
    private $config = [
        'matcher'            => ['JetFire\Routing\Match\RoutesMatch', 'JetFire\Routing\Match\SmartMatch'],
        'viewPath'           => 'Views',
        'viewExtension'      => ['.html', '.php', '.json', '.xml'],
        'viewCallback'       => [],
        'controllerPath'     => 'Controllers',
        'generateRoutesPath' => false,
    ];

    /**
     * @param RouteCollection $collection
     */
    public function __construct(RouteCollection $collection)
    {
        $this->collection = $collection;
        $this->route = new Route();
    }

    /**
     * @param array $config
     */
    public function setConfig($config)
    {
        $this->config = array_merge($this->config, $config);
        if (!empty($this->config['viewPath'])) $this->config['viewPath'] = trim($this->config['viewPath'], '/');
        if (!empty($this->config['controllerPath'])) $this->config['controllerPath'] = trim($this->config['controllerPath'], '/');
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @description main function to execute the router
     */
    public function run()
    {
        $url = (isset($_GET['url'])) ? $_GET['url'] : substr(str_replace(str_replace('/index.php', '', $_SERVER['SCRIPT_NAME']), '', $_SERVER['REQUEST_URI']), 1);
        $this->route->setUrl('/' . trim(explode('?', $url)[0], '/'));
        if ($this->config['generateRoutesPath']) $this->collection->generateRoutesPath();
        foreach ($this->config['matcher'] as $matcher) {
            $this->config['matcherInstance'][$matcher] = new $matcher($this);
            if (call_user_func([$this->config['matcherInstance'][$matcher], 'match'])) {
                $this->handle();
                $this->callTarget();
                break;
            }
        }
        $this->callResponse();
        var_dump($this->route);
    }

    /**
     * @return mixed
     */
    private function callTarget()
    {
        $target = $this->route->getTarget('dispatcher');
        $this->dispatcher = new $target($this);
        return call_user_func([$this->dispatcher, 'call']);
    }

    /**
     * @description handle middleware
     */
    private function handle()
    {
        $this->middleware = new Middleware($this);
        $this->middleware->globalMiddleware();
        $this->middleware->blockMiddleware();
        $this->middleware->classMiddleware();
        $this->middleware->routeMiddleware();
    }

    /**
     * @param array $responses
     */
    public function setResponse($responses = [])
    {
        $this->route->setResponse('templates', $responses);
    }

    /**
     * @description set response code
     */
    private function callResponse()
    {
        if (isset($this->route->getResponse()['templates']) && isset($this->route->getResponse()['templates'][$this->route->getResponse('code')])) {
            $this->route->setCallback($this->route->getResponse()['templates'][$this->route->getResponse('code')]);
            $matcher = $this->config['matcherInstance']['JetFire\Routing\Match\RoutesMatch'];
            if (call_user_func([$matcher, 'anonymous']) || call_user_func([$matcher, 'mvc']) || call_user_func([$matcher, 'template']))
                $this->callTarget();
        }
        http_response_code($this->route->getResponse('code'));
    }
} 