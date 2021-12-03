<?php

namespace PHPSpreadsheetDBTest\Spreadsheet;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use PHPSpreadsheetDB\Spreadsheet\Xlsx;
use PHPSpreadsheetDBTest\TestCase;

class XlsxTest extends TestCase
{
    public function testDeleteAllSheets()
    {
        $path = __DIR__."/docs/XlsxTest_testDeleteAllSheets.xlsx";
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
        $path = __DIR__."/docs/XlsxTest_testCreateSheet.xlsx";

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
        $path = __DIR__."/docs/XlsxTest_testSettableDatas1.xlsx";
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

    public function testSetTableDatas2()
    {
        $path = __DIR__."/files/XlsxTest_testSetTableDatas2.xlsx";

        if(file_exists($path)) unlink($path);
        $spreadsheet = new Spreadsheet();
        $sheet = new Worksheet($spreadsheet, self::TESTTB01);
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

    /** @test テスト */
    public function testGetData1()
    {
        $path = __DIR__."/files/XlsxTest_testGetData1.xlsx";
        $table = "tablez01";

        if(file_exists($path)) unlink($path);
        $spreadsheet = new Spreadsheet();
        $sheet = new Worksheet($spreadsheet, $table);
        $sheet->setCellValue('A1', 'col1');
        $sheet->setCellValue('B1', 'col2');
        $sheet->setCellValue('C1', 'col3');
        $sheet->setCellValue('A2', 'string');
        $sheet->setCellValue('B2', '100');
        $sheet->setCellValue('C2', '2021/10/10');
        $sheet->setCellValue('A3', '2021/10/10 23:59:59');
        $sheet->setCellValue('B3', 'true');
        $sheet->setCellValue('C3', '3.14');
        $spreadsheet->addSheet($sheet);
        $spreadsheet->removeSheetByIndex($spreadsheet->getIndex($spreadsheet->getSheetByName('Worksheet')));
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);

        $xlsx = new Xlsx($path);
        $datas = $xlsx->getData($table);

        $this->assertEquals('col1', $datas[0][0]);
        $this->assertEquals('col2', $datas[0][1]);
        $this->assertEquals('col3', $datas[0][2]);
        $this->assertEquals('string', $datas[1][0]);
        $this->assertEquals('100', $datas[1][1]);
        $this->assertEquals('2021/10/10', $datas[1][2]);
        $this->assertEquals('2021/10/10 23:59:59', $datas[2][0]);
        $this->assertEquals('true', $datas[2][1]);
        $this->assertEquals('3.14', $datas[2][2]);
    }

    /** @test 一行目のカラム行に空文字列が混じっている場合のテスト */
    public function testGetData2()
    {
        $path = __DIR__."/files/XlsxTest_testGetData2.xlsx";
        $table = "tablez01";

        if(file_exists($path)) unlink($path);
        $spreadsheet = new Spreadsheet();
        $sheet = new Worksheet($spreadsheet, $table);
        $sheet->setCellValue('A1', 'col1');
        $sheet->setCellValue('B1', 'col2');
        $sheet->setCellValue('C1', ' ');
        $sheet->setCellValue('A2', 'string');
        $sheet->setCellValue('B2', '100');
        $sheet->setCellValue('A3', '2021/10/10 23:59:59');
        $sheet->setCellValue('B3', 'true');
        $spreadsheet->addSheet($sheet);
        $spreadsheet->removeSheetByIndex($spreadsheet->getIndex($spreadsheet->getSheetByName('Worksheet')));
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);

        $xlsx = new Xlsx($path);
        $datas = $xlsx->getData($table);

        $this->assertCount(2, $datas[0]);
        $this->assertEquals('col1', $datas[0][0]);
        $this->assertEquals('col2', $datas[0][1]);
        $this->assertEquals('string', $datas[1][0]);
        $this->assertEquals('100', $datas[1][1]);
        $this->assertEquals('2021/10/10 23:59:59', $datas[2][0]);
        $this->assertEquals('true', $datas[2][1]);
    }

    /** @test 一行目のカラム行に空文字列が混じっている場合のテスト */
    public function testGetData3()
    {
        $path = __DIR__."/files/XlsxTest_testGetData3.xlsx";
        $table = "tablez01";

        if(file_exists($path)) unlink($path);
        $spreadsheet = new Spreadsheet();
        $sheet = new Worksheet($spreadsheet, $table);
        $sheet->setCellValue('A1', 'col1');
        $sheet->setCellValue('B1', 'col2');
        $sheet->setCellValue('A2', 'string');
        $sheet->setCellValue('B2', '<null>');
        $sheet->setCellValue('C2', '不正な値');
        $sheet->setCellValue('A3', '2021/10/10 23:59:59');
        $sheet->setCellValue('B3', '');
        $spreadsheet->addSheet($sheet);
        $spreadsheet->removeSheetByIndex($spreadsheet->getIndex($spreadsheet->getSheetByName('Worksheet')));
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);

        $xlsx = new Xlsx($path);
        $datas = $xlsx->getData($table);

        $this->assertCount(2, $datas[0]);
        $this->assertEquals('col1', $datas[0][0]);
        $this->assertEquals('col2', $datas[0][1]);
        $this->assertEquals('string', $datas[1][0]);
        $this->assertNull($datas[1][1]);
        $this->assertEquals('2021/10/10 23:59:59', $datas[2][0]);
        $this->assertEquals('',$datas[2][1]);
    }

}
