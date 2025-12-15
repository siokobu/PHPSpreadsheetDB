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
    CONST TEMPDIR = __DIR__.DIRECTORY_SEPARATOR."files".DIRECTORY_SEPARATOR;

    private $columnsStr = \PHPSpreadsheetDB\Spreadsheet\Xlsx::COLUMNS_STR;
    
    private $dataStr = \PHPSpreadsheetDB\Spreadsheet\Xlsx::DATA_STR;

    /** @test */
    public function testDeleteAllSheets()
    {
        $path = self::TEMPDIR."XlsxTest_testDeleteAllSheets.xlsx";
        $spreadsheet = new Spreadsheet();
        $sheet = new Worksheet($spreadsheet, "hoge");
        $spreadsheet->addSheet($sheet, 0);
        $sheet = new Worksheet($spreadsheet, "fuga");
        $spreadsheet->addSheet($sheet, 0);
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);

        $xlsx = new Xlsx($path);
        $xlsx->deleteAllSheets();

        $spreadsheet = IOFactory::load($path);
        $count = $spreadsheet->getSheetCount();

        $this->assertEquals(1,$count);
    }

    public function testCreateSheet()
    {
        $table = "hogeTable";
        $columns = array( ['Name' => 'hoge', 'Type' => 0], ['Name' => 'fuga', 'Type' => 1]);
        $path = self::TEMPDIR."XlsxTest_testCreateSheet.xlsx";

        if(file_exists($path)) unlink($path);
        $spreadsheet = new Spreadsheet();
        $sheetNames = $spreadsheet->getSheetNames();
        foreach ($sheetNames as $sheetName) {
            $sheetIndex = $spreadsheet->getIndex($spreadsheet->getSheetByName($sheetName));
            $spreadsheet->removeSheetByIndex($sheetIndex);
        }
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($path);

        $xlsx = new Xlsx($path);
        $xlsx->createSheet($table, $columns);

        $spreadsheet = IOFactory::load($path);
        $this->assertEquals($spreadsheet->getSheetByName($table)->getCell('A1')->getValue(), "hoge");
        $this->assertEquals($spreadsheet->getSheetByName($table)->getCell('B1')->getValue(), "fuga");

    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws Exception
     */
    public function testSetTableDatas()
    {
        $path = self::TEMPDIR."XlsxTest_testSettableDatas1.xlsx";
        $table = "testtb";

        if(file_exists($path)) unlink($path);
        $spreadsheet = new Spreadsheet();
        $sheet = new Worksheet($spreadsheet, $table);
        $sheet->setCellValue('A1', 'col1');
        $sheet->setCellValue('B1', 'col2');
        $spreadsheet->addSheet($sheet);
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);

        $datas = array(array('col1' => 'hoge', 'col2' => 'fuga'), array('col1' => 'foo', 'col2' => 'var'));

        $xlsx = new Xlsx($path);
        $xlsx->setTableDatas($table, $datas);

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getSheetByName($table);

        $this->assertEquals($sheet->getCell('A1')->getValue(), 'col1');
        $this->assertEquals($sheet->getCell('A2')->getValue(), 'hoge');
        $this->assertEquals($sheet->getCell('A3')->getValue(), 'foo');
        $this->assertEquals($sheet->getCell('B1')->getValue(), 'col2');
        $this->assertEquals($sheet->getCell('B2')->getValue(), 'fuga');
        $this->assertEquals($sheet->getCell('B3')->getValue(), 'var');

    }

    /** @test */
    public function testGetAllTables()
    {
        $path = __DIR__."/docs/Xlsx_GetAllTables.xlsx";
        $tables = ["tablez01", "tabley02", "tablex03"];

        if(file_exists($path)) unlink($path);
        $spreadsheet = new Spreadsheet();
        foreach($tables as $table) {
            $sheet = new Worksheet($spreadsheet, $table);
            $spreadsheet->addSheet($sheet);
        }
        $spreadsheet->removeSheetByIndex($spreadsheet->getIndex($spreadsheet->getSheetByName('Worksheet')));
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);

        $xlsx = new Xlsx($path);
        $tableNames = $xlsx->getTableNames();

        $this->assertEquals($tables[0], $tableNames[0]);
        $this->assertEquals($tables[1], $tableNames[1]);
        $this->assertEquals($tables[2], $tableNames[2]);
    }

    /** @test */
    public function testGetData_通常のパターン_様々な型()
    {
        $path = self::TEMPDIR."XlsxTest_testGetData1.xlsx";
        $table = "tablez01";

        if(file_exists($path)) unlink($path);
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
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);

        $xlsx = new Xlsx($path);
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

        unlink($path);
    }

    /** @test */
    public function testGetData_2行目ヘッダ_空文字_コメント()
    {
        $path = self::TEMPDIR."XlsxTest_testGetData2.xlsx";
        $table = "tablez01";

        if(file_exists($path)) unlink($path);
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
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);

        $xlsx = new Xlsx($path);
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
        $this->assertEquals('<null>', $data['data'][2][0]);
        $this->assertEquals('bbbbb', $data['data'][2][1]);
        $this->assertEquals('',      $data['data'][2][2]);

        unlink($path);
    }

    /** @test */
    public function testGetData_headerより前のdataは無効()
    {
        $path = self::TEMPDIR."XlsxTest_testGetData3.xlsx";
        $table = "tablez01";

        if(file_exists($path)) unlink($path);
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
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);

        $this->expectException(\PHPSpreadsheetDB\PHPSpreadsheetDBException::class);

        $xlsx = new Xlsx($path);
        $xlsx->getData($table);

        unlink($path);
    }

        /** @test */
    public function testGetData_dataが20行目ならOK()
    {
        $path = self::TEMPDIR."XlsxTest_testGetData4.xlsx";
        $table = "tablez01";

        if(file_exists($path)) unlink($path);
        $spreadsheet = new Spreadsheet();
        $sheet = new Worksheet($spreadsheet, $table);
        $sheet->setCellValue('A19', $this->columnsStr);
        $sheet->setCellValue('B19', "header1");
        $sheet->setCellValue('A20', $this->dataStr);
        $sheet->setCellValue('B21', 'data11');
        $spreadsheet->addSheet($sheet);
        $spreadsheet->removeSheetByIndex($spreadsheet->getIndex($spreadsheet->getSheetByName('Worksheet')));
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);

        $xlsx = new Xlsx($path);
        $data = $xlsx->getData($table);

        $this->assertEquals('header1', $data['columns'][0]);
        $this->assertEquals('data11', $data['data'][0][0]);

        unlink($path);
    }

    /** @test */
    public function testGetData_dataが21行目ならOUT()
    {
        $path = self::TEMPDIR."XlsxTest_testGetData4.xlsx";
        $table = "tablez01";

        if(file_exists($path)) unlink($path);
        $spreadsheet = new Spreadsheet();
        $sheet = new Worksheet($spreadsheet, $table);
        $sheet->setCellValue('A20', $this->columnsStr);
        $sheet->setCellValue('B20', "header1");
        $sheet->setCellValue('A21', $this->dataStr);
        $spreadsheet->addSheet($sheet);
        $spreadsheet->removeSheetByIndex($spreadsheet->getIndex($spreadsheet->getSheetByName('Worksheet')));
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);

        $this->expectException(\PHPSpreadsheetDB\PHPSpreadsheetDBException::class);

        $xlsx = new Xlsx($path);
        $data = $xlsx->getData($table);

        $this->assertEquals('header1', $data['columns'][0]);

        unlink($path);
    }

}
