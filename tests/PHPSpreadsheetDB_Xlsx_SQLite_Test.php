<?php

namespace PHPSpreadsheetDBTest;

use PDO;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPSpreadsheetDB\PHPSpreadsheetDB;
use PHPSpreadsheetDB\Spreadsheet\Xlsx;
use PHPSpreadsheetDB\DB\Postgres;
use PHPSpreadsheetDB\DB\SQLite;

class PHPSpreadsheetDB_Xlsx_SQLite_Test extends TestCase
{
    private $columnsStr = self::COLUMNS_STR;

    private $dataStr = self::DATA_STR;

    private $table1 = 'TESTTB01';

    private $table2 = 'TESTTB02';

    private $path = self::TEMPDIR."testdata.xlsx";

    private $sqlFile;

    public function setUp(): void
    {
        parent::setUp();

        $this->sqlFile = self::TEMPDIR . getenv('TEST_SQLITE_FILENAME');
    }

    public function testImport(): void
    {
        // prepare schema 
        $schemas = [
            $this->table1 => "CREATE TABLE $this->table1 (id INTEGER PRIMARY KEY, numeric_col NUMERIC, integer_col INTEGER, real_col REAL, text_col text);",
            $this->table2 => "CREATE TABLE $this->table2 (id INTEGER PRIMARY KEY, numeric_col NUMERIC, integer_col INTEGER, real_col REAL, text_col text);",
        ];
        $this->sqlite_createSchema($schemas);

        // prepare xlsx file
        $contents = [
            $this->table1 => [
                'columns' => ['id', 'numeric_col', 'integer_col', 'real_col', 'text_col'],
                'data' => [
                    [1, -99999.99, -9999999999, -9999999.999, 'テキストテキストテキスト'],
                    [2, 99999.99, 9999999999, 9999999.999, 'テキストテキストテキスト'],
                ]
            ],
            $this->table2 => [
                'columns' => ['id', 'numeric_col', 'integer_col', 'real_col', 'text_col'],
                'data' => [
                    [1, 9999999, 0, 0, ''],
                    [2, '<null>', '<null>', '<null>', '<null>', '<null>'],
                ]
            ],
        ];
        $this->xlsx_createFile($this->path, $contents);

        // execute test - import()
        $sqlite = new SQLite($this->sqlFile);
        $xlsx = new Xlsx($this->path);
        $phpSpreadsheetDB = new PHPSpreadsheetDB($sqlite, $xlsx);
        $phpSpreadsheetDB->import();

        // verify results
        $pdo = $this->sqlite_connect();
        $result = $pdo->query("SELECT * FROM $this->table1 ORDER BY id ASC;")->fetchAll(PDO::FETCH_ASSOC);
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

        $result = $pdo->query("SELECT * FROM $this->table2 ORDER BY id ASC;")->fetchAll(PDO::FETCH_ASSOC);
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
        unlink($this->sqlFile);
        unlink($this->path);
    }

    public function testExport(): void
    {
        // prepare schema and data
        $schemas = [
            $this->table1 => "CREATE TABLE $this->table1 ("
                . "id INTEGER PRIMARY KEY,"
                . "col11 INTEGER, "
                . "col12 TEXT "
                . ");",
            $this->table2 => "CREATE TABLE $this->table2 ("
                . "id INTEGER PRIMARY KEY,"
                . "col21 INTEGER, "
                . "col22 TEXT "
                . ");"
        ];
        $this->sqlite_createSchema($schemas);

        $pdo = $this->sqlite_connect();
        $pdo->exec("INSERT INTO $this->table1 (id, col11, col12) VALUES (1, 100, 'hoge'), (2, 200, 'fuga');");
        $pdo->exec("INSERT INTO $this->table2 (id, col21, col22) VALUES (1, 1000, 'foo'), (2, 2000, 'var');");
        $this->sqlite_close($pdo);

        // execute test - export()
        $sqlite = new SQLite($this->sqlFile);
        $xlsx = new Xlsx($this->path);

        $phpSpreadsheetDB = new PHPSpreadsheetDB($sqlite, $xlsx);
        $sql1 = "SELECT id, col12 FROM $this->table1 ORDER BY id ASC;";
        $sql2 = "SELECT id, col21 FROM $this->table2 ORDER BY id ASC;";
        $phpSpreadsheetDB->export([$this->table1 => $sql1, $this->table2 => $sql2]);

        $sheet = IOFactory::load($this->path)->getSheetByName($this->table1);

        $this->assertEquals($sheet->getCell('A1')->getValue(), $this->columnsStr);
        $this->assertEquals($sheet->getCell('B1')->getValue(), 'id');
        $this->assertEquals($sheet->getCell('C1')->getValue(), 'col12');
        $this->assertEquals($sheet->getCell('A2')->getValue(), $this->dataStr);
        $this->assertEquals($sheet->getCell('B2')->getValue(), "");
        $this->assertEquals($sheet->getCell('C2')->getValue(), "");
        $this->assertEquals($sheet->getCell('A3')->getValue(), "");
        $this->assertEquals($sheet->getCell('B3')->getValue(), '1');
        $this->assertEquals($sheet->getCell('C3')->getValue(), 'hoge');
        $this->assertEquals($sheet->getCell('A4')->getValue(), "");
        $this->assertEquals($sheet->getCell('B4')->getValue(), '2');
        $this->assertEquals($sheet->getCell('C4')->getValue(), 'fuga');

        $sheet = IOFactory::load($this->path)->getSheetByName($this->table2);

        $this->assertEquals($sheet->getCell('A1')->getValue(), $this->columnsStr);
        $this->assertEquals($sheet->getCell('B1')->getValue(), 'id');
        $this->assertEquals($sheet->getCell('C1')->getValue(), 'col21');
        $this->assertEquals($sheet->getCell('A2')->getValue(), $this->dataStr);
        $this->assertEquals($sheet->getCell('B2')->getValue(), "");
        $this->assertEquals($sheet->getCell('C2')->getValue(), "");
        $this->assertEquals($sheet->getCell('A3')->getValue(), "");
        $this->assertEquals($sheet->getCell('B3')->getValue(), '1');
        $this->assertEquals($sheet->getCell('C3')->getValue(), '1000');
        $this->assertEquals($sheet->getCell('A4')->getValue(), "");
        $this->assertEquals($sheet->getCell('B4')->getValue(), '2');
        $this->assertEquals($sheet->getCell('C4')->getValue(), '2000');

        // cleanup
        $this->sqlite_close($pdo);
        unlink($this->sqlFile);
        unlink($this->path);
    }

