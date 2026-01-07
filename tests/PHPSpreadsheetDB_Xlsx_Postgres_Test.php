<?php

namespace PHPSpreadsheetDBTest;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PHPSpreadsheetDB\PHPSpreadsheetDB;
use PHPSpreadsheetDB\Spreadsheet\Xlsx;
use PDO;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PHPSpreadsheetDB\DB\Postgres;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PHPSpreadsheetDB_Xlsx_Postgres_Test extends TestCase
{
    private $columnsStr = self::COLUMNS_STR;

    private $dataStr = self::DATA_STR;

    private $table1 = 'TESTTB01';

    private $table2 = 'TESTTB02';

    private $path = self::TEMPDIR."testdata.xlsx";

    private string $db;

    private string $port;

    private string $host;

    private string $user;

    private string $pass;

    public function setUp(): void
    {
        // Call parent method
        parent::setUp();
        
        // get environment variable
        $this->db = getenv('TEST_PG_DB');
        $this->port = getenv('TEST_PG_DBPORT');
        $this->host = getenv('TEST_PG_DBHOST');
        $this->user = getenv('TEST_PG_DBUSER');
        $this->pass = getenv('TEST_PG_DBPASS');
    }

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

    public function testExport(): void
    {
        // prepare schema and data
        $schemas = [
            $this->table1 => "CREATE TABLE $this->table1 ("
                . "id serial PRIMARY kEY,"
                . "col11 integer, "
                . "col12 varchar(100) "
                . ");",
            $this->table2 => "CREATE TABLE $this->table2 ("
                . "id serial PRIMARY kEY,"
                . "col21 integer, "
                . "col22 varchar(100) "
                . ");"
        ];
        $this->pgsql_createSchema($schemas);

        $pdo = $this->pgsql_connect();
        $pdo->exec("INSERT INTO $this->table1 (id, col11, col12) VALUES (1, 100, 'hoge'), (2, 200, 'fuga');");
        $pdo->exec("INSERT INTO $this->table2 (id, col21, col22) VALUES (1, 1000, 'foo'), (2, 2000, 'var');");
        $this->pgsql_close($pdo);

        // execute test - export()
        $postgres = new Postgres($this->host, $this->port, $this->db, $this->user, $this->pass);
        $xlsx = new Xlsx($this->path);

        $phpSpreadsheetDB = new PHPSpreadsheetDB($postgres, $xlsx);
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
        unlink($this->path);
    }

    public function testExportByTables(): void
    {
        // prepare schema and data
        $schemas = [
            $this->table1 => "CREATE TABLE $this->table1 ("
                . "id serial PRIMARY kEY,"
                . "col11 integer, "
                . "col12 varchar(100) "
                . ");",
            $this->table2 => "CREATE TABLE $this->table2 ("
                . "id serial PRIMARY kEY,"
                . "col21 integer, "
                . "col22 varchar(100) "
                . ");"
        ];
        $this->pgsql_createSchema($schemas);

        $pdo = $this->pgsql_connect();
        $pdo->exec("INSERT INTO $this->table1 (id, col11, col12) VALUES (1, 100, 'hoge'), (2, 200, 'fuga');");
        $pdo->exec("INSERT INTO $this->table2 (id, col21, col22) VALUES (1, 1000, 'foo'), (2, 2000, 'var');");
        $this->pgsql_close($pdo);

        // execute test - exportByTables()
        $postgres = new Postgres($this->host, $this->port, $this->db, $this->user, $this->pass);
        $xlsx = new Xlsx($this->path);

        $phpSpreadsheetDB = new PHPSpreadsheetDB($postgres, $xlsx);
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
        unlink($this->path);
    }
}
