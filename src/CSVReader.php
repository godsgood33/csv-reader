<?php

namespace Godsgood33\CSVReader;

use Iterator;

use Godsgood33\CSVReader\Exceptions\InvalidHeaderOrField;
use Godsgood33\CSVReader\Exceptions\FileException;

/**
 * Class to read CSV files using the header row as the field title
 *
 * @property int $lineCount
 * @property string $delimiter
 *      string value representing the character used to delimit the CSV fields
 * @property string $enclosure
 *      string value representing the character used to surround fields where the delimiting character
 *      is present in the field itself
 * @property int $headerIndex
 *      zero-based integer representing the row the header is on
 * @property string[] $required_headers
 *      an array of header fields that are required in the file
 * @property string[] $alias
 *      array of aliases and the field header they link to
 *
 * @author Ryan Prather <godsgood3@gmail.com>
 */
class CSVReader implements Iterator
{
    /**
     * File handler for the CSV file
     *
     * @var resource|false
     */
    private $fh;

    /**
     * Options for parsing the file
     *
     * @var array<string, array|int|string>
     */
    private array $options;

    /**
     * Header
     *
     * @var CSVHeader
     */
    private CSVHeader $header;

    /**
     * Index of the row
     *
     * @var int
     */
    private int $index;

    /**
     * Array to store the data in the row
     *
     * @var array<int, string>
     */
    private array $data;

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
     * @param array{
     *      'delimiter'?: string,
     *      'enclosure'?: string,
     *      'headerIndex'?: int,
     *      'required_headers'?: array<int, string>,
     *      'alias'?: array<int, string>
     * } $options
     *      optional array properties to assist in reading the file
     *
     * @throws FileException
     * @throws InvalidHeaderOrField
     */
    public function __construct(string $filename, ?array $options = [])
    {
        $this->options = [
            'delimiter' => ',',
            'enclosure' => '"',
            'headerIndex' => 0,
            'required_headers' => [],
            'alias' => [],
        ];
        $this->checkFile($filename);
        $this->checkOptions($options);
        $this->filename = $filename;

        // open the file and store the handler
        $this->fh = fopen($this->filename, "r");

        $this->setHeader();

        $this->next();
    }

    /**
     * Magic getter method to return the value at the given header index
     *
     * @param string $field
     *
     * @return null|string|int|array
     */
    public function __get(string $field)
    {
        if ($this->isOption($field)) {
            return $this->getOption($field);
        }
        
        $alias = $this->hasAlias($field);

        if ($alias) {
            $field = $alias;
        }

        $header = $this->header->{$field};

        if ($header !== null) {
            return $this->data[$header];
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
        if (isset($this->alias[$alias])) {
            return $this->alias[$alias];
        }

        return null;
    }

    /**
     * Method to determine if we want an option
     *
     * @param string $field
     *
     * @return bool
     */
    private function isOption(string $field): bool
    {
        if (in_array($field, [
            'delimiter', 'enclosure', 'headerIndex', 'required_headers', 'alias'
        ])) {
            return true;
        }

        return false;
    }

    /**
     * Method to retrieve an option
     *
     * @param string $field
     *
     * @return array|string|int
     */
    private function getOption(string $field)
    {
        return $this->options[$field];
    }

    /**
     * Method to get the header titles
     *
     * @return string[]|null
     */
    public function getHeaderTitles()
    {
        if (is_a($this->header, 'Godsgood33\CSVReader\CSVHeader')) {
            return $this->header->getTitles();
        }
        return null;
    }

    /**
     * Method to check the file
     *
     * @param string $filename
     *
     * @return void
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
     * @param array{
     *      'delimiter'?: string,
     *      'enclosure'?: string,
     *      'header'?: int,
     *      'required_headers'?: array<int, string>,
     *      'alias'?: array<int, string>
     * } $options
     *
     * @return void
     */
    private function checkOptions(?array $options)
    {
        // check to see if any options were passed in
        if (is_array($options) && count($options)) {
            if (isset($options['delimiter'])) {
                $this->delimiter = $options['delimiter'];
            }

            if (isset($options['enclosure'])) {
                $this->enclosure = $options['enclosure'];
            }

            if (isset($options['header']) && is_int($options['header'])) {
                $this->header = $options['header'];
            }

            if (isset($options['required_headers']) && is_countable($options['required_headers'])) {
                $this->required_headers = $options['required_headers'];
            }

            if (isset($options['alias']) && is_countable($options['alias'])) {
                $this->alias = $options['alias'];
            }
        }
    }

    /**
     * Method to set the headers
     *
     * @return void
     *
     * @throws InvalidHeaderOrField
     */
    private function setHeader(): void
    {
        $row = 0;
        $this->index = 0;

        if (!is_resource($this->fh)) {
            throw new FileException('Invalid file');
        }

        // loop until you get to the header row
        while ($data = fgetcsv($this->fh, 0, $this->delimiter, $this->enclosure)) {
            if ($row == $this->header) {
                $this->header = new CSVHeader($data, $this->required_headers);
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
     * @return array<string, string>
     */
    public function current()
    {
        $ret = [];

        if (!is_a($this->header, 'Godsgood33\CSVReader\CSVHeader')) {
            return $ret;
        }

        foreach ($this->header->all() as $h => $idx) {
            $ret[$h] = $this->data[$idx];
        }

        return $ret;
    }

    /**
     * Read the next row
     *
     * @return void
     */
    public function next(): void
    {
        if (!is_resource($this->fh)) {
            throw new FileException('File is no longer open');
        }
        $tmp = fgetcsv($this->fh, 0, $this->delimiter, $this->enclosure, '\\');
        if (!$tmp) {
            throw new FileException('End of file');
        }
        if (feof($this->fh)) {
            throw new FileException('End of file', 100);
        }

        $this->data = $tmp;
        $this->index++;
    }

    /**
     * Return the current row number (excluding the header row)
     *
     * @return int
     */
    public function key()
    {
        // start at the current row index and then subtract whatever the header row is supposed to be on
        return $this->index - $this->headerIndex;
    }

    /**
     * Start the file over
     *
     * @return void
     */
    public function rewind(): void
    {
        if (is_resource($this->fh)) {
            fseek($this->fh, 0);
            $this->setHeader();

            $this->next();
        }
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
        if (is_resource($this->fh)) {
            return fclose($this->fh);
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
        if (is_resource($h)) {
            while (!feof($h)) {
                fgetcsv($h);
                $count++;
            }
        }

        return $count;
    }
}
