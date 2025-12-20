<?php

namespace Tests\Unit;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use League\Csv\Statement;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CsvParsingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    #[Test]
    public function it_can_parse_csv_with_comma_delimiter()
    {
        $csvContent = "Name,Hostname,Type\nRouter-01,router01.example.com,router\nSwitch-01,switch01.example.com,switch";

        $csvFile = UploadedFile::fake()->createWithContent('test.csv', $csvContent);
        Storage::putFileAs('csv-imports', $csvFile, 'test.csv');

        $csv = Reader::createFromPath(Storage::path('csv-imports/test.csv'), 'r');
        $csv->setDelimiter(',');
        $csv->setHeaderOffset(0);

        $records = iterator_to_array($csv->getRecords());
        $recordsArray = array_values($records);

        $this->assertCount(2, $records);
        $this->assertEquals('Router-01', $recordsArray[0]['Name']);
        $this->assertEquals('router01.example.com', $recordsArray[0]['Hostname']);
        $this->assertEquals('router', $recordsArray[0]['Type']);
    }

    #[Test]
    public function it_can_parse_csv_with_semicolon_delimiter()
    {
        $csvContent = "Name;Hostname;Type\nRouter-01;router01.example.com;router\nSwitch-01;switch01.example.com;switch";

        $csvFile = UploadedFile::fake()->createWithContent('test.csv', $csvContent);
        Storage::putFileAs('csv-imports', $csvFile, 'test.csv');

        $csv = Reader::createFromPath(Storage::path('csv-imports/test.csv'), 'r');
        $csv->setDelimiter(';');
        $csv->setHeaderOffset(0);

        $records = iterator_to_array($csv->getRecords());
        $recordsArray = array_values($records);

        $this->assertCount(2, $records);
        $this->assertEquals('Router-01', $recordsArray[0]['Name']);
        $this->assertEquals('router01.example.com', $recordsArray[0]['Hostname']);
    }

    #[Test]
    public function it_can_parse_csv_with_tab_delimiter()
    {
        $csvContent = "Name\tHostname\tType\nRouter-01\trouter01.example.com\trouter\nSwitch-01\tswitch01.example.com\tswitch";

        $csvFile = UploadedFile::fake()->createWithContent('test.csv', $csvContent);
        Storage::putFileAs('csv-imports', $csvFile, 'test.csv');

        $csv = Reader::createFromPath(Storage::path('csv-imports/test.csv'), 'r');
        $csv->setDelimiter("\t");
        $csv->setHeaderOffset(0);

        $records = iterator_to_array($csv->getRecords());
        $recordsArray = array_values($records);

        $this->assertCount(2, $records);
        $this->assertEquals('Router-01', $recordsArray[0]['Name']);
        $this->assertEquals('router01.example.com', $recordsArray[0]['Hostname']);
    }

    #[Test]
    public function it_can_parse_csv_without_header()
    {
        $csvContent = "Router-01,router01.example.com,router\nSwitch-01,switch01.example.com,switch";

        $csvFile = UploadedFile::fake()->createWithContent('test.csv', $csvContent);
        Storage::putFileAs('csv-imports', $csvFile, 'test.csv');

        $csv = Reader::createFromPath(Storage::path('csv-imports/test.csv'), 'r');
        $csv->setDelimiter(',');

        $records = iterator_to_array($csv->getRecords());

        $this->assertCount(2, $records);
        $this->assertEquals('Router-01', $records[0][0]);
        $this->assertEquals('router01.example.com', $records[0][1]);
        $this->assertEquals('router', $records[0][2]);
    }

    #[Test]
    public function it_can_get_limited_preview_of_csv()
    {
        $csvContent = "Name,Hostname\n".
                     "Router-01,router01.example.com\n".
                     "Router-02,router02.example.com\n".
                     "Router-03,router03.example.com\n".
                     "Router-04,router04.example.com\n".
                     "Router-05,router05.example.com\n".
                     'Router-06,router06.example.com';

        $csvFile = UploadedFile::fake()->createWithContent('test.csv', $csvContent);
        Storage::putFileAs('csv-imports', $csvFile, 'test.csv');

        $csv = Reader::createFromPath(Storage::path('csv-imports/test.csv'), 'r');
        $csv->setDelimiter(',');

        $stmt = Statement::create()->limit(3);
        $records = iterator_to_array($stmt->process($csv));

        $this->assertCount(3, $records); // Header + 2 data rows
        $this->assertEquals('Name', $records[0][0]); // Header
        $this->assertEquals('Router-01', $records[1][0]); // First data row
        $this->assertEquals('Router-02', $records[2][0]); // Second data row
    }

    #[Test]
    public function it_handles_empty_csv_file()
    {
        $csvContent = '';

        $csvFile = UploadedFile::fake()->createWithContent('test.csv', $csvContent);
        Storage::putFileAs('csv-imports', $csvFile, 'test.csv');

        $csv = Reader::createFromPath(Storage::path('csv-imports/test.csv'), 'r');
        $csv->setDelimiter(',');

        $records = iterator_to_array($csv->getRecords());

        $this->assertCount(0, $records);
    }

    #[Test]
    public function it_handles_csv_with_quoted_fields()
    {
        $csvContent = '"Device Name","Host,Name","Description with, comma"'."\n".'"Router-01","router01.example.com","Main router, very important"';

        $csvFile = UploadedFile::fake()->createWithContent('test.csv', $csvContent);
        Storage::putFileAs('csv-imports', $csvFile, 'test.csv');

        $csv = Reader::createFromPath(Storage::path('csv-imports/test.csv'), 'r');
        $csv->setDelimiter(',');
        $csv->setHeaderOffset(0);

        $records = iterator_to_array($csv->getRecords());
        $recordsArray = array_values($records);

        $this->assertCount(1, $records);
        $this->assertEquals('Router-01', $recordsArray[0]['Device Name']);
        $this->assertEquals('router01.example.com', $recordsArray[0]['Host,Name']);
        $this->assertEquals('Main router, very important', $recordsArray[0]['Description with, comma']);
    }

    #[Test]
    public function it_handles_csv_with_special_characters()
    {
        $csvContent = "Name,Description\nRouter-Ü1,Röuter für Dëutschländ\nSwitch-ß,Nëtzwerk Swïtch";

        $csvFile = UploadedFile::fake()->createWithContent('test.csv', $csvContent);
        Storage::putFileAs('csv-imports', $csvFile, 'test.csv');

        $csv = Reader::createFromPath(Storage::path('csv-imports/test.csv'), 'r');
        $csv->setDelimiter(',');
        $csv->setHeaderOffset(0);

        $records = iterator_to_array($csv->getRecords());
        $recordsArray = array_values($records);

        $this->assertCount(2, $records);
        $this->assertEquals('Router-Ü1', $recordsArray[0]['Name']);
        $this->assertEquals('Röuter für Dëutschländ', $recordsArray[0]['Description']);
        $this->assertEquals('Switch-ß', $recordsArray[1]['Name']);
        $this->assertEquals('Nëtzwerk Swïtch', $recordsArray[1]['Description']);
    }
}
