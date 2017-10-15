<?php

namespace System\Database\Query;

use System\Database\Connection;


class Builder
{
    /**
     * @var \System\Database\Connection
     */
    protected $connection; // The Connection instance.

    /**
     * @var string
     */
    protected $table; // The table which the query is targeting.

    /**
     * The query constraints.
     */
    protected $distinct = false;

    protected $bindings = array();
    protected $wheres   = array();
    protected $orders   = array();

    protected $columns;
    protected $offset;
    protected $limit;
    protected $query;


    /**
     * Create a new Builder instance.
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
     * Set the columns to be selected.
     *
     * @param  array  $columns
     * @return static
     */
    public function select($columns = array('*'))
    {
        $this->columns = is_array($columns) ? $columns : func_get_args();

        return $this;
    }

    /**
     * Force the query to only return distinct results.
     *
     * @return static
     */
    public function distinct()
    {
        $this->distinct = true;

        return $this;
    }

    /**
     * Get a single record by ID.
     *
     * @param int $id
     * @param  array  $columns
     * @return mixed|static
     */
    public function find($id, $columns = array('*'))
    {
        return $this->where('id', '=', $id)->first($columns);
    }

    /**
     * Get the first record.
     *
     * @param  array  $columns
     * @return mixed|static
     */
    public function first($columns = array('*'))
    {
        $results = $this->limit(1)->get($columns);

        return (count($results) > 0) ? reset($results) : null;
    }

    /**
     * Get all records.
     *
     * @param $table
     * @return array
     */
    public function get($columns = array('*'))
    {
        $columns = implode(', ', array_map(array($this, 'wrap'), $this->columns ?: $columns));

        $this->query = 'SELECT ' .($this->distinct ? 'DISTINCT ' : '') .$columns .' FROM {' .$this->table .'}' .$this->constraints();

        return $this->connection->select($this->query, $this->bindings);
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
     * Execute an UPDATE query.
     *
     * @param  array  $data
     * @return boolean
     */
    public function update(array $data)
    {
        foreach ($data as $field => $value) {
            $items[] = $this->wrap($field) .' = ?';

            $this->bindings[] = $value;
        }

        $this->query = 'UPDATE {' .$this->table .'} SET ' .implode(', ', $items) .$this->constraints();

        return $this->connection->update($this->query, $this->bindings);
    }

    /**
     * Execute a DELETE query.
     *
     * @return array
     */
    public function delete()
    {
        $this->query = 'DELETE FROM {' .$this->table .'}' .$this->constraints();

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
        if (is_callable($column)) {
            return call_user_func($column, $this);
        }

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
     * Set the "OFFSET" value of the query.
     *
     * @param  int  $value
     * @return static
     */
    public function offset($value)
    {
        $this->offset = max(0, (int) $value);

        return $this;
    }

    /**
     * Set the "LIMIT" value of the query.
     *
     * @param int $limit
     * @return static
     */
    public function limit($value)
    {
        if ($value > 0) {
            $this->limit = (int) $value;
        }

        return $this;
    }

    /**
     * Add an "ORDER BY" clause to the query.
     *
     * @param string $column
     * @param string $direction
     * @return static
     */
    public function orderBy($column, $direction = 'asc')
    {
        $direction = (strtolower($direction) == 'asc') ? 'ASC' : 'DESC';

        $this->orders[] = compact('column', 'direction');

        return $this;
    }

    /**
     * Compute the SQL and parameters for constraints.
     *
     * @return string
     */
    protected function constraints()
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

        // Orders
        $items = array();

        foreach ($this->orders as $order) {
            $items[] = $this->wrap($order['column']) .' ' .$order['direction'];
        }

        if (! empty($items)) {
            $query .= ' ORDER BY ' .implode(', ', $items);
        }

        // Limits
        if (isset($this->limit)) {
            $query .= ' LIMIT ' .$this->limit;
        }

        if (isset($this->offset)) {
            $query .= ' OFFSET ' .$this->offset;
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
