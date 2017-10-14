<?php

namespace System\Database;

use System\Config\Config;
use System\Database\Connection;

use Exception;


class Manager
{
    /**
     * Connection instances
     *
     * @var \System\Database\Connection[]
     */
    private static $instances = array();


    public static function connection($name = 'default')
    {
        if (isset(static::$instances[$name])) {
            return static::$instances[$name];
        }

        // Get the requested Connection configuration.
        $config = Config::get('database.' .$name);

        if (! is_null($config)) {
            return static::$instances[$name] = new Connection($config);
        }

        throw new Exception("Connection name [$name] is not defined in your configuration");
    }

    public static function __callStatic($method, $parameters)
    {
        $instance = static::connection();

        return call_user_func_array(array($instance, $method), $parameters);
    }
}
