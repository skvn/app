<?php

namespace Skvn\App\Facades;

use Skvn\Base\Facade;

class DB extends Facade
{
    protected static function getFacadeTarget()
    {
        return 'db';
    }
}