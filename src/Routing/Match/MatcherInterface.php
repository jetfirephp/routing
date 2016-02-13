<?php

namespace JetFire\Routing\Match;


use JetFire\Routing\Router;

/**
 * Interface MatcherInterface
 * @package JetFire\Routing\Match
 */
interface MatcherInterface
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
