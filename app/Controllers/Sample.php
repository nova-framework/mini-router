<?php

namespace App\Controllers;


class Sample
{

    public function index()
    {
        echo '</pre>This is the Homepage</pre>';
    }

    public function page($slug = null)
    {
        echo '</pre>URI: ' .var_export($slug, true) .'</pre>';
    }

    public function post($slug = null)
    {
        echo '</pre>URI: ' .var_export($slug, true) .'</pre>';
    }
}
