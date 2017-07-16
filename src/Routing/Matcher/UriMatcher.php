<?php

namespace JetFire\Routing\Matcher;

use JetFire\Routing\Dispatcher\ControllerDispatcher;
use JetFire\Routing\Dispatcher\TemplateDispatcher;
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
    private $resolver = ['isControllerAndTemplate'];

    /**
     * @var array
     */
    private $dispatcher = [
        'isTemplate' => TemplateDispatcher::class,
        'isController' => ControllerDispatcher::class,
        'isControllerAndTemplate' => [ControllerDispatcher::class, TemplateDispatcher::class],
    ];

    /**
     * @param Router $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * @param array $resolver
     */
    public function setResolver($resolver = [])
    {
        $this->resolver = $resolver;
    }

    /**
     * @param string $resolver
     */
    public function addResolver($resolver)
    {
        $this->resolver[] = $resolver;
    }

    /**
     * @return array
     */
    public function getResolver()
    {
        return $this->resolver;
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
     */
    public function addDispatcher($method, $class)
    {
        $this->dispatcher[$method] = $class;
    }

    /**
     * @return bool
     */
    public function match()
    {
        foreach ($this->resolver as $resolver) {
            if (is_array($target = call_user_func([$this, $resolver]))) {
                $this->setTarget($target);
                return true;
            }
        }
        return false;
    }

    /**
     * @param array $target
     */
    public function setTarget($target = [])
    {
        $index = isset($this->request['collection_index']) ? $this->request['collection_index'] : 0;
        $this->router->route->setDetail($this->request);
        $this->router->route->setTarget($target);
        $this->router->route->addTarget('block', $this->router->collection->getRoutes('block_' . $index));
        $this->router->route->addTarget('view_dir', $this->router->collection->getRoutes('view_dir_' . $index));
    }

    /**
     * @return array|bool
     */
    public function isControllerAndTemplate()
    {
        if (is_array($ctrl = $this->isController())) {
            if (is_array($tpl = $this->isTemplate())) {
                return array_merge(array_merge($ctrl, $tpl), [
                    'dispatcher' => $this->dispatcher['isControllerAndTemplate']
                ]);
            }
            return $ctrl;
        }
        return $this->isTemplate();
    }

    /**
     * @return bool|array
     */
    public function isTemplate()
    {
        foreach ($this->router->getConfig()['templateExtension'] as $extension) {
            for ($i = 0; $i < $this->router->collection->countRoutes; ++$i) {
                $url = explode('/', str_replace($this->router->collection->getRoutes('prefix_' . $i), '', $this->router->route->getUrl()));
                $end = array_pop($url);
                $url = implode('/', array_map('ucwords', $url)) . '/' . $end;
                if (is_file(($template = rtrim($this->router->collection->getRoutes('view_dir_' . $i), '/') . $url . $extension))) {
                    $this->request['collection_index'] = $i;
                    return [
                        'dispatcher' => $this->dispatcher['isTemplate'],
                        'template' => $template,
                        'extension' => substr(strrchr($extension, "."), 1),
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
    public function isController()
    {
        $routes = array_slice(explode('/', $this->router->route->getUrl()), 1);
        $i = 0;
        do {
            $route = ('/' . $routes[0] == $this->router->collection->getRoutes('prefix_' . $i)) ? array_slice($routes, 1) : $routes;
            if (isset($route[0])) {
                $class = (class_exists($this->router->collection->getRoutes('ctrl_namespace_' . $i) . ucfirst($route[0]) . 'Controller'))
                    ? $this->router->collection->getRoutes('ctrl_namespace_' . $i) . ucfirst($route[0]) . 'Controller'
                    : ucfirst($route[0]) . 'Controller';
                $route[1] = isset($route[1]) ? $route[1] : 'index';
                if (method_exists($class, $route[1])) {
                    $this->request['parameters'] = array_slice($route, 2);
                    $this->request['collection_index'] = $i;
                    return [
                        'dispatcher' => $this->dispatcher['isController'],
                        'di' => $this->router->getConfig()['di'],
                        'controller' => $class,
                        'action' => $route[1]
                    ];
                }
            }
            ++$i;
        } while ($i < $this->router->collection->countRoutes);
        return false;
    }

}
