<?php

namespace Skvn\App\Exceptions;


class HttpNotAllowedException extends HttpException
{
    function __construct($message, $params = [])
    {
        parent :: __construct($message, $params, 403);
    }

}