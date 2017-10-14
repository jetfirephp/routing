<?php

namespace JetFire\Routing\Dispatcher;

use JetFire\Routing\Response;
use JetFire\Routing\ResponseInterface;
use JetFire\Routing\Route;
use JetFire\Routing\RouteCollection;
use JetFire\Routing\Router;

/**
 * Class ClosureDispatcher
 * @package JetFire\Routing\Dispatcher
 */
class ClosureDispatcher implements DispatcherInterface
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
        $classInstance = [
            Response::class => $this->router->response,
            Route::class => $this->router->route,
            RouteCollection::class => $this->router->collection,
        ];

        $params = empty($this->router->route->getParameters()) ? $classInstance : array_merge($this->router->route->getParameters(), $classInstance);
        $content = call_user_func_array($this->router->route->getTarget('closure'), $params);
        if ($content instanceof ResponseInterface) {
            $this->router->response = $content;
        } else {
            if (is_array($content)) {
                $this->router->route->addTarget('data', $content);
                $content = json_encode($content);
            }
            $this->router->callMiddleware('between');
            $this->router->response->setContent($content);
        }
    }

}
