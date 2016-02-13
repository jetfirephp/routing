<?php

namespace JetFire\Routing\Dispatcher;


use JetFire\Routing\Route;

/**
 * Interface DispatcherInterface
 * @package JetFire\Routing\Dispatcher
 */
interface DispatcherInterface {

    /**
     * @param Route $route
     */
    public function __construct(Route $route);

    /**
     * @return mixed
     */
    public function call();

}
