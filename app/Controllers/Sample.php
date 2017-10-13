<?php

namespace App\Controllers;

use App\Controllers\BaseController;

use DB;


class Sample extends BaseController
{

    public function index()
    {
        $content = 'This is the Homepage';

        return $this->createView()
            ->shares('title', 'Homepage')
            ->with('content', $content);
    }

    public function page($slug = null)
    {
        return '</pre>URI: ' .var_export($slug, true) .'</pre>';
    }

    public function post($slug = null)
    {
        return '</pre>URI: ' .var_export($slug, true) .'</pre>';
    }

    public function database()
    {
        $content = '';

        //
        $users = DB::select('select * from ' .PREFIX .'users');

        $content .= '<pre>' .var_export($users, true) .'</pre>';

        //
        $user = DB::selectOne('select * from ' .PREFIX .'users WHERE id = :id', array('id' => 1));

        $content .= '<pre>' .var_export($user, true) .'</pre>';

        return $this->createView(compact('content'), 'Index')
            ->shares('title', 'Database API');
    }
}
