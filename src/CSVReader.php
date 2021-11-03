<?php

namespace Godsgood33\CSVReader;

use Iterator;

use Godsgood33\CSVReader\Exceptions\InvalidHeaderOrField;
use Godsgood33\CSVReader\Exceptions\FileException;

/**
 * Class to read CSV files using the header row as the field title
 *
 * @property int $lineCount
 *
 * @author Ryan Prather <godsgood3@gmail.com>
 */
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
    private array $_options = [
        'delimiter' => ',',
        'enclosure' => '"',
        'header' => 0,
    ];

    /**
     * Header
     *
     * @var CSVHeader
     */
    private ?CSVHeader $_header = null;

    /**
     * Index of the row
     *
     * @var int
     */
    private int $_index = 0;

    /**
     * Array to store the data in the row
     *
     * @var array
     */
    private array $_data = [];

    /**
     * Constructor
     *
     * @param string $filename
     *      filename to read and parse
     * @param array $options
     *      optional array properties to assist in reading the file
     *
     * @property string $options['delimiter']
     *      string value representing the character used to delimit the CSV fields
     * @property string $options['enclosure']
     *      string value representing the character used to surround fields where the delimiting character
     *      is present in the field itself
     * @property int $options['header']
     *      zero-based integer representing the row the header is on
     * @property array $options['required_headers']
     *      an array of header fields that are required in the file
     * @property array $options['alias']
     *      array of aliases and the field header they link to
     *
     * @throws FileException
     * @throws InvalidHeaderOrField
     */
    public function __construct(string $filename, ?array $options = [])
    {
        if (substr($filename, 0, 4) == 'http') {
            $res = @get_headers($filename);
            if ($res[0] != 'HTTP/1.1 200 OK') {
                throw new FileException("Unable to access remote file");
            }
        } elseif (!file_exists($filename) ||!is_readable($filename)) {
            throw new FileException("File does not exist or is not readable");
        }

        // check to see if any options were passed in
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

            if (isset($options['required_headers']) && is_countable($options['required_headers'])) {
                $this->_options['required_headers'] = $options['required_headers'];
            }

            if (isset($options['alias']) && is_countable($options['alias'])) {
                $this->_options['alias'] = $options['alias'];
            }
        }

        // open the file and store the handler
        $this->_fh = fopen($filename, "r");

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

        $this->lineCount = self::getLineCount($filename) - ($row + 1);

        if (isset($this->_options['required_headers'])) {
            if (!$this->_header->checkHeaders($this->_options['required_headers'])) {
                throw new InvalidHeaderOrField("Missing Headers (".
                    implode(",", $this->_options['required_headers']).")");
            }
        }

        if (!$this->next()) {
            throw new FileException("There are no data rows in file $filename");
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
        if ($field == 'lineCount') {
            return $this->lineCount;
        }
        
        $alias = $this->hasAlias($field);

        if ($alias) {
            $field = $alias;
        }

        $header = $this->_header->__get($field);

        if ($header !== null) {
            return $this->_data[$header];
        }

        return $header;
    }

    /**
     * Method to check if there is an alias
     *
     * @param string $alias
     *
     * @return null|string
     */
    public function hasAlias(string $alias)
    {
        if (isset($this->_options['alias']) &&
            is_array($this->_options['alias']) &&
            isset($this->_options['alias'][$alias])) {
            return $this->_options['alias'][$alias];
        }

        return null;
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
        if (!is_resource($this->_fh)) {
            throw new FileException('File is no longer open');
        }
        $tmp = fgetcsv($this->_fh, 0, $this->_options['delimiter'], $this->_options['enclosure'], '\\');
        if (feof($this->_fh) && !is_array($tmp)) {
            return false;
        }

        $this->_data = $tmp;
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
        // start at the current row index and then subtract whatever the header row is supposed to be on
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
        if (is_resource($this->_fh)) {
            return fclose($this->_fh);
        }

        return false;
    }

    /**
     * Get line count
     *
     * @return int
     */
    public static function getLineCount(string $file): int
    {
        $count = 0;
        $h = fopen($file, "r");
        while (!feof($h)) {
            fgetcsv($h);
            $count++;
        }

        return $count;
    }
}
