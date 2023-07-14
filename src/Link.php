<?php

namespace Godsgood33\CSVReader;

use BadMethodCallException;
use stdClass;

/**
 * Class to store Link data
 *
 * @property string $column
 *   Trigger column that triggers this link
 * @property callable $callback
 *    Method/function to call
 * @property array $field
 *    Fields to pull together
 */
class Link
{
    /**
     * Column that triggers this link
     *
     * @var string
     */
    public string $column;

    /**
     * Variable to store the callback in
     *
     * @var callable
     */
    public $callback;

    /**
     * Variable to store the fields that need to be combined
     *
     * @var array
     */
    public array $fields;

    /**
     * Constructor
     *
     * @param string $column
     * @param array $fields
     * @param callable $callback
     */
    public function __construct(string $column, array $fields, ?callable $callback = null)
    {
        $this->column = $column;
        $this->callback = $callback;
        $this->fields = $fields;
    }

    /**
     * Method to trigger a callback and return the result of that method
     *
     * @param stdClass $values
     *
     * @return mixed
     */
    public function trigger(stdClass $values)
    {
        if (!is_callable($this->callback)) {
            return false;
        }
        return call_user_func($this->callback, $values);
    }
}
