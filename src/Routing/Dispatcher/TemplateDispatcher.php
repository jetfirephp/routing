<?php

namespace JetFire\Routing\Dispatcher;


use JetFire\Routing\ResponseInterface;
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
     * @var ResponseInterface
     */
    private $response;

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
     * @param Route $route
     */
    public function __construct(Route $route,ResponseInterface $response)
    {
        $this->route = $route;
        $this->response = $response;
    }

    /**
     * @description call template file
     */
    public function call()
    {
        if ($this->response->getStatusCode() == 202)
            $this->setContentType($this->route->getTarget('extension'));
        if (isset($this->route->getTarget('callback')[$this->route->getTarget('extension')]))
            $this->response->setContent(call_user_func_array($this->route->getTarget('callback')[$this->route->getTarget('extension')], [$this->route]));
        else {
            ob_start();
            if(isset($this->route->getTarget()['data']))extract($this->route->getTarget('data'));
            require($this->route->getTarget('template'));
            $this->response->setContent(ob_get_clean());
        }
    }

    /**
     * @param $extension
     */
    public function setContentType($extension){
        $this->response->setStatusCode(200);
        isset($this->types[$extension])
            ? $this->response->setHeaders(['Content-Type' => $this->types[$extension]])
            : $this->response->setHeaders(['Content-Type' => $this->types['html']]);
    }

}
