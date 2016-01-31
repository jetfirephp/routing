<?php

namespace JetFire\Routing\Match;


use JetFire\Routing\Router;

/**
 * Class RoutesMatch
 * @package JetFire\Routing\Match
 */
class RoutesMatch implements Matcher
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
        if (isset($this->request['arguments']) && isset($this->request['arguments'][$match[1]])) {
            $this->request['arguments'][$match[1]] = str_replace('(', '(?:', $this->request['arguments'][$match[1]]);
            return '(' . $this->request['arguments'][$match[1]] . ')';
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
        if (isset($this->request['path']['name'])) $this->router->route->setName($this->request['path']['name']);
        if (isset($this->request['path']['method'])) $this->request['path']['method'] = is_array($this->request['path']['method']) ? $this->request['path']['method'] : [$this->request['path']['method']];
        if (isset($this->request['path']))
            (is_array($this->request['path']) && isset($this->request['path']['use']))
                ? $this->router->route->setCallback($this->request['path']['use'])
                : $this->router->route->setCallback($this->request['path']);
        $this->router->route->setDetail($this->request);
        if ($this->validMethod()) {
            $this->router->route->setResponse(['code' => 202, 'message' => 'Accepted']);
            $this->anonymous();
            $this->mvc();
            $this->template();
        } else
            $this->router->route->setResponse(['code' => 405, 'message' => 'Method Not Allowed']);
        return $this->router->route->hasTarget();
    }

    /**
     * @return bool
     */
    public function validMethod()
    {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
            return (isset($this->request['path']['ajax']) && $this->request['path']['ajax'] == true) ? true : false;
        $method = (isset($this->router->route->getDetail()['path']['method'])) ? $this->router->route->getDetail()['path']['method'] : ['GET'];
        return (in_array($this->router->route->getMethod(), $method)) ? true : false;
    }


    /**
     * @return bool
     */
    public function anonymous()
    {
        if (is_callable($this->router->route->getCallback())) {
            $this->router->route->setTarget(['dispatcher' => 'JetFire\Routing\Dispatcher\FunctionDispatcher', 'function' => $this->router->route->getCallback()]);
            return true;
        }
        return false;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function mvc()
    {
        if (!$this->router->route->hasTarget() && strpos($this->router->route->getCallback(), '@') !== false) {
            $routes = explode('@', $this->router->route->getCallback());
            if (!isset($routes[1])) $routes[1] = 'index';
            $class = (class_exists($routes[0]))
                ? $routes[0]
                : $this->router->collection->getRoutes()['namespace_'.$this->request['index']].$routes[0];
            if (!class_exists($class))
                throw new \Exception('Class "' . $class . '." is not found');
            if (method_exists($class, $routes[1])) {
                $this->router->route->setTarget(['dispatcher' => 'JetFire\Routing\Dispatcher\MvcDispatcher', 'controller' => $class, 'action' => $routes[1]]);
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
    public function template()
    {
        if (!$this->router->route->hasTarget()) {
            $path = trim($this->router->route->getCallback(), '/');
            $extension = explode('.', $path);
            $extension = end($extension);
            $block = $this->router->collection->getRoutes()['path_'.$this->request['index']];
            if (in_array('.' . $extension, $this->router->getConfig()['viewExtension'])) {
                if (is_file($block . $path)) {
                    $target = $block . $path;
                    $this->router->route->setTarget(['dispatcher' => 'JetFire\Routing\Dispatcher\TemplateDispatcher', 'template' => $target,'block' => $block, 'extension' => $extension]);
                    return true;
                }
                throw new \Exception('Template file "' . $path . '" is not found in "' . $block . '"');
            } else {
                foreach ($this->router->getConfig()['viewExtension'] as $ext) {
                    if (is_file($block . $path . $ext)){
                        $target = $block . $path . $ext;
                        $this->router->route->setTarget(['dispatcher' => 'JetFire\Routing\Dispatcher\TemplateDispatcher', 'template' => $target,'block' => $block,  'extension' => str_replace('.', '', $ext)]);
                        return true;
                    }
                }
                throw new \Exception('Template file "' . $path . '" is not found in "' .$block . '"');
            }
        }
        return false;
    }
} 