<?php

namespace JetFire\Routing\Match;


use JetFire\Routing\Router;

/**
 * Class RoutesMatch
 * @package JetFire\Routing\Match
 */
class RoutesMatch implements MatcherInterface
{

    /**
     * @var
     */
    private $router;
    /**
     * @var array
     */
    private $request = [];

    /**
     * @var array
     */
    private $matcher = ['matchClosure','matchController','matchTemplate'];

    /**
     * @var array
     */
    private $dispatcher = [
        'matchClosure' => 'JetFire\Routing\Dispatcher\ClosureDispatcher',
        'matchController' => 'JetFire\Routing\Dispatcher\ControllerDispatcher',
        'matchTemplate' => 'JetFire\Routing\Dispatcher\TemplateDispatcher',
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
    public function setDispatcher($dispatcher = []){
        $this->dispatcher = array_merge($dispatcher,$this->dispatcher);
    }

    /**
     * @return bool
     */
    public function match()
    {
        $this->request = [];
        for ($i = 0; $i < $this->router->collection->countRoutes; ++$i) {
            $this->request['block'] = $this->router->collection->getRoutes('path_' . $i);
            $this->request['prefix'] = ($this->router->collection->getRoutes('prefix_' . $i) != '') ? $this->router->collection->getRoutes('prefix_' . $i) : '';
            foreach ($this->router->collection->getRoutes('routes_' . $i) as $route => $dependencies) {
                $this->request['path'] = $dependencies;
                $this->request['index'] = $i;
                $this->request['route'] = preg_replace_callback('#:([\w]+)#', [$this, 'paramMatch'], '/' . trim(trim($this->request['prefix'], '/') . '/' . trim($route, '/'), '/'));
                if ($this->routeMatch('#^' . $this->request['route'] . '$#'))
                    return $this->generateTarget();
            }
        }
        unset($this->request);
        return false;
    }

    /**
     * @param $match
     * @return string
     */
    private function paramMatch($match)
    {
        if (is_array($this->request['path']) && isset($this->request['path']['arguments'][$match[1]])) {
            $this->request['path']['arguments'][$match[1]] = str_replace('(', '(?:', $this->request['path']['arguments'][$match[1]]);
            return '(' . $this->request['path']['arguments'][$match[1]] . ')';
        }
        return '([^/]+)';
    }

    /**
     * @param $regex
     * @return bool
     */
    private function routeMatch($regex)
    {
        if (substr($this->request['route'], -1) == '*') {
            $pos = strpos($this->request['route'], '*');
            if (substr($this->router->route->getUrl(), 0, $pos) == substr($this->request['route'], 0, $pos))
                if (isset($this->request)) return true;
        }
        if (preg_match($regex, $this->router->route->getUrl(), $this->request['parameters'])) {
            array_shift($this->request['parameters']);
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    private function generateTarget()
    {
        if(is_callable($this->request['path'])){
            $this->router->route->setCallback($this->request['path']);
            $this->router->route->setDetail($this->request);
            $this->matchClosure();
            $this->router->response->setStatusCode(202);
        } else {
            if (isset($this->request['path']['name'])) $this->router->route->setName($this->request['path']['name']);
            if (isset($this->request['path']['method'])) $this->request['path']['method'] = is_array($this->request['path']['method']) ? $this->request['path']['method'] : [$this->request['path']['method']];
            if (isset($this->request['path']))
                (is_array($this->request['path']) && isset($this->request['path']['use']))
                    ? $this->router->route->setCallback($this->request['path']['use'])
                    : $this->router->route->setCallback($this->request['path']);
            $this->router->route->setDetail($this->request);
            if($this->validMethod()) {
                foreach($this->matcher as $matcher)
                    call_user_func([$this,$matcher]);
                $this->router->response->setStatusCode(202);
            }else
                $this->router->response->setStatusCode(405);
        }
        return $this->router->route->hasTarget();
    }

    /**
     * @return bool
     */
    public function validMethod()
    {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
            return (isset($this->request['path']['ajax']) && $this->request['path']['ajax'] === true) ? true : false;
        $method = (isset($this->router->route->getDetail()['path']['method'])) ? $this->router->route->getDetail()['path']['method'] : ['GET'];
        return (in_array($this->router->route->getMethod(), $method)) ? true : false;
    }


    /**
     * @return bool
     */
    public function matchClosure()
    {
        if (is_callable($this->router->route->getCallback())) {
            $this->router->route->setTarget(['dispatcher' => $this->dispatcher['matchClosure'], 'closure' => $this->router->route->getCallback()]);
            return true;
        }
        return false;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function matchController()
    {
        if (!$this->router->route->hasTarget() && strpos($this->router->route->getCallback(), '@') !== false) {
            $routes = explode('@', $this->router->route->getCallback());
            if (!isset($routes[1])) $routes[1] = 'index';
            $index = isset($this->request['index']) ? $this->request['index'] : 0;
            $class = (class_exists($routes[0]))
                ? $routes[0]
                : $this->router->collection->getRoutes()['namespace_'.$index].$routes[0];
            if (!class_exists($class))
                throw new \Exception('Class "' . $class . '." is not found');
            if (method_exists($class, $routes[1])) {
                $this->router->route->setTarget([
                    'dispatcher' => $this->dispatcher['matchController'],
                    'di' => $this->router->getConfig()['di'],
                    'controller' => $class,
                    'action' => $routes[1]
                ]);
                return true;
            }
            throw new \Exception('The required method "' . $routes[1] . '" is not found in "' . $class . '"');
        }
        return false;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function matchTemplate()
    {
        if (!$this->router->route->hasTarget()) {
            $path = trim($this->router->route->getCallback(), '/');
            $extension = explode('.', $path);
            $extension = end($extension);
            $index = isset($this->request['index']) ? $this->request['index'] : 0;
            $block = $this->router->collection->getRoutes('path_'.$index);
            if (in_array('.' . $extension, $this->router->getConfig()['viewExtension'])) {
                if (is_file($block . $path)) {
                    $target = $block . $path;
                    $this->router->route->setTarget([
                        'dispatcher' => $this->dispatcher['matchTemplate'],
                        'template' => $target,
                        'block' => $block,
                        'extension' => $extension,
                        'callback' => $this->router->getConfig()['viewCallback']
                    ]);
                    return true;
                }
                throw new \Exception('Template file "' . $path . '" is not found in "' . $block . '"');
            } else {
                foreach ($this->router->getConfig()['viewExtension'] as $ext) {
                    if (is_file($block . $path . $ext)){
                        $target = $block . $path . $ext;
                        $extension = explode('.', $ext);
                        $extension = end($extension);
                        $this->router->route->setTarget([
                            'dispatcher' => $this->dispatcher['matchTemplate'],
                            'template' => $target,
                            'block' => $block,
                            'extension' => str_replace('.', '', $extension),
                            'callback' => $this->router->getConfig()['viewCallback']
                        ]);
                        return true;
                    }
                }
                throw new \Exception('Template file "' . $path . '" is not found in "' .$block . '"');
            }
        }
        return false;
    }
}
