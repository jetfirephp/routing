<?php

namespace JetFire\Routing\Dispatcher;

use JetFire\Routing\Router;
use ReflectionClass;
use ReflectionMethod;

/**
 * Class MvcDispatcher
 * @package JetFire\Routing\Dispatcher
 */
class MvcDispatcher
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
     * @return mixed
     * @throws \Exception
     */
    public function call()
    {
        $reflectionMethod = new ReflectionMethod($this->router->route->getTarget('controller'), $this->router->route->getTarget('action'));
        $dependencies = ($this->router->route->getParameters() == '') ? [] : $this->router->route->getParameters();
        foreach ($reflectionMethod->getParameters() as $arg) {
            if (!is_null($arg->getClass())) {
                $class = $arg->getClass()->name;
                array_unshift($dependencies, new $class);
            }
        }
        if ($this->router->route->getResponse('code') == 202)
            $this->router->route->setResponse(['code' => 200, 'message' => 'OK', 'type' => 'text/html']);
        return $reflectionMethod->invokeArgs($this->getController(), $dependencies);
    }


    /**
     * @return object
     * @throws \Exception
     */
    private function getController()
    {
        $reflector = new ReflectionClass($this->router->route->getTarget('controller'));
        if (!$reflector->isInstantiable())
            throw new \Exception('Target [' . $this->router->route->getTarget('controller') . '] is not instantiable.');
        $constructor = $reflector->getConstructor();
        if (is_null($constructor)) {
            $class = $this->router->route->getTarget('controller');
            return new $class;
        }
        $dependencies = $constructor->getParameters();
        $arguments = [];
        foreach ($dependencies as $dep) {
            $class = $dep->getClass()->name;
            array_push($arguments, new $class);
        }
        return $reflector->newInstanceArgs($arguments);
    }

} 