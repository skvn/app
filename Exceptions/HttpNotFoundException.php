<?php

namespace Skvn\App\Exceptions;


class HttpNotFoundException extends HttpException
{
    function __construct($message, $params = [])
    {
        parent :: __construct($message, $params, 404);
    }

}