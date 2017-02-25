<?php

namespace Skvn\App\Facades;

use Skvn\Base\Facade;

class Events extends Facade
{
    protected static function getFacadeTarget()
    {
        return 'events';
    }
}