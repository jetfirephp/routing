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
        if (isset($this->request['path']) && is_array($this->request['path']))
            if (isset($this->request['path']['use'])) $this->router->route->setCallback($this->request['path']['use']);
            elseif (is_string($this->request['path'])) $this->router->route->setCallback($this->request['path']);
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
            if (is_file(($path = $this->router->route->getBlock() . $this->router->getConfig()['controllerPath'] . '/') . $routes[0] . '.php'))
                require $path . $routes[0] . '.php';
            elseif (is_file(($path = $this->router->getConfig()['controllerPath'] . '/') . $routes[0] . '.php'))
                require $path . $routes[0] . '.php';
            else
                throw new \Exception('The require file "' . $routes[0] . '.php" is not found in "' . $path . '"');
            $routes[0] = str_replace('/', '\\', $path) . $routes[0];
            if (method_exists($routes[0], $routes[1])) {
                $this->router->route->setTarget(['dispatcher' => 'JetFire\Routing\Dispatcher\MvcDispatcher', 'controller' => $routes[0], 'action' => $routes[1]]);
                return true;
            }
            throw new \Exception('The require method "' . $routes[1] . '" is not found in "' . $routes[0] . '"');
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
            $target = '';
            $path = trim($this->router->route->getCallback(), '/');
            $extension = explode('.', $path);
            $extension = end($extension);
            if (in_array('.' . $extension, $this->router->getConfig()['viewExtension'])) {
                if (is_file($this->router->route->getBlock() . $this->router->getConfig()['viewPath'] . '/' . $path))
                    $target = $this->router->route->getBlock() . $this->router->getConfig()['viewPath'] . '/' . $path;
                elseif (is_file($this->router->getConfig()['viewPath'] . '/' . $path))
                    $target = $this->router->getConfig()['viewPath'] . '/' . $path;
                elseif (is_file($path))
                    $target = $path;
                else
                    throw new \Exception('Template file "' . $path . '" is not found in "' . $this->router->getConfig()['viewPath'] . '" or "' . $this->router->route->getBlock() . $this->router->getConfig()['viewPath'] . '"');
                if (!empty($target)) {
                    $this->router->route->setTarget(['dispatcher' => 'JetFire\Routing\Dispatcher\TemplateDispatcher', 'template' => $target,'block' => str_replace($path,'',$target), 'extension' => $extension]);
                    return true;
                }
            } else {
                foreach ($this->router->getConfig()['viewExtension'] as $ext) {
                    if (is_file($this->router->route->getBlock() . $this->router->getConfig()['viewPath'] . '/' . $path . $ext))
                        $target = $this->router->route->getBlock() . $this->router->getConfig()['viewPath'] . '/' . $path . $ext;
                    elseif (is_file($this->router->getConfig()['viewPath'] . '/' . $path . $ext))
                        $target = $this->router->getConfig()['viewPath'] . '/' . $path . $ext;
                    elseif (is_file($path . $ext))
                        $target = $path . $ext;
                    if (!empty($target)) {
                        $this->router->route->setTarget(['dispatcher' => 'JetFire\Routing\Dispatcher\TemplateDispatcher', 'template' => $target,'block' => str_replace($path.$ext,'',$target),  'extension' => str_replace('.', '', $ext)]);
                        return true;
                    }
                }
                throw new \Exception('Template file "' . $path . '" is not found in "' . $this->router->getConfig()['viewPath'] . '" or "' . $this->router->route->getBlock() . $this->router->getConfig()['viewPath'] . '"');
            }
        }
        return false;
    }
} 