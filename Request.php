<?php

namespace Skvn\App;

use Skvn\Base\Traits\CastedProps;
use Skvn\Base\Helpers\Str;

class Request
{
    use CastedProps;

    protected $get = [];
    protected $post = [];
    protected $request = [];
    protected $files = [];
    protected $cookie = [];
    protected $method = null;
    protected $server = [];
    protected $data = [];
    protected $arguments = [];
    protected $options = [];

    function __construct()
    {
        $this->get = $_GET ?? [];
        $this->post = $_POST ?? [];
        $this->request = $_REQUEST ?? [];
        $this->cookie = $_COOKIE ?? [];
        $this->server = $_SERVER ?? [];
        $this->files = $_FILES ?? [];
        $this->method = $this->server['REQUEST_METHOD'] ?? "GET";
        $this->data = array_merge($this->get, $this->post, $this->request);
        if (!empty($this->server['argv'])) {
            $args = $this->server['argv'];
            array_shift($args);
            while (count($args) > 0) {
                $arg = array_shift($args);
                if (Str :: pos('--', $arg) === 0) {
                    $arg = substr($arg, 2);
                    $scope = 'options';
                } elseif (Str :: pos('-', $arg) === 0) {
                    $arg = substr($arg, 1);
                    $scope = 'options';
                } else {
                    $scope = 'arguments';
                }
                if (Str :: pos('=', $arg) !== false) {
                    list($k, $v) = explode('=', $arg);
                } else {
                    list($k, $v) = [$arg, true];
                }
                if (array_key_exists($k, $this->$scope)) {
                    if (is_array($this->$scope[$k])) {
                        array_push($this->$scope[$k], $v);
                    } else {
                        $this->$scope[$k] = [$this->$scope[$k], $v];
                    }
                } else {
                    $this->$scope[$k] = $v;
                }
            }
        }
    }

    function export()
    {
        return $this->data;
    }

    function dump()
    {
        return $this->export();
    }

    function all()
    {
        return $this->export();
    }

    function set($name, $value)
    {
        $this->data[$name] = $value;
    }

    function get($name)
    {
        return $this->data[$name] ?? null;
    }

    function has($name)
    {
        return isset($this->data[$name]);
    }

    function getMethod()
    {
        return $this->method;
    }

    function getArgument($name)
    {
        return $this->arguments[$name] ?? null;
    }

    function getArguments()
    {
        return $this->arguments;
    }

    function getOption($name)
    {
        return $this->options[$name] ?? null;
    }

    function getOptions()
    {
        return $this->options;
    }

    function getServer($var)
    {
        return $this->server[$var] ?? null;
    }

    function hasServer($var)
    {
        return isset($this->server[$var]);
    }

    function getCookie($var)
    {
        return $this->cookie[$var] ?? null;
    }

    function hasCookie($var)
    {
        return isset($this->cookie[$var]);
    }

//    function setCookie($var, $value)
//    {
//        $this->cookie[$var] = $value;
//    }

    function getUri()
    {
        return $this->uri;
    }

    function hasPost()
    {
        return $this->method == "POST";
    }

    function getRawUrl()
    {
        return $this->getServer('REQUEST_URI');
    }



}