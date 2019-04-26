<?php

namespace JetFire\Routing;

/**
 * Class Route
 * @package JetFire\Routing
 * @method getParameters()
 * @method getBlock()
 * @method getKeys()
 * @method getPath()
 * @method string getParams()
 */
class Route
{

    /**
     * @var
     */
    private $url;
    /**
     * @var
     */
    private $name;
    /**
     * @var
     */
    private $callback;
    /**
     * @var string
     */
    private $method;
    /**
     * @var array
     */
    private $target = [];
    /**
     * @var array
     */
    private $detail = [];

    /**
     */
    public function __construct()
    {
        $this->method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
        if ($this->method === 'POST' && isset($_SERVER['X-HTTP-METHOD-OVERRIDE'])) {
            $this->method = strtoupper($_SERVER['X-HTTP-METHOD-OVERRIDE']);
        }
        if (isset($_POST['_METHOD']) && in_array($_POST['_METHOD'], array('PUT', 'PATCH', 'HEAD', 'DELETE'))) {
            $this->method = $_POST['_METHOD'];
        }
    }

    /**
     * @param array $args
     */
    public function set($args = [])
    {
        if (isset($args['name'])) {
            $this->name = $args['name'];
        }
        if (isset($args['callback'])) {
            $this->callback = $args['callback'];
        }
        if (isset($args['target'])) {
            $this->target = $args['target'];
        }
        if (isset($args['detail'])) {
            $this->detail = $args['detail'];
        }
    }

    /**
     * @return null
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * @param $callback
     */
    public function setCallback($callback)
    {
        $this->callback = $callback;
    }

    /**
     * @return array
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return array
     */
    public function getDetail()
    {
        return $this->detail;
    }

    /**
     * @param $detail
     */
    public function setDetail($detail)
    {
        $this->detail = array_merge($detail, $this->detail);
    }

    /**
     * @param $key
     * @param $value
     */
    public function addDetail($key, $value)
    {
        $this->detail[$key] = $value;
    }

    /**
     * @param null $key
     * @return array|string
     */
    public function getTarget($key = null)
    {
        if ($key !== null) {
            return isset($this->target[$key]) ? $this->target[$key] : '';
        }
        return empty($this->target) ? '' : $this->target;
    }

    /**
     * @param $target
     */
    public function setTarget($target = [])
    {
        $this->target = $target;
    }

    /**
     * @param $key
     * @param $value
     */
    public function addTarget($key, $value)
    {
        $this->target[$key] = $value;
    }

    /**
     * @param null $key
     * @return bool
     */
    public function hasTarget($key = null)
    {
        if ($key !== null) {
            return isset($this->target[$key]) ? true : false;
        }
        return empty($this->target) ? false : true;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return (isset($this->getDetail()['data']) && is_array($this->getDetail()['data'])) ? $this->getDetail()['data'] : [];
    }

    /**
     * @param $name
     * @param $arguments
     * @return null
     */
    public function __call($name, $arguments)
    {
        if (strpos($name, 'get') === 0) {
            $key = strtolower(str_replace('get', '', $name));
            return isset($this->detail[$key]) ? $this->detail[$key] : '';
        }

        if (strpos($name, 'set') === 0) {
            $key = strtolower(str_replace('set', '', $name));
            $this->detail[$key] = $arguments[0];
        }
        return '';
    }
}
