<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\Users;

use DB;


class Sample extends BaseController
{
    protected $users;


    public function __construct()
    {
        $this->users = new Users();
    }

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

        $content .= '<h3>Database API</h3>';

        //
        $query = 'SELECT {users.id} FROM {users} WHERE id = :id';

        $statement = DB::prepare($query);

        $content .= '<pre>' .var_export($query, true) .'</pre>';

        $content .= '<pre>' .var_export($statement->queryString, true) .'</pre>';

        //
        $users = DB::select('SELECT id, username, realname, email FROM {users}');

        $content .= '<pre>' .var_export($users, true) .'</pre>';

        //
        $user = DB::selectOne('SELECT {users.*} FROM {users} WHERE id = :id', array('id' => 1));

        $content .= '<pre>' .var_export($user, true) .'</pre>';

        $user = DB::selectOne('SELECT username, realname, email FROM {users} WHERE id = :id', array('id' => 4));

        $content .= '<pre>' .var_export($user, true) .'</pre>';

        $content .= '<br><h3>QueryBuilder</h3>';

        //
        $query = DB::table('users');

        $users = $query->select('id', 'username', 'email')->where('id', array(1, 3, 4))->get();

        $content .= '<pre>' .var_export($query->lastQuery(), true) .'</pre>';
        $content .= '<pre>' .var_export($users, true) .'</pre><br>';

        //
        $query = DB::table('users');

        $users = $query->where('username', '!=', 'admin')
            ->limit(2)
            ->orderBy('realname', 'desc')
            ->get(array('id', 'username', 'realname', 'email'));

        $content .= '<pre>' .var_export($query->lastQuery(), true) .'</pre>';
        $content .= '<pre>' .var_export($users, true) .'</pre><br>';

        //
        $query = DB::table('users');

        $userId = $query->insert(array(
            'username'  => 'testuser',
            'password'  => 'password',
            'realname'  => 'Test User',
            'email'     => 'test@testuser.dev',
            'activated' => 0,
        ));

        $content .= '<pre>' .var_export($query->lastQuery(), true) .'</pre>';
        $content .= '<pre>' .var_export($userId, true) .'</pre><br>';

        //
        $query = DB::table('users');

        $user = $query->find($userId);

        $content .= '<pre>' .var_export($query->lastQuery(), true) .'</pre>';
        $content .= '<pre>' .var_export($user, true) .'</pre><br>';

        //
        $query = DB::table('users');

        $result = $query->where('id', $userId)->update(array(
            'username'  => 'testuser2',
            'password'  => 'another password',
            'realname'  => 'Updated Test User',
            'email'     => 'test@testuser.dev',
            'activated' => 1,
        ));

        $content .= '<pre>' .var_export($query->lastQuery(), true) .'</pre>';
        $content .= '<pre>' .var_export($result, true) .'</pre><br>';

        //
        $query = DB::table('users');

        $user = $query->find($userId);

        $content .= '<pre>' .var_export($query->lastQuery(), true) .'</pre>';
        $content .= '<pre>' .var_export($user, true) .'</pre><br>';

        //
        $query = DB::table('users');

        $result = $query->where('id', $userId)->delete();

        $content .= '<pre>' .var_export($query->lastQuery(), true) .'</pre>';
        $content .= '<pre>' .var_export($result, true) .'</pre>';

        $content .= '<br><h3>Models</h3>';

        //
        $user = $this->users->find(1);

        $content .= '<pre>' .var_export($user, true) .'</pre><br>';

        //
        $users = $this->users->all();

        $content .= '<pre>' .var_export($users, true) .'</pre><br>';

        $users = $this->users->select('id', 'username', 'realname', 'email')
            ->where('username', '!=', 'admin')
            ->orderBy('realname', 'desc')
            ->limit(2)
            ->get();

        $content .= '<pre>' .var_export($users, true) .'</pre><br>';


        return $this->createView(compact('content'), 'Index')
            ->shares('title', 'Database API & QueryBuilder');
    }

    public function error()
    {
        abort(404);
    }

    public function test()
    {
        redirect_to('database');
    }
}
