<?php

namespace System\Support\Facades;

use System\Routing\Router;


class Route
{

    public static function __callStatic($method, $parameters)
    {
        $instance = Router::getInstance();

        return call_user_func_array(array($instance, $method), $parameters);
    }
}
