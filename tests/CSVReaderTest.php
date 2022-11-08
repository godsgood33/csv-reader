<?php

declare(strict_types=1);

namespace Godsgood33\CSVReaderTests;

use Exception;
use stdClass;
use Godsgood33\CSVReader\Header;
use Godsgood33\CSVReader\Reader;
use Godsgood33\CSVReader\Filter;
use Godsgood33\CSVReader\Link;
use Godsgood33\CSVReader\Map;
use Godsgood33\CSVReader\Exceptions\FileException;
use Godsgood33\CSVReader\Exceptions\InvalidHeaderOrField;

/**
 * @coversDefaultClass Reader
 */
final class CSVReaderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Reader
     */
    private $csvreader = null;

    protected function setUp(): void
    {
        $fn = __DIR__ . "/Example.csv";
        $this->csvreader = new Reader($fn);
    }

    protected function tearDown(): void
    {
        $this->csvreader->close();
    }

    public function testConstructor()
    {
        $this->assertInstanceOf("Godsgood33\CSVReader\Reader", $this->csvreader);
    }

    public function testItem()
    {
        $this->assertEquals("Harry Potter & The Sorcerer Stone", $this->csvreader->Item);
    }

    public function testInvalidColumn()
    {
        $this->assertNull($this->csvreader->Test);
    }

    public function testNext()
    {
        $this->csvreader->next();
        $this->assertEquals("Curious George", $this->csvreader->Item);
    }

    public function testGetCurrent()
    {
        $row = $this->csvreader->current();

        $this->assertEquals("Harry Potter & The Sorcerer Stone", $row['Item']);
    }

    public function testGetTitles()
    {
        $titles = $this->csvreader->getHeaderTitles();

        $this->assertEquals(['Item', 'SKU', 'Qty', 'Price', 'Cost'], $titles);
    }

    public function testOptions()
    {
        $this->csvreader = new Reader(
            __DIR__ . "/Example.csv",
            [
                'delimiter' => ',',
                'enclosure' => '"',
                'header' => 1
            ]
        );
        $this->assertInstanceOf("Godsgood33\CSVReader\Reader", $this->csvreader);
    }

    public function testPropToLower()
    {
        $this->csvreader = new Reader(__DIR__ . "/Example.csv", [
            'propToLower' => true
        ]);

        $this->assertNull($this->csvreader->SKU);
        $this->assertEquals('HPSS', $this->csvreader->sku);
    }

    public function testGetLineCount()
    {
        $this->assertEquals(2, $this->csvreader->lineCount);
    }

    public function testReadEmptyFile()
    {
        $this->expectException(FileException::class);
        $this->csvreader = new Reader(__DIR__ . "/empty_file.csv");
    }

    public function testGetKey()
    {
        $this->assertEquals(1, $this->csvreader->key());
    }

    public function testRewind()
    {
        $this->csvreader->next(); // skip to row 2
        $this->assertEquals(2, $this->csvreader->key());
        $this->csvreader->rewind();
        $this->assertEquals(1, $this->csvreader->key());
    }

    public function testMissingFile()
    {
        $this->expectException(Exception::class);
        $this->csvreader = new Reader("nonexistentfile.csv");
    }

    public function testValid()
    {
        $this->assertTrue($this->csvreader->valid());
    }

    public function testEmptyHeaderColumn()
    {
        $this->expectException(InvalidHeaderOrField::class);
        $this->csvreader = new Reader(__DIR__ . "/empty_header.csv");
    }

    public function testEmptyHeaderArray()
    {
        $arr = [];
        $this->expectException(InvalidHeaderOrField::class);
        $header = new Header($arr);
    }

    public function testRewindFileWithHeaderOnSecondRow()
    {
        $this->csvreader = new Reader(__DIR__ . "/header_on_second_line.csv", ['header' => 1]);
        $this->assertEquals("row 1-1", $this->csvreader->header1);
        $this->csvreader->next();
        $this->assertEquals("row 2-2", $this->csvreader->header2);
        $this->csvreader->rewind();
        $this->assertEquals("row 1-2", $this->csvreader->header2);
    }

    public function testSingleHeaderFile()
    {
        $this->csvreader = new Reader(__DIR__ . "/single_header_file.csv");
        $this->assertEquals("value 1", $this->csvreader->column1);
    }

    public function testRequiredHeaderAllPresent()
    {
        $req_headers = ["Item","SKU","Qty","Price","Cost"];
        $this->csvreader = new Reader(__DIR__ . "/Example.csv", ['required_headers' => $req_headers]);
        $this->assertInstanceOf("Godsgood33\CSVReader\Reader", $this->csvreader);
    }

    public function testRequiredHeaderOneMissing()
    {
        $this->expectExceptionMessage("MissingField Missing from headers (Item,SKU,Qty,Price,Cost,MissingField)");
        $req_headers = ["Item","SKU","Qty","Price","Cost","MissingField"];
        $this->csvreader = new Reader(__DIR__ . "/Example.csv", ['required_headers' => $req_headers]);
    }

    public function testFileClosesBeforeNext()
    {
        $this->expectException(FileException::class);
        $this->csvreader->close();
        $this->csvreader->next();
    }

    public function testHeaderAliases()
    {
        $this->csvreader = new Reader(__DIR__."/Example.csv", [
            'alias' => [
                'item' => 'Item',
                'id' => 'SKU',
                'qty' => 'Qty',
                'cost' => 'Cost',
                'price' => 'Price',
            ]
        ]);

        $this->assertEquals('HPSS', $this->csvreader->id);
    }

    public function testGetAliases()
    {
        $this->csvreader = new Reader(__DIR__."/Example.csv", [
            'alias' => [
                'item' => 'Item',
                'id' => 'SKU',
                'qty' => 'Qty',
                'cost' => 'Cost',
                'price' => 'Price',
            ]
        ]);

        $aliases = $this->csvreader->alias;
        $this->assertEquals('Qty', $aliases['qty']);
    }

    public function testGetEmptyAliases()
    {
        $aliases = $this->csvreader->alias;
        $this->assertEmpty($aliases);
    }

    public function testInvalidHeaderAlias()
    {
        $this->csvreader = new Reader(__DIR__."/Example.csv", [
            'alias' => [
                'item' => 'frank',
                'id' => 'SKU',
                'qty' => 'Qty',
                'cost' => 'Cost',
                'price' => 'Price',
            ]
        ]);

        $this->assertNull($this->csvreader->item);
    }

    public function testURLCSVReader()
    {
        $this->csvreader = new Reader(
            "https://support.staffbase.com/hc/en-us/article_attachments/360009197031/username.csv",
            [
                'delimiter' => ';'
            ]
        );
        $this->assertEquals('booker12', $this->csvreader->Username);
    }

    public function testInvalidURL()
    {
        $this->expectException(FileException::class);
        $this->csvreader = new Reader("http://www.example.com/example.csv");
    }

    public function testReadLargeFile()
    {
        $this->csvreader = new Reader(__DIR__ . '/movie-library.csv');

        $this->assertEquals(6, $this->csvreader->lineCount);
        $this->assertEquals("300", $this->csvreader->title);
        $this->csvreader->next();
        $this->assertEquals("Ace Ventura: Pet Detective", $this->csvreader->title_sort);

        $lineCount = 0;

        do {
            $lineCount++;
        } while ($this->csvreader->next());

        $this->assertEquals(4, $lineCount);
    }

    public function testReadFileWithSemicolonDelimiters()
    {
        $this->csvreader = new Reader(
            __DIR__.'/csv_semicolon_delimiter.csv',
            [
                'delimiter' => ';'
            ]
        );

        $this->assertEquals('HPSS', $this->csvreader->SKU);
    }

    public function testReadFileWithSingleQuoteEnclosures()
    {
        $this->csvreader = new Reader(
            __DIR__.'/csv_singlequote_enclosure.csv',
            [
                'enclosure' => "'"
            ]
        );

        $this->assertEquals('HPSS', $this->csvreader->SKU);
    }

    public function testSetMap()
    {
        $this->csvreader = new Reader(
            __DIR__.'/movie-library.csv'
        );
        $map = new Map('full_title', "%0 (%1)", ['title', 'year']);
        $this->csvreader->addMap($map);
        $this->assertEquals("300 (2007)", $this->csvreader->full_title);
    }

    public function testLargeMap()
    {
        $this->csvreader = new Reader(
            __DIR__.'/movie-library.csv'
        );
        $map = new Map('test', "%0, %1, %2, %3, %4, %5, %6, %7, %8, %9, %10", [
            'id', 'guid', 'media_item_count', 'title', 'title_sort', 'original_title',
             'studio', 'content_rating', 'duration', 'tags_genre', 'tags_collection'
        ]);
        $this->csvreader->addMap($map);
        $this->assertEquals(
            "138, plex://movie/5d7768296f4521001ea99959, 1, 300, 300, , Virtual Studios, R, 7020000, War|Action, 300|test",
            $this->csvreader->test
        );
    }

    public function testMapStringFieldDifference()
    {
        $this->csvreader = new Reader(
            __DIR__.'/movie-library.csv'
        );
        $map = new Map('test', "%0, %1, %2, %3, %4, %5, %6, %7, %8, %9, %10", [
            'id', 'guid', 'media_item_count', 'title', 'title_sort', 'original_title',
        ]);
        $this->csvreader->addMap($map);
        $this->assertEquals(
            "138, plex://movie/5d7768296f4521001ea99959, 1, 300, 300, , %6, %7, %8, %9, plex://movie/5d7768296f4521001ea999590",
            $this->csvreader->test
        );
    }

    public function testMapStringMultipleInstance()
    {
        $this->csvreader = new Reader(
            __DIR__.'/movie-library.csv'
        );
        $map = new Map('test', "%0, %0", [
            'id',
        ]);
        $this->csvreader->addMap($map);
        $this->assertEquals(
            "138, 138",
            $this->csvreader->test
        );
    }

    public function testMapMoreFieldsThanString()
    {
        $this->csvreader = new Reader(
            __DIR__.'/movie-library.csv'
        );
        $map = new Map('test', "%0, %1, %2", [
            'id', 'guid', 'media_item_count', 'title', 'title_sort',
        ]);
        $this->csvreader->addMap($map);
        $this->assertEquals(
            "138, plex://movie/5d7768296f4521001ea99959, 1",
            $this->csvreader->test
        );
    }

    public function testSetFilterStaticMethod()
    {
        // add filter to reverse string
        $filter = new Filter('SKU', [CSVReaderTest::class, 'skuFilter']);
        $this->csvreader->addFilter($filter);
        $this->assertEquals("SSPH", $this->csvreader->SKU);
    }

    public function testSetFilterCallableObject()
    {
        $t = new Test();
        $filter = new Filter('SKU', [$t, 'foo']);
        $this->csvreader->addFilter($filter);
        $this->assertEquals('bar', $this->csvreader->SKU);
    }

    public function testFilterOnAlias()
    {
        // create object, add alias to collection and genre
        $this->csvreader = new Reader(
            __DIR__.'/movie-library.csv',
            [
                'alias' => [
                    'collection' => 'tags_collection'
                ]
            ]
        );

        $filter = new Filter('tags_collection', [CSVReaderTest::class, 'splitCollection']);
        // add filter on original field and check that filter is called on alias
        $this->csvreader->addFilter($filter);
        $this->assertEquals(['300', 'test'], $this->csvreader->collection);
    }

    public function testLink()
    {
        $this->csvreader = new Reader(__DIR__.'/movie-library.csv');
        $link = new Link('object_data', [
            'id', 'title', 'studio', 'content_rating', 'year'
        ], [Address::class, 'fromCSV']);
        $this->csvreader->addLink($link);
        $res = $this->csvreader->object_data;
        $expected = new Address();
        $expected->id = 138;
        $expected->title = '300';
        $expected->studio = 'Virtual Studios';
        $expected->content_rating = 'R';
        $expected->year = 2007;

        $this->assertIsObject($res);
        $this->assertInstanceOf(Address::class, $res);
        $this->assertEquals($expected, $res);
    }

    public function testLinkWithoutCallback()
    {
        $this->csvreader = new Reader(__DIR__.'/movie-library.csv');
        $link = new Link('MovieData', [
            'id', 'title', 'studio', 'content_rating', 'year'
        ]);
        $this->csvreader->addLink($link);
        $res = $this->csvreader->MovieData;
        $expected = new stdClass();
        $expected->id = 138;
        $expected->title = '300';
        $expected->studio = 'Virtual Studios';
        $expected->content_rating = 'R';
        $expected->year = 2007;

        $this->assertIsObject($res);
        $this->assertEquals($expected, $res);
    }

    public function testChangeEscapeCharacter()
    {
        $this->csvreader = new Reader(
            __DIR__.'/Example.csv',
            [
                'escape' => '/'
            ]
        );
        $this->assertEquals('/', $this->csvreader->escape);
    }

    /**
     * Test method to just reverse a string
     *
     * @param string $val
     *
     * @return string
     */
    public static function skuFilter($val): string
    {
        return strrev($val);
    }

    /**
     * Test method to take a string and split with the pipe character (|)
     *
     * @param string $val
     *
     * @return array
     */
    public static function splitCollection($val): array
    {
        return explode('|', $val);
    }
}
