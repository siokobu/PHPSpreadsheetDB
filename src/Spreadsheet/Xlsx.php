<?php

namespace PHPSpreadsheetDB\Spreadsheet;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PHPSpreadsheetDB\PHPSpreadsheetDBException;
use PHPSpreadsheetDB\Spreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Exception;

class Xlsx implements Spreadsheet
{
    private $spreadsheet;

    private $path;

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function __construct($path)
    {
        if (file_exists($path)) {
            $this->spreadsheet = IOFactory::load($path);
        } else {
            $this->spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($this->spreadsheet))->save($path);
        }
        $this->path = $path;
    }

    /**
     * @throws PHPSpreadsheetDBException
     */
    public function deleteAllSheets()
    {
        try {
            $sheetNames = $this->spreadsheet->getSheetNames();
            foreach ($sheetNames as $sheetName) {
                $sheetIndex = $this->spreadsheet->getIndex($this->spreadsheet->getSheetByName($sheetName));
                $this->spreadsheet->removeSheetByIndex($sheetIndex);
            }
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($this->spreadsheet);
            $writer->save($this->path);
        } catch (\Exception $e) {
            throw new PHPSpreadsheetDBException($e);
        }
    }

    public function createSheet($table, $columns)
    {
        $sheet = new Worksheet($this->spreadsheet, $table);
        $rowarray = array();
        foreach($columns as $column)
        {
            array_push($rowarray, $column['Name']);
        }
        $sheet->fromArray($rowarray, NULL, 'A1');

        $this->spreadsheet->addSheet($sheet, 0);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($this->spreadsheet);
        $writer->save($this->path);

    }

    /**
     * @throws Exception
     */
    public function setTableDatas($tableName, $datas)
    {
        $sheet = $this->spreadsheet->getSheetByName($tableName);

        $columns = array();
        for ($i = 1; $i <= Coordinate::columnIndexFromString($sheet->getHighestColumn()); $i++) {
            array_push($columns, $sheet->getCellByColumnAndRow($i, 1)->getValue());
        }
        $j = 2;
        foreach($datas as $data) {
            for($k = 0; $k < count($columns); $k++) {
                $sheet->setCellValueByColumnAndRow($k+1, $j, $data[$columns[$k]]);
            }
            $j++;
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($this->spreadsheet);
        $writer->save($this->path);
    }

    /**
     * @return array Extract All Worksheet Names.
     * @throws Exception
     */
    public function getAllTables(): array
    {
        // 戻り値を配列で定義
        $result = array();

        // 対象となるExcelのすべてのシート名を取得
        for($i=0; $i < $this->spreadsheet->getSheetCount(); $i++) {
            $sheet = $this->spreadsheet->getSheet($i);
            array_push($result, $sheet->getTitle());
        }

        return $result;
    }

    /**
     * @throws Exception
     */
    public function getDatasFromTable($tableName)
    {
        $sheet = $this->spreadsheet->getSheetByName($tableName);
        $rows = array();
        for($i = 1; $i <= $sheet->getHighestRow(); $i++) {
            $columns = array();
            for ($j = 1; $j <= Coordinate::columnIndexFromString($sheet->getHighestColumn()); $j++) {
                array_push($columns, $sheet->getCellByColumnAndRow($j, $i)->getValue());
            }
            array_push($rows, $columns);
        }

        return $rows;
    }
}