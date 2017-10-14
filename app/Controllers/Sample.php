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
        $data = array(
            'username'  => 'testuser',
            'password'  => 'password',
            'realname'  => 'Test User',
            'email'     => 'test@testuser.dev',
            'activated' => 0,
        );

        $query = DB::compile('insert', $data);

        $content .= '<pre>' .var_export($query, true) .'</pre>';

        //
        $userId = DB::insertGetId('INSERT INTO {users} ' .$query, $data);

        $content .= '<pre>' .var_export($userId, true) .'</pre>';

        //
        $user = DB::selectOne('SELECT {users.*} FROM {users} WHERE id = :id', array('id' => $userId));

        $content .= '<pre>' .var_export($user, true) .'</pre>';

        $data = array(
            'username'  => 'testuser2',
            'password'  => 'another password',
            'realname'  => 'Updated Test User',
            'email'     => 'test@testuser.dev',
            'activated' => 1,
        );

        $query = DB::compile('update', $data);

        $content .= '<pre>' .var_export($query, true) .'</pre>';

        //
        $result = DB::insert('UPDATE {users} SET ' .$query .' WHERE id = :id', array_merge($data, array('id' => $userId)));

        $content .= '<pre>' .var_export($result, true) .'</pre>';

        //
        $user = DB::selectOne('SELECT {users.*} FROM {users} WHERE id = :id', array('id' => $userId));

        $content .= '<pre>' .var_export($user, true) .'</pre>';

        //
        $result = DB::delete('DELETE FROM {users} WHERE id = :id', array(':id' => $userId));

        $content .= '<pre>' .var_export($result, true) .'</pre>';

        //
        $data = array(
            'username'  => 'testuser',
            'password'  => 'password',
            'realname'  => 'Test User',
            'email'     => 'test@testuser.dev',
            'activated' => 0,
        );

        $userId = DB::table('users')->insert($data);

        $content .= '<pre>' .var_export($userId, true) .'</pre>';

        //
        $user = DB::selectOne('SELECT {users.*} FROM {users} WHERE id = :id', array('id' => $userId));

        $content .= '<pre>' .var_export($user, true) .'</pre>';

        //
        $data = array(
            'username'  => 'testuser2',
            'password'  => 'another password',
            'realname'  => 'Updated Test User',
            'email'     => 'test@testuser.dev',
            'activated' => 1,
        );

        $result = DB::table('users')->where('id', $userId)->update($data);

        $content .= '<pre>' .var_export($result, true) .'</pre>';

        //
        $user = DB::selectOne('SELECT {users.*} FROM {users} WHERE id = :id', array('id' => $userId));

        $content .= '<pre>' .var_export($user, true) .'</pre>';

        //
        $result = DB::table('users')->where('id', $userId)->delete();

        $content .= '<pre>' .var_export($result, true) .'</pre>';


        return $this->createView(compact('content'), 'Index')
            ->shares('title', 'Database API');
    }
}
