<?php

namespace Skvn\App;

use Skvn\Base\Traits\CastedProps;
use Skvn\Base\Traits\ArrayOrObjectAccessImpl;
use Skvn\Base\Traits\AppHolder;
use Skvn\Base\Traits\ConstructorConfig;
use Skvn\Base\Helpers\Str;
use Skvn\Base\Exceptions\Exception;

class SessionInCache
{
    use CastedProps;
    use ArrayOrObjectAccessImpl;
    use AppHolder;
    use ConstructorConfig;

    private $data = [];
    private $id = null;
    private $response;

    private function getCache()
    {
        return $this->app->get('cache')->storage($this->config['storage']);
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $this->isValidId($id) ? $id : $this->generateSessionId();
        $this->response->setCookie($this->config['name'], $this->id, $this->config['cookie_ttl'], ['httponly' => true]);
        $this->data = array_merge($this->data, $this->getCache()->get($this->id) ?? []);
    }

    public function setName($name)
    {
        $this->config['name'] = $name;
    }

    public function get($key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    public function all()
    {
        return $this->data;
    }

    public function export()
    {
        return $this->all();
    }

    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function has($key)
    {
        return array_key_exists($key, $this->data);
    }

    public function delete($key)
    {
        unset($this->data[$key]);
    }

    public function start($request, $response)
    {
        $this->response = $response;
        $this->setId($request->getCookie($this->config['name']));
        register_shutdown_function(function () {
            $this->close();
        });
    }

    public function clear()
    {
        $this->data = [];
    }

    public function destroy()
    {
        $this->data = [];
        if (!empty($this->id)) {
            $this->getCache()->delete($this->id);
        }
    }

    private function isValidId($id)
    {
        return is_string($id) && ctype_alnum($id) && strlen($id) === 40;
    }

    private function generateSessionId()
    {
        return Str::random(40);
    }

    public function close()
    {
        if (!empty($this->id)) {
            $this->getCache()->set($this->id, $this->data, $this->config['cookie_ttl']);
        }
    }


}
