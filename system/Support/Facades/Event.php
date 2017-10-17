<?php

namespace System\Support\Facades;

use System\Events\Dispatcher;


class Event
{

    public static function __callStatic($method, $parameters)
    {
        $instance = Dispatcher::getInstance();

        return call_user_func_array(array($instance, $method), $parameters);
    }
}
