<?php

namespace Skvn\App\Facades;

use Skvn\Base\Facade;

class RedisDB extends Facade
{
    protected static function getFacadeTarget()
    {
        return 'redis';
    }
}