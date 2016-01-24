<?php

namespace JetFire\Routing\Dispatcher;


use JetFire\Routing\Router;

/**
 * Class FunctionDispatcher
 * @package JetFire\Routing\Dispatcher
 */
class FunctionDispatcher
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
     * @description call anonymous function
     */
    public function call()
    {
        $this->router->route->setResponse(['code' => 200, 'message' => 'OK', 'type' => 'text/html']);
        $params = ($this->router->route->getParameters() == '') ? [] : $this->router->route->getParameters();
        echo call_user_func_array($this->router->route->getTarget('function'), $params);
    }

} 