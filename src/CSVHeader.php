<?php

namespace Godsgood33\CSVReader;

use Godsgood33\CSVReader\Exceptions\InvalidHeaderOrField;

class CSVHeader
{
    /**
     * Header titles after sanitizing
     *
     * @var array
     */
    private $_header = [];

    /**
     * Original header titles
     *
     * @var array
     */
    private $_titles = [];

    /**
     * Constructor method
     *
     * @param array $header
     */
    public function __construct($header)
    {
        if (is_string($header)) {
            return null;
        }

        if (empty($header) || !count($header)) {
            throw new InvalidHeaderOrField("Header array is empty");
        }

        foreach ($header as $row => $h) {
            $h = preg_replace("/[^a-zA-Z0-9_]/", "", $h);
            if(empty($h)) {
                throw new InvalidHeaderOrField("Empty header");
            }
            $this->_header[$h] = $row;
        }

        $this->_titles = $header;
    }

    /**
     * Magic method to convert the requested header to a field index
     *
     * @return null|integer
     */
    public function __get(string $field)
    {
        if (isset($this->_header[$field])) {
            return $this->_header[$field];
        }

        return null;
    }

    /**
     * Method to return all headers
     *
     * @return array
     */
    public function all()
    {
        return $this->_header;
    }

    /**
     * Method to get the original header titles
     *
     * @return array
     */
    public function getTitles()
    {
        return array_values($this->_titles);
    }
}
