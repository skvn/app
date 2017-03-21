<?php

namespace Skvn\App;

use Skvn\Base\Traits\AppHolder;

abstract class ApiService
{
    use AppHolder;

    abstract function getName();
    abstract function authorize($data);


    function call($method, $data)
    {
        if (!method_exists($this, $method)) {
            throw new Exceptions\ApiException('Method ' . $method . ' do not exist at ' . get_class($this));
        }
        return $this->$method($data);
    }

}