<?php

namespace Skvn\App\Facades;

use Skvn\Base\Facade;

class Queue extends Facade
{
    protected static function getFacadeTarget()
    {
        return 'queue';
    }
}