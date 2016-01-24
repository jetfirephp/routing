<?php

namespace JetFire\Routing\Match;

use JetFire\Routing\Router;

/**
 * Class SmartMatch
 * @package JetFire\Routing\Match
 */
class SmartMatch implements Matcher
{

    /**
     * @var
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
     * @return bool
     */
    public function match()
    {
        if ($this->matchTemplate() || $this->matchMvc()) {
            $this->router->route->setResponse(['code' => 202, 'message' => 'Accepted']);
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    private function matchTemplate()
    {
        foreach ($this->router->getConfig()['viewExtension'] as $extension) {
            if (is_file(rtrim($this->router->getConfig()['viewPath'] . $this->router->route->getUrl() . $extension, '/'))) {
                $this->router->route->setTarget(['dispatcher' => 'JetFire\Routing\Dispatcher\TemplateDispatcher', 'template' => $this->router->getConfig()['viewPath'] . $this->router->route->getUrl() . $extension, 'extension' => str_replace('.', '', $extension)]);
                return true;
            } else {
                for ($i = 0; $i < $this->router->collection->countRoutes; ++$i) {
                    if (is_file($this->router->collection->getRoutes('path_' . $i) . $this->router->getConfig()['viewPath'] . $this->router->route->getUrl() . $extension)) {
                        $this->router->route->setTarget(['dispatcher' => 'JetFire\Routing\Dispatcher\TemplateDispatcher', 'template' => $this->router->collection->getRoutes('path_' . $i) . $this->router->getConfig()['viewPath'] . $this->router->route->getUrl() . $extension, 'extension' => str_replace('.', '', $extension)]);
                        $this->router->route->addDetail('block', $this->router->collection->getRoutes('path_' . $i));
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * @return bool
     */
    private function matchMvc()
    {
        $routes = array_slice(explode('/', $this->router->route->getUrl()), 1);
        for ($i = 0; $i < $this->router->collection->countRoutes; ++$i)
            if ('/' . $routes[0] == $this->router->collection->getRoutes('prefix_' . $i))
                $route = array_slice($routes, 1);
        if (isset($route[0])) {
            $block = ($this->router->collection->getRoutes('path_' . $i) != '') ? $this->router->collection->getRoutes('path_' . $i) : '';
            if (is_file(($path = $block . $this->router->getConfig()['controllerPath'] . '/') . ucfirst($route[0]) . 'Controller.php'))
                require $path . ucfirst($route[0]) . 'Controller.php';
            $class = str_replace('/', '\\', $path) . ucfirst($routes[0]) . 'Controller';
            if (isset($route[1]) && method_exists($class, $route[1])) {
                $this->router->route->setTarget(['dispatcher' => 'JetFire\Routing\Dispatcher\MvcDispatcher', 'controller' => $class, 'action' => $route[1]]);
                $this->router->route->addDetail('parameters', array_slice($route, 2));
                return true;
            }
        }

        if (is_file(($path = $this->router->getConfig()['controllerPath'] . '/') . ucfirst($routes[0]) . 'Controller.php'))
            require $path . ucfirst($routes[0]) . 'Controller.php';
        $class = str_replace('/', '\\', $path) . $routes[0];
        if (isset($routes[1]) && method_exists($class, $routes[1])) {
            $this->router->route->setTarget(['dispatcher' => 'JetFire\Routing\Dispatcher\MvcDispatcher', 'controller' => $class, 'action' => $routes[1]]);
            $this->router->route->addDetail('parameters', array_slice($routes, 2));
            return true;
        }
        return false;
    }

} 