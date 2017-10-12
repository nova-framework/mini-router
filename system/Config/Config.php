<?php

namespace System\Config;


class Config
{
    /**
     * @var array
     */
    protected static $settings = array();


    /**
     * Return true if the key exists.
     * @param string $key
     * @return bool
     */
    public static function has($key)
    {
        return ! is_null(array_get(static::$settings, $key));
    }

    /**
     * Get the value.
     * @param string $key
     * @return mixed|null
     */
    public static function get($key, $default = null)
    {
        return array_get(static::$settings, $key, $default);
    }

    /**
     * Set the value.
     * @param string $key
     * @param mixed $value
     */
    public static function set($key, $value)
    {
        array_set(static::$settings, $key, $value);
    }
}
