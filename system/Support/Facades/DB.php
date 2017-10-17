<?php

namespace System\Support\Facades;

use System\Database\Manager;


class DB
{

    public static function __callStatic($method, $parameters)
    {
        $instance = Manager::getInstance();

        return call_user_func_array(array($instance, $method), $parameters);
    }
}
