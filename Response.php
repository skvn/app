<?php

namespace Skvn\App;

use Skvn\Base\Container;
use Skvn\Base\Traits\AppHolder;
use Skvn\View\View;
use Skvn\Base\Helpers\Str;

class Response
{
    use AppHolder;

    private $format = 'html';
    private $content;
    private $headers = [];
    private $redirect = null;
    private $cookies = [];

    public function setContent($content, $format = 'auto')
    {
        $this->content = $content;
        if ($format == 'auto') {
            switch ($format) {
                case is_array($content):
                    $format = 'json';
                break;
                default:
                    $format = 'html';
                break;
            }
        }
        $this->format = $format;
    }

    public function pushNoCache()
    {
        $now = gmdate('D, d M Y H:i:s', strtotime('2000-01-01')) . ' GMT';
        $this->headers[] = 'Expires: ' . $now;
        $this->headers[] = 'Last-Modified: ' . $now;
        $this->headers[] = 'Cache-Control: no-cache, must-revalidate';
        $this->headers[] = 'Pragma: no-cache';
        return $this;
    }

    public function redirect($url, $code = null)
    {
        $this->redirect = ['url' => $url, 'code' => $code];
        return $this;
    }

    public function setCookie($name, $value, $expire=0, $args = [])
    {
        $this->cookies[] = [
            'name' => $name,
            'value' => $value,
            'expire' => $expire,
            'args' => $args
        ];
        return $this;
    }

    public function removeHeader($header)
    {
        $this->headers = array_values(array_filter($this->headers, function ($h) use ($header) {
            return Str::pos($header, $h) !== 0;
        }));
        return $this;
    }

    public function addHeader($header, $value, $replace = false)
    {
        if ($replace) {
            $this->removeHeader($header);
        }
        $this->headers[] = $header . ': ' . $value;
        return $this;
    }

    public function setContentType($type)
    {
        $this->headers[] = 'Content-Type: ' . $type;
        return $this;
    }

    public function setAnswerCode($code, $status = null)
    {
        if (is_null($status)) {
            switch ($code) {
                case 404:
                    $status = 'Not Found';
                break;
                case 304:
                    $status = 'Not Modified';
                break;
            }
        }
        array_unshift($this->headers, 'HTTP/1.x ' . $code . ' ' . $status);
        return $this;
    }

    public function commit($renderer = null)
    {
        $domain = $this->app->config->get('app.domain');
        foreach ($this->cookies as $cookie) {
            setcookie(
                $cookie['name'],
                $cookie['value'],
                $cookie['expire'] ? time() + $cookie['expire'] * 24 * 3600 : 0,
                $cookie['args']['path'] ?? '/',
                $cookie['args']['domain'] ?? ('.' . $domain),
                $cookie['args']['secure'] ?? false,
                $cookie['args']['httponly'] ?? false
            );
        }
        foreach ($this->headers as $header) {
            header($header);
        }
        if (!empty($this->redirect)) {
            header('Location: ' . $this->redirect['url'], true, $this->redirect['code']);
            return;
        }
        switch ($this->format) {
            case 'json':
                $this->removeHeader('Content-Type');
                $this->setContentType('application/json');
            break;
        }
        switch ($this->format) {
            case 'json';
                echo json_encode($this->content);
            break;
            case $this->content instanceof View:
                if (is_callable($renderer)) {
                    echo $renderer($this->content);
                } else {
                    echo $this->content->render();
                }
            break;
            default:
                echo $this->content;
            break;
        }
    }



}