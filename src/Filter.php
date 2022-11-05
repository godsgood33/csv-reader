<?php

namespace Godsgood33\CSVReader;

use BadFunctionCallException;

/**
 * Class to represent filter data
 */
class Filter
{
    /**
     * Column that triggers this filter
     * 
     * @var string
     */
    public string $column;

    /**
     * Variable to store callback
     * 
     * @var callable
     */
    private $callback;

    /**
     * Constructor
     * 
     * @param string $column
     * @param callable $callback
     */
    public function __construct(string $column, callable $callback)
    {
        if (!is_callable($callback)) {
            throw new BadFunctionCallException("Callback method for $column is not callable");
        }

        $this->column = $column;
        $this->callback = $callback;
    }

    /**
     * Method to trigger the callback
     * 
     * @param string $data
     * 
     * @return mixed
     */
    public function trigger(?string $data)
    {
        return call_user_func($this->callback, $data);
    }
}