    public function testExportByTables(): void
    {
        // prepare schema and data
        $schemas = [
            $this->table1 => "CREATE TABLE $this->table1 ("
                . "id INTEGER PRIMARY KEY,"
                . "col11 INTEGER, "
                . "col12 TEXT "
                . ");",
            $this->table2 => "CREATE TABLE $this->table2 ("
                . "id INTEGER PRIMARY KEY,"
                . "col21 INTEGER, "
                . "col22 TEXT "
                . ");"
        ];
        $this->sqlite_createSchema($schemas);

        $pdo = $this->sqlite_connect();
        $pdo->exec("INSERT INTO $this->table1 (id, col11, col12) VALUES (1, 100, 'hoge'), (2, 200, 'fuga');");
        $pdo->exec("INSERT INTO $this->table2 (id, col21, col22) VALUES (1, 1000, 'foo'), (2, 2000, 'var');");
        $this->sqlite_close($pdo);

        // execute test - exportByTables()
        $sqlite = new SQLite($this->sqlFile);
        $xlsx = new Xlsx($this->path);

        $phpSpreadsheetDB = new PHPSpreadsheetDB($sqlite, $xlsx);
        $phpSpreadsheetDB->exportByTables($this->table1, $this->table2);

        // verify exported data
        $sheet = IOFactory::load($this->path)->getSheetByName($this->table1);

        $this->assertEquals($sheet->getCell('A1')->getValue(), $this->columnsStr);
        $this->assertEquals($sheet->getCell('B1')->getValue(), 'id');
        $this->assertEquals($sheet->getCell('C1')->getValue(), 'col11');
        $this->assertEquals($sheet->getCell('D1')->getValue(), 'col12');
        $this->assertEquals($sheet->getCell('A2')->getValue(), $this->dataStr);
        $this->assertEquals($sheet->getCell('B2')->getValue(), "");
        $this->assertEquals($sheet->getCell('C2')->getValue(), "");
        $this->assertEquals($sheet->getCell('D2')->getValue(), "");
        $this->assertEquals($sheet->getCell('A3')->getValue(), "");
        $this->assertEquals($sheet->getCell('B3')->getValue(), '1');
        $this->assertEquals($sheet->getCell('C3')->getValue(), '100');
        $this->assertEquals($sheet->getCell('D3')->getValue(), 'hoge');
        $this->assertEquals($sheet->getCell('A4')->getValue(), "");
        $this->assertEquals($sheet->getCell('B4')->getValue(), '2');
        $this->assertEquals($sheet->getCell('C4')->getValue(), '200');
        $this->assertEquals($sheet->getCell('D4')->getValue(), 'fuga');

        $sheet = IOFactory::load($this->path)->getSheetByName($this->table2);

        $this->assertEquals($sheet->getCell('A1')->getValue(), $this->columnsStr);
        $this->assertEquals($sheet->getCell('B1')->getValue(), 'id');
        $this->assertEquals($sheet->getCell('C1')->getValue(), 'col21');
        $this->assertEquals($sheet->getCell('D1')->getValue(), 'col22');
        $this->assertEquals($sheet->getCell('A2')->getValue(), $this->dataStr);
        $this->assertEquals($sheet->getCell('B2')->getValue(), "");
        $this->assertEquals($sheet->getCell('C2')->getValue(), "");
        $this->assertEquals($sheet->getCell('D2')->getValue(), "");
        $this->assertEquals($sheet->getCell('A3')->getValue(), "");
        $this->assertEquals($sheet->getCell('B3')->getValue(), '1');
        $this->assertEquals($sheet->getCell('C3')->getValue(), '1000');
        $this->assertEquals($sheet->getCell('D3')->getValue(), 'foo');
        $this->assertEquals($sheet->getCell('A4')->getValue(), "");
        $this->assertEquals($sheet->getCell('B4')->getValue(), '2');
        $this->assertEquals($sheet->getCell('C4')->getValue(), '2000');
        $this->assertEquals($sheet->getCell('D4')->getValue(), 'var');

        // cleanup
        $this->sqlite_close($pdo);
        unlink($this->sqlFile);
        unlink($this->path);
    }
}