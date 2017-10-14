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
     * @param string|null $operator
     * @param mixed|null $value
     * @return \System\Database\Query|static
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
        $connection = $this->getConnection();

        foreach ($data as $field => $value) {
            $fields[] = $connection->wrap($field);

            $values[] = ":{$field}";
        }

        $query = '(' .implode(', ', $fields) .') VALUES (' .implode(', ', $values) .')';

        $connection->insert("INSERT INTO {" .$this->getTable() ."} $query", $data);

        return $connection->lastInsertId();
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

        foreach ($data as $field => $value) {
            $field = trim($field, ':');

            $sql[] = $connection->wrap($field) ." = :{$field}";
        }

        $query = ' ' .implode(', ', $sql) .' ';

        $where = $this->compileWheres();

        return $connection->update(
            "UPDATE {" .$this->getTable() ."} SET $query WHERE $where", array_merge($data, $this->params)
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

        $where = $this->compileWheres();

        return $connection->delete("DELETE FROM {" .$this->getTable() ."} WHERE $where", $this->params);
    }

    /**
     * Build the SQL string for WHEREs and populate the parameters list.
     *
     * @return string
     */
    protected function compileWheres()
    {
        $connection = $this->getConnection();

        //
        $wheres = array();

        foreach ($this->wheres as $where) {
            $param = ':' .$where['column'];

            $wheres[] = strtoupper($where['boolean']) .' ' .$connection->wrap($where['column']) .' ' .$where['operator'] .' ' .$param;

            $this->params[$param] = $where['value'];
        }

        return preg_replace('/AND |OR /', '', implode(' ', $wheres), 1);
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
