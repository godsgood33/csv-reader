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
     * @var CSVHeader
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
     * @param string $filename filename to read and parse
     * @param array $options optional array properties to assist in reading the file
     *
     * @property $options['delimiter'] string value representing the character used to delimit the CSV fields
     * @property $options['enclosure'] string value representing the character used to surround fields where the delimiting character is present in the field itself
     * @property $options['header'] zero-based integer representing the row the header is on
     *
     * @throws Exception
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

            if (isset($options['header']) && preg_match("/^[0-9]+$/", $options['header'])) {
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
        $this->_index = 0;

        // loop until you get to the header row
        while ($data = fgetcsv($this->_fh, 0, $this->_options['delimiter'], $this->_options['enclosure'])) {
            if ($row == $this->_options['header']) {
                $this->_header = new CSVHeader($data);
                break;
            } else {
                $row++;
            }
        }

        if (!$this->next()) {
            throw new Exception("There are no data rows in file $filename");
        }
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
     * Method to get the header titles
     *
     * @return array
     */
    public function getHeaderTitles(): array
    {
        return $this->_header->getTitles();
    }

    /**
     * Method to return an associative array where the with the header => field pairs
     *
     * @return array
     */
    public function current()
    {
        $ret = [];

        foreach ($this->_header->all() as $h => $row) {
            $ret[$h] = $this->_data[$row];
        }

        return $ret;
    }

    /**
     * Read the next row
     *
     * @return bool Returns FALSE if you are at the end of the file, otherwise returns TRUE
     */
    public function next()
    {
        if (feof($this->_fh)) {
            return false;
        }

        $this->_data = fgetcsv($this->_fh, 0, $this->_options['delimiter'], $this->_options['enclosure']);
        $this->_index++;

        return true;
    }

    /**
     * Return the current row number (excluding the header row)
     *
     * @return int
     */
    public function key()
    {
        return $this->_index - $this->_options['header'];
    }

    /**
     * Start the file over
     */
    public function rewind()
    {
        fseek($this->_fh, 0);

        $row = 0;
        $this->_index = 0;

        // loop until you get to the header row
        while ($data = fgetcsv($this->_fh, 0, $this->_options['delimiter'], $this->_options['enclosure'])) {
            if ($row == $this->_options['header']) {
                $this->_header = new CSVHeader($data);
                break;
            } else {
                $row++;
            }
        }

        $this->next();
    }

    /**
     * Not sure what this should return
     *
     * @return bool
     */
    public function valid()
    {
        return true;
    }

    /**
     * Close the file
     *
     * @return bool
     */
    public function close()
    {
        return fclose($this->_fh);
    }
}
