<?php

namespace PHPSpreadsheetDBTest\Spreadsheet;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use PHPSpreadsheetDB\Spreadsheet\Xlsx;
use PHPUnit\Framework\TestCase;

class XlsxTest extends TestCase
{
    public function testDeleteAllSheets()
    {
        $path = "./XlsxTest_testDeleteAllSheets.xlsx";
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
        $path = "./XlsxTest_testCreateSheet.xlsx";

        $spreadsheet = IOFactory::load($path);
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
        $path = "./TestXlsx3.xlsx";
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

    }

    /** @test */
    public function testGetAllTables()
    {
        $path = "./Xlsx_GetAllTables.xlsx";
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
        $tableNames = $xlsx->getAllTables();

        $this->assertEquals($tables[0], $tableNames[0]);
        $this->assertEquals($tables[1], $tableNames[1]);
        $this->assertEquals($tables[2], $tableNames[2]);
    }

    public function testGetDatasFromTable()
    {
        $path = "./Xlsx_GetDatasFromTable.xlsx";
        $table = "tablez01";

        if(file_exists($path)) unlink($path);
        $spreadsheet = new Spreadsheet();
        $sheet = new Worksheet($spreadsheet, $table);
        $sheet->setCellValue('A1', 'col1');
        $sheet->setCellValue('B1', 'col2');
        $sheet->setCellValue('C1', 'col3');
        $sheet->setCellValue('A2', 'string');
        $sheet->setCellValue('B2', 100);
        $sheet->setCellValue('C2', '2021/10/10');
        $sheet->setCellValue('A3', '2021/10/10 23:59:59');
        $sheet->setCellValue('B3', true);
        $sheet->setCellValue('C3', 3.14);
        $spreadsheet->addSheet($sheet);
        $spreadsheet->removeSheetByIndex($spreadsheet->getIndex($spreadsheet->getSheetByName('Worksheet')));
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);

        $xlsx = new Xlsx($path);
        $datas = $xlsx->getDatasFromTable($table);

        $this->assertEquals('col1', $datas[0][0]);
        $this->assertEquals('col2', $datas[0][1]);
        $this->assertEquals('col3', $datas[0][2]);
        $this->assertEquals('string', $datas[1][0]);
        $this->assertEquals(100, $datas[1][1]);
        $this->assertEquals('2021/10/10', $datas[1][2]);
        $this->assertEquals('2021/10/10 23:59:59', $datas[2][0]);
        $this->assertEquals(true, $datas[2][1]);
        $this->assertEquals(3.14, $datas[2][2]);
    }
}
