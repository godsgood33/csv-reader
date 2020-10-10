<?php

declare(strict_types=1);

require_once dirname(__DIR__) . "/vendor/autoload.php";

use Godsgood33\CSVReader\CSVReader;

final class CSVReaderTest extends PHPUnit\Framework\TestCase
{
    private $csvreader = null;

    protected function setUp(): void
    {
        $fn = __DIR__ . "/Example.csv";
        $this->csvreader = new CSVReader($fn);
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

    public function testGetAll()
    {
        $row = $this->csvreader->all();

        $this->assertEquals("Harry Potter & The Sorcerer Stone", $row['Item']);
    }
}
