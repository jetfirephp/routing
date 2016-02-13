<?php

namespace JetFire\Routing\Dispatcher;


use JetFire\Routing\Route;

/**
 * Class TemplateDispatcher
 * @package JetFire\Routing\Dispatcher
 */
class TemplateDispatcher implements DispatcherInterface
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
     * @description call template file
     */
    public function call()
    {
        if ($this->route->getResponse('code') == 202)
            switch ($this->route->getTarget('extension')) {
                case 'json':
                    $this->route->setResponse(['code' => 200, 'message' => 'OK', 'type' => 'application/json']);
                    header('Content-Type: application/json');
                    break;
                case 'xml':
                    $this->route->setResponse(['code' => 200, 'message' => 'OK', 'type' => 'application/xml']);
                    header('Content-Type: application/xml');
                    break;
                default:
                    $this->route->setResponse(['code' => 200, 'message' => 'OK', 'type' => 'text/html']);
                    break;
            }
        if (isset($this->route->getTarget()['callback'][$this->route->getTarget('extension')]))
            call_user_func_array($this->route->getTarget()['callback'][$this->route->getTarget('extension')], [$this->route]);
        else
            require($this->route->getTarget('template'));
    }

}
