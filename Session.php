<?php

namespace Skvn\App;

use Skvn\Base\Traits\CastedProps;
use Skvn\Base\Traits\ArrayOrObjectAccessImpl;

class Session implements \ArrayAccess
{

    use CastedProps;
    use ArrayOrObjectAccessImpl;

    function get($key)
    {
        return $_SESSION[$key] ?? null;
    }

    function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    function has($key)
    {
        return isset($_SESSION[$key]);
    }

    function delete($key)
    {
        unset($_SESSION[$key]);
    }

    function start()
    {
        session_start();
    }

    function clear()
    {
        $_SESSION = "";
    }

    function destroy()
    {
        session_destroy();
    }
}