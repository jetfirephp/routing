<?php

namespace JetFire\Routing\Dispatcher;

use JetFire\Routing\Router;

/**
 * Class TemplateDispatcher
 * @package JetFire\Routing\Dispatcher
 */
class TemplateDispatcher implements DispatcherInterface
{

    /**
     * @var Router
     */
    private $router;

    /**
     * @var array
     */
    protected $types = [
        'json' => 'application/json',
        'xml' => 'application/xml',
        'txt' => 'text/plain',
        'html' => 'text/html'
    ];

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
        $this->setContentType($this->router->route->getTarget('extension'));
        if (isset($this->router->route->getTarget('callback')[$this->router->route->getTarget('extension')])) {
            $this->router->response->setContent(call_user_func_array($this->router->route->getTarget('callback')[$this->router->route->getTarget('extension')], [$this->router->route]));
        } else {
            ob_start();
            if (isset($this->router->route->getTarget()['data'])) extract($this->router->route->getTarget('data'));
            if (isset($this->router->route->getParams()['data'])) extract($this->router->route->getParams()['data']);
            require($this->router->route->getTarget('template'));
            $this->router->response->setContent(ob_get_clean());
        }
    }

    /**
     * @param $extension
     */
    public function setContentType($extension)
    {
        isset($this->types[$extension])
            ? $this->router->response->setHeaders(['Content-Type' => $this->types[$extension]])
            : $this->router->response->setHeaders(['Content-Type' => $this->types['html']]);
    }

}
