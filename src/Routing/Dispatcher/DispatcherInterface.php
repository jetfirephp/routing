<?php

namespace JetFire\Routing\Dispatcher;

use JetFire\Routing\Router;

/**
 * Interface DispatcherInterface
 * @package JetFire\Routing\Dispatcher
 */
interface DispatcherInterface {

    /**
     * @param Router $router
     */
    public function __construct(Router $router);

    /**
     * @return mixed
     */
    public function call();

}
