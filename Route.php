<?php

namespace JetFire\Routing;

/**
 * Class Route
 * @package JetFire\Routing
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
     * @var array
     */
    private $response = [
        'code'    => 404,
        'message' => 'Not Found',
        'type'    => 'text/plain'
    ];
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
     * @param array $args
     */
    public function __construct($args = [])
    {
        $this->set($args);
        $this->method = (
            isset($_POST['_METHOD'])
            && ($_method = strtoupper($_POST['_METHOD']))
            && in_array($_method, array('PUT', 'DELETE'))
        ) ? $_method : $_SERVER['REQUEST_METHOD'];
    }

    /**
     * @param array $args
     */
    public function set($args = [])
    {
        if (isset($args['name'])) $this->name = $args['name'];
        if (isset($args['callback'])) $this->callback = $args['callback'];
        if (isset($args['code'])) $this->code = $args['code'];
        if (isset($args['target'])) $this->target = $args['target'];
        if (isset($args['detail'])) $this->detail = $args['detail'];
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
     * @param null $key
     * @return int
     */
    public function getResponse($key = null)
    {
        if (!is_null($key) && isset($this->response[$key]))
            return $this->response[$key];
        return $this->response;
    }

    /**
     * @param null $key
     * @param null $value
     */
    public function setResponse($key = null, $value = null)
    {
        if (!is_null($key) && !is_null($value))
            $this->response[$key] = $value;
        else
            $this->response = array_merge($this->response, $key);
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
        $this->detail = $detail;
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
     * @param $key
     * @param $value
     */
    public function addParam($key, $value)
    {
        $this->detail[$key] = $value;
    }

    /**
     * @param null $key
     * @return array|null
     */
    public function getTarget($key = null)
    {
        if (!is_null($key))
            return isset($this->target[$key]) ? $this->target[$key] : '';
        return empty($this->target) ? '' : $this->target;
    }

    /**
     * @param $target
     * @return mixed
     */
    public function setTarget($target = [])
    {
        $this->target = $target;
    }

    /**
     * @param null $key
     * @return bool
     */
    public function hasTarget($key = null)
    {
        if (!is_null($key))
            return isset($this->target[$key]) ? true : false;
        return empty($this->target) ? false : true;
    }

    /**
     * @param $name
     * @param $arguments
     * @return null
     */
    public function __call($name, $arguments)
    {
        if (substr($name, 0, 3) === "get") {
            $key = strtolower(str_replace('get', '', $name));
            return isset($this->detail[$key]) ? $this->detail[$key] : '';
        } elseif (substr($name, 0, 3) === "set") {
            $key = strtolower(str_replace('set', '', $name));
            $this->detail[$key] = $arguments[0];
        }
        return '';
    }
}