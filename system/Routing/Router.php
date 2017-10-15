<?php

namespace System\Routing;

use System\Http\Exceptions\HttpException;
use System\View\View;

use Closure;
use LogicException;


class Router
{
    /**
     * The current Router instance.
     *
     * @var \System\Routing\Router
     */
    protected static $instance;

    /**
     * An array of registered routes.
     *
     * @var array
     */
    protected $routes = array(
        'GET'     => array(),
        'POST'    => array(),
        'PUT'     => array(),
        'DELETE'  => array(),
        'PATCH'   => array(),
        'HEAD'    => array(),
        'OPTIONS' => array(),
    );


    protected function any($route, $action)
    {
        $methods = array('GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD');

        return $this->match($methods, $route, $action);
    }

    protected function match(array $methods, $route, $action)
    {
        $methods = array_map('strtoupper', $methods);

        if (in_array('GET', $methods) && ! in_array('HEAD', $methods)) {
            $methods[] = 'HEAD';
        }

        $route = '/' .trim($route, '/');

        foreach ($methods as $method) {
            if (array_key_exists($method, $this->routes)) {
                $this->routes[$method][$route] = $action;
            }
        }
    }

    protected function dispatch()
    {
        $method = $_SERVER['REQUEST_METHOD'];

        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

        // Get the routes registered for the current HTTP method.
        $routes = $this->routes[$method];

        foreach ($routes as $route => $action) {
            list ($pattern, $variables) = $this->compileRoute($route);

            if (preg_match($pattern, $path, $matches) !== 1) {
                continue;
            }

            $parameters = array_filter($matches, function ($key) use ($variables)
            {
                return in_array($key, $variables) && ! empty($key);

            }, ARRAY_FILTER_USE_KEY);

            if ($callback instanceof Closure) {
                return call_user_func_array($callback, $parameters);
            }

            list ($controller, $method) = explode('@', $callback);

            if (! method_exists($instance = new $controller(), $method)) {
                throw new LogicException("Controller [$controller] has no method [$method].");
            }

            return $instance->callAction($method, $parameters);
        }

        throw new HttpException(404, 'Page not found.');
    }

    protected function compileRoute($route)
    {
        $optionals = 0;

        $variables = array();

        $pattern = preg_replace_callback('#/\{(.*?)(?:\:(.+?))?(\?)?\}#', function ($matches) use ($route, &$optionals, &$variables)
        {
            @list($text, $name, $condition, $optional) = $matches;

            if (in_array($name, $variables)) {
                throw new LogicException("Pattern [$route] cannot reference variable name [$name] more than once.");
            }

            $variables[] = $name;

            $regexp = sprintf('/(?P<%s>%s)', $name, ! empty($condition)
                ? str_replace(array('num', 'any', 'all'), array('[0-9]+', '[^/]+', '.*'), $condition)
                : '[^/]+');

            if ($optional) {
                $regexp = "(?:$regexp";

                $optionals++;
            } else if ($optionals > 0) {
                throw new LogicException("Pattern [$route] cannot reference variable [$name] after one or more optionals.");
            }

            return $regexp;

        }, $route);

        if ($optionals > 0) {
            $pattern .= str_repeat(')?', $optionals);
        }

        return array('#^' .$pattern .'$#s', $variables);
    }

    public static function getInstance()
    {
        if (isset(static::$instance)) {
            return static::$instance;
        }

        return static::$instance = new static();
    }

    public static function __callStatic($method, $parameters)
    {
        $instance = static::getInstance();

        if (array_key_exists(strtoupper($method), $instance->routes)) {
            array_unshift($parameters, array($method));

            $method = 'match';
        }

        return call_user_func_array(array($instance, $method), $parameters);
    }
}
