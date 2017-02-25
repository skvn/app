<?php

namespace Skvn\App\Facades;

use Skvn\Base\Facade;

class Response extends Facade
{
    protected static function getFacadeTarget()
    {
        return 'response';
    }
}