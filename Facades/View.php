<?php

namespace Skvn\App\Facades;

use Skvn\Base\Facade;

class View extends Facade
{
    protected static function getFacadeTarget()
    {
        return 'view';
    }
}