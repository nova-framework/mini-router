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
        $sql = DB::prepare('SELECT {users.id} FROM {users} WHERE id = :id');

        $content .= '<pre>' .var_export($sql, true) .'</pre>';

        //
        $users = DB::select('SELECT id, username, realname, email FROM {users}');

        $content .= '<pre>' .var_export($users, true) .'</pre>';

        //
        $user = DB::selectOne('SELECT {users.*} FROM {users} WHERE id = :id', array('id' => 1));

        $content .= '<pre>' .var_export($user, true) .'</pre>';

        $user = DB::selectOne('SELECT username, realname, email FROM {users} WHERE id = :id', array('id' => 4));

        $content .= '<pre>' .var_export($user, true) .'</pre>';

        //
        $users = DB::select('SELECT id, username, realname, email FROM {users}');

        $content .= '<pre>' .var_export($users, true) .'</pre>';

        //
        $users = DB::table('users')
            ->where('username', '!=', 'admin')
            ->limit(2)
            ->orderBy('realname', 'desc')
            ->get(array('id', 'username', 'realname', 'email'));

        $content .= '<pre>' .var_export($users, true) .'</pre>';

        //
        $userId = DB::table('users')->insertGetId(array(
            'username'  => 'testuser',
            'password'  => 'password',
            'realname'  => 'Test User',
            'email'     => 'test@testuser.dev',
            'activated' => 0,
        ));

        $content .= '<pre>' .var_export($userId, true) .'</pre>';

        //
        $user = DB::table('users')->find($userId);

        $content .= '<pre>' .var_export($user, true) .'</pre>';

        //
        $result = DB::table('users')->where('id', $userId)->update(array(
            'username'  => 'testuser2',
            'password'  => 'another password',
            'realname'  => 'Updated Test User',
            'email'     => 'test@testuser.dev',
            'activated' => 1,
        ));

        $content .= '<pre>' .var_export($result, true) .'</pre>';

        //
        $user = DB::table('users')->find($userId);

        $content .= '<pre>' .var_export($user, true) .'</pre>';

        //
        $result = DB::table('users')->where('id', $userId)->delete();

        $content .= '<pre>' .var_export($result, true) .'</pre>';


        return $this->createView(compact('content'), 'Index')
            ->shares('title', 'Database API & QueryBuilder');
    }
}
