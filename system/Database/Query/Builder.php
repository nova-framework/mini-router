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
     * The table prefix.
     *
     * @var string
     */
    protected $tablePrefix = '';

    /**
     * Plain data inputs
     */
    protected $columns  = null;

    protected $distinct = false;

    protected $data     = array();

    protected $params   = array();

    protected $wheres   = array();

    protected $limit    = null;

    protected $offset   = null;

    protected $orders   = array();

    // The last SQL query.
    protected $lastQuery = '';

    /**
     * All of the available clause operators.
     *
     * @var array
     */
    protected $operators = array('=', '<', '>', '<=', '>=', '<>', '!=', 'like', 'not like', 'between', 'ilike', '&', '|', '^', '<<', '>>');


    /**
     * Create a new Query Builder instance.
     *
     * @param  \System\Database\Connection $connection
     * @return void
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;

        $this->tablePrefix = $connection->getTablePrefix();
    }

    /**
     * Set the columns to be selected.
     *
     * @param  array  $columns
     * @return \System\Database\Query\Builder|static
     */
    public function select($columns = array('*'))
    {
        $this->columns = is_array($columns) ? $columns : func_get_args();

        return $this;
    }

    /**
     * Add a new select column to the query.
     *
     * @param  mixed  $column
     * @return \System\Database\Query\Builder|static
     */
    public function addSelect($column)
    {
        $column = is_array($column) ? $column : func_get_args();

        $this->columns = array_merge((array) $this->columns, $column);

        return $this;
    }

    /**
     * Force the query to only return distinct results.
     *
     * @return \System\Database\Query\Builder|static
     */
    public function distinct()
    {
        $this->distinct = true;

        return $this;
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
     * Execute a query for a single Record by ID.
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
     * @return arary
     */
    public function get($columns = array('*'))
    {
        if (is_null($this->columns)) {
            $this->columns = $columns;
        }

        $sql = $this->compileFor('select');

        return $this->connection->select($sql, $this->params);
    }

    /**
     * Register an insert query.
     *
     * @param array $data
     * @return array
     */
    public function insert(array $data)
    {
        $this->data = array_merge($this->data, $data);

        $sql = $this->compileFor('insert');

        return $this->connection->insert($sql, $data);
    }

    /**
     * Insert a new Record and get the value of the primary key.
     *
     * @param  array   $values
     * @return int
     */
    public function insertGetId(array $data)
    {
        $this->insert($data);

        return $this->connection->lastInsertId();
    }

    /**
     * Register an update query
     */
    public function update(array $data)
    {
        $this->data = array_merge($this->data, $data);

        $sql = $this->compileFor('update');

        return $this->connection->update($sql, array_merge($this->data, $this->params));
    }

    /**
     * Deletes a record.
     *
     * @param string $table
     * @param int $id
     * @return boolean
     */
    public function delete($id = null)
    {
        if (! is_null($id)) {
            $this->where('id', '=', $id);
        }

        $sql = $this->compileFor('delete');

        return $this->connection->delete($sql, $this->params);
    }

    /**
     * Add a "WHERE" clause to the query.
     *
     * @param array|callable $conditions
     * @param string $operator
     * @param mixed $value
     * @return \System\Database\Query\Builder|static
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        if (is_callable($column)) {
            return call_user_func($column, $this);
        }

        if (func_num_args() == 2) {
            list ($value, $operator) = array($operator, '=');
        }

        // Check the oeprator and value.
        else if ($this->invalidOperatorAndValue($operator, $value)) {
            throw new \InvalidArgumentException("A value must be provided.");
        }

        if (! in_array(strtolower($operator), $this->operators, true)) {
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
     * Determine if the given operator and value combination is legal.
     *
     * @param  string  $operator
     * @param  mixed  $value
     * @return bool
     */
    protected function invalidOperatorAndValue($operator, $value)
    {
        $isOperator = in_array($operator, $this->operators);

        return $isOperator && ($operator != '=') && is_null($value);
    }

    /**
     * Set the "OFFSET" value of the query.
     *
     * @param  int  $value
     * @return \System\Database\Query\Builder|static
     */
    public function offset($value)
    {
        $this->offset = max(0, $value);

        return $this;
    }

    /**
     * Set the "LIMIT" value of the query.
     *
     * @param int $limit
     * @return \System\Database\Query\Builder|static
     */
    public function limit($value)
    {
        if ($value > 0) {
            $this->limit = $value;
        }

        return $this;
    }

    /**
     * Add an "ORDER BY" clause to the query.
     *
     * @param array|string $fields
     * @param string $order
     * @return \System\Database\Query\Builder|static
     */
    public function orderBy($column, $direction = 'asc')
    {
        $direction = (strtolower($direction) == 'asc') ? 'ASC' : 'DESC';

        $this->orders[] = compact('column', 'direction');

        return $this;
    }

    /**
     * Get a new instance of the QueryBuilder.
     *
     * @return \System\Database\Query\Builder|static
     */
    public function newQuery()
    {
        return new static($this->connection);
    }

    /**
     * Build the SQL string.
     *
     * @return string
     */
    public function compileFor($type)
    {
        $query = '';

        $table = $this->wrapTable($this->table);

        if (is_null($this->columns)) {
            $this->columns = array('*');
        }

        // Select statements
        if ($type == 'select') {
            $query .= $this->distinct ? 'SELECT DISTINCT ' : 'SELECT ';

            foreach ($this->columns as $column) {
                $columns[] = $this->wrap($column);
            }

            $query .= implode(', ', $columns) ." FROM {$table}";
        }

        // Insert statements
        else if ($type == 'insert') {
            foreach ($this->data as $field => $value) {
                $fields[] = $this->wrap($field);

                $values[] = ":{$field}";
            }

            $query = "INSERT INTO {$table} (" .implode(', ', $fields) .") VALUES (" .implode(', ', $values) .")";
        }

        // Update statements
        else if ($type == 'update') {
            foreach ($this->data as $field => $value) {
                $data[] = $this->wrap($field) ." = :{$field}";
            }

            $query .= "UPDATE {$table} SET " .implode(', ', $data);
        }

        // Delete statements
        else if ($type == 'delete') {
            $query = "DELETE FROM {$table}";
        }

        // Wheres
        if (! empty($this->wheres)) {
            $wheres = array();

            foreach ($this->wheres as $where) {
                $column = $where['column'];

                $value = $where['value'];

                if ($value instanceof Expression) {
                    $param = $value->getValue();
                } else {
                    $param = ":{$column}";

                    $this->params[$param] = $value;
                }

                $wheres[] = strtoupper($where['boolean']) .' ' .$this->wrap($column) .' ' .$where['operator'] .' ' .$param;
            }

            if (count($wheres) > 0) {
                $query .= ' WHERE ' .preg_replace('/AND |OR /', '', implode(' ', $wheres), 1);
            }
        }

        // Orders
        if (! empty($this->orders)) {
            $orders = array();

            foreach ($this->orders as $order) {
                $orders[] = $this->wrap($order['column']) .' ' .$order['direction'];
            }

            $query .= ' ORDER BY ' .implode(', ', $orders);
        }

        // Limits
        if (isset($this->limit)) {
            $query .= ' LIMIT ' .intval($this->limit);
        }

        if (isset($this->offset)) {
            $query .= ' OFFSET ' .intval($this->offset);
        }

        return $this->lastQuery = $query;
    }

    /**
     * Wrap a value in keyword identifiers.
     *
     * @param  string  $value
     * @return string
     */
    public function wrap($value)
    {
        if ($value instanceof Expression) {
            return $value->getValue();
        }

        if (strpos(strtolower($value), ' as ') !== false) {
            list ($field, $alias) = explode(' ', $value);

            return $this->wrap($field) .' AS ' .$this->wrapValue($alias);
        }

        $wrapped = array();

        $segments = explode('.', $value);

        foreach ($segments as $key => $segment) {
            if (($key == 0) && (count($segments) > 1)) {
                $wrapped[] = $this->wrapTable($segment);
            } else {
                $wrapped[] = $this->wrapValue($segment);
            }
        }

        return implode('.', $wrapped);
    }

    /**
     * Wrap a table in keyword identifiers.
     *
     * @param  string  $table
     * @return string
     */
    public function wrapTable($table)
    {
        if ($table instanceof Expression) {
            return $table->getValue();
        }

        return $this->wrap($this->tablePrefix .$table);
    }

    /**
     * Wrap a single string in keyword identifiers.
     *
     * @param  string  $value
     * @return string
     */
    protected function wrapValue($value)
    {
        if ($value === '*') {
            return $value;
        }

        return '`'.str_replace('`', '``', $value).'`';
    }

    /**
     * Get the grammar's table prefix.
     *
     * @return string
     */
    public function getTablePrefix()
    {
        return $this->tablePrefix;
    }

    /**
     * Get the format for database stored dates.
     *
     * @return string
     */
    public function getDateFormat()
    {
        return 'Y-m-d H:i:s';
    }

    /**
     * Get the last SQL query.
     *
     * @return string
     */
    public function getLastQuery()
    {
        return $this->lastQuery;
    }
}
