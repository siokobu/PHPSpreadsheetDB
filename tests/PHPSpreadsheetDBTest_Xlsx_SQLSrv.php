<?php

namespace PHPSpreadsheetDBTest;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PHPSpreadsheetDB\DB\SQLSrv;
use PHPSpreadsheetDB\PHPSpreadsheetDB;
use PHPSpreadsheetDB\PHPSpreadsheetDBException;
use PHPSpreadsheetDB\Spreadsheet\Xlsx;


class PHPSpreadsheetDBTest_Xlsx_SQLSrv extends TestCase
{
    private string $database;

    private string $uid;

    private string $pwd;

    private string $charset;

    private array $connectionInfo;

    private string $serverName;

    public function setUp(): void
    {
        parent::setUp();
        $this->database = $this->getEnv("SQLSRV_DATABASE");
        $this->uid = $this->getEnv("SQLSRV_UID");
        $this->pwd = $this->getEnv("SQLSRV_PWD");
        $this->charset = $this->getEnv("SQLSRV_CHARSET");
        $this->connectionInfo['Database'] = $this->database;
        $this->connectionInfo['UID'] = $this->uid;
        $this->connectionInfo['PWD'] = $this->pwd;
        $this->connectionInfo['CharacterSet'] = $this->charset;
        $this->serverName = $this->getEnv("SQLSRV_HOST");
    }

    /** @test */
    public function testImportFromSpreadsheet()
    {
        $tableName = ['TESTTB01', 'TESTTB02'];
        $testData1 = [
            [
                'primary_key' => '1',
                'int_col' => '100',
                'float_col' => '3.14',
                'char_col' => 'abcde',
                'str_col' => 'string',
                'datetime_col' => '2021/10/10 23:59:59'
            ],
            [
                'primary_key' => '2',
                'int_col' => '<null>',
                'float_col' => '<null>',
                'char_col' => '<null>',
                'str_col' => '<null>',
                'datetime_col' => '<null>'
            ]
        ];

        $testData2 = [
            [
                'primary_key' => '1',
                'int_col' => '-10000000',
                'float_col' => '-3',
                'char_col' => 'abcde',
                'str_col' => 'string',
                'datetime_col' => '1970/01/01 00:00:00'
            ],
            [
                'primary_key' => '2',
                'int_col' => '',
                'float_col' => '',
                'char_col' => '',
                'str_col' => '',
                'datetime_col' => ''
            ]
        ];

        $path = __DIR__.DIRECTORY_SEPARATOR."temp".DIRECTORY_SEPARATOR."/Xlsx_SQLSrv-ImportFromSpreadsheet.xlsx";

        if(file_exists($path)) unlink($path);
        $sourceSS = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        $sheet = new Worksheet($sourceSS, $tableName[0]);
        $sheet->setCellValue('A1', 'primary_key');
        $sheet->setCellValue('B1', 'int_col');
        $sheet->setCellValue('C1', 'float_col');
        $sheet->setCellValue('D1', 'char_col');
        $sheet->setCellValue('E1', 'str_col');
        $sheet->setCellValue('F1', 'datetime_col');
        $sheet->setCellValue('A2', $testData1[0]['primary_key']);
        $sheet->setCellValue('B2', $testData1[0]['int_col']);
        $sheet->setCellValue('C2', $testData1[0]['float_col']);
        $sheet->setCellValue('D2', $testData1[0]['char_col']);
        $sheet->setCellValue('E2', $testData1[0]['str_col']);
        $sheet->setCellValue('F2', $testData1[0]['datetime_col']);
        $sheet->setCellValue('A3', $testData1[1]['primary_key']);
        $sheet->setCellValue('B3', $testData1[1]['int_col']);
        $sheet->setCellValue('C3', $testData1[1]['float_col']);
        $sheet->setCellValue('D3', $testData1[1]['char_col']);
        $sheet->setCellValue('E3', $testData1[1]['str_col']);
        $sheet->setCellValue('F3', $testData1[1]['datetime_col']);
        $sourceSS->addSheet($sheet);

        $sheet = new Worksheet($sourceSS, $tableName[1]);
        $sheet->setCellValue('A1', 'primary_key');
        $sheet->setCellValue('B1', 'int_col');
        $sheet->setCellValue('C1', 'float_col');
        $sheet->setCellValue('D1', 'char_col');
        $sheet->setCellValue('E1', 'str_col');
        $sheet->setCellValue('F1', 'datetime_col');
        $sheet->setCellValue('A2', $testData2[0]['primary_key']);
        $sheet->setCellValue('B2', $testData2[0]['int_col']);
        $sheet->setCellValue('C2', $testData2[0]['float_col']);
        $sheet->setCellValue('D2', $testData2[0]['char_col']);
        $sheet->setCellValue('E2', $testData2[0]['str_col']);
        $sheet->setCellValue('F2', $testData2[0]['datetime_col']);
        $sheet->setCellValue('A3', $testData2[1]['primary_key']);
        $sheet->setCellValue('B3', $testData2[1]['int_col']);
        $sheet->setCellValue('C3', $testData2[1]['float_col']);
        $sheet->setCellValue('D3', $testData2[1]['char_col']);
        $sheet->setCellValue('E3', $testData2[1]['str_col']);
        $sheet->setCellValue('F3', $testData2[1]['datetime_col']);
        $sourceSS->addSheet($sheet);

        $sourceSS->removeSheetByIndex($sourceSS->getIndex($sourceSS->getSheetByName('Worksheet')));
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($sourceSS))->save($path);

