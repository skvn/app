<?php

namespace Skvn\App;

use Skvn\Base\Traits\CastedProps;
use Skvn\Base\Traits\ArrayOrObjectAccessImpl;
use Skvn\Base\Traits\AppHolder;


class Session implements \ArrayAccess
{

    use CastedProps;
    use ArrayOrObjectAccessImpl;
    use AppHolder;

    public $started = false;

    public function getId()
    {
        return session_id();
    }

    public function setId($id)
    {
        session_id($id);
    }

    public function setName($name)
    {
        session_name($name);
    }

    function get($key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    function set($key, $value)
    {
        if ($this->started) {
            $_SESSION[$key] = $value;
        }
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
        $this->started = true;
    }

    function clear()
    {
        $_SESSION = "";
    }

    function destroy()
    {
        session_destroy();
    }

    function close()
    {
        session_write_close();
    }

    function all()
    {
        return $_SESSION;
    }

    function export()
    {
        return $this->all();
    }
}