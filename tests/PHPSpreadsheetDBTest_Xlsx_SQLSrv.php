<?php

namespace PHPSpreadsheetDBTest;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PHPSpreadsheetDB\DB\SQLSrv;
use PHPSpreadsheetDB\PHPSpreadsheetDB;
use PHPSpreadsheetDB\PHPSpreadsheetDBException;
use PHPSpreadsheetDB\Spreadsheet\Xlsx;
use PDO;
use PDOException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class PHPSpreadsheetDBTest_Xlsx_SQLSrv extends TestCase
{
    private string $database;

    private string $port;

    private string $host;

    private string $user;

    private string $password;

    private ?PDO $pdo;

    /** @test */
    public function testImport()
    {
        $tableName = ['TESTTB01', 'TESTTB02'];
        $testData1 = [
            'columns' => ['primary_key', 'int_col', 'float_col', 'char_col', 'str_col', 'datetime_col'],
            'data' => [
                [1, 100, 3.14, 'abcde', 'string', '2021/10/10 23:59:59'],
                [2, '<null>', '<null>', '<null>', '<null>', '<null>'    ]
            ]
        ];
        $testData2 = [
            'columns' => ['primary_key', 'int_col', 'float_col', 'char_col', 'str_col', 'datetime_col'],
            'data' => [
                [1, -10000000, -3, 'abcde', 'string', '1970/01/01 00:00:00'],
                [2, '<null>', '<null>', '<null>', '<null>', '<null>'    ]
            ]
        ];

        $path = __DIR__.DIRECTORY_SEPARATOR."temp".DIRECTORY_SEPARATOR."/Xlsx_SQLSrv-Import.xlsx";

        if(file_exists($path)) unlink($path);
        $sourceSS = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        $sheet = new Worksheet($sourceSS, $tableName[0]);
        $this->setColumnsRow($sheet, 1, $testData1['columns']);
        foreach ($testData1['data'] as $index => $dataRow) {
            $this->setDataRow($sheet, $index + 2, $dataRow);
        }
        $sourceSS->addSheet($sheet);

        $sheet = new Worksheet($sourceSS, $tableName[1]);
        $this->setColumnsRow($sheet, 1, $testData2['columns']);
        foreach ($testData2['data'] as $index => $dataRow) {
            $this->setDataRow($sheet, $index + 2, $dataRow);
        }
        $sourceSS->addSheet($sheet);

        $sourceSS->removeSheetByIndex($sourceSS->getIndex($sourceSS->getSheetByName('Worksheet')));
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($sourceSS))->save($path);


        /** テスト実行を実施 */
        $SQLSrv = new SQLSrv($this->host, $this->port, $this->database, $this->user, $this->password);
        $xlsx = new Xlsx($path);

        $phpSpreadsheetDB = new PHPSpreadsheetDB($SQLSrv, $xlsx);
        $phpSpreadsheetDB->import();


    }

    // public function testExportToSpreadsheet()
    // {
    //     $db = new SQLSrv($this->serverName, $this->connectionInfo);

    //     $path = __DIR__.DIRECTORY_SEPARATOR."temp".DIRECTORY_SEPARATOR."/Xlsx_SQLSrv-ExportToSpreadsheet.xlsx";

    //     $spreadsheet = new Xlsx($path);

    //     $psdb = new PHPSpreadsheetDB($db, $spreadsheet);

    //     $psdb->exportToSpreadsheet(["testtb01", "TESTTB02"]);

    //     $this->assertTrue(true);
    // }

    /**
     * テーブル削除用SQLを生成する
     * @param string $tableName テーブル名
     * @return string テーブル削除用SQL
     */
    private function getDropStmt(string $tableName): string
    {
        return "DROP TABLE IF EXISTS ".$tableName.";";
    }

    /**
     * テーブル作成用SQLを生成する．本クラスではカラムの詳細まで調べない（DBドライバのテストで実施）ためテーブルは１種類のみ
     * @param string $tableName テーブル名
     * @return string テーブル作成用SQL
     */
    private function getCreateStmt(string $tableName): string
    {
        return "CREATE TABLE " . $tableName . " ("
            . "primary_key integer NOT NULL PRIMARY kEY,"
            . "int_col integer, "
            . "float_col float,"
            . "char_col nchar(10),"
            . "str_col nvarchar(100),"
            . "datetime_col datetime2 "
            . ");";
    }

    public function setColumnsRow(Worksheet $sheet, int $rowNumber, array $columns): void
    {
        $sheet->setCellValue('A'.$rowNumber, 'columns');
        for ($i = 0; $i <= count($columns); $i++) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($i + 2).$rowNumber, $columns[$i]);
        } 
    }

    public function setDataRow(Worksheet $sheet, int $rowNumber, array $data): void
    {
        for ($i = 0; $i <= count($data); $i++) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($i + 2).$rowNumber, $data[$i]);
        } 
    }

    public function connect(): void
    {
        $this->database = getenv('TEST_SQLSRV_DB');
        $this->port = getenv('TEST_SQLSRV_DBPORT');
        $this->host = getenv('TEST_SQLSRV_DBHOST');
        $this->user = getenv('TEST_SQLSRV_DBUSER');
        $this->password = getenv('TEST_SQLSRV_DBPASS');

        $this->pdo = new PDO(
            'sqlsrv:server='.$this->host.','.$this->port.';Database='.$this->database.';TrustServerCertificate=true',
            $this->user,
            $this->password
        );
        $this->pdo->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_UTF8);
    }

    public function close(): void
    {   
        $this->pdo = null;
    }
}