        $conn = sqlsrv_connect($this->serverName, $this->connectionInfo);

        $stmt = sqlsrv_query($conn, $this->getDropStmt($tableName[0]));
        if($stmt == false) { die( print_r( sqlsrv_errors(), true));   }

        $stmt = sqlsrv_query($conn, $this->getCreateStmt($tableName[0]));
        if($stmt == false) { die( print_r( sqlsrv_errors(), true));   }

        $stmt = sqlsrv_query($conn, $this->getDropStmt($tableName[1]));
        if($stmt == false) { die( print_r( sqlsrv_errors(), true));   }

        $stmt = sqlsrv_query($conn, $this->getCreateStmt($tableName[1]));
        if($stmt == false) { die( print_r( sqlsrv_errors(), true));   }

        sqlsrv_close($conn);

        /** テスト実行を実施 */
        $SQLSrv = new SQLSrv($this->serverName, $this->connectionInfo);
        $xlsx = new Xlsx($path);

        $phpSpreadsheetDB = new PHPSpreadsheetDB($SQLSrv, $xlsx);
        $phpSpreadsheetDB->importFromSpreadsheet();

        $conn = sqlsrv_connect($this->serverName, $this->connectionInfo);

        $stmt = sqlsrv_query($conn, "SELECT * FROM ".$tableName[0].";");
        if($stmt == false) { die( print_r( sqlsrv_errors(), true));   }

        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        $this->assertEquals($testData1[0]['primary_key'], $row['primary_key']);
        $this->assertEquals($testData1[0]['int_col'], $row['int_col']);
        $this->assertEquals($testData1[0]['float_col'], $row['float_col']);
        $this->assertEquals(str_pad($testData1[0]['char_col'], 10), $row['char_col']);
        $this->assertEquals($testData1[0]['str_col'], $row['str_col']);
        $this->assertEquals(date_format(new \DateTime($testData1[0]['datetime_col']), 'YmdHis'), date_format($row['datetime_col'], 'YmdHis'));

        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        $this->assertEquals($testData1[1]['primary_key'], $row['primary_key']);
        $this->assertNull($row['int_col']);
        $this->assertNull($row['float_col']);
        $this->assertNull($row['char_col']);
        $this->assertNull($row['str_col']);
        $this->assertNull($row['datetime_col']);

        $stmt = sqlsrv_query($conn, "SELECT * FROM ".$tableName[1].";");
        if($stmt == false) { die( print_r( sqlsrv_errors(), true));   }

        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        $this->assertEquals($testData2[0]['primary_key'], $row['primary_key']);
        $this->assertEquals($testData2[0]['int_col'], $row['int_col']);
        $this->assertEquals($testData2[0]['float_col'], $row['float_col']);
        $this->assertEquals(str_pad($testData2[0]['char_col'], 10), $row['char_col']);
        $this->assertEquals($testData2[0]['str_col'], $row['str_col']);
        $this->assertEquals(date_format(new \DateTime($testData2[0]['datetime_col']), 'YmdHis'), date_format($row['datetime_col'], 'YmdHis'));

        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        $this->assertEquals($testData2[1]['primary_key'], $row['primary_key']);
        $this->assertEquals(0, $row['int_col']);
        $this->assertEquals(0, $row['float_col']);
        $this->assertEquals('          ', $row['char_col']);
        $this->assertEquals('', $row['str_col']);
        $this->assertEquals(date_format(new \DateTime('19000101000000'), 'YmdHis'), date_format($row['datetime_col'], 'YmdHis'));

        sqlsrv_close($conn);

    }

    public function testExportToSpreadsheet()
    {
        $db = new SQLSrv($this->serverName, $this->connectionInfo);

        $path = __DIR__.DIRECTORY_SEPARATOR."temp".DIRECTORY_SEPARATOR."/Xlsx_SQLSrv-ExportToSpreadsheet.xlsx";

        $spreadsheet = new Xlsx($path);

        $psdb = new PHPSpreadsheetDB($db, $spreadsheet);

        $psdb->exportToSpreadsheet(["testtb01", "TESTTB02"]);

        $this->assertTrue(true);
    }

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
}
