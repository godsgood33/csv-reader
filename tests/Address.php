<?php

namespace Godsgood33\CSVReaderTests;

use stdClass;

class Address
{
    public int $id;
    public string $title;
    public string $studio;
    public string $content_rating;
    public int $year;

    public static function fromCSV(stdClass $data)
    {
        $me = new static();
        $me->id = $data->id;
        $me->title = $data->title;
        $me->studio = $data->studio;
        $me->content_rating = $data->content_rating;
        $me->year = $data->year;

        return $me;
    }
}
