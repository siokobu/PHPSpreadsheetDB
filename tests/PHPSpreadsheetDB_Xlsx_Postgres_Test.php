<?php

namespace PHPSpreadsheetDBTest;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PHPSpreadsheetDB\PHPSpreadsheetDB;
use PHPSpreadsheetDB\Spreadsheet\Xlsx;
use PDO;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PHPSpreadsheetDB\DB\Postgres;

class PHPSpreadsheetDB_Xlsx_Postgres_Test extends TestCase
{
    const COLUMNS_STR = \PHPSpreadsheetDB\Spreadsheet\Xlsx::COLUMNS_STR;

    const DATA_STR = \PHPSpreadsheetDB\Spreadsheet\Xlsx::DATA_STR;

    public function testImport(): void
    {
        // prepare schema 
        $schemas = [
            'TESTTB01' => "CREATE TABLE TESTTB01 ("
                . "primary_key serial PRIMARY kEY,"
                . "int_col integer, "
                . "float_col float,"
                . "char_col char(10),"
                . "str_col varchar(100),"
                . "datetime_col timestamp "
                . ");",
            'TESTTB02' => "CREATE TABLE TESTTB02 ("
                . "primary_key serial PRIMARY kEY,"
                . "int_col integer, "
                . "float_col float,"
                . "char_col char(10),"
                . "str_col varchar(100),"
                . "datetime_col timestamp "
                . ");"
        ];
        $this->pgsql_createSchema($schemas);

        // prepare xlsx file
        $contents = [
            'TESTTB01' => [
                'columns' => ['primary_key', 'int_col', 'float_col', 'char_col', 'str_col', 'datetime_col'],
                'data' => [
                    [1, 100, 3.14, 'abcde', 'string', '2021/10/10 23:59:59'],
                    [2, '<null>', '<null>', '<null>', '<null>', '<null>']
                ]
            ],
            'TESTTB02' => [
                'columns' => ['primary_key', 'int_col', 'float_col', 'char_col', 'str_col', 'datetime_col'],
                'data' => [
                    [1, -10000000, -3, 'abcde', 'string', '1970/01/01 00:00:00'],
                    [2, '<null>', '<null>', '<null>', '<null>', '<null>']
                ]
            ]
        ];
        $path = self::TEMPDIR."testdata.xlsx";
        $this->xlsx_createFile($path, $contents);

        // execute test - import()
        $postgres = new Postgres(getenv('TEST_PG_DBHOST'), getenv('TEST_PG_DBPORT'), getenv('TEST_PG_DB'), getenv('TEST_PG_DBUSER'), getenv('TEST_PG_DBPASS'));
        $xlsx = new Xlsx($path);

        $phpSpreadsheetDB = new PHPSpreadsheetDB($postgres, $xlsx);
        $phpSpreadsheetDB->import();

        // verify inserted data
        $pdo = $this->pgsql_connect();
        $stmt = $pdo->query("SELECT * FROM TESTTB01 ORDER BY primary_key ASC;");
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->assertCount(2, $result);
        $this->assertEquals(100, $result[0]['int_col']);
        $this->assertEquals(3.14, $result[0]['float_col']);
        $this->assertEquals('abcde     ', $result[0]['char_col']);
        $this->assertEquals('string', $result[0]['str_col']);
        $this->assertEquals('2021-10-10 23:59:59', $result[0]['datetime_col']);
        $this->assertEquals(0, $result[1]['int_col']);
        $this->assertEquals(0.0, $result[1]['float_col']);
        $this->assertNull($result[1]['char_col']);
        $this->assertNull($result[1]['str_col']);
        $this->assertNull($result[1]['datetime_col']);  
        $this->pgsql_close($pdo);

        // cleanup
        unlink($path);
    }
}
