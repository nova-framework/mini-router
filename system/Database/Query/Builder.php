<?php

namespace System\Database\Query;

use System\Database\Connection;

use Closure;
use Exception;


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
    public function __construct(Connection $connection, $table = null)
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
     * Set the table which the query is targeting.
     *
     * @param  string  $table
     * @return static
     */
    public function from($table)
    {
        $this->from = $table;

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
        if (is_null($this->columns)) {
            $this->columns = array('*');
        }

        $this->query = $this->compileSelect();

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

        return $this->connection->insert($this->query, $this->bindings);
    }

    /**
     * Execute an INSERT query and return the last inserted ID.
     *
     * @param array $data
     * @return array
     */
    public function insertGetId(array $data)
    {
        $this->insert($data);

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
        $column = $this->columnize($columns);

        if ($this->distinct && ($column !== '*')) {
            $column = 'DISTINCT ' .$column;
        }

        $this->query = 'SELECT ' .$function .'(' .$column .') AS aggregate FROM {' .$this->table .'}' .$this->constraints();

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

        if ($column instanceof Closure) {
            return $this->whereNested($column, $boolean);
        }

        if ($value instanceof Closure) {
            return $this->whereSub($column, $operator, $value, $boolean);
        }

        $type = 'Basic';

        $this->wheres[] = compact('type', 'column', 'operator', 'value', 'boolean');

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
     * Add a nested where statement to the query.
     *
     * @param  \Closure $callback
     * @param  string   $boolean
     * @return static
     */
    public function whereNested(Closure $callback, $boolean = 'and')
    {
        $query = new static($this->connection, $this->from);

        call_user_func($callback, $query);

        if (! empty($query->wheres)) {
            $type = 'Nested';

            $this->wheres[] = compact('type', 'query', 'boolean');
        }

        return $this;
    }

    /**
     * Add a full sub-select to the query.
     *
     * @param  string   $column
     * @param  string   $operator
     * @param  \Closure $callback
     * @param  string   $boolean
     * @return static
     */
    protected function whereSub($column, $operator, Closure $callback, $boolean)
    {
        $type = 'Sub';

        //
        $query = new static($this->connection);

        call_user_func($callback, $query);

        $this->wheres[] = compact('type', 'column', 'operator', 'query', 'boolean');

        return $this;
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
        $query = $this->compileWheres();

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
     * Compile the "WHERE" portions of the query.
     *
     * @return string
     */
    protected function compileWheres()
    {
        $items = array();

        foreach ($this->wheres as $where) {
            $items[] = strtoupper($where['boolean']) .' ' .$this->compileWhere($where);
        }

        if (! empty($items)) {
            return ' WHERE ' .preg_replace('/AND |OR /', '', implode(' ', $items), 1);
        }
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
        $column = $this->wrap($column);

        if ($type === 'Nested') {
            $sql = $query->compileWheres();

            $this->bindings = array_merge($this->bindings, $query->bindings);

            return '(' .substr($sql, 6) .')';
        } else if ($type === 'Sub') {
            $sql = $query->compileSelect();

            $this->bindings = array_merge($this->bindings, $query->bindings);

            return $column .' ' .$operator .' (' .$sql .')';
        }

        $not = ($operator !== '=') ? 'NOT ' : '';

        if (is_null($value)) {
            return $column .' IS ' .$not .'NULL';
        }

        // Multiple values given?
        else if (is_array($value)) {
            $this->bindings = array_merge($this->bindings, $value);

            $values = array_fill(0, count($value), '?');

            return $column .' ' .$not .'IN (' .implode(', ', $values) .')';
        }

        $this->bindings[] = $value;

        return $column .' ' .$operator .' ?';
    }

    /**
     * Compile a select query into SQL.
     *
     * @return string
     */
    public function compileSelect()
    {
        if (is_null($this->columns)) {
            $this->columns = array('*');
        }

        $select = $this->distinct ? 'SELECT DISTINCT' : 'SELECT';

        return  $select .' ' .$this->columnize($this->columns) .' FROM {' .$this->table .'}' .$this->constraints();
    }

    /**
     * Convert an array of column names into a delimited string.
     *
     * @param  array   $columns
     * @return string
     */
    public function columnize(array $columns)
    {
        return implode(', ', array_map(array($this, 'wrap'), $columns));
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
