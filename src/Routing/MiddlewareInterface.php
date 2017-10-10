<?php

namespace JetFire\Routing;


/**
 * Interface MiddlewareInterface
 * @package JetFire\Routing
 */
interface MiddlewareInterface
{

    /**
     * MiddlewareInterface constructor.
     * @param Router $router
     */
    public function __construct(Router $router);

    /**
     * @param $action
     * @return array
     */
    public function getCallbacks($action);

    /**
     * @return array
     */
    public function getMiddleware();

    /**
     * @param $middleware
     * @return mixed
     */
    public function setBeforeCallback($middleware);

    /**
     * @param $middleware
     * @return mixed
     */
    public function setAfterCallback($middleware);
}
