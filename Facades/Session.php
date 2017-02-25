<?php

namespace Skvn\App\Facades;

use Skvn\Base\Facade;

class Session extends Facade
{
    protected static function getFacadeTarget()
    {
        return 'session';
    }
}