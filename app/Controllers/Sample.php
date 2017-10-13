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
        $users = DB::select('select id, username, realname from ' .PREFIX .'users');

        $content .= '<pre>' .var_export($users, true) .'</pre>';

        //
        $user = DB::selectOne('select * from ' .PREFIX .'users WHERE id = :id', array('id' => 1));

        $content .= '<pre>' .var_export($user, true) .'</pre>';

        //
        $result = DB::table('users')->where('id', 4)->update(array(
            'username'  => 'testuser',
            'realname'  => 'Test User',
            'email'     => 'test@testuser.dev',
            'activated' => 1,
        ));

        $user = DB::selectOne('select * from ' .PREFIX .'users WHERE id = :id', array('id' => 4));

        $content .= '<pre>' .var_export($user, true) .'</pre>';

        //
        $result = DB::table('users')->where('username', 'testuser2')->delete();

        $content .= '<pre>' .var_export($result, true) .'</pre>';

        //
        $result = DB::table('users')->insert(array(
            'username'  => 'testuser2',
            'password'  => 'testuser2',
            'realname'  => 'Test User',
            'email'     => 'test@testuser2.dev',
            'activated' => 1,
        ));

        $content .= '<pre>' .var_export($result, true) .'</pre>';

        //
        $user = DB::table('users')->select('id', 'username', 'realname')->where('username', 'testuser2')->first();

        $content .= '<pre>' .var_export($user, true) .'</pre>';

        return $this->createView(compact('content'), 'Index')
            ->shares('title', 'Database API');
    }
}
