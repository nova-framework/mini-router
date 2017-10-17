<?php

namespace System\Http;


class Request
{
    protected static $instance;

    //
    protected $method;
    protected $headers;
    protected $server;
    protected $get;
    protected $post;
    protected $files;
    protected $cookies;


    public function __construct($method, array $headers, array $server, array $get, array $post, array $files, array $cookies)
    {
        $this->method = strtoupper($method);

        $this->headers = array_change_key_case($headers);

        //
        $this->server  = $server;
        $this->get     = $get;
        $this->post    = $post;
        $this->files   = $files;
        $this->cookies = $cookies;
    }

    public static function getInstance()
    {
        if (isset(static::$instance)) {
            return static::$instance;
        }

        // Get the HTTP method.
        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';

        if (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
            $method = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'];
        } elseif (isset($_REQUEST['_method'])) {
            $method = $_REQUEST['_method'];
        }

        // Get the request headers.
        $headers = apache_request_headers();

        return static::$instance = new static($method, $headers, $_SERVER, $_GET, $_POST, $_FILES, $_COOKIE);
    }

    public function instance()
    {
        return $this;
    }

    public function method()
    {
        return $this->method;
    }

    public function path()
    {
        return parse_url($this->server['REQUEST_URI'], PHP_URL_PATH) ?: '/';
    }

    public function ip()
    {
        if (! empty($this->server['HTTP_CLIENT_IP'])) {
            return $this->server['HTTP_CLIENT_IP'];
        } else if (! empty($this->server['HTTP_X_FORWARDED_FOR'])) {
            return $this->server['HTTP_X_FORWARDED_FOR'];
        }

        return $this->server['REMOTE_ADDR'];
    }

    public static function ajax()
    {
        if (! is_null($header = array_get($this->server, 'HTTP_X_REQUESTED_WITH'))) {
            return strtolower($header) === 'xmlhttprequest';
        }

        return false;
    }

    public static function input($key, $default = null)
    {
        return array_get(array_merge($this->get, $this->post), $key, $default);
    }

    public static function file($key)
    {
        return array_get($this->files, $key);
    }

    public static function cookie($key, $default = null)
    {
        return array_get($this->cookies, $key, $default);
    }

    public static function get()
    {
        return $this->get;
    }

    public static function post()
    {
        return $this->post;
    }

    public static function files()
    {
        return $this->files;
    }

    public static function cookies()
    {
        return $this->cookies;
    }

    public static function server($key = null)
    {
        if (is_null($key)) {
            return $this->server;
        }

        return array_get($this->server, $key);
    }

    public static function hasFile($key)
    {
        return array_has($this->files, $key);
    }
}
