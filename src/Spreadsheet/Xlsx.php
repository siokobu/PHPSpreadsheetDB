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
     * @inheritDoc
     */
    public function getTableNames(): array
    {
        try {
            // 戻り値を配列で定義
            $result = array();

            // 対象となるExcelのすべてのシート名を取得
            for ($i = 0; $i < $this->spreadsheet->getSheetCount(); $i++) {
                $sheet = $this->spreadsheet->getSheet($i);
                array_push($result, $sheet->getTitle());
            }
        } catch(Exception $e) {
            throw new PHPSpreadsheetDBException($e);
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function getData(string $tableName): array
    {
        try {
            // worksheetオブジェクトを取得する
            $sheet = $this->spreadsheet->getSheetByName($tableName);

            // 戻り値用の配列を初期化
            $rows = array();

            // シートの一行目（カラム名行）を利用してカラム数を測定する
            $highestColumn = 1;
            while(true) {
                $val = trim($sheet->getCellByColumnAndRow($highestColumn, 1)->getValue());
                if(strlen($val) == 0) {
                    $highestColumn = $highestColumn - 1;
                    break;
                } else {
                    $highestColumn = $highestColumn + 1;
                }
            }

            // 最終行までデータの取得を繰り返す
            for ($i = 1; $i <= $sheet->getHighestRow(); $i++) {

                // 戻り値の $rows に格納するための $columns 配列を初期化
                $columns = array();

                // 最終列までデータの取得を繰り返し、$columns に格納していく
                for ($j = 1; $j <= $highestColumn; $j++) {
                    $val = $sheet->getCellByColumnAndRow($j, $i)->getValue();
                    if($val == '<null>') $val = null;
                    if(strlen($val) == 0) $val = "";
                    array_push($columns, $val);
                }

                // 作成した １行分の配列 $columns を 戻り値 $rows に格納する
                array_push($rows, $columns);
            }

        } catch(Exception $e) {
            throw new PHPSpreadsheetDBException($e);
        }

        return $rows;
    }
}