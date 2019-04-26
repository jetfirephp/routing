<?php

namespace JetFire\Routing\Dispatcher;

use JetFire\Routing\Response;
use JetFire\Routing\ResponseInterface;
use JetFire\Routing\Route;
use JetFire\Routing\RouteCollection;
use JetFire\Routing\Router;
use ReflectionClass;
use ReflectionMethod;

/**
 * Class ControllerDispatcher
 * @package JetFire\Routing\Dispatcher
 */
class ControllerDispatcher implements DispatcherInterface
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
     * @throws \Exception
     */
    public function call()
    {

        if (!class_exists($this->router->route->getTarget('controller'))) {
            throw new \RuntimeException('Class not found : "' . $this->router->route->getTarget('controller') . '"');
        }

        $classInstance = [
            Route::class => $this->router->route,
            Response::class => $this->router->response,
            RouteCollection::class => $this->router->collection,
        ];

        $reflectionMethod = new ReflectionMethod($this->router->route->getTarget('controller'), $this->router->route->getTarget('action'));
        $dependencies = [];
        $count = 0;

        foreach ($reflectionMethod->getParameters() as $arg) {
            if ($arg->getClass() !== null) {
                if (isset($classInstance[$arg->getClass()->name])) {
                    $dependencies[] = $classInstance[$arg->getClass()->name];
                } else {
                    $dependencies[] = call_user_func($this->router->route->getTarget('di'), $arg->getClass()->name);
                }
            } else {
                $count++;
            }
        }

        if (($this->router->route->getParameters() === '' && $count === 0) || $count === count($this->router->route->getParameters())) {
            $dependencies = array_merge($dependencies, ($this->router->route->getParameters() === '') ? [] : $this->router->route->getParameters());
            $content = $reflectionMethod->invokeArgs($this->getController($classInstance), $dependencies);
            if ($content instanceof ResponseInterface) {
                $this->router->response = $content;
            } else {
                if (is_array($content)) {
                    $this->router->route->addTarget('data', $content);
                    $content = json_encode($content);
                }
                $this->router->callMiddleware('between');
                $this->router->response->setContent($content);
            }
        } else {
            $this->router->response->setStatusCode(404);
        }
    }


    /**
     * @param array $classInstance
     * @return object
     * @throws \Exception
     */
    private function getController($classInstance = [])
    {
        $reflector = new ReflectionClass($this->router->route->getTarget('controller'));
        if (!$reflector->isInstantiable()) {
            throw new \RuntimeException('Target [' . $this->router->route->getTarget('controller') . '] is not instantiable.');
        }
        $constructor = $reflector->getConstructor();
        if ($constructor === null) {
            $class = $this->router->route->getTarget('controller');
            return call_user_func($this->router->route->getTarget('di'), $class);
        }
        $dependencies = [];
        foreach ($constructor->getParameters() as $dep) {
            $class = $dep->getClass()->name;
            if (isset($classInstance[$class])) {
                $dependencies[] = $classInstance[$class];
            } else {
                $dependencies[] = call_user_func($this->router->route->getTarget('di'), $class);
            }
        }
        return $reflector->newInstanceArgs($dependencies);
    }

}
