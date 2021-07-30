<?php

namespace Skvn\App;

use Skvn\Base\Traits\CastedProps;
use Skvn\Base\Helpers\Str;
use Skvn\Base\Traits\AppHolder;
use Skvn\Base\Traits\ArrayOrObjectAccessImpl;

class Request
{
    use CastedProps;
    use ArrayOrObjectAccessImpl;
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
        $this->method = $this->server['REQUEST_METHOD'] ?? null;
        $this->data = array_merge($this->get, $this->post, $this->request);
        if (!empty($this->server['argv'])) {
            $args = $this->server['argv'];
            array_shift($args);
            while (count($args) > 0) {
                $arg = array_shift($args);
                if (Str::pos('-', $arg) === 0 || Str::pos('=', $arg) !== false) {
                    if (Str::pos('--', $arg) === 0) {
                        $arg = substr($arg, 2);
                    } elseif (Str::pos('-', $arg) === 0) {
                        $arg = substr($arg, 1);
                    }
                    if (Str::pos('=', $arg) !== false) {
                        list($k, $v) = explode('=', $arg, 2);
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
    
    function allGet()
    {
        return $this->get;
    }
    
    function allPost()
    {
        return $this->post;
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

    function get($name, $default = null)
    {
        return $this->data[$name] ?? $default;
    }

    function has($name)
    {
        return isset($this->data[$name]);
    }

    function getMethod($default = '')
    {
        return $this->method ?? $default;
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

    public function hasServer($var)
    {
        return isset($this->server[$var]);
    }

    public function getContentType()
    {
        $type = $this->getServer('CONTENT_TYPE');
        if (is_null($type)) {
            $type = $this->getServer('HTTP_CONTENT_TYPE');
        }
        return $type;
    }

    public function getReferrer($default = null)
    {
        return $this->getServer('HTTP_REFERER', $default);
    }

    public function getUserAgent($default = null)
    {
        return $this->getServer('HTTP_USER_AGENT', $default);
    }

    public function getClientIp()
    {
        return $this->getServer('REMOTE_ADDR', '0.0.0.0');
    }
    
    public function getHost($default = '')
    {
        return $this->getServer('HTTP_HOST', $default);
    }

    public function getCookie($var)
    {
        return $this->cookie[$var] ?? null;
    }

    public function getAllCookies()
    {
        return $this->cookie;
    }

    public function hasCookie($var)
    {
        return isset($this->cookie[$var]);
    }

    public function setCookie($var, $value)
    {
        $this->cookie[$var] = $value;
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function hasPost()
    {
        return $this->method === 'POST';
    }

    public function isPost()
    {
        return $this->hasPost();
    }

    public function isSearchBot()
    {
        $pattern = $this->app->config['app.sebot_agent_pattern'] ?? ['Googlebot', 'YandexBot'];
        return Str::contains($pattern, $this->getUserAgent());
    }

    public function getRawUrl($default = null)
    {
        return $this->getServer('REQUEST_URI', $default);
    }

    public function getRawBody()
    {
        if (is_null($this->raw)) {
            $this->raw = file_get_contents('php://input');
        }
        return $this->raw;
    }

    public function isSecure()
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
        return Str::pos('Shockwave', $agent) !== false || Str::pos('Flash', $agent) !== false;
    }


    function getFile($name)
    {
        if (isset($this->request[$name])) {
            if (Str::pos('url', $name) !== false) {
                return UploadedFile::createUrl($this->app, $this->request[$name]);
            } else {
                return UploadedFile::createXhr($this->app, $this->request[$name]);
            }
        }
        if (isset($this->files[$name])) {
            return UploadedFile::createMultipart($this->app, $this->files[$name]);
        }

        $obj = new UploadedFile();
        $obj->setApp($this->app);
        return $obj;
    }

    function getRawFiles()
    {
        return $this->files;
    }




}