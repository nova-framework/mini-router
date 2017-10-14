<?php

namespace System\Database;

use System\Database\Query\Builder as QueryBuilder;

use \PDO;


class Connection
{
    /**
     * The active PDO connection.
     *
     * @var PDO
     */
    protected $pdo;

    /**
     * The default fetch mode of the connection.
     *
     * @var int
     */
    protected $fetchMode = PDO::FETCH_OBJ;

    /**
     * The table prefix for the connection.
     *
     * @var string
     */
    protected $tablePrefix = '';

    /**
     * The table prefix for the connection.
     *
     * @var string
     */
    protected $wrapper = '`';


    /**
     * Create a new connection instance.
     *
     * @param  array  $config
     * @return void
     */
    public function __construct(array $config)
    {
        $this->pdo = $this->createConnection($config);

        $this->tablePrefix = $config['prefix'];

        $this->wrapper = $config['wrapper'];

        //
        $this->setFetchMode($this->fetchMode);
    }

    /**
     * Create a new PDO connection.
     *
     * @param  array   $config
     * @return PDO
     */
    protected function createConnection(array $config)
    {
        extract($config);

        $dsn = "$driver:host={$hostname};dbname={$database}";

        $options = array(
            PDO::ATTR_CASE               => PDO::CASE_NATURAL,
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_ORACLE_NULLS       => PDO::NULL_NATURAL,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
            PDO::ATTR_EMULATE_PREPARES   => false,

            // The MySQL init command.
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset}" .(! is_null($collation) ? " COLLATE '$collation'" : ''),
        );

        return new PDO($dsn, $username, $password, $options);
    }

    /**
     * Begin a Fluent Query against a database table.
     *
     * @param  string  $table
     * @return \System\Database\Query
     */
    public function table($table)
    {
        $query = new QueryBuilder($this);

        return $query->from($table);
    }

    /**
     * Run a select statement and return a single result.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return mixed
     */
    public function selectOne($query, $bindings = array())
    {
        $statement = $this->getPdo()->prepare($this->prepare($query));

        $statement->execute($bindings);

        return $statement->fetch($this->getFetchMode()) ?: null;
    }

    /**
     * Run a select statement against the database.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return array
     */
    public function select($query, array $bindings = array())
    {
        $statement = $this->getPdo()->prepare($this->prepare($query));

        $statement->execute($bindings);

        return $statement->fetchAll($this->getFetchMode());
    }

    /**
     * Run an insert statement against the database.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return bool
     */
    public function insert($query, array $bindings = array())
    {
        return $this->statement($query, $bindings);
    }

    /**
     * Run an update statement against the database.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return int
     */
    public function update($query, array $bindings = array())
    {
        return $this->affectingStatement($query, $bindings);
    }

    /**
     * Run a delete statement against the database.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return int
     */
    public function delete($query, array $bindings = array())
    {
        return $this->affectingStatement($query, $bindings);
    }

    /**
     * Execute an SQL statement and return the boolean result.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return bool
     */
    public function statement($query, array $bindings = array())
    {
        $statement = $this->getPdo()->prepare($this->prepare($query));

        return $statement->execute($bindings);
    }

    /**
     * Run an SQL statement and get the number of rows affected.
     *
     * @param  string  $query
     * @param  array   $bindings
     * @return int
     */
    public function affectingStatement($query, array $bindings = array())
    {
        $statement = $this->getPdo()->prepare($this->prepare($query));

        $statement->execute($bindings);

        return $statement->rowCount();
    }

    /**
     * Parse the table variables and add the table prefix.
     *
     * @param  string  $query
     * @return string
     */
    public function prepare($query)
    {
        return preg_replace_callback('#\{(.*?)\}#', function ($matches)
        {
            @list ($table, $field) = explode('.', $matches[1], 2);

            $result = $this->wrap($this->getTablePrefix() .$table);

            if (! is_null($field)) {
                $result .= '.' . $this->wrap($field);
            }

            return $result;

        }, $query);
    }

    /**
     * Wrap a single string in keyword identifiers.
     *
     * @param  string  $value
     * @return string
     */
    public function wrap($value)
    {
        if ($value === '*') {
            return $value;
        }

        $wrapper = $this->getWrapper();

        return $wrapper .$value .$wrapper;
    }

    /**
     * Get the PDO instance.
     *
     * @return PDO
     */
    public function getPdo()
    {
        return $this->pdo;
    }

    /**
     * Returns the wrapper string.
     *
     * @return string
     */
    public function getWrapper()
    {
        return $this->wrapper;
    }

    /**
     * Get the table prefix for the connection.
     *
     * @return string
     */
    public function getTablePrefix()
    {
        return $this->tablePrefix;
    }

    /**
     * Set the table prefix in use by the connection.
     *
     * @param  string  $prefix
     * @return void
     */
    public function setTablePrefix($prefix)
    {
        $this->tablePrefix = $prefix;
    }

    /**
     * Get the default fetch mode for the connection.
     *
     * @return int
     */
    public function getFetchMode()
    {
        return $this->fetchMode;
    }

    /**
     * Set the default fetch mode for the connection.
     *
     * @param  int  $fetchMode
     * @return int
     */
    public function setFetchMode($fetchMode)
    {
        $this->fetchMode = $fetchMode;

        //
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, $fetchMode);
    }
}
