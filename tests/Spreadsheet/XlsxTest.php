<?php

namespace PHPSpreadsheetDBTest\Spreadsheet;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use PHPSpreadsheetDB\PHPSpreadsheetDB;
use PHPSpreadsheetDB\Spreadsheet\Xlsx;
use PHPSpreadsheetDBTest\TestCase;

class XlsxTest extends TestCase
{
    private $tempDir;

    private $tempFile;

    private $columnsStr = \PHPSpreadsheetDB\Spreadsheet\Xlsx::COLUMNS_STR;
    
    private $dataStr = \PHPSpreadsheetDB\Spreadsheet\Xlsx::DATA_STR;

    public function setUp(): void
    {
        parent::setUp();
        $this->tempDir = self::TEMPDIR;
        $this->tempFile = $this->tempDir."XlsxTest.xlsx";
    }

    /** @test */
    public function testGetTableNames()
    {
        $tables = ["tablez01", "tabley02", "tablex03"];

        // delete temp file if exists
        file_exists($this->tempFile) && unlink($this->tempFile);

        // prepare xlsx with 3 sheets
        $spreadsheet = new Spreadsheet();
        foreach($tables as $table) {
            $sheet = new Worksheet($spreadsheet, $table);
            $spreadsheet->addSheet($sheet);
        }
        $spreadsheet->removeSheetByIndex($spreadsheet->getIndex($spreadsheet->getSheetByName('Worksheet')));
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($this->tempFile);

        // execute getTableNames
        $xlsx = new Xlsx($this->tempFile);
        $tableNames = $xlsx->getTableNames();

        // verify
        $this->assertCount(3, $tableNames);
        $this->assertEquals($tables[0], $tableNames[0]);
        $this->assertEquals($tables[1], $tableNames[1]);
        $this->assertEquals($tables[2], $tableNames[2]);
    }

    /** @test */
    public function testGetData_various_types()
    {
        $table = "tablez01";

        // delete temp file if exists
        file_exists($this->tempFile) && unlink($this->tempFile);

        $spreadsheet = new Spreadsheet();
        $sheet = new Worksheet($spreadsheet, $table);
        $sheet->setCellValue('A1', $this->columnsStr);
        $sheet->setCellValue('B1', 'col1');
        $sheet->setCellValue('C1', 'col2');
        $sheet->setCellValue('D1', 'col3');
        $sheet->setCellValue('A2', $this->dataStr);
        $sheet->setCellValue('B3', 'string');
        $sheet->setCellValue('C3', '100');
        $sheet->setCellValue('D3', '2021/10/10');
        $sheet->setCellValue('B4', '2021/10/10 23:59:59');
        $sheet->setCellValue('C4', 'true');
        $sheet->setCellValue('D4', '3.14');
        $spreadsheet->addSheet($sheet);
        $spreadsheet->removeSheetByIndex($spreadsheet->getIndex($spreadsheet->getSheetByName('Worksheet')));
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($this->tempFile);

        $xlsx = new Xlsx($this->tempFile);
        $data = $xlsx->getData($table);

        $this->assertCount(3, $data['columns']);
        $this->assertEquals('col1', $data['columns'][0]);
        $this->assertEquals('col2', $data['columns'][1]);
        $this->assertEquals('col3', $data['columns'][2]);
        $this->assertCount(2, $data['data']);
        $this->assertCount(3, $data['data'][0]);
        $this->assertEquals('string',     $data['data'][0][0]);
        $this->assertEquals('100',        $data['data'][0][1]);
        $this->assertEquals('2021/10/10', $data['data'][0][2]);
        $this->assertCount(3, $data['data'][1]);
        $this->assertEquals('2021/10/10 23:59:59', $data['data'][1][0]);
        $this->assertEquals('true',                $data['data'][1][1]);
        $this->assertEquals('3.14',                $data['data'][1][2]);

        unlink($this->tempFile);
    }

