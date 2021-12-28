<?php

declare(strict_types=1);

require_once dirname(__DIR__) . "/vendor/autoload.php";

use Godsgood33\CSVReader\CSVHeader;
use Godsgood33\CSVReader\CSVReader;
use Godsgood33\CSVReader\Exceptions\FileException;
use Godsgood33\CSVReader\Exceptions\InvalidHeaderOrField;

/**
 * @coversDefaultClass CSVReader
 */
final class CSVReaderTest extends PHPUnit\Framework\TestCase
{
    /**
     * @var CSVReader
     */
    private $csvreader = null;

    protected function setUp(): void
    {
        $fn = __DIR__ . "/Example.csv";
        $this->csvreader = new CSVReader($fn);
    }

    protected function tearDown(): void
    {
        $this->csvreader->close();
    }

    public function testConstructor()
    {
        $this->assertInstanceOf("Godsgood33\CSVReader\CSVReader", $this->csvreader);
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
        $this->csvreader = new CSVReader(
            __DIR__ . "/Example.csv",
            [
                'delimiter' => ',',
                'enclosure' => '"',
                'header' => 1
            ]
        );
        $this->assertInstanceOf("Godsgood33\CSVReader\CSVReader", $this->csvreader);
    }

    public function testGetLineCount()
    {
        $this->assertEquals(2, $this->csvreader->lineCount);
    }

    public function testReadEmptyFile()
    {
        $this->expectException(FileException::class);
        $this->csvreader = new CSVReader(__DIR__ . "/empty_file.csv");
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
        $this->csvreader = new CSVReader("nonexistentfile.csv");
    }

    public function testValid()
    {
        $this->assertTrue($this->csvreader->valid());
    }

    public function testEmptyHeaderColumn()
    {
        $this->expectException(InvalidHeaderOrField::class);
        $this->csvreader = new CSVReader(__DIR__ . "/empty_header.csv");
    }

    public function testEmptyHeaderArray()
    {
        $arr = [];
        $this->expectException(InvalidHeaderOrField::class);
        $header = new CSVHeader($arr);
    }

    public function testRewindFileWithHeaderOnSecondRow()
    {
        $this->csvreader = new CSVReader(__DIR__ . "/header_on_second_line.csv", ['header' => 1]);
        $this->assertEquals("row 1-1", $this->csvreader->header1);
        $this->csvreader->next();
        $this->assertEquals("row 2-2", $this->csvreader->header2);
        $this->csvreader->rewind();
        $this->assertEquals("row 1-2", $this->csvreader->header2);
    }

    public function testSingleHeaderFile()
    {
        $this->csvreader = new CSVReader(__DIR__ . "/single_header_file.csv");
        $this->assertEquals("value 1", $this->csvreader->column1);
    }

    public function testRequiredHeaderAllPresent()
    {
        $req_headers = ["Item","SKU","Qty","Price","Cost"];
        $this->csvreader = new CSVReader(__DIR__ . "/Example.csv", ['required_headers' => $req_headers]);
        $this->assertInstanceOf("Godsgood33\CSVReader\CSVReader", $this->csvreader);
    }

    public function testRequiredHeaderOneMissing()
    {
        $this->expectExceptionMessage("Missing Headers (Item,SKU,Qty,Price,Cost,MissingField)");
        $req_headers = ["Item","SKU","Qty","Price","Cost","MissingField"];
        $this->csvreader = new CSVReader(__DIR__ . "/Example.csv", ['required_headers' => $req_headers]);
    }

    public function testFileClosesBeforeNext()
    {
        $this->expectException(FileException::class);
        $this->csvreader->close();
        $this->csvreader->next();
    }

    public function testHeaderAliases()
    {
        $this->csvreader = new CSVReader(__DIR__."/Example.csv", [
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
        $this->csvreader = new CSVReader(__DIR__."/Example.csv", [
            'alias' => [
                'item' => 'Item',
                'id' => 'SKU',
                'qty' => 'Qty',
                'cost' => 'Cost',
                'price' => 'Price',
            ]
        ]);

        $aliases = $this->csvreader->getAliases();
        $this->assertEquals('Qty', $aliases['qty']);
    }

    public function testGetEmptyAliases()
    {
        $aliases = $this->csvreader->getAliases();
        $this->assertNull($aliases);
    }

    public function testInvalidHeaderAlias()
    {
        $this->csvreader = new CSVReader(__DIR__."/Example.csv", [
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
        $this->csvreader = new CSVReader(
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
        $this->csvreader = new CSVReader("http://www.example.com/example.csv");
    }
}
