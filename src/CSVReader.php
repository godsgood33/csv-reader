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
    private array $_options = [];

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
     * Variable to store the filename that is being parsed
     *
     * @var string
     */
    private string $filename;

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
        $this->_options = [
            'delimiter' => ',',
            'enclosure' => '"',
            'header' => 0,
            'required_headers' => [],
            'alias' => [],
        ];
        $this->checkFile($filename);
        $this->checkOptions($options);
        $this->filename = $filename;

        // open the file and store the handler
        $this->_fh = fopen($this->filename, "r");

        $this->setHeader();

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
        if (isset($this->_options['alias'][$alias])) {
            return $this->_options['alias'][$alias];
        }

        return null;
    }

    /**
     * Method to get the aliases
     *
     * @return array|null
     */
    public function getAliases()
    {
        if (count($this->_options['alias'])) {
            return $this->_options['alias'];
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
     * Method to check the file
     *
     * @param string $filename
     *
     * @throws FileException
     */
    private function checkFile(string $filename)
    {
        if (substr($filename, 0, 4) == 'http') {
            $res = @get_headers($filename);
            if (is_array($res) && $res[0] != 'HTTP/1.1 200 OK') {
                throw new FileException("Unable to access remote file");
            } elseif (!$res) {
                throw new FileException('Unable to access remote file');
            }
        } elseif (!file_exists($filename) || !is_readable($filename)) {
            throw new FileException("File does not exist or is not readable");
        }
    }

    /**
     * Method to check the options
     *
     * @param array $options
     */
    private function checkOptions(?array $options)
    {
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
    }

    /**
     * Method to set the headers
     *
     * @throws InvalidHeaderOrField
     */
    private function setHeader()
    {
        $row = 0;
        $this->_index = 0;

        // loop until you get to the header row
        while ($data = fgetcsv($this->_fh, 0, $this->_options['delimiter'], $this->_options['enclosure'])) {
            if ($row == $this->_options['header']) {
                $this->_header = new CSVHeader($data, $this->_options['required_headers']);
                break;
            } else {
                $row++;
            }
        }

        $this->lineCount = self::getLineCount($this->filename) - ($row + 1);
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
     */
    public function next(): void
    {
        if (!is_resource($this->_fh)) {
            throw new FileException('File is no longer open');
        }
        $tmp = fgetcsv($this->_fh, 0, $this->_options['delimiter'], $this->_options['enclosure'], '\\');
        if (feof($this->_fh) && !is_array($tmp)) {
            throw new FileException('End of file', 100);
        }

        $this->_data = $tmp;
        $this->_index++;
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
    public function rewind(): void
    {
        fseek($this->_fh, 0);
        $this->setHeader();

        $this->next();
    }

    /**
     * Not sure what this should return
     *
     * @return bool
     */
    public function valid(): bool
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
