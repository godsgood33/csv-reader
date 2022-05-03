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
     * @var array<string, int>
     */
    private array $header;

    /**
     * Original header titles
     *
     * @var string[]
     */
    private array $titles;

    /**
     * Constructor method
     *
     * @param array<int, string> $header
     * @param string[] $requiredHeaders
     *
     * @throws InvalidHeaderOrField
     */
    public function __construct(array $header, array $requiredHeaders = [])
    {
        // check that there is valid header data
        if (empty($header)) {
            throw new InvalidHeaderOrField("Header array is empty");
        }

        // loop through each header field to strip out invalid characters and
        // reverse the key/value pairs to get the index of each header field
        $this->header = $this->stripColumns($header);

        $reqHeadersMissing = $this->checkHeaders($requiredHeaders);

        if ($reqHeadersMissing !== true) {
            throw new InvalidHeaderOrField("{$reqHeadersMissing} Missing from headers (".
                implode(",", $requiredHeaders).")");
        }

        // store the original header titles
        $this->titles = $header;
    }

    /**
     * Method to strip invalid characters from columns headers
     *
     * @param array $columns
     *
     * @return array
     *
     * @throws InvalidHeaderOrField
     */
    private function stripColumns(array $columns): array
    {
        $ret = [];
        foreach ($columns as $columnIndex => $h) {
            $h = preg_replace("/[^a-zA-Z0-9_]/", "", $h);
            if (empty($h)) {
                throw new InvalidHeaderOrField("Empty header");
            }
            $ret[$h] = $columnIndex;
        }
        return $ret;
    }

    /**
     * Magic method to convert the requested header to a field index
     *
     * @return null|int|string
     */
    public function __get(string $field)
    {
        return $this->header[$field] ?? null;
    }

    /**
     * Method to return all headers
     *
     * @return array<string, int>
     */
    public function all()
    {
        return $this->header;
    }

    /**
     * Method to get the original header titles
     *
     * @return array<string>
     */
    public function getTitles()
    {
        return array_values($this->titles);
    }

    /**
     * Method to check that all required headers are present in the file
     *
     * @param string[] $requiredHeaders
     *      An array of headers that are required
     *
     * @return bool|string
     *      Returns TRUE only if ALL required headers are present, otherwise FALSE
     */
    private function checkHeaders(array $requiredHeaders)
    {
        $keys = array_keys($this->header);
        foreach ($requiredHeaders as $h) {
            if (!in_array($h, $keys)) {
                return $h;
            }
        }

        return true;
    }
}
