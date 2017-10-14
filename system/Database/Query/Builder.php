<?php

namespace System\Database\Query;

use System\Database\Connection;


class Builder
{
    /**
     * @var  \System\Database\Connection  The Connection instance.
     */
    protected $connection;

    /**
     * @var  string  The table which the query is targeting.
     */
    protected $table;

    /**
     * The query conditions.
     */
    protected $params = array();

    protected $wheres = array();
    protected $orders = array();

    protected $distinct = false;

    protected $offset;
    protected $limit;


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
        foreach ($columns as $column) {
            $sql[] = $this->wrap($column);
        }

        $query = 'SELECT ' .($this->distinct ? 'DISTINCT ' : '') .implode(', ', $sql) .' FROM {' .$this->table .'}' .$this->conditions();

        return $this->connection->select($query, $this->params);
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

        $query = 'INSERT INTO {' .$this->table .'} (' .implode(', ', $fields) .') VALUES (' .implode(', ', $values) .')';

        return $this->connection->insert($query, $data);
    }

    /**
     * Insert a new record and get the last insert ID.
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
     * Execute an update query.
     *
     * @param  array   $data
     * @return boolean
     */
    public function update(array $data)
    {
        foreach ($data as $field => $value) {
            $sql[] = $this->wrap($field) ." = :{$field}";
        }

        $query = 'UPDATE {' .$this->table .'} SET ' .implode(', ', $sql) .$this->conditions();

        return $this->connection->update($query, array_merge($data, $this->params));
    }

    /**
     * Execute a delete query.
     *
     * @return array
     */
    public function delete()
    {
        $query = 'DELETE FROM {' .$this->table .'}' .$this->conditions();

        return $this->connection->delete($query, $this->params);
    }

    /**
     * Add a "WHERE" clause to the query.
     *
     * @param string $field
     * @param string|null $operator
     * @param mixed|null $value
     * @return static
     */
    public function where($column, $operator = null, $value = null, $boolean = 'AND')
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
        return $this->where($column, $operator, $value, 'OR');
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
     * Build the SQL string and parameters for conditions.
     *
     * @return string
     */
    protected function conditions()
    {
        $query = '';

        // Wheres.
        $sql = array();

        foreach ($this->wheres as $where) {
            $param = ':' .$where['column'];

            $sql[] = $where['boolean'] .' ' .$this->wrap($where['column']) .' ' .$where['operator'] .' ' .$param;

            $this->params[$param] = $where['value'];
        }

        if (! empty($sql)) {
            $query .= ' WHERE ' .preg_replace('/AND |OR /', '', implode(' ', $sql), 1);
        }

        // Orders
        $sql = array();

        foreach ($this->orders as $order) {
            $sql[] = $this->wrap($order['column']) .' ' .$order['direction'];
        }

        if (! empty($sql)) {
            $query .= ' ORDER BY ' .implode(', ', $sql);
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
