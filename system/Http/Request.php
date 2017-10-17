<?php

namespace System\Http;


class Request
{
    protected static $instance;

    //
    protected $method;
    protected $headers;
    protected $server;
    protected $query;
    protected $post;
    protected $files;
    protected $cookies;


    public function __construct($method, array $headers, array $server, array $query, array $post, array $files, array $cookies)
    {
        $this->method = strtoupper($method);

        $this->headers = array_change_key_case($headers);

        //
        $this->server  = $server;
        $this->query   = $query;
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

    public function previous()
    {
        return array_get($this->server, 'HTTP_REFERER');
    }

    public function server($key = null)
    {
        if (is_null($key)) {
            return $this->server;
        }

        return array_get($this->server, $key);
    }

    public function input($key, $default = null)
    {
        $input = ($this->method == 'GET') ? $this->query : $this->post;

        return array_get($input, $key, $default);
    }

    public function query($key = null, $default = null)
    {
        if (is_null($key)) {
            return $this->query;
        }

        return array_get($this->query, $key, $default);
    }

    public function files()
    {
        return $this->files;
    }

    public function file($key)
    {
        return array_get($this->files, $key);
    }

    public function hasFile($key)
    {
        return array_has($this->files, $key);
    }

    public function cookies()
    {
        return $this->cookies;
    }

    public function cookie($key, $default = null)
    {
        return array_get($this->cookies, $key, $default);
    }

    public function hasCookie($key)
    {
        return array_has($this->cookies, $key);
    }
}
