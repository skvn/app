<?php

namespace Skvn\App;

use Skvn\Base\Traits\CastedProps;
use Skvn\Base\Helpers\Str;
use Skvn\Base\Traits\AppHolder;


class Request
{
    use CastedProps;
    use AppHolder;

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
    protected $raw = null;

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
                if (Str :: pos('-', $arg) === 0 || Str :: pos('=', $arg) !== false) {
                    if (Str :: pos('--', $arg) === 0) {
                        $arg = substr($arg, 2);
                    } elseif (Str :: pos('-', $arg) === 0) {
                        $arg = substr($arg, 1);
                    }
                    if (Str :: pos('=', $arg) !== false) {
                        list($k, $v) = explode('=', $arg);
                    } else {
                        list($k, $v) = [$arg, true];
                    }
                    if (array_key_exists($k, $this->options)) {
                        if (is_array($this->options[$k])) {
                            array_push($this->options[$k], $v);
                        } else {
                            $this->options[$k] = [$this->options[$k], $v];
                        }
                    } else {
                        $this->options[$k] = $v;
                    }

                } else {
                    $this->arguments[] = $arg;
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

    function getServer($var, $default = null)
    {
        return $this->server[$var] ?? $default;
    }

    function hasServer($var)
    {
        return isset($this->server[$var]);
    }

    function getContentType()
    {
        $type = $this->getServer('CONTENT_TYPE');
        if (is_null($type)) {
            $type = $this->getServer('HTTP_CONTENT_TYPE');
        }
        return $type;
    }

    public function getReferrer()
    {
        return $this->getServer('HTTP_REFERER');
    }

    public function getUserAgent()
    {
        return $this->getServer('HTTP_USER_AGENT');
    }

    public function getClientIp()
    {
        return $this->getServer('REMOTE_ADDR', '0.0.0.0');
    }

    function getCookie($var)
    {
        return $this->cookie[$var] ?? null;
    }

    function hasCookie($var)
    {
        return isset($this->cookie[$var]);
    }

    function setCookie($var, $value)
    {
        $this->cookie[$var] = $value;
    }

    function getUri()
    {
        return $this->uri;
    }

    function hasPost()
    {
        return $this->method === "POST";
    }

    function isPost()
    {
        return $this->hasPost();
    }

    function getRawUrl()
    {
        return $this->getServer('REQUEST_URI');
    }

    public function getRawBody()
    {
        if (is_null($this->raw)) {
            $this->raw = file_get_contents('php://input');
        }
        return $this->raw;
    }

    function isSecure()
    {
        $https = $this->getServer('HTTPS');
        return !empty($https);
    }

    public function isAjax()
    {
        return $this->getServer('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest';
    }

    public function isPjax()
    {
        return $this->isAjax() && $this->getServer('HTTP_X_PJAX') != null;
    }

    public function isFlash()
    {
        $agent = $this->getUserAgent();
        return Str :: pos('Shockwave', $agent) !== false || Str :: pos('Flash', $agent) !== false;
    }


    function getFile($name)
    {
        if (isset($this->request[$name])) {
            if (Str :: pos('url', $name) !== false) {
                return UploadedFile :: createUrl($this->app, $this->request[$name]);
            } else {
                return UploadedFile :: createXhr($this->app, $this->request[$name]);
            }
        }
        if (isset($this->files[$name])) {
            return UploadedFile :: createMultipart($this->app, $this->files[$name]);
        }

        $obj = new UploadedFile();
        $obj->setApp($this->app);
        return $obj;
    }




}