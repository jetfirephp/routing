<?php

namespace JetFire\Routing\Matcher;


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
     * @param string $matcher
     */
    public function addMatcher($matcher);

    /**
     * @return array
     */
    public function getMatcher();

    /**
     * @param array $dispatcher
     */
    public function setDispatcher($dispatcher = []);

    /**
     * @param $method
     * @param $class
     * @return mixed
     */
    public function addDispatcher($method,$class);

    /**
     * @return mixed
     */
    public function match();

    /**
     * @param array $target
     * @return void
     */
    public function setTarget($target = []);
}
