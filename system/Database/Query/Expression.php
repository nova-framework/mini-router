<?php

namespace System\Database\Query;


class Expression
{
    /**
     * The value of the database expression.
     *
     * @var string
     */
    protected $value;


    /**
     * Create a new database expression instance.
     *
     * @param  string  $value
     * @return void
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Get the string value of the database expression.
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Get the string value of the database expression.
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getValue();
    }
}
