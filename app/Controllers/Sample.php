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
        $users = DB::select('select id, username, realname, email from ' .PREFIX .'users');

        $content .= '<pre>' .var_export($users, true) .'</pre>';

        //
        $user = DB::selectOne('select * from ' .PREFIX .'users WHERE id = :id', array('id' => 1));

        $content .= '<pre>' .var_export($user, true) .'</pre>';

         $user = DB::selectOne('select * from ' .PREFIX .'users WHERE id = :id', array('id' => 4));

        $content .= '<pre>' .var_export($user, true) .'</pre>';

        //
        $result = DB::table('users')->where('username', 'testuser')->delete();

        $content .= '<pre>' .var_export($result, true) .'</pre>';

        //
        $id = DB::table('users')->insertGetId(array(
            'username'  => 'testuser',
            'password'  => 'testuser',
            'realname'  => 'Test User',
            'email'     => 'test@testuser.dev',
            'activated' => 0,
        ));

        $content .= '<pre>' .var_export($id, true) .'</pre>';

        //
        $user = DB::table('users')->where('username', 'testuser')->first();

        $content .= '<pre>' .var_export($user, true) .'</pre>';

       //
        $result = DB::table('users')->where('username', 'testuser')->update(array(
            'realname'  => 'Test2 User',
            'email'     => 'test2@testuser.dev',
            'activated' => 1,
        ));

        //
        $user = DB::table('users')->select('id', 'username', 'realname', 'email', 'activated')->where('username', 'testuser')->first();

        $content .= '<pre>' .var_export($user, true) .'</pre>';

        return $this->createView(compact('content'), 'Index')
            ->shares('title', 'Database API');
    }
}