    /** @test */
    public function testGetData_headersOnLine2_emptyString_Comment()
    {
        // prepared table
        $table = "tablez01";

        // delete temp file if exists
        file_exists($this->tempFile) && unlink($this->tempFile);
        $spreadsheet = new Spreadsheet();
        $sheet = new Worksheet($spreadsheet, $table);
        $sheet->setCellValue('A1', 'ダミー');
        $sheet->setCellValue('B1', 'ダミー');
        $sheet->setCellValue('A2', $this->columnsStr);
        $sheet->setCellValue('B2', 'col1');
        $sheet->setCellValue('C2', 'col2');
        $sheet->setCellValue('D2', 'col3');
        $sheet->setCellValue('A3', $this->dataStr);
        $sheet->setCellValue('A4', '-- コメント行');
        $sheet->setCellValue('B5', '');
        $sheet->setCellValue('C5', 'bbb');
        $sheet->setCellValue('D5', 'ccc');
        $sheet->setCellValue('B6', 'AAA');
        $sheet->setCellValue('C6', '');
        $sheet->setCellValue('D6', 'CCC');
        $sheet->setCellValue('B7', '<null>');
        $sheet->setCellValue('C7', 'bbbbb');
        $sheet->setCellValue('D7', '');
        $sheet->setCellValue('A8', '-- コメント行');
        $spreadsheet->addSheet($sheet);
        $spreadsheet->removeSheetByIndex($spreadsheet->getIndex($spreadsheet->getSheetByName('Worksheet')));
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($this->tempFile);

        $xlsx = new Xlsx($this->tempFile);
        $data = $xlsx->getData($table);
        $this->assertEquals('col1', $data['columns'][0]);
        $this->assertEquals('col2', $data['columns'][1]);
        $this->assertEquals('col3', $data['columns'][2]);
        $this->assertEquals('',    $data['data'][0][0]);
        $this->assertEquals('bbb', $data['data'][0][1]);
        $this->assertEquals('ccc', $data['data'][0][2]);
        $this->assertEquals('AAA', $data['data'][1][0]);
        $this->assertEquals('',    $data['data'][1][1]);
        $this->assertEquals('CCC', $data['data'][1][2]);
        $this->assertNull($data['data'][2][0]);
        $this->assertEquals('bbbbb', $data['data'][2][1]);
        $this->assertEquals('',      $data['data'][2][2]);

        unlink($this->tempFile);
    }

    /** @test */
    public function testGetData_dataBeforeColumnsIInvalid()
    {
        // prepared table
        $table = "tablez01";

        // delete temp file if exists
        file_exists($this->tempFile) && unlink($this->tempFile);

        $spreadsheet = new Spreadsheet();
        $sheet = new Worksheet($spreadsheet, $table);
        $sheet->setCellValue('A1', $this->dataStr);
        $sheet->setCellValue('B1', 'ダミー');
        $sheet->setCellValue('C1', 'ダミー');
        $sheet->setCellValue('A2', '');
        $sheet->setCellValue('B2', 'Data11');
        $sheet->setCellValue('C2', 'Data12');
        $sheet->setCellValue('A3', $this->columnsStr);
        $sheet->setCellValue('B3', 'col1');
        $sheet->setCellValue('C3', 'col2');
        $sheet->setCellValue('A4', '');
        $sheet->setCellValue('B4', 'Data21');
        $sheet->setCellValue('C4', 'Data22');
        $spreadsheet->addSheet($sheet);
        $spreadsheet->removeSheetByIndex($spreadsheet->getIndex($spreadsheet->getSheetByName('Worksheet')));
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($this->tempFile);

        $this->expectException(\PHPSpreadsheetDB\PHPSpreadsheetDBException::class);

        $xlsx = new Xlsx($this->tempFile);
        $xlsx->getData($table);

        unlink($this->tempFile);
    }

    /** @test */
    public function testGetData_dataColumnsOn20Rows()
    {
        // prepared table
        $table = "tablez01";

        // delete temp file if exists
        file_exists($this->tempFile) && unlink($this->tempFile);

        // prepare xlsx with 3 sheets
        $spreadsheet = new Spreadsheet();
        $sheet = new Worksheet($spreadsheet, $table);
        $sheet->setCellValue('A19', $this->columnsStr);
        $sheet->setCellValue('B19', "header1");
        $sheet->setCellValue('A20', $this->dataStr);
        $sheet->setCellValue('B21', 'data11');
        $spreadsheet->addSheet($sheet);
        $spreadsheet->removeSheetByIndex($spreadsheet->getIndex($spreadsheet->getSheetByName('Worksheet')));
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($this->tempFile);

        // execute getData
        $xlsx = new Xlsx($this->tempFile);
        $data = $xlsx->getData($table);

        // verify
        $this->assertEquals('header1', $data['columns'][0]);
        $this->assertEquals('data11', $data['data'][0][0]);

        // delete temp file
        unlink($this->tempFile);
    }

