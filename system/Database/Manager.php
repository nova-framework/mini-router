<?php

namespace System\Database;

use System\Config\Config;
use System\Database\Connection;

use Exception;


class Manager
{
    /**
     * The Connection instances.
     *
     * @var \System\Database\Connection[]
     */
    protected static $instances = array();


    public static function connection($name = 'default')
    {
        if (isset(static::$instances[$name])) {
            return static::$instances[$name];
        }

        if (is_null($config = Config::get('database.' .$name))) {
            throw new Exception("Connection [$name] is not defined in configuration");
        }

        return static::$instances[$name] = new Connection($config);
    }

    public static function __callStatic($method, $parameters)
    {
        $instance = static::connection();

        return call_user_func_array(array($instance, $method), $parameters);
    }
}
