<?php

namespace JetFire\Routing\Matcher;


use JetFire\Routing\Dispatcher\ClosureDispatcher;
use JetFire\Routing\Dispatcher\ControllerDispatcher;
use JetFire\Routing\Dispatcher\TemplateDispatcher;
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
    private $resolver = ['isClosureAndTemplate', 'isControllerAndTemplate', 'isTemplate'];

    /**
     * @var array
     */
    private $dispatcher = [
        'isClosure' => ClosureDispatcher::class,
        'isController' => ControllerDispatcher::class,
        'isTemplate' => TemplateDispatcher::class,
        'isControllerAndTemplate' => [ControllerDispatcher::class, TemplateDispatcher::class],
        'isClosureAndTemplate' => [ClosureDispatcher::class, TemplateDispatcher::class],
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
     * @internal param array $dispatcher
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
        $this->request = [];
        for ($i = 0; $i < $this->router->collection->countRoutes; ++$i) {
            $this->request['prefix'] = ($this->router->collection->getRoutes('prefix_' . $i) != '') ? $this->router->collection->getRoutes('prefix_' . $i) : '';
            $this->request['subdomain'] = ($this->router->collection->getRoutes('subdomain_' . $i) != '') ? $this->router->collection->getRoutes('subdomain_' . $i) : '';
            foreach ($this->router->collection->getRoutes('routes_' . $i) as $route => $params) {
                $this->request['params'] = $params;
                $this->request['collection_index'] = $i;
                if ($this->checkSubdomain($route)) {
                    $route = strstr($route, '/');
                    $this->request['route'] = preg_replace_callback('#:([\w]+)#', [$this, 'paramMatch'], '/' . trim(trim($this->request['prefix'], '/') . '/' . trim($route, '/'), '/'));
                    if ($this->routeMatch('#^' . $this->request['route'] . '$#')) {
                        $this->setCallback();
                        return $this->generateTarget();
                    }
                }
            }
        }
        return false;
    }

    /**
     * @param $route
     * @return bool
     */
    private function checkSubdomain($route)
    {
        $url = (isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http') . '://' . ($host = (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST']));
        $host = explode(':', $host)[0];
        $domain = $this->router->collection->getDomain($url);
        if (!empty($this->request['subdomain']) && $route[0] == '/') $route = trim($this->request['subdomain'], '.') . '.' . $domain . $route;
        if ($route[0] == '/') {
            return ($host != $domain) ? false : true;
        } elseif ($route[0] != '/' && $host != $domain) {
            $route = substr($route, 0, strpos($route, "/"));
            $route = str_replace('{host}', $domain, $route);
            $route = preg_replace_callback('#{subdomain}#', [$this, 'subdomainMatch'], $route);
            if (preg_match('#^' . $route . '$#', $host, $this->request['called_subdomain'])) {
                $this->request['called_subdomain'] = array_shift($this->request['called_subdomain']);
                $this->request['subdomain'] = str_replace('.' . $domain, '', $host);
                return true;
            }
        }
        return false;
    }

    /**
     * @return string
     */
    private function subdomainMatch()
    {
        if (is_array($this->request['params']) && isset($this->request['params']['subdomain'])) {
            return '(' . $this->request['params']['subdomain'] . ')';
        }
        return '([^/]+)';
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
        $regex = (substr($this->request['route'], -1) == '*') ? '#^' . $this->request['route'] . '#' : $regex;
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
        if ($this->validMethod()) {
            foreach ($this->resolver as $resolver) {
                if (is_array($target = call_user_func_array([$this, $resolver], [$this->router->route->getCallback()]))) {
                    $this->setTarget($target);
                    return true;
                }
            }
        }
        $this->router->response->setStatusCode(405);
        return false;
    }

    /**
     * @param array $target
     */
    public function setTarget($target = [])
    {
        $index = isset($this->request['collection_index']) ? $this->request['collection_index'] : 0;
        $this->checkRequest('subdomain');
        $this->checkRequest('prefix');
        $this->router->route->setDetail($this->request);
        $this->router->route->setTarget($target);
        $this->router->route->addTarget('block', $this->router->collection->getRoutes('block_' . $index));
        $this->router->route->addTarget('view_dir', $this->router->collection->getRoutes('view_dir_' . $index));
        $this->router->route->addTarget('params', $this->router->collection->getRoutes('params_' . $index));
    }

    /**
     * @param $key
     */
    private function checkRequest($key)
    {
        if (strpos($this->request[$key], ':') !== false && isset($this->request['parameters'][0])) {
            $replacements = $this->request['parameters'];
            $keys = [];
            $this->request['@' . $key] = $this->request[$key];
            $this->request[$key] = preg_replace_callback('#:([\w]+)#', function ($matches) use (&$replacements, &$keys) {
                $keys[$matches[0]] = $replacements[0];
                return array_shift($replacements);
            }, $this->request[$key]);
            $this->request['keys'] = $keys;
            $this->request['parameters'] = $replacements;
        }
    }

    /**
     *
     */
    private function setCallback()
    {
        if (isset($this->request['params'])) {
            if (is_callable($this->request['params'])) {
                $this->router->route->setCallback($this->request['params']);
            } else {
                if (is_array($this->request['params']) && isset($this->request['params']['use'])) {
                    if (is_array($this->request['params']['use']) && isset($this->request['params']['use'][$this->router->route->getMethod()])) {
                        $this->router->route->setCallback($this->request['params']['use'][$this->router->route->getMethod()]);
                    } elseif (!is_array($this->request['params']['use'])) {
                        $this->router->route->setCallback($this->request['params']['use']);
                    }
                } else {
                    $this->router->route->setCallback($this->request['params']);
                }
                if (isset($this->request['params']['name'])) {
                    $this->router->route->setName($this->request['params']['name']);
                }
                if (isset($this->request['params']['method'])) {
                    $this->request['params']['method'] = is_array($this->request['params']['method']) ? $this->request['params']['method'] : [$this->request['params']['method']];
                }
            }
        }
    }

    /**
     * @return bool
     */
    public function validMethod()
    {
        if (is_callable($this->request['params'])) {
            return true;
        }
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            return (isset($this->request['params']['ajax']) && $this->request['params']['ajax'] === true) ? true : false;
        }
        $method = (isset($this->request['params']['method'])) ? $this->request['params']['method'] : ['GET'];
        return (in_array($this->router->route->getMethod(), $method)) ? true : false;
    }

    /**
     * @param $callback
     * @return array|bool
     * @throws \Exception
     */
    public function isClosureAndTemplate($callback)
    {
        if (is_array($cls = $this->isClosure($callback))) {
            if (is_array($this->request['params']) && isset($this->request['params']['template']) && is_array($tpl = $this->isTemplate($this->request['params']['template']))) {
                return array_merge(array_merge($cls, $tpl), [
                    'dispatcher' => $this->dispatcher['isClosureAndTemplate']
                ]);
            }
            return $cls;
        }
        return false;
    }

    /**
     * @param $callback
     * @return array|bool
     * @throws \Exception
     */
    public function isControllerAndTemplate($callback)
    {
        if (is_array($ctrl = $this->isController($callback))) {
            if (is_array($this->request['params']) && isset($this->request['params']['template']) && is_array($tpl = $this->isTemplate($this->request['params']['template']))) {
                return array_merge(array_merge($ctrl, $tpl), [
                    'dispatcher' => $this->dispatcher['isControllerAndTemplate']
                ]);
            }
            return $ctrl;
        }
        return false;
    }


    /**
     * @param $callback
     * @return bool|array
     */
    public function isClosure($callback)
    {
        if (is_callable($callback)) {
            return [
                'dispatcher' => $this->dispatcher['isClosure'],
                'closure' => $callback
            ];
        }
        return false;
    }

    /**
     * @param $callback
     * @throws \Exception
     * @return bool|array
     */
    public function isController($callback)
    {
        if (is_string($callback) && strpos($callback, '@') !== false) {
            $routes = explode('@', $callback);
            if (!isset($routes[1])) $routes[1] = 'index';
            if ($routes[1] == '{method}') {
                $params = explode('/', trim(preg_replace('#' . rtrim(str_replace('*', '', $this->request['route']), '/') . '#', '', $this->router->route->getUrl()), '/'));
                $routes[1] = empty($params[0]) ? 'index' : $params[0];
                $this->request['@method'] = $routes[1];
                array_shift($params);
                $this->request['parameters'] = array_merge($this->request['parameters'], $params);
                if (preg_match('/[A-Z]/', $routes[1])) return false;
                $routes[1] = lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $routes[1]))));
            }
            $index = isset($this->request['collection_index']) ? $this->request['collection_index'] : 0;
            $class = (class_exists($routes[0]))
                ? $routes[0]
                : $this->router->collection->getRoutes()['ctrl_namespace_' . $index] . $routes[0];
            if (method_exists($class, $routes[1])) {
                return [
                    'dispatcher' => $this->dispatcher['isController'],
                    'di' => $this->router->getConfig()['di'],
                    'controller' => $class,
                    'action' => $routes[1]
                ];
            }
            if (!strpos($callback, '{method}') !== false) {
                throw new \Exception('The required method "' . $routes[1] . '" is not found in "' . $class . '"');
            }
        }
        return false;
    }

    /**
     * @param $callback
     * @throws \Exception
     * @return bool|array
     */
    public function isTemplate($callback)
    {
        if (is_string($callback) && strpos($callback, '@') === false) {
            $replace = isset($this->request['@method']) ? str_replace('-', '_', $this->request['@method']) : 'index';
            $path = str_replace('{template}', $replace, trim($callback, '/'));
            $extension = substr(strrchr($path, "."), 1);
            $index = isset($this->request['collection_index']) ? $this->request['collection_index'] : 0;
            $viewDir = $this->router->collection->getRoutes('view_dir_' . $index);
            $target = null;
            if (in_array('.' . $extension, $this->router->getConfig()['templateExtension']) && (is_file($fullPath = $viewDir . $path) || is_file($fullPath = $path))) {
                $target = $fullPath;
            } else {
                foreach ($this->router->getConfig()['templateExtension'] as $ext) {
                    if (is_file($fullPath = $viewDir . $path . $ext) || is_file($fullPath = $path . $ext)) {
                        $target = $fullPath;
                        $extension = substr(strrchr($ext, "."), 1);
                        break;
                    }
                }
            }
            return [
                'dispatcher' => $this->dispatcher['isTemplate'],
                'template' => $target,
                'extension' => $extension,
                'callback' => $this->router->getConfig()['templateCallback']
            ];
        }
        return false;
    }

}
