<?php

namespace System\Database\Query;

use System\Database\Connection;

use Exception;


class ExtendedBuilder
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
     * The query conditions.
     */
    protected $columns;

    protected $distinct = false;

    protected $bindings = array();
    protected $wheres   = array();
    protected $orders   = array();

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

        $this->query = 'SELECT ' .($this->distinct ? 'DISTINCT ' : '') .$columns .' FROM {' .$this->table .'}' .$this->conditions();

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

            $items[] = '?';

            $this->bindings[] = $value;
        }

        $this->query = 'INSERT INTO {' .$this->table .'} (' .implode(', ', $fields) .') VALUES (' .implode(', ', $items) .')';

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
     * Determine if any rows exist for the current query.
     *
     * @return bool
     */
    public function exists()
    {
        return $this->count() > 0;
    }

    /**
     * Retrieve the "COUNT" result of the query.
     *
     * @param  string  $column
     * @return int
     */
    public function count($column = '*')
    {
        return (int) $this->aggregate('count', array($column));
    }

    /**
     * Execute an aggregate function on the database.
     *
     * @param  string  $function
     * @param  array   $columns
     * @return mixed
     */
    public function aggregate($function, $columns = array('*'))
    {
        $column = implode(', ', array_map(array($this, 'wrap'), $columns));

        if ($this->distinct && ($column !== '*')) {
            $column = 'DISTINCT ' .$column;
        }

        $this->query = 'SELECT ' .$function .'(' .$column .') AS aggregate FROM {' .$this->table .'}' .$this->conditions();

        $result = $this->connection->selectOne($this->query, $this->bindings);

        // Reset the bindings.
        $this->bindings = array();

        if (! is_null($result)) {
            $result = (array) $result;

            return $result['aggregate'];
        }
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
     * Compute the SQL and parameters for conditions.
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

        if (is_null($value)) {
            return $column .' IS ' .$not .'NULL';
        }

        // Where with multiple values?
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

    /**
     * Magic call.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed|null
     * @throws \Exception
     */
    public function __call($method, $parameters)
    {
        if (in_array($method, array('min', 'max', 'sum', 'avg'))) {
            $column = reset($parameters);

            return $this->aggregate($method, array($column));
        }

        throw new Exception("Method [$method] not found.");
    }
}
