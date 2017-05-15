<?php

namespace Skvn\App\Exceptions;


class HttpBadRequestException extends HttpException
{
    function __construct($message, $params = [])
    {
        parent :: __construct($message, $params, 400);
    }

}