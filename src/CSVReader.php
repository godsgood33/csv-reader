<?php

namespace Godsgood33\CSVReader;

use Exception;
use Iterator;

class CSVReader implements Iterator
{
    /**
     * File handler for the CSV file
     *
     * @var resource
     */
    private $_fh = null;

    /**
     * Options for parsing the file
     *
     * @var array
     */
    private $_options = [
        'delimiter' => ',',
        'enclosure' => '"',
        'header' => 0
    ];

    /**
     * Header
     *
     * @var CVSHeader
     */
    private $_header = null;

    /**
     * Index of the row
     *
     * @var integer
     */
    private $_index = 0;

    /**
     * Array to store the data in the row
     *
     * @var array
     */
    private $_data = [];

    /**
     * Constructor
     *
     * @param string $filename
     * @param array $options
     */
    public function __construct(string $filename, ?array $options = [])
    {
        if (!file_exists($filename) || !is_readable($filename)) {
            throw new Exception("File does not exist or is not readable");
        }

        if (is_array($options) && count($options)) {
            if (isset($options['delimiter'])) {
                $this->_options['delimiter'] = $options['delimiter'];
            }

            if (isset($options['enclosure'])) {
                $this->_options['enclosure'] = $options['enclosure'];
            }

            if (isset($options['header'])) {
                $this->_options['header'] = $options['header'];
            }
        }

        // open the file and store the handler
        $this->_fh = fopen($filename, "r");

        // check that the handler contains a reference to the file
        if (!is_resource($this->_fh)) {
            throw new Exception("Was not able to open file");
        }

        $row = 0;

        // loop until you get to the header row
        while ($data = fgetcsv($this->_fh, 0, $this->_options['delimiter'], $this->_options['enclosure'])) {
            if ($row == $this->_options['header']) {
                $this->_header = new CSVHeader($data);
                $this->_index = $row + 1;
                break;
            } else {
                $row++;
            }
        }

        $this->next();
    }

    /**
     * Magic getter method to return the value at the given header index
     *
     * @param string $field
     *
     * @return null|string
     */
    public function __get(string $field)
    {
        return $this->_data[$this->_header->{$field}] ?? null;
    }

    /**
     * Method to return all values
     *
     * @return array
     */
    public function all()
    {
        $headers = $this->_header->all();
        $ret = [];

        foreach ($this->_header->all() as $h => $row) {
            $ret[$h] = $this->_data[$row];
        }

        return $ret;
    }

    public function current()
    {
        if ($this->_index > $this->_options['header']) {
            return $this->_index;
        }

        return null;
    }

    public function next()
    {
        $this->_data = fgetcsv($this->_fh, 0, $this->_options['delimiter'], $this->_options['enclosure']);
        $this->_index++;
    }

    public function key()
    {
        return $this->_index;
    }

    public function rewind()
    {
        fseek($this->_fh, 0);
        $this->_index = 0;
    }

    public function valid()
    {
    }
}
