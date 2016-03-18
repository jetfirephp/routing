<?php

namespace JetFire\Routing\Matcher;


use JetFire\Routing\Router;

/**
 * Class RoutesMatch
 * @package JetFire\Routing\Match
 */
class ArrayMatcher implements MatcherInterface
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
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param $method
     * @param $class
     * @return mixed|void
     * @internal param array $dispatcher
     */
    public function addDispatcher($method,$class){
        $this->dispatcher[$method] = $class;
    }

    /**
     * @return bool
     */
    public function match()
    {
        $this->request = [];
        for ($i = 0; $i < $this->router->collection->countRoutes; ++$i) {
            $this->request['prefix'] = ($this->router->collection->getRoutes('prefix_' . $i) != '') ? $this->router->collection->getRoutes('prefix_' . $i) : '';
            foreach ($this->router->collection->getRoutes('routes_' . $i) as $route => $params) {
                $this->request['params'] = $params;
                $this->request['collection_index'] = $i;
                $this->request['route'] = preg_replace_callback('#:([\w]+)#', [$this, 'paramMatch'], '/' . trim(trim($this->request['prefix'], '/') . '/' . trim($route, '/'), '/'));
                if ($this->routeMatch('#^' . $this->request['route'] . '$#')) {
                    $this->setRoute();
                    return $this->generateTarget();
                }
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
        if (is_array($this->request['params']) && isset($this->request['params']['arguments'][$match[1]])) {
            $this->request['params']['arguments'][$match[1]] = str_replace('(', '(?:', $this->request['params']['arguments'][$match[1]]);
            return '(' . $this->request['params']['arguments'][$match[1]] . ')';
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
        if($this->validMethod()) {
            foreach($this->matcher as $match) if(call_user_func([$this,$match])) break;
            $index = isset($this->request['collection_index']) ? $this->request['collection_index'] : 0;
            $this->router->route->addTarget('block',$this->router->collection->getRoutes('block_'.$index));
            $this->router->route->addTarget('view_dir',$this->router->collection->getRoutes('view_dir_'.$index));
            $this->router->response->setStatusCode(202);
        }else
            $this->router->response->setStatusCode(405);
        return $this->router->route->hasTarget();
    }

    /**
     *
     */
    private function setRoute(){
        if (isset($this->request['params'])) {
            if(is_callable($this->request['params']))
                $this->router->route->setCallback($this->request['params']);
            else {
                (is_array($this->request['params']) && isset($this->request['params']['use']))
                    ? $this->router->route->setCallback($this->request['params']['use'])
                    : $this->router->route->setCallback($this->request['params']);
                if (isset($this->request['params']['name'])) $this->router->route->setName($this->request['params']['name']);
                if (isset($this->request['params']['method'])) $this->request['params']['method'] = is_array($this->request['params']['method']) ? $this->request['params']['method'] : [$this->request['params']['method']];
            }
        }
        $this->router->route->setDetail($this->request);
    }

    /**
     * @return bool
     */
    public function validMethod()
    {
        if(is_callable($this->request['params']))return true;
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
            return (isset($this->request['params']['ajax']) && $this->request['params']['ajax'] === true) ? true : false;
        $method = (isset($this->router->route->getDetail()['params']['method'])) ? $this->router->route->getDetail()['params']['method'] : ['GET'];
        return (in_array($this->router->route->getMethod(), $method)) ? true : false;
    }


    /**
     * @return bool
     */
    public function matchClosure()
    {
        if (is_callable($this->router->route->getCallback())) {
            $this->router->route->setTarget([
                'dispatcher' => $this->dispatcher['matchClosure'],
                'closure' => $this->router->route->getCallback()
            ]);
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
        if (strpos($this->router->route->getCallback(), '@') !== false) {
            $routes = explode('@', $this->router->route->getCallback());
            if (!isset($routes[1])) $routes[1] = 'index';
            $index = isset($this->request['collection_index']) ? $this->request['collection_index'] : 0;
            $class = (class_exists($routes[0]))
                ? $routes[0]
                : $this->router->collection->getRoutes()['ctrl_namespace_'.$index].$routes[0];
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
        if(is_string($this->router->route->getCallback())) {
            $path = trim($this->router->route->getCallback(), '/');
            $extension = substr(strrchr($path, "."), 1);
            $index = isset($this->request['collection_index']) ? $this->request['collection_index'] : 0;
            $viewDir = $this->router->collection->getRoutes('view_dir_' . $index);
            $target = null;
            if (in_array('.' . $extension, $this->router->getConfig()['templateExtension']) && is_file($viewDir . $path))
                $target = $viewDir . $path;
            else {
                foreach ($this->router->getConfig()['templateExtension'] as $ext) {
                    if (is_file($viewDir . $path . $ext)) {
                        $target = $viewDir . $path . $ext;
                        $extension = substr(strrchr($ext, "."), 1);
                        break;
                    }
                }
            }
            if(is_null($target))
                throw new \Exception('Template file "' . $path . '" is not found in "' . $viewDir . '"');
            $this->router->route->setTarget([
                'dispatcher' => $this->dispatcher['matchTemplate'],
                'template'   => $target,
                'extension'  => $extension,
                'callback'   => $this->router->getConfig()['templateCallback']
            ]);
            return true;
        }
        return false;
    }
}
