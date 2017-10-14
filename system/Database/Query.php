<?php

namespace System\Database;

use System\Database\Connection;


class Query
{
    /**
     * The Database Connection instance.
     *
     * @var \System\Database\Connection
     */
    protected $connection;

    /**
     * The table which the query is targeting.
     *
     * @var string
     */
    protected $table;

    /**
     * The conditions.
     *
     * @var array
     */
    protected $wheres = array();


    /**
     * Create a new Query Builder instance.
     *
     * @param  \System\Database\Connection $connection
     * @return void
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Set the table which the query is targeting.
     *
     * @param string $table
     * @return \System\Database\Query|static
     */
    public function from($table)
    {
        $this->table = $table;

        return $this;
    }

    /**
     * Add one or more conditions.
     *
     * @param string $field
     * @param mixed|null $value
     * @return \System\Database\Query|static
     */
    public function where($field, $value = null)
    {
        if (! is_array($field)) {
            $field = array($field => $value);
        }

        $this->wheres = array_merge($this->wheres, $field);

        return $this;
    }

    /**
     * Execute an insert query.
     *
     * @param array $data
     * @return array
     */
    public function insert(array $data)
    {
        $connection = $this->getConnection();

        $table = $this->getTable();

        //
        $query = $connection->query('insert', $data);

        return $connection->insertGetId("INSERT INTO {{$table}} $query", $data);
    }

    /**
     * Execute an update query
     *
     * @param  array   $data
     * @param  array   $wheres
     * @return boolean
     */
    public function update(array $data, array $wheres = array())
    {
        $connection = $this->getConnection();

        $table = $this->getTable();

        $wheres = array_merge($this->wheres, $wheres);

        //
        $query = $connection->query('update', $data);

        $whereSql = $this->compileWheres($wheres);

        return $this->connection->update(
            "UPDATE {{$table}} SET $query WHERE " .$whereSql, array_merge($data, $wheres)
        );
    }

    /**
     * Execute a delete query.
     *
     * @return array
     */
    public function delete()
    {
        $connection = $this->getConnection();

        $table = $this->getTable();

        //
        $whereSql = $this->compileWheres($this->wheres);

        return $connection->delete("DELETE FROM {{$table}} WHERE " .$whereSql, $this->wheres);
    }

    /**
     * Compile the wheres part of a SQL statement.
     *
     * @param  array   $wheres
     * @return string
     */
    protected function compileWheres(array $wheres)
    {
        $connection = $this->getConnection();

        foreach ($wheres as $field => $value) {
            $field = trim($field, ':');

            $sql[] = $connection->wrap($field) ." = :{$field}";
        }

        return implode(' AND ', $sql);
    }

    /**
     * Get the Connection instance.
     *
     * @return \System\Database\Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Get the table which the query is targeting.
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }
}
