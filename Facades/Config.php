<?php

namespace Skvn\App\Facades;

use Skvn\Base\Facade;

class Config extends Facade
{
    protected static function getFacadeTarget()
    {
        return 'config';
    }
}