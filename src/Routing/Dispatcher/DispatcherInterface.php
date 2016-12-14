<?php

namespace JetFire\Routing\Dispatcher;


use JetFire\Routing\ResponseInterface;
use JetFire\Routing\Route;

/**
 * Interface DispatcherInterface
 * @package JetFire\Routing\Dispatcher
 */
interface DispatcherInterface {

    /**
     * @param Route $route
     * @param ResponseInterface $response
     */
    public function __construct(Route $route, ResponseInterface $response);

    /**
     * @return mixed
     */
    public function call();

}
