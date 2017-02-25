<?php

namespace Skvn\App\Facades;

use Skvn\Base\Facade;

class App extends Facade
{
    protected static function getFacadeTarget()
    {
        return 'app';
    }
}