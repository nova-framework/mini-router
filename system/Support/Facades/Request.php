<?php

namespace System\Support\Facades;

use System\Http\Request as HttpRequest;


class Request
{

    public static function __callStatic($method, $parameters)
    {
        $instance = HttpRequest::getInstance();

        return call_user_func_array(array($instance, $method), $parameters);
    }
}
