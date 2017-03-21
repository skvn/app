<?php

namespace Skvn\App\Facades;

use Skvn\Base\Facade;

class Api extends Facade
{
    protected static function getFacadeTarget()
    {
        return 'api';
    }
}