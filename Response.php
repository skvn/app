<?php

namespace Skvn\App;

use Skvn\Base\Container;
use Skvn\Base\Traits\AppHolder;

class Response
{
    use AppHolder;

    public $format = "html";
    public $content;
    protected $headers = [];


    function pushNoCache()
    {
        $now = gmdate('D, d M Y H:i:s', strtotime("2000-01-01")) . ' GMT';
        $this->headers[] = "Expires: " . $now;
        $this->headers[] = "Last-Modified: ".$now;
        $this->headers[] = "Cache-Control: no-cache, must-revalidate";
        $this->headers[] = "Pragma: no-cache";

        foreach ($this->headers as $h)
        {
            header($h);
        }
    }

    function redirect($url, $code = null)
    {
        header('Location: ' . $url, true, $code);
    }

    function setCookie($name, $value, $expires=0, $args = [])
    {
        $domain = $this->app->config->get('app.domain');
        setcookie(  $name,
            $value,
            $expires ? time() + $expires * 24 * 3600 : 0,
            !empty($args['path']) ? $args['path'] : "/",
            !empty($args['domain']) ? $args['domain'] : ("." . $domain)
        );
    }

}