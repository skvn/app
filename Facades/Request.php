<?php

namespace Skvn\App\Facades;

use Skvn\Base\Facade;

class Request extends Facade
{
    protected static function getFacadeTarget()
    {
        return 'request';
    }
}