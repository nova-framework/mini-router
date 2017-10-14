<?php

namespace System\Database\Query;

use System\Database\Connection;


class Builder
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
     * The WHERE conditions.
     *
     * @var array
     */
    protected $wheres = array();

    /**
     * The WHERE parameters.
     *
     * @var array
     */
    protected $params = array();


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
     * @return \System\Database\Query\Builder|static
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
     * @param string|null $operator
     * @param mixed|null $value
     * @return \System\Database\Query\Builder|static
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        if (func_num_args() == 2) {
            list ($value, $operator) = array($operator, '=');
        }

        $this->wheres[] = compact('column', 'operator', 'value', 'boolean');

        return $this;
    }

    /**
     * Add an "OR WHERE" clause to the query.
     *
     * @param  string  $column
     * @param  string  $operator
     * @param  mixed   $value
     * @return \System\Database\Query\Builder|static
     */
    public function orWhere($column, $operator = null, $value = null)
    {
        return $this->where($column, $operator, $value, 'or');
    }

    /**
     * Execute an insert query.
     *
     * @param array $data
     * @return array
     */
    public function insert(array $data)
    {
        foreach ($data as $field => $value) {
            $fields[] = $this->wrap($field);

            $values[] = ":{$field}";
        }

        $query = '(' .implode(', ', $fields) .') VALUES (' .implode(', ', $values) .')';

        $this->connection->insert("INSERT INTO {{$this->table}} $query", $data);

        return $this->connection->lastInsertId();
    }

    /**
     * Execute an update query
     *
     * @param  array   $data
     * @return boolean
     */
    public function update(array $data)
    {
        foreach ($data as $field => $value) {
            $field = trim($field, ':');

            $sql[] = $this->wrap($field) ." = :{$field}";
        }

        $query = ' ' .implode(', ', $sql) .' ';

        $where = $this->compileWheres();

        return $this->connection->update(
            "UPDATE {{$this->table}} SET $query WHERE $where", array_merge($data, $this->params)
        );
    }

    /**
     * Execute a delete query.
     *
     * @return array
     */
    public function delete()
    {
        $where = $this->compileWheres();

        return $this->connection->delete("DELETE FROM {{$this->table}} WHERE $where", $this->params);
    }

    /**
     * Build the SQL string for WHEREs and populate the parameters list.
     *
     * @return string
     */
    protected function compileWheres()
    {
        $wheres = array();

        foreach ($this->wheres as $where) {
            $param = ':' .$where['column'];

            $wheres[] = strtoupper($where['boolean']) .' ' .$this->wrap($where['column']) .' ' .$where['operator'] .' ' .$param;

            $this->params[$param] = $where['value'];
        }

        return preg_replace('/AND |OR /', '', implode(' ', $wheres), 1);
    }

    /**
     * Wrap a value in keyword identifiers.
     *
     * @param  string  $value
     * @return string
     */
    protected function wrap($value)
    {
        return $this->connection->wrap($value);
    }
}
