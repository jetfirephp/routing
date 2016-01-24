<?php

namespace JetFire\Routing\Match;


use JetFire\Routing\Router;

/**
 * Interface Matcher
 * @package JetFire\Routing\Match
 */
interface Matcher
{

    /**
     * @param Router $router
     */
    public function __construct(Router $router);

    /**
     * @return mixed
     */
    public function match();

} 