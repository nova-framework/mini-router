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
        $query = $connection->compile('insert', $data);

        return $connection->insertGetId("INSERT INTO {{$table}} $query", $data);
    }

    /**
     * Execute an update query
     *
     * @param  array   $data
     * @return boolean
     */
    public function update(array $data)
    {
        $connection = $this->getConnection();

        $table = $this->getTable();

        //
        $query = $connection->compile('update', $data);

        $where = $connection->compile('wheres', $this->wheres);

        return $connection->update(
            "UPDATE {{$table}} SET $query WHERE $where", array_merge($data, $this->wheres)
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
        $where = $connection->compile('wheres', $this->wheres);

        return $connection->delete("DELETE FROM {{$table}} WHERE $where", $this->wheres);
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