    /** @test */
    public function testGetData_dataColumnOn21Rows()
    {
        $path = self::TEMPDIR."XlsxTest_testGetData4.xlsx";
        $table = "tablez01";

        // delete temp file if exists
        file_exists($this->tempFile) && unlink($this->tempFile);

        // prepare xlsx with 3 sheets
        $spreadsheet = new Spreadsheet();
        $sheet = new Worksheet($spreadsheet, $table);
        $sheet->setCellValue('A20', $this->columnsStr);
        $sheet->setCellValue('B20', "header1");
        $sheet->setCellValue('A21', $this->dataStr);
        $spreadsheet->addSheet($sheet);
        $spreadsheet->removeSheetByIndex($spreadsheet->getIndex($spreadsheet->getSheetByName('Worksheet')));
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($this->tempFile);

        // expect exception
        $this->expectException(\PHPSpreadsheetDB\PHPSpreadsheetDBException::class);

        // execute getData
        $xlsx = new Xlsx($this->tempFile);
        $data = $xlsx->getData($table);

        // verify
        $this->assertEquals('header1', $data['columns'][0]);

        // delete temp file
        unlink($this->tempFile);
    }

    /** @test */
    public function testDeleteAllSheets()
    {
        // delete temp file if exists
        file_exists($this->tempFile) && unlink($this->tempFile);

        // prepare xlsx with 2 sheets
        $spreadsheet = new Spreadsheet();
        $sheet = new Worksheet($spreadsheet, "hoge");
        $spreadsheet->addSheet($sheet, 0);
        $sheet = new Worksheet($spreadsheet, "fuga");
        $spreadsheet->addSheet($sheet, 0);
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($this->tempFile);

        // execute deleteAllSheets
        $xlsx = new Xlsx($this->tempFile);
        $xlsx->deleteAllSheets();

        // verify all sheets are deleted. This messages are expected to be thrown because all sheets are deleted.
        $this->expectException(\PhpOffice\PhpSpreadsheet\Exception::class);
        $this->expectExceptionMessage('You tried to set a sheet active by the out of bounds index: 0. The actual number of sheets is 0.');

        $spreadsheet = IOFactory::load($this->tempFile);
    }

    /** @test */
    public function testSetTabledata()
    {
        // delete temp file if exists
        file_exists($this->tempFile) && unlink($this->tempFile);

        // prepare data
        $table = "testtb";
        $data = [
            'columns' => ['col1', 'col2'],
            'data' => [
                ['hoge', 'fuga'],
                ['foo', 'var']
            ]
        ];

        $xlsx = new Xlsx($this->tempDir."XlsxTest.xlsx");
        $xlsx->setTableData($table, $data);

        $sheet = IOFactory::load($this->tempFile)->getSheetByName($table);

        $this->assertEquals($sheet->getCell('A1')->getValue(), $this->columnsStr);
        $this->assertEquals($sheet->getCell('B1')->getValue(), 'col1');
        $this->assertEquals($sheet->getCell('C1')->getValue(), 'col2');
        $this->assertEquals($sheet->getCell('A2')->getValue(), $this->dataStr);
        $this->assertEquals($sheet->getCell('B2')->getValue(), "");
        $this->assertEquals($sheet->getCell('C2')->getValue(), "");
        $this->assertEquals($sheet->getCell('A3')->getValue(), "");
        $this->assertEquals($sheet->getCell('B3')->getValue(), 'hoge');
        $this->assertEquals($sheet->getCell('C3')->getValue(), 'fuga');
        $this->assertEquals($sheet->getCell('A4')->getValue(), "");
        $this->assertEquals($sheet->getCell('B4')->getValue(), 'foo');
        $this->assertEquals($sheet->getCell('C4')->getValue(), 'var');

        // delete temp file
        unlink($this->tempFile);
    }
}
