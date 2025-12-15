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
    public const COLUMNS_STR = "##columns##";

    public const DATA_STR = "##data##";

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
        // 戻り値用の配列を初期化
        $result = array();

        try {
            // worksheetオブジェクトを取得する
            $sheet = $this->spreadsheet->getSheetByName($tableName);

            // シートの一行目（カラム名行）を利用してカラム数を測定する
            $highestColumn = 1;
            $currentRow = 1;
            $dataMode = false;
            $result['data'] = array();
            while(true) {
                // 1列目の値を指示カラムとして確認する
                $pointerCol = $sheet->getCell("A" . $currentRow)->getValue() ?? "";

                // すべての値をカラム名として戻り値配列に格納する
                if ($pointerCol === self::COLUMNS_STR ) {
                    $currentColumn = 2;
                    $rowData = array();
                    while(true) {
                        $val = $sheet->getCell(Coordinate::stringFromColumnIndex($currentColumn) . $currentRow)->getValue();
                        $val = $val === null ? "" : trim($val);
                        if(strlen($val) === 0) {
                            $highestColumn = $currentColumn - 1;
                            break;
                        } else {
                            array_push($rowData, $val);
                            $currentColumn = $currentColumn + 1;
                        }
                    }
                    $result['columns'] = $rowData;
                }

                // 20行以内にデータモードでない場合エラーとする
                if ($currentRow > 20 && $dataMode === false) {
                    throw new PHPSpreadsheetDBException("Data row not found in first 20 rows.");
                }
                
                // データモードの場合.指示カラムの最初の２文字が"--"の場合、コメント行と判定
                if ($dataMode && substr($pointerCol, 0, 2) !== '--') {
                    $currentColumn = 2;
                    $isData = false;
                    $rowData = array();
                    while($currentColumn <= $highestColumn) {
                        $val = $sheet->getCell(Coordinate::stringFromColumnIndex($currentColumn) . $currentRow)->getValue();
                        $val = $val === null ? "" : trim($val);
                        $val = $val === '<null>' ? null : $val;
                        array_push($rowData, $val);
                        $currentColumn = $currentColumn + 1;
                        strlen($val) > 0 && $isData = true;
                    }
                    if ($isData) {
                        array_push($result['data'], $rowData);
                    } else {
                        break;
                    }
                }

                // データモードに更新する．この行はデータ行としない
                if ($pointerCol === Xlsx::DATA_STR ) {
                    // ヘッダ行が未定義の場合エラーとする
                    if (!isset($result['columns'])) {
                        throw new PHPSpreadsheetDBException("Columns row must be defined before data row.");
                    }
                    $dataMode = true;
                }

                $currentRow = $currentRow + 1;
            }

        } catch(Exception $e) {
            throw new PHPSpreadsheetDBException($e);
        }

        return $result;
    }
}