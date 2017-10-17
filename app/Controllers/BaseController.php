<?php

namespace App\Controllers;

use System\Routing\Controller;
use System\Http\Response;
use System\View\View;

use BadMethodCallException;


class BaseController extends Controller
{
    /**
     * The currently requested Action.
     *
     * @var string
     */
    protected $action;

    /**
     * The currently used Layout.
     *
     * @var string
     */
    protected $layout = 'Default';


    public function callAction($method, array $parameters)
    {
        $this->action = $method;

        if (! is_null($response = $this->before())) {
            return $response;
        }

        $response = call_user_func_array(array($this, $method), $parameters);

        return $this->after($response);
    }

    protected function before()
    {
        //
    }

    protected function after($response)
    {
        if (($response instanceof View) && ! empty($this->layout)) {
            $view = 'Layouts/' .$this->layout;

            $response = View::make($view, array('content' => $response))->render();
        }

        if (! $response instanceof Response) {
            $response = new Response($response);
        }

        return $response;
    }

    protected function createView(array $data = array(), $view = null)
    {
        if (is_null($view)) {
            $view = ucfirst($this->action);
        }

        $classPath = str_replace('\\', '/', static::class);

        if (preg_match('#^App/Controllers/(.*)$#', $classPath, $matches) === 1) {
            $view = $matches[1] .'/' .$view;

            return View::make($view, $data);
        }

        throw new BadMethodCallException('Invalid Controller namespace');
    }
}
