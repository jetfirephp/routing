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
    private $request = [];

    /**
     * @var array
     */
    private $matcher = ['matchControllerTemplate'];

    /**
     * @var array
     */
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
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param $method
     * @param $class
     * @return mixed|void
     */
    public function addDispatcher($method,$class){
        $this->dispatcher[$method] = $class;
    }

    /**
     * @return bool
     */
    public function match()
    {
        foreach($this->matcher as $matcher){
            if(is_array($target = call_user_func([$this,$matcher]))) {
                $this->setTarget($target);
                $this->router->response->setStatusCode(202);
                return true;
            }
        }
        return false;
    }

    /**
     * @param array $target
     */
    public function setTarget($target = []){
        $index = isset($this->request['collection_index']) ? $this->request['collection_index'] : 0;
        $this->router->route->setDetail($this->request);
        $this->router->route->setTarget($target);
        $this->router->route->addTarget('block', $this->router->collection->getRoutes('block_'.$index));
        $this->router->route->addTarget('view_dir', $this->router->collection->getRoutes('view_dir_'.$index));
    }

    /**
     * @return array|bool
     */
    public function matchControllerTemplate(){
        if(is_array($ctrl = $this->matchController())) {
            if (is_array($tpl = $this->matchTemplate())) {
                return array_merge(array_merge($ctrl, $tpl),[
                    'dispatcher' => [$this->dispatcher['matchController'], $this->dispatcher['matchTemplate']]
                ]);
            }
            return $ctrl;
        }
        return $this->matchTemplate();
    }

    /**
     * @return bool|array
     */
    public function matchTemplate()
    {
        foreach ($this->router->getConfig()['templateExtension'] as $extension) {
            for ($i = 0; $i < $this->router->collection->countRoutes; ++$i) {
                $url = explode('/', str_replace($this->router->collection->getRoutes('prefix_' . $i), '',$this->router->route->getUrl()));
                $end = array_pop($url);
                $url = implode('/', array_map('ucwords', $url)).'/'.$end;
                if (is_file(($template = rtrim($this->router->collection->getRoutes('view_dir_' . $i), '/') . $url . $extension))) {
                    $this->request['collection_index'] = $i;
                    return [
                        'dispatcher' => $this->dispatcher['matchTemplate'],
                        'template' => $template,
                        'extension' => str_replace('.', '', $extension),
                        'callback' => $this->router->getConfig()['templateCallback']
                    ];
                }
            }
        }
        return false;
    }

    /**
     * @return bool|array
     */
    public function matchController()
    {
        $routes = array_slice(explode('/', $this->router->route->getUrl()), 1);
        $i = 0;
        do{
            $route =  ('/' . $routes[0] == $this->router->collection->getRoutes('prefix_' . $i)) ? array_slice($routes, 1) : $routes;
            if (isset($route[0])) {
                $class =  (class_exists($this->router->collection->getRoutes('ctrl_namespace_' . $i). ucfirst($route[0]) . 'Controller'))
                    ? $this->router->collection->getRoutes('ctrl_namespace_' . $i). ucfirst($route[0]) . 'Controller'
                    : ucfirst($route[0]) . 'Controller';
                $route[1] = isset($route[1])?$route[1]:'index';
                if (method_exists($class, $route[1])) {
                    $this->request['parameters'] = array_slice($route, 2);
                    $this->request['collection_index'] = $i;
                    return [
                        'dispatcher' => $this->dispatcher['matchController'],
                        'di' => $this->router->getConfig()['di'],
                        'controller' => $class,
                        'action' => $route[1]
                    ];
                }
            }
            ++$i;
        }while($i < $this->router->collection->countRoutes);
        return false;
    }

}
