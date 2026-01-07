<?php

namespace PHPSpreadsheetDBTest;

use PDO;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPSpreadsheetDB\PHPSpreadsheetDB;
use PHPSpreadsheetDB\Spreadsheet\Xlsx;
use PHPSpreadsheetDB\DB\MariaDB;

class PHPSpreadsheetDB_Xlsx_MariaDB_Test extends TestCase
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
        $this->db = getenv('TEST_MARIA_DB');
        $this->port = getenv('TEST_MARIA_DBPORT');
        $this->host = getenv('TEST_MARIA_DBHOST');
        $this->user = getenv('TEST_MARIA_DBUSER');
        $this->pass = getenv('TEST_MARIA_DBPASS');
    }

    public function testImport(): void
    {
        // prepare schema 
        $schemas = [
            $this->table1 => "CREATE TABLE $this->table1 ("
                . "id int primary key auto_increment,"
                . "int_col int, "
                . "float_col float,"
                . "char_col char(10),"
                . "str_col varchar(100),"
                . "datetime_col datetime "
                . ");",
            $this->table2 => "CREATE TABLE $this->table2 ("
                . "id int primary key auto_increment,"
                . "int_col int, "
                . "float_col float,"
                . "char_col char(10),"
                . "str_col varchar(100),"
                . "datetime_col datetime "
                . ");"
        ];
        $this->mariadb_createSchema($schemas);

        // prepare xlsx file
        $contents = [
            $this->table1 => [
                'columns' => ['id', 'int_col', 'float_col', 'char_col', 'str_col', 'datetime_col'],
                'data' => [
                    [1, 100, 3.14, 'abcde', 'string', '2021/10/10 23:59:59'],
                    [2, '<null>', '<null>', '<null>', '<null>', '<null>']
                ]
            ],
            $this->table2 => [
                'columns' => ['id', 'int_col', 'float_col', 'char_col', 'str_col', 'datetime_col'],
                'data' => [
                    [1, -10000000, -3, 'abcde', 'string', '1970/01/01 00:00:00'],
                    [2, '<null>', '<null>', '<null>', '<null>', '<null>']
                ]
            ]
        ];
        $path = $this->path;
        $this->xlsx_createFile($path, $contents);

        // execute test - import()
        $mariadb = new MariaDB($this->host, $this->port, $this->db, $this->user, $this->pass);
        $xlsx = new Xlsx($path);

        $phpSpreadsheetDB = new PHPSpreadsheetDB($mariadb, $xlsx);
        $phpSpreadsheetDB->import();

        // verify inserted data
        $pdo = $this->mariadb_connect();
        $stmt = $pdo->query("SELECT * FROM $this->table1 ORDER BY id ASC;");
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->assertCount(2, $result);
        $this->assertEquals(100, $result[0]['int_col']);
        $this->assertEquals(3.14, $result[0]['float_col']);
        $this->assertEquals('abcde', $result[0]['char_col']);
        $this->assertEquals('string', $result[0]['str_col']);
        $this->assertEquals('2021-10-10 23:59:59', $result[0]['datetime_col']);
        $this->assertEquals(0, $result[1]['int_col']);
        $this->assertEquals(0.0, $result[1]['float_col']);
        $this->assertNull($result[1]['char_col']);
        $this->assertNull($result[1]['str_col']);
        $this->assertNull($result[1]['datetime_col']);  
        $this->mariadb_close($pdo);

        // cleanup
        $this->mariadb_close($pdo);
        unlink($this->path);
    }

    public function testExport(): void
    {
        // prepare schema and data
        $schemas = [
            $this->table1 => "CREATE TABLE $this->table1 ("
                . "id int primary key auto_increment,"
                . "col11 int, "
                . "col12 varchar(100) "
                . ");",
            $this->table2 => "CREATE TABLE $this->table2 ("
                . "id int primary key auto_increment,"
                . "col21 int, "
                . "col22 varchar(100) "
                . ");"
        ];
        $this->mariadb_createSchema($schemas);

        $pdo = $this->mariadb_connect();
        $pdo->exec("INSERT INTO $this->table1 (id, col11, col12) VALUES (1, 100, 'hoge'), (2, 200, 'fuga');");
        $pdo->exec("INSERT INTO $this->table2 (id, col21, col22) VALUES (1, 1000, 'foo'), (2, 2000, 'var');");
        $this->mariadb_close($pdo);

        // execute test - export()
        $mariadb = new MariaDB($this->host, $this->port, $this->db, $this->user, $this->pass);
        $xlsx = new Xlsx($this->path);

        $phpSpreadsheetDB = new PHPSpreadsheetDB($mariadb, $xlsx);
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
        $this->mariadb_close($pdo);
        unlink($this->path);
    }

    public function testExportByTables(): void
    {
        // prepare schema and data
        $schemas = [
            $this->table1 => "CREATE TABLE $this->table1 ("
                . "id int primary key auto_increment,"
                . "col11 int, "
                . "col12 varchar(100) "
                . ");",
            $this->table2 => "CREATE TABLE $this->table2 ("
                . "id int primary key auto_increment,"
                . "col21 int, "
                . "col22 varchar(100) "
                . ");"
        ];
        $this->mariadb_createSchema($schemas);

        $pdo = $this->mariadb_connect();
        $pdo->exec("INSERT INTO $this->table1 (id, col11, col12) VALUES (1, 100, 'hoge'), (2, 200, 'fuga');");
        $pdo->exec("INSERT INTO $this->table2 (id, col21, col22) VALUES (1, 1000, 'foo'), (2, 2000, 'var');");
        $this->mariadb_close($pdo);

        // execute test - exportByTables()
        $mariadb = new MariaDB($this->host, $this->port, $this->db, $this->user, $this->pass);
        $xlsx = new Xlsx($this->path);

        $phpSpreadsheetDB = new PHPSpreadsheetDB($mariadb, $xlsx);
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
        $this->mariadb_close($pdo);
        unlink($this->path);
    }
}
