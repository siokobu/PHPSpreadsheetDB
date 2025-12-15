<?php

namespace PHPSpreadsheetDBTest;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PHPSpreadsheetDB\PHPSpreadsheetDB;
use PHPSpreadsheetDB\Spreadsheet\Xlsx;
use PDO;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PHPSpreadsheetDB\DB\Postgres;
use PHPSpreadsheetDB\DB\SQLite;

class PHPSpreadsheetDB_Xlsx_SQLite_Test extends TestCase
{
    const COLUMNS_STR = \PHPSpreadsheetDB\Spreadsheet\Xlsx::COLUMNS_STR;

    const DATA_STR = \PHPSpreadsheetDB\Spreadsheet\Xlsx::DATA_STR;

    public function testImport(): void
    {
        // prepare schema 
        $schemas = [
            'TESTTB01' => "CREATE TABLE TESTTB01 (id INTEGER PRIMARY KEY, numeric_col NUMERIC, integer_col INTEGER, real_col REAL, text_col text);",
            'TESTTB02' => "CREATE TABLE TESTTB02 (id INTEGER PRIMARY KEY, numeric_col NUMERIC, integer_col INTEGER, real_col REAL, text_col text);",
        ];
        $this->sqlite_createSchema($schemas);

        // prepare xlsx file
        $contents = [
            'TESTTB01' => [
                'columns' => ['id', 'numeric_col', 'integer_col', 'real_col', 'text_col'],
                'data' => [
                    [1, -99999.99, -9999999999, -9999999.999, 'テキストテキストテキスト'],
                    [2, 99999.99, 9999999999, 9999999.999, 'テキストテキストテキスト'],
                ]
            ],
            'TESTTB02' => [
                'columns' => ['id', 'numeric_col', 'integer_col', 'real_col', 'text_col'],
                'data' => [
                    [1, 9999999, 0, 0, ''],
                    [2, '<null>', '<null>', '<null>', '<null>', '<null>'],
                ]
            ],
        ];
        $path = self::TEMPDIR."testdata.xlsx";
        $this->xlsx_createFile($path, $contents);

        // execute test - import()
        $sqlite = new SQLite(getenv('TEST_SQLITE_FILENAME'));
        $xlsx = new Xlsx($path);
        $phpSpreadsheetDB = new PHPSpreadsheetDB($sqlite, $xlsx);
        $phpSpreadsheetDB->import();

        // verify results
        $pdo = $this->sqlite_connect();
        $result = $pdo->query("SELECT * FROM TESTTB01 ORDER BY id ASC;")->fetchAll(PDO::FETCH_ASSOC);
        $this->assertCount(2, $result);
        $this->assertSame(1, $result[0]['id']);
        $this->assertSame(-99999.99, (float)$result[0]['numeric_col']);
        $this->assertSame(-9999999999, (int)$result[0]['integer_col']);
        $this->assertSame(-9999999.999, (float)$result[0]['real_col']);
        $this->assertSame('テキストテキストテキスト', $result[0]['text_col']);
        $this->assertSame(2, $result[1]['id']);
        $this->assertSame(99999.99, (float)$result[1]['numeric_col']);
        $this->assertSame(9999999999, (int)$result[1]['integer_col']);
        $this->assertSame(9999999.999, (float)$result[1]['real_col']);
        $this->assertSame('テキストテキストテキスト', $result[1]['text_col']);
        $result = $pdo->query("SELECT * FROM TESTTB02 ORDER BY id ASC;")->fetchAll(PDO::FETCH_ASSOC);
        $this->assertCount(2, $result);
        $this->assertSame(1, $result[0]['id']);
        $this->assertSame(9999999.0, (float)$result[0]['numeric_col']);
        $this->assertSame(0, (int)$result[0]['integer_col']);
        $this->assertSame(0.0, (float)$result[0]['real_col']);
        $this->assertSame('', $result[0]['text_col']);
        $this->assertSame(2, $result[1]['id']);
        $this->assertNull($result[1]['numeric_col']);
        $this->assertNull($result[1]['integer_col']);
        $this->assertNull($result[1]['real_col']);
        $this->assertNull($result[1]['text_col']);

        $this->sqlite_close($pdo);
        unlink(getenv('TEST_SQLITE_FILENAME'));
        unlink($path);
    }
}