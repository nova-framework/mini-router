<?php

namespace App\Controllers;

use System\Routing\Controller;


class Sample extends Controller
{

    public function index()
    {
        return '</pre>This is the Homepage</pre>';
    }

    public function page($slug = null)
    {
        return '</pre>URI: ' .var_export($slug, true) .'</pre>';
    }

    public function post($slug = null)
    {
        return '</pre>URI: ' .var_export($slug, true) .'</pre>';
    }
}
