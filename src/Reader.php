<?php

namespace Godsgood33\CSVReader;

use Iterator;
use Godsgood33\CSVReader\Exceptions\InvalidHeaderOrField;
use Godsgood33\CSVReader\Exceptions\FileException;
use stdClass;

/**
 * Class to read CSV files using the header row as the field title
 *
 * @property int $lineCount
 * @property string $delimiter
 *      string value representing the character used to delimit the CSV fields
 * @property string $enclosure
 *      string value representing the character used to surround fields where the delimiting character
 *      is present in the field itself
 * @property string $escape
 *      string character representing what character is used to escape the delimiter or enclosure characters
 * @property int $headerIndex
 *      zero-based integer representing the row the header is on
 * @property string[] $required_headers
 *      an array of header fields that are required in the file
 * @property string[] $alias
 *      array of aliases and the field header they link to
 * @property int $headerCase
 *      Change what case the properties should be available in
 *          Header::TO_LOWER - converts the properties to all lower case
 *          Header::TO_CAMEL_CASE - coverts the properties to camel case
 *
 * @author Ryan Prather <godsgood3@gmail.com>
 */
class Reader implements Iterator
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
     * @var array<string, array|int|string|bool>
     */
    private array $options;

    /**
     * Header
     *
     * @var Header
     */
    private Header $header;

    /**
     * Index of the row
     *
     * @var int
     */
    private int $index;

    /**
     * Number of lines
     *
     * @var int
     */
    private int $lineCount;

    /**
     * Array to store the data in the row
     *
     * @var array<int, string|array|bool>
     */
    private array $data;

    /**
     * Array to store mapping
     *
     * @var array<string, Map>
     */
    private array $map;

    /**
     * Array to store filters
     *
     * @var array<string, Filter>
     */
    private array $filter;

    /**
     * Array to store links
     *
     * @var array<string, Link>
     */
    private array $links;

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
     *      'escape'?: string,
     *      'headerIndex'?: int,
     *      'headerCase'?: int,
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
            'escape' => '\\',
            'headerIndex' => 0,
            'required_headers' => [],
            'alias' => [],
            'headerCase' => false,
        ];
        $this->checkFile($filename);
        $this->checkOptions($options);
        $this->filename = $filename;

        // open the file and store the handler
        $this->fh = fopen($this->filename, "r");

        if (is_resource($this->fh)) {
            $this->setHeader();
        }

        $this->next();
    }

    /**
     * Magic getter method to return the value at the given header index
     * **NOTE: Will also retrieve option values**
     *
     * @param string $field
     *
     * @return mixed
     */
    public function __get(string $field)
    {
        if ($this->isOption($field)) {
            return $this->getOption($field);
        }

        if ($this->hasAlias($field)) {
            $field = $this->alias[$field];
        }

        if ($this->isMap($field)) {
            return $this->triggerMap($field);
        }

        if ($this->isLink($field)) {
            return $this->triggerLink($field);
        }

        $val = null;
        $headerIndex = $this->header->{$field};
        if ($headerIndex !== null) {
            $val = $this->data[$headerIndex];
        }

        if ($this->hasFilter($field)) {
            return $this->triggerFilter($field, $val);
        }

        return $val;
    }

    /**
     * Check to see if a field is linked to a map
     *
     * @param string $column
     *
     * @return bool
     */
    private function isMap(string $column): bool
    {
        return isset($this->map[$column]);
    }

    /**
     * Method to add a map to the array
     *
     * @param Map $map
     *
     * @return bool
     */
    public function addMap(Map $map): bool
    {
        if (isset($this->map[$map->column])) {
            throw new InvalidHeaderOrField('Map column already exists');
        }

        foreach ($map->fields as $f) {
            if (is_null($this->header->{$f})) {
                throw new InvalidHeaderOrField("Header for Map not found ($f)");
            }
        }

        $this->map[$map->column] = $map;
        return true;
    }

    /**
     * Method to retrieve the map data and return the formatted string
     *
     * @param string $column
     *
     * @return string
     */
    private function triggerMap(string $column): string
    {
        $vals = [];
        $map = $this->map[$column];
        foreach ($map->fields as $f) {
            $vals[] = $this->{$f};
        }

        return $map->trigger($vals);
    }

    /**
     * Method to check if a field has a filter
     *
     * @param string $field
     *
     * @return bool
     */
    private function hasFilter(string $field): bool
    {
        return isset($this->filter[$field]);
    }

    /**
     * Method to add a filter to the object
     *
     * @param Filter $filter
     *
     * @return bool
     */
    public function addFilter(Filter $filter): bool
    {
        if (!$this->header->fieldExists($filter->column) && !isset($this->alias[$filter->column])) {
            return false;
        }

        $this->filter[$filter->column] = $filter;
        return true;
    }

    /**
     * Method to remove a filter
     *
     * @param string $filter
     *
     * @return bool
     */
    public function removeFilter(string $filter): bool
    {
        if (isset($this->filter[$filter])) {
            unset($this->filter[$filter]);
            return true;
        }

        return false;
    }

    /**
     * Method to call a filter
     *
     * @param string $field
     * @param string $val
     *
     * @return mixed
     */
    private function triggerFilter(string $field, ?string $val)
    {
        if (!$this->hasFilter($field)) {
            throw new InvalidHeaderOrField("Filter for field {$field} not found");
        }

        return $this->filter[$field]->trigger($val);
    }

    /**
     * Method to check if a link is present in the system
     *
     * @param string $column
     *
     * @return bool
     */
    public function isLink(string $column): bool
    {
        return isset($this->links[$column]);
    }

    /**
     * Method to add a link to the system
     *
     * @param Link $link
     *
     * @return bool
     */
    public function addLink(Link $link): bool
    {
        if ($this->isLink($link->column)) {
            throw new InvalidHeaderOrField("Link for column {$link->column} already present");
        }

        $this->links[$link->column] = $link;

        return true;
    }

    /**
     * Method to remove a link
     *
     * @param string $column
     *
     * @return bool
     */
    public function removeLink(string $column): bool
    {
        if (isset($this->links[$column])) {
            unset($this->links[$column]);
            return true;
        }
        return true;
    }

    /**
     * Trigger a link
     *
     * @param string $column
     *
     * @return mixed
     */
    public function triggerLink(string $column)
    {
        $ret = new stdClass();
        $link = $this->links[$column];

        foreach ($link->fields as $f) {
            $val = $this->{$f};

            $ret->{$f} = $val;
        }

        if (is_callable($link->callback)) {
            return $link->trigger($ret);
        }
        return $ret;
    }

    /**
     * Method to add an alias
     *
     * @param string $alias
     * @param string $field
     *
     * @return bool
     */
    public function addAlias(string $alias, string $field): bool
    {
        if (isset($this->alias[$alias])) {
            return false;
        }

        if (!$this->header->fieldExists($field)) {
            return false;
        }

        $this->alias[$alias] = $field;
        return true;
    }

    /**
     * Method to remove an alias
     *
     * @param string $alias
     *
     * @return bool
     */
    public function removeAlias(string $alias): bool
    {
        if (!isset($this->alias[$alias])) {
            return false;
        }

        unset($this->alias[$alias]);
        return true;
    }

    /**
     * Method to check if there is an alias
     *
     * @param string $alias
     *
     * @return bool
     */
    private function hasAlias(string $alias): bool
    {
        return isset($this->alias[$alias]);
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
            'delimiter', 'enclosure', 'escape', 'headerIndex',
            'required_headers', 'alias', 'headerCase', 'lineCount'
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
     * @return array|string|int|bool
     */
    private function getOption(string $field)
    {
        if (isset($this->{$field})) {
            return $this->{$field};
        }
        return $this->options[$field];
    }

    /**
     * Method to get the header titles
     *
     * @return string[]
     */
    public function getHeaderTitles()
    {
        if (is_a($this->header, 'Godsgood33\CSVReader\Header')) {
            return $this->header->getTitles();
        }
        return [];
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
            $res = @get_headers($filename, true);
            if (is_array($res) && $res[0] != 'HTTP/1.1 200 OK') {
                throw new FileException("Unable to access remote file");
            }
            return;
        }

        if (!file_exists($filename) || !is_readable($filename)) {
            throw new FileException("File does not exist or is not readable");
        }
        if (!filesize($filename)) {
            throw new FileException("File is empty");
        }
    }

    /**
     * Method to check the options
     *
     * @param array{
     *      'delimiter'?: string,
     *      'enclosure'?: string,
     *      'escape'?: string,
     *      'header'?: int,
     *      'required_headers'?: array<int, string>,
     *      'alias'?: array<int, string>,
     *      'headerCase'?: int
     * } $options
     *
     * @return void
     */
    private function checkOptions(?array $options)
    {
        if (!is_array($options) || !count($options)) {
            return;
        }

        // check to see if any options were passed in
        $this->options['delimiter'] = $options['delimiter'] ?? ",";
        $this->options['enclosure'] = $options['enclosure'] ?? '"';
        $this->options['escape'] = $options['escape'] ?? "\\";
        $this->options['headerIndex'] = $options['header'] ?? 0;
        $this->options['required_headers'] = $options['required_headers'] ?? [];
        $this->options['alias'] = $options['alias'] ?? [];
        $this->options['headerCase'] = $options['headerCase'] ?? false;
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

        if (!$this->fh) {
            return;
        }

        // loop until you get to the header row
        while ($data = fgetcsv($this->fh, 0, $this->delimiter, $this->enclosure, $this->escape)) {
            if ($row == $this->headerIndex) {
                if ($this->options['headerCase'] == Header::TO_LOWER) {
                    $data = array_map('strtolower', $data);
                } elseif ($this->options['headerCase'] == Header::TO_CAMEL_CASE) {
                    $data = Header::toCamelCase($data);
                }
                $this->header = new Header($data, $this->required_headers);
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
    public function current(): array
    {
        $ret = [];

        if (!is_a($this->header, 'Godsgood33\CSVReader\Header')) {
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
     * @throws FileException
     *
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function next(): bool
    {
        if (!is_resource($this->fh)) {
            throw new FileException('File is no longer open');
        }
        $tmp = fgetcsv($this->fh, 0, $this->delimiter, $this->enclosure, $this->escape);
        if (!$tmp && feof($this->fh)) {
            return false;
        }

        $this->data = $tmp;
        $this->index++;
        return true;
    }

    /**
     * Return the current row number (excluding the header row)
     *
     * @return int
     */
    public function key(): int
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
