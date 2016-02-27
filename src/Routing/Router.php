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
        'viewExtension'      => ['.html', '.php', '.json', '.xml'],
        'viewCallback'       => [],
        'di'                 => '',
        'generateRoutesPath' => false,
    ];

    /**
     * @param RouteCollection $collection
     * @param ResponseInterface $response
     * @param Route $route
     */
    public function __construct(RouteCollection $collection,ResponseInterface $response = null,Route $route = null)
    {
        $this->collection = $collection;
        $this->response = is_null($response)? new Response() : $response;
        $this->response->setStatusCode(404);
        $this->route = is_null($response)? new Route() : $route;
        $this->config['di'] = function($class){
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
     * @param string $matcher
     */
    public function addMatcher($matcher){
        $this->config['matcher'][] = $matcher;
    }

    /**
     * @description main function
     */
    public function run()
    {
        $this->setUrl();
        if ($this->config['generateRoutesPath']) $this->collection->generateRoutesPath();
        if ($this->match()) {
            $this->handle();
            $this->callTarget();
        }
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
        foreach ($this->config['matcher'] as $matcher) {
            $this->config['matcherInstance'][$matcher] = new $matcher($this);
            if (call_user_func([$this->config['matcherInstance'][$matcher], 'match']))
                return true;
        }
        return false;
    }

    /**
     * @return mixed
     */
    public function callTarget()
    {
        $target = $this->route->getTarget('dispatcher');
        if(!empty($target)) {
            $this->dispatcher = new $target($this->route, $this->response);
            return call_user_func([$this->dispatcher, 'call']);
        }
        return null;
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
            foreach($this->config['matcherInstance'] as $matcher) {
                foreach (call_user_func([$matcher, 'getMatcher']) as $match)
                    if (call_user_func([$matcher, $match])){ $this->callTarget(); break; }
            }
            $this->response->setStatusCode($code);
        }
        $this->response->send();
    }
}
