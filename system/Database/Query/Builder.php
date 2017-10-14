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
     * The query conditions.
     */
    protected $wheres = array();
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

        $query = "INSERT INTO {{$this->table}} (" .implode(', ', $fields) .") VALUES (" .implode(', ', $values) .")";

        return $this->connection->insert($query, $data);
    }

    /**
     * Insert a new Record and get the value of the primary key.
     *
     * @param array $data
     * @return array
     */
    public function insertGetId(array $data)
    {
        $this->insert($data);

        $id = $this->connection->getPdo()->lastInsertId();

        return is_numeric($id) ? (int) $id : $id;
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
            $sql[] = $this->wrap($field) ." = :{$field}";
        }

        $query = "UPDATE {{$this->table}} SET " .implode(', ', $sql) .$this->conditions();

        return $this->connection->update($query, array_merge($data, $this->params));
    }

    /**
     * Execute a delete query.
     *
     * @return array
     */
    public function delete()
    {
        $query = "DELETE FROM {{$this->table}}" .$this->conditions();

        return $this->connection->delete($query, $this->params);
    }

    /**
     * Add a "WHERE" clause to the query.
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
     * Build the SQL string and parameters for conditions.
     *
     * @return string
     */
    protected function conditions()
    {
        $wheres = array();

        foreach ($this->wheres as $where) {
            $column = $where['column'];

            $param = ':' .$column;

            $wheres[] = strtoupper($where['boolean']) .' ' .$this->wrap($column) .' ' .$where['operator'] .' ' .$param;

            $this->params[$param] = $where['value'];
        }

        if (! empty($wheres)) {
            return ' WHERE ' .preg_replace('/AND |OR /', '', implode(' ', $wheres), 1);
        }
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
