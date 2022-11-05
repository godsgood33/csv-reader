<?php

namespace Godsgood33\CSVReader;

/**
 * Class to store data for maps
 * 
 * @property string $column
 *      Column that triggers this map
 * @property string $format
 *      The string that is the output of this string
 * @property array $fields
 *      The values to format
 *
 * @author Ryan Prather <godsgood33@gmail.com>
 */
class Map
{

    /**
     * The column that triggers this map
     *
     * @var string
     */
    public string $column;

    /**
     * The string format to concatenate the fields into
     *
     * @var string
     */
    public string $format;

    /**
     * Array of fields to retrieve
     *
     * @var array
     */
    public array $fields;

    /**
     * Constructor
     *
     * @param string $column
     * @param string $format
     * @param array $fields
     */
    public function __construct(string $column, string $format, array $fields)
    {
        $this->column = $column;
        $this->format = $format;
        $this->fields = $fields;
    }

    /**
     * Method to return a string
     *
     * @return string
     */
    public function trigger(array $values)
    {
        // get the string all this is going into
        $ret = $this->format;

        // loop starting at the end so that lower indexes don't replace larger ones %1 -> %10
        for ($x = count($values) - 1; $x >= 0; $x--) {
            // string replace the index with the value of the field
            $ret = str_replace("%".$x, $values[$x], $ret);
        }

        return $ret;
    }
}
