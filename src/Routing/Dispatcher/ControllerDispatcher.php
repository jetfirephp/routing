<?php

namespace JetFire\Routing\Dispatcher;

use JetFire\Routing\ResponseInterface;
use JetFire\Routing\Route;
use ReflectionClass;
use ReflectionMethod;

/**
 * Class ControllerDispatcher
 * @package JetFire\Routing\Dispatcher
 */
class ControllerDispatcher implements DispatcherInterface
{

    /**
     * @var Route
     */
    private $route;

    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @param Route $route
     */
    public function __construct(Route $route,ResponseInterface $response)
    {
        $this->route = $route;
        $this->response = $response;
    }


    /**
     * @return mixed
     * @throws \Exception
     */
    public function call()
    {
        $reflectionMethod = new ReflectionMethod($this->route->getTarget('controller'), $this->route->getTarget('action'));
        $dependencies = [];
        foreach ($reflectionMethod->getParameters() as $arg)
            if (!is_null($arg->getClass()))
                $dependencies[] = call_user_func_array($this->route->getTarget('di'),[$arg->getClass()->name]);
        $dependencies = array_merge($dependencies,($this->route->getParameters() == '') ? [] : $this->route->getParameters());
        if ($this->response->getStatusCode() == 202)
            $this->response->setStatusCode(200);
        return $this->response->setContent($reflectionMethod->invokeArgs($this->getController(), $dependencies));
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
