<?php

namespace Godsgood33\CSVReader;

use Godsgood33\CSVReader\Exceptions\InvalidHeaderOrField;

/**
 * Class to store the header data
 *
 * @author Ryan Prather <godsgood33@gmail.com>
 */
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
     *
     * @throws InvalidHeaderOrField
     */
    public function __construct(array $header)
    {
        // check that there is valid header data
        if (empty($header) || !count($header)) {
            throw new InvalidHeaderOrField("Header array is empty");
        }

        // loop through each header field to strip out invalid characters and reverse the key/value pairs to get the index of each header field
        foreach ($header as $row => $h) {
            $h = preg_replace("/[^a-zA-Z0-9_]/", "", $h);
            if (empty($h)) {
                throw new InvalidHeaderOrField("Empty header");
            }
            $this->_header[$h] = $row;
        }

        // store the original header titles
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

    /**
     * Method to check that all required headers are present in the file
     *
     * @param array $req_headers
     *      An array of headers that are required
     *
     * @return bool
     *      Returns TRUE only if ALL required headers are present, otherwise FALSE
     */
    public function checkHeaders(array $req_headers): bool
    {
        foreach ($req_headers as $h) {
            if (!in_array($h, array_keys($this->_header))) {
                return false;
            }
        }

        return true;
    }
}
