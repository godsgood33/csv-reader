<?php

declare(strict_types=1);

require_once dirname(__DIR__) . "/vendor/autoload.php";

use Godsgood33\CSVReader\CSVReader;
use Godsgood33\CSVReader\Exceptions\InvalidHeaderOrField;

/**
 * @coversDefaultClass CSVReader
 */
final class CSVReaderTest extends PHPUnit\Framework\TestCase
{
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
        $this->csvreader = new CSVReader(__DIR__ . "/Example.csv", ['delimiter' => ',', 'enclosure' => '"', 'header' => 1]);
        $this->assertInstanceOf("Godsgood33\CSVReader\CSVReader", $this->csvreader);
    }

    public function testReadEmptyFile()
    {
        $this->expectException(Exception::class);
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
}