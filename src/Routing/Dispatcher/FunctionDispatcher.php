<?php

namespace JetFire\Routing\Dispatcher;


use JetFire\Routing\Route;

/**
 * Class FunctionDispatcher
 * @package JetFire\Routing\Dispatcher
 */
class FunctionDispatcher
{

    /**
     * @var Route
     */
    private $route;

    /**
     * @param Route $route
     */
    public function __construct(Route $route)
    {
        $this->route = $route;
    }

    /**
     * @description call anonymous function
     *
     */
    public function call()
    {
        if ($this->route->getResponse('code') == 202) $this->route->setResponse(['code' => 200, 'message' => 'OK', 'type' => 'text/html']);
        $params = ($this->route->getParameters() == '') ? [] : $this->route->getParameters();
        echo call_user_func_array($this->route->getTarget('closure'), $params);
    }

}
