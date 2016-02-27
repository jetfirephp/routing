<?php

namespace JetFire\Routing\Dispatcher;


use JetFire\Routing\ResponseInterface;
use JetFire\Routing\Route;

/**
 * Class ClosureDispatcher
 * @package JetFire\Routing\Dispatcher
 */
class ClosureDispatcher implements DispatcherInterface
{
    /**
     * @var Route
     */
    private $route;

    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @param Route $route
     */
    public function __construct(Route $route,ResponseInterface $response)
    {
        $this->route = $route;
        $this->response = $response;
    }


    /**
     * @description call anonymous function
     */
    public function call()
    {
        if ($this->response->getStatusCode() == 202) {
            $this->response->setStatusCode(200);
            $this->response->setHeaders(['Content-Type' => 'text/html']);
        }
        $params = ($this->route->getParameters() == '') ? [] : $this->route->getParameters();
        $this->response->setContent(call_user_func_array($this->route->getTarget('closure'), $params));
    }

}
