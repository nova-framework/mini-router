<?php

namespace System\Database;

use System\Events\Dispatcher;


class Facade
{

    public static function __callStatic($method, $parameters)
    {
        $instance = Dispatcher::getInstance();

        return call_user_func_array(array($instance, $method), $parameters);
    }
}
