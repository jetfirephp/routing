<?php

namespace JetFire\Routing\Dispatcher;


use JetFire\Routing\Router;

/**
 * Class TemplateDispatcher
 * @package JetFire\Routing\Dispatcher
 */
class TemplateDispatcher
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
     * @description call template file
     */
    public function call()
    {
        if ($this->router->route->getResponse('code') == 202)
            switch ($this->router->route->getTarget('extension')) {
                case 'json':
                    $this->router->route->setResponse(['code' => 200, 'message' => 'OK', 'type' => 'application/json']);
                    header('Content-Type: application/json');
                    break;
                case 'xml':
                    $this->router->route->setResponse(['code' => 200, 'message' => 'OK', 'type' => 'application/xml']);
                    header('Content-Type: application/xml');
                    break;
                default:
                    $this->router->route->setResponse(['code' => 200, 'message' => 'OK', 'type' => 'text/html']);
                    break;
            }
        if (isset($this->router->getConfig()['viewCallback'][$this->router->route->getTarget('extension')]))
            call_user_func_array($this->router->getConfig()['viewCallback'][$this->router->route->getTarget('extension')], [$this->router->route]);
        else
            require($this->router->route->getTarget('template'));
    }

} 