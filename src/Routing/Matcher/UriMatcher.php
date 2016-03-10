<?php

namespace JetFire\Routing\Matcher;

use JetFire\Routing\Router;

/**
 * Class SmartMatch
 * @package JetFire\Routing\Match
 */
class UriMatcher implements MatcherInterface
{

    /**
     * @var
     */
    private $router;


    /**
     * @var array
     */
    private $matcher = ['matchController','matchTemplate'];

    private $dispatcher = [
        'matchTemplate' => 'JetFire\Routing\Dispatcher\TemplateDispatcher',
        'matchController' => 'JetFire\Routing\Dispatcher\ControllerDispatcher'
    ];

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
     * @param array $dispatcher
     */
    public function setDispatcher($dispatcher = [])
    {
        $this->dispatcher = array_merge($dispatcher,$this->dispatcher);
    }

    /**
     * @return bool
     */
    public function match()
    {
        foreach($this->matcher as $matcher){
            if(call_user_func([$this,$matcher])) {
                $this->router->response->setStatusCode(202);
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
        foreach ($this->router->getConfig()['templateExtension'] as $extension) {
            for ($i = 0; $i < $this->router->collection->countRoutes; ++$i) {
                $url = explode('/', str_replace($this->router->collection->getRoutes('prefix_' . $i), '',$this->router->route->getUrl()));
                $end = array_pop($url);
                $url = implode('/', array_map('ucwords', $url)).'/'.$end;
                if (is_file(($template = rtrim($this->router->collection->getRoutes('path_' . $i), '/') . $url . $extension))) {
                    $this->router->route->setTarget([
                        'dispatcher' => $this->dispatcher['matchTemplate'],
                        'template' => $template,
                        'extension' => str_replace('.', '', $extension),
                        'callback' => $this->router->getConfig()['templateCallback']
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
                        'dispatcher' => $this->dispatcher['matchController'],
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
