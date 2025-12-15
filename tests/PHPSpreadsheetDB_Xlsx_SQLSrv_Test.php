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

class PHPSpreadsheetDB_Xlsx_SQLSrv_Test extends TestCase
{
    const COLUMNS_STR = \PHPSpreadsheetDB\Spreadsheet\Xlsx::COLUMNS_STR;

    const DATA_STR = \PHPSpreadsheetDB\Spreadsheet\Xlsx::DATA_STR;

    /** @test */
    public function testImport()
    {
        // prepare schema 
        $schemas = [
            'TESTTB01' => "CREATE TABLE TESTTB01 (id integer identity(1,1) NOT NULL PRIMARY kEY, int_col integer,"
                           ."float_col float, char_col nchar(10), str_col nvarchar(100),datetime_col datetime2);",
            'TESTTB02' => "CREATE TABLE TESTTB02 (id integer identity(1,1) NOT NULL PRIMARY kEY, int_col integer,"
                           ."float_col float, char_col nchar(10), str_col nvarchar(100),datetime_col datetime2);",
        ];
        $this->sqlsrv_createSchema($schemas);

        // prepare xlsx file
        $contents = [
            'TESTTB01' => [
                'columns' => ['id', 'int_col', 'float_col', 'char_col', 'str_col', 'datetime_col'],
                'data' => [
                    [1, 100, 3.14, 'abcde', 'string', '2021/10/10 23:59:59'],
                    [2, '<null>', '<null>', '<null>', '<null>', '<null>']
                ]
            ],
            'TESTTB02' => [
                'columns' => ['id', 'int_col', 'float_col', 'char_col', 'str_col', 'datetime_col'],
                'data' => [
                    [1, -10000000, -3, 'abcde', 'string', '1970/01/01 00:00:00'],
                    [2, '<null>', '<null>', '<null>', '<null>', '<null>']
                ]
            ]
        ];
        $path = self::TEMPDIR."testdata.xlsx";
        $this->xlsx_createFile($path, $contents);

        // execute test - import()
        $SQLSrv = new SQLSrv(getenv('TEST_SQLSRV_DBHOST'), getenv('TEST_SQLSRV_DBPORT'), getenv('TEST_SQLSRV_DB'), getenv('TEST_SQLSRV_DBUSER'), getenv('TEST_SQLSRV_DBPASS'));
        $xlsx = new Xlsx($path);

        $phpSpreadsheetDB = new PHPSpreadsheetDB($SQLSrv, $xlsx);
        $phpSpreadsheetDB->import();

        // verify inserted data
        $pdo = $this->sqlsrv_connect();
        $result = $pdo->query("SELECT * FROM TESTTB01 ORDER BY id ASC;")->fetchAll(PDO::FETCH_ASSOC);
        $this->assertCount(2, $result);
        $this->assertSame('1', $result[0]['id']);
        $this->assertSame('100', $result[0]['int_col']);
        $this->assertSame('3.1400000000000001', $result[0]['float_col']);
        $this->assertSame('abcde     ', $result[0]['char_col']);
        $this->assertSame('string', $result[0]['str_col']);
        $this->assertSame('2021-10-10 23:59:59.0000000', $result[0]['datetime_col']);
        $this->assertSame('2', $result[1]['id']);
        $this->assertNull($result[1]['int_col']);
        $this->assertNull($result[1]['float_col']);
        $this->assertNull($result[1]['char_col']);
        $this->assertNull($result[1]['str_col']);
        $this->assertNull($result[1]['datetime_col']);  

        // cleanup
        $this->sqlsrv_close($pdo);
        unlink($path);
    }
}
