<?php

use System\Config\Config;
use System\Http\Exceptions\HttpException;
use System\Http\Exceptions\HttpRedirectException;


/**
 * Abort the Application with an HttpException.
 *
 * @param int  code
 * @param string  $message
 * @return string
 */
function abort($code = 404, $message = null)
{
    throw new HttpException($code, $message);
}

/**
 * Abort the Application with an HttpRedirectException.
 *
 * @param string  $url
 * @param int  code
 * @param string  $message
 * @return void
 */
function redirect_to($url, $fullPath = false, $code = 301, $message = null)
{
    if (! $fullPath) {
        $url = site_url($url);
    }

    throw new HttpRedirectException($url, $code, $message);
}

/**
 * Abort the Application with an HttpRedirectException.
 *
 * @param int  code
 * @param string  $message
 * @return void
 */
function redirect_back($code = 301, $message = null)
{
    $url = ! empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : site_url();

    throw new HttpRedirectException($url, $code, $message);
}

/**
 * Site URL helper
 *
 * @param string $path
 * @return string
 */
function site_url($path = '/')
{
    return Config::get('app.url') .ltrim($path, '/');
}

/**
 * Get an item from an array using "dot" notation.
 *
 * @param  array   $array
 * @param  string  $key
 * @param  mixed   $default
 * @return mixed
 */
function array_get($array, $key, $default = null)
{
    if (is_null($key)) {
        return $array;
    } else if (isset($array[$key])) {
        return $array[$key];
    }

    foreach (explode('.', $key) as $segment) {
        if (! is_array($array) || ! array_key_exists($segment, $array)) {
            return $default;
        }

        $array = $array[$segment];
    }

    return $array;
}

/**
 * Check if an item exists in an array using "dot" notation.
 *
 * @param  array   $array
 * @param  string  $key
 * @return bool
 */
function array_has($array, $key)
{
    if (empty($array) || is_null($key)) {
        return false;
    } else if (array_key_exists($key, $array)) {
        return true;
    }

    foreach (explode('.', $key) as $segment) {
        if (! is_array($array) || ! array_key_exists($segment, $array)) {
            return false;
        }

        $array = $array[$segment];
    }

    return true;
}

/**
 * Set an array item to a given value using "dot" notation.
 *
 * If no key is given to the method, the entire array will be replaced.
 *
 * @param  array   $array
 * @param  string  $key
 * @param  mixed   $value
 * @return array
 */
function array_set(&$array, $key, $value)
{
    if (is_null($key)) {
        return $array = $value;
    }

    $keys = explode('.', $key);

    while (count($keys) > 1) {
        $key = array_shift($keys);

        if (! isset($array[$key]) || ! is_array($array[$key])) {
            $array[$key] = array();
        }

        $array =& $array[$key];
    }

    $key = array_shift($keys);

    $array[$key] = $value;

    return $array;
}
