<?php

namespace Skvn\App;

use Skvn\Base\Container;
use Skvn\Base\Traits\AppHolder;
use Skvn\View\View;
use Skvn\Base\Helpers\Str;
use Skvn\Base\Helpers\File;

class Response
{
    use AppHolder;

    private $format = null;
    private $content;
    private $headers = [];
    private $redirect = null;
    private $cookies = [];
    private $finished = false;

    public function forceFinish()
    {
        fastcgi_finish_request();
        $this->finished = true;
    }

    public function setContent($content, $format = 'auto')
    {
        if ($format == 'auto') {
            if (!is_null($this->format)) {
                return $this;
            }
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
        $this->content = $content;
        return $this;
    }

    function reset()
    {
        $this->content = null;
        $this->format = null;
        return $this;
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
        $this->cookies = array_filter($this->cookies, function($cook) use ($name) { return $cook['name'] != $name;});
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
        if ($this->finished) {
            return;
        }
        switch ($this->format) {
            case 'json':
                $this->removeHeader('Content-Type');
                $this->setContentType('application/json');
            break;
            case 'download':
            case 'push_file':
                $this->addHeader('Content-Description', 'File Transfer');
                $this->addHeader('Content-Transfer-Encoding', 'binary');
                $mime = $this->content['mime'] ?? null;
                if (empty($mime)) {
                    $mime = File::getMimeType($this->content['filename']);
                }
                if (!empty($mime)) {
                    $this->setContentType($mime);
                }
                $filename = basename($this->content['filename']);
                if (!empty($this->content['download_name'])) {
                    $filename = $this->content['download_name'];
                }
                $this->addHeader('Content-disposition', ($this->format == 'download' ? 'attachment': 'inline') . '; filename=' . $filename);
                $this->addHeader('Expires', '0');
                $this->addHeader('Cache-Control', 'must-revalidate, post-check=0,pre-check=0');
                $this->addHeader('Pragma', 'public');
                $this->addHeader('Content-Length', filesize($this->content['filename']));
                $this->addHeader('Connection', 'close');
            break;
            case 'image':
                if (!empty($this->content['mime'])) {
                    $this->setContentType($this->content['mime']);
                } else {
                    $this->setContentType('image');
                }
            break;
        }
        $domain = $this->app->config->get('app.domain');
        foreach ($this->cookies as $cookie) {
//            setcookie(
//                $cookie['name'],
//                $cookie['value'],
//                $cookie['expire'] ? time() + $cookie['expire'] * 24 * 3600 : 0,
//                $cookie['args']['path'] ?? '/',
//                $cookie['args']['domain'] ?? ('.' . $domain),
//                $cookie['args']['secure'] ?? false,
//                $cookie['args']['httponly'] ?? false
//            );
            setcookie(
                $cookie['name'],
                $cookie['value'],
                [
                    'expires' => $cookie['expire'] ? time() + $cookie['expire'] * 24 * 3600 : 0,
                    'path' => $cookie['args']['path'] ?? '/',
                    'domain' => $cookie['args']['domain'] ?? ('.' . $domain),
                    'secure' => $cookie['args']['secure'] ?? false,
                    'httponly' => $cookie['args']['httponly'] ?? false,
                    'samesite' => $cookie['args']['samesite'] ?? null,
                ]
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
            case 'json';
                echo json_encode($this->content);
            break;
            case 'download':
            case 'push_file':
                ob_clean();
                flush();
                readfile($this->content['filename']);
                if (!empty($this->content['autoremove']) && file_exists($this->content['filename'])) {
                    File::rm($this->content['filename']);
                }
                if (!empty($this->content['autoremove_dir']) && file_exists(dirname($this->content['filename']))) {
                    File::rm(dirname($this->content['filename']));
                }
            break;
            case 'image':
                if (!empty($this->content['filename'])) {
                    readfile($this->content['filename']);
                } elseif (!empty($this->content['content'])) {
                    echo $this->content['content'];
                } else {
                    echo base64_decode('R0lGODlhAQABAJAAAP8AAAAAACH5BAUQAAAALAAAAAABAAEAAAICBAEAOw==');
                }
            break;
            case ($this->content instanceof View):
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