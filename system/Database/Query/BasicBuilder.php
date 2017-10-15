<?php

namespace System\Database\Query;

use System\Database\Connection;


class BasicBuilder
{
    /**
     * @var \System\Database\Connection
     */
    protected $connection; // The Connection instance.

    /**
     * @var string
     */
    protected $table;  // The table which the query is targeting.

    /**
     * The query conditions.
     */
    protected $wheres   = array();
    protected $bindings = array();

    protected $query;


    /**
     * Create a new Query Builder instance.
     *
     * @param  \System\Database\Connection $connection
     * @param string $table
     * @return void
     */
    public function __construct(Connection $connection, $table)
    {
        $this->connection = $connection;

        $this->table = $table;
    }

    /**
     * Execute an INSERT query.
     *
     * @param array $data
     * @return array
     */
    public function insert(array $data)
    {
        foreach ($data as $field => $value) {
            $fields[] = $this->wrap($field);

            $values[] = '?';

            $this->bindings[] = $value;
        }

        $this->query = 'INSERT INTO {' .$this->table .'} (' .implode(', ', $fields) .') VALUES (' .implode(', ', $values) .')';

        $this->connection->insert($this->query, $this->bindings);

        return $this->connection->lastInsertId();
    }

    /**
     * Execute an UPDATE query
     *
     * @param  array   $data
     * @return boolean
     */
    public function update(array $data)
    {
        foreach ($data as $field => $value) {
            $items[] = $this->wrap($field) .' = ?';

            $this->bindings[] = $value;
        }

        $this->query = 'UPDATE {' .$this->table .'} SET ' .implode(', ', $items) .$this->conditions();

        return $this->connection->update($this->query, $this->bindings);
    }

    /**
     * Execute a DELETE query.
     *
     * @return array
     */
    public function delete()
    {
        $this->query = 'DELETE FROM {' .$this->table .'}' .$this->conditions();

        return $this->connection->delete($this->query, $this->bindings);
    }

    /**
     * Add a "WHERE" clause to the query.
     *
     * @param string $field
     * @param string|null $operator
     * @param mixed|null $value
     * @return static
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
     * @return static
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
        $query = '';

        // Wheres.
        $items = array();

        foreach ($this->wheres as $where) {
            $items[] = $this->compileWhere($where);
        }

        if (! empty($items)) {
            $query .= ' WHERE ' .preg_replace('/AND |OR /', '', implode(' ', $items), 1);
        }

        return $query;
    }

    /**
     * Compile a WHERE condition.
     *
     * @param  array  $where
     * @return string
     */
    protected function compileWhere(array $where)
    {
        extract($where);

        //
        $column = strtoupper($boolean) .' ' .$this->wrap($column);

        $not = ($operator !== '=') ? 'NOT ' : '';

        // No value given?
        if (is_null($value)) {
            return $column .' IS ' .$not .'NULL';
        }

        // Multiple values given?
        else if (is_array($value)) {
            $this->bindings = array_merge($this->bindings, $value);

            $items = array_fill(0, count($value), '?');

            return $column .' ' .$not .'IN (' .implode(', ', $items) .')';
        }

        // Standard WHERE.
        $this->bindings[] = $value;

        return $column .' ' .$operator .' ?';
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

    /**
     * Get the last executed SQL query.
     *
     * @return string|null
     */
    public function lastQuery()
    {
        return $this->query;
    }
}
