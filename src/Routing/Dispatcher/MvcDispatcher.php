<?php

namespace JetFire\Routing\Dispatcher;

use JetFire\Routing\Route;
use ReflectionClass;
use ReflectionMethod;

/**
 * Class MvcDispatcher
 * @package JetFire\Routing\Dispatcher
 */
class MvcDispatcher
{

    /**
     * @var Route
     */
    private $route;

    /**
     * @param Route $route
     */
    public function __construct(Route $route)
    {
        $this->route = $route;
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function call()
    {
        $reflectionMethod = new ReflectionMethod($this->route->getTarget('controller'), $this->route->getTarget('action'));
        $dependencies = ($this->route->getParameters() == '') ? [] : $this->route->getParameters();
        foreach ($reflectionMethod->getParameters() as $arg) {
            if (!is_null($arg->getClass())) {
                $class = $arg->getClass()->name;
                array_unshift($dependencies, call_user_func_array($this->route->getTarget('di'),[$class]));
            }
        }
        if ($this->route->getResponse('code') == 202)
            $this->route->setResponse(['code' => 200, 'message' => 'OK', 'type' => 'text/html']);
        return $reflectionMethod->invokeArgs($this->getController(), $dependencies);
    }


    /**
     * @return object
     * @throws \Exception
     */
    private function getController()
    {
        $reflector = new ReflectionClass($this->route->getTarget('controller'));
        if (!$reflector->isInstantiable())
            throw new \Exception('Target [' . $this->route->getTarget('controller') . '] is not instantiable.');
        $constructor = $reflector->getConstructor();
        if (is_null($constructor)) {
            $class = $this->route->getTarget('controller');
            return call_user_func_array($this->route->getTarget('di'),[$class]);
        }
        $dependencies = $constructor->getParameters();
        $arguments = [];
        foreach ($dependencies as $dep) {
            $class = $dep->getClass()->name;
            array_push($arguments, call_user_func_array($this->route->getTarget('di'),[$class]));
        }
        return $reflector->newInstanceArgs($arguments);
    }

}
