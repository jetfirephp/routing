<?php

namespace JetFire\Routing\Match;

use JetFire\Routing\Router;

/**
 * Class SmartMatch
 * @package JetFire\Routing\Match
 */
class SmartMatch implements MatcherInterface
{

    /**
     * @var
     */
    private $router;


    /**
     * @var array
     */
    private $matcher = ['matchController','matchTemplate'];

    /**
     * @param Router $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * @param string $matcher
     */
    public function addMatcher($matcher){
        $this->matcher[] = $matcher;
    }

    /**
     * @return array
     */
    public function getMatcher()
    {
        return $this->matcher;
    }

    /**
     * @return bool
     */
    public function match()
    {
        foreach($this->matcher as $matcher){
            if(call_user_func([$this,$matcher])) {
                $this->router->route->setResponse(['code' => 202, 'message' => 'Accepted']);
                return true;
            }
        }
        return false;
    }

    /**
     * @return bool
     */
    public function matchTemplate()
    {
        foreach ($this->router->getConfig()['viewExtension'] as $extension) {
            for ($i = 0; $i < $this->router->collection->countRoutes; ++$i) {
                $url = explode('/', str_replace($this->router->collection->getRoutes('prefix_' . $i), '',$this->router->route->getUrl()));
                $end = array_pop($url);
                $url = implode('/', array_map('ucwords', $url)).'/'.$end;
                if (is_file(($template = rtrim($this->router->collection->getRoutes('path_' . $i), '/') . $url . $extension))) {
                    $this->router->route->setTarget([
                        'dispatcher' => 'JetFire\Routing\Dispatcher\TemplateDispatcher',
                        'template' => $template,
                        'extension' => str_replace('.', '', $extension),
                        'callback' => $this->router->getConfig()['viewCallback']
                    ]);
                    $this->router->route->addDetail('block', $this->router->collection->getRoutes('path_' . $i));
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @return bool
     */
    public function matchController()
    {
        $routes = array_slice(explode('/', $this->router->route->getUrl()), 1);
        $i = 0;
        do{
            $route =  ('/' . $routes[0] == $this->router->collection->getRoutes('prefix_' . $i)) ? array_slice($routes, 1) : $routes;
            if (isset($route[0])) {
                $class =  (class_exists($this->router->collection->getRoutes('namespace_' . $i). ucfirst($route[0]) . 'Controller'))
                    ? $this->router->collection->getRoutes('namespace_' . $i). ucfirst($route[0]) . 'Controller'
                    : ucfirst($route[0]) . 'Controller';
                if (isset($route[1]) && method_exists($class, $route[1])) {
                    $this->router->route->setTarget([
                        'dispatcher' => 'JetFire\Routing\Dispatcher\ControllerDispatcher',
                        'di' => $this->router->getConfig()['di'],
                        'controller' => $class,
                        'action' => $route[1]
                    ]);
                    $this->router->route->addDetail('parameters', array_slice($route, 2));
                    $this->router->route->addDetail('block', $this->router->collection->getRoutes('path_' . $i));
                    return true;
                }
            }
            ++$i;
        }while($i < $this->router->collection->countRoutes);
        return false;
    }

}
