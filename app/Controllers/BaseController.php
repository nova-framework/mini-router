<?php

namespace App\Controllers;

use System\Routing\Controller;
use System\View\View;

use BadMethodCallException;


class BaseController extends Controller
{
    /**
     * The currently used Layout.
     *
     * @var string
     */
    protected $layout = 'Default';

    /**
     * The current View path.
     *
     * @var string
     */
    protected $viewPath;


    public function after($response)
    {
        if (($response instanceof View) && ! empty($this->layout)) {
            $view = 'Layouts/' .$this->layout;

            return View::make($view, array('content' => $response))->render();
        }

        return $response;
    }

    protected function createView(array $data = array(), $view = null)
    {
        if (is_null($view)) {
            $view = ucfirst($this->action);
        }

        $view = $this->getViewPath() .$view;

        return View::make($view, $data);
    }

    protected function getViewPath()
    {
        if (isset($this->viewPath)) {
            return $this->viewPath;
        }

        $classPath = str_replace('\\', '/', static::class);

        if (preg_match('#^App/Controllers/(.*)$#', $classPath, $matches) === 1) {
            return $this->viewPath = $matches[1] .DS;
        }

        throw new BadMethodCallException('Invalid Controller namespace');
    }
}
