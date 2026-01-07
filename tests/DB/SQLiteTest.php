<?php

namespace PHPSpreadsheetDBTest\DB;

use PDO;
use PHPSpreadsheetDB\DB\SQLite;

class SQLiteTest extends TestCase
{
    const TESTTB = "testtb";

    private string $filename;

    private string $createSql = "CREATE TABLE TESTTB(id INTEGER PRIMARY KEY, int_col INTEGER, real_col REAL, text_col text)";

    private PDO $pdo;

    public function setUp(): void
    {
        parent::setUp();

        $this->filename = TestCase::TEMPDIR . getEnv("TEST_SQLITE_FILENAME");
    }

    /** @test */
    public function testDeleteData()
    {
        // prepare schema
        $schemas = [
            self::TESTTB => "CREATE TABLE ".self::TESTTB." (
                id integer PRIMARY KEY,
                name VARCHAR(100)
            )"
        ];
        $this->sqlite_createSchema($schemas);

        // prepare data
        $pdo = $this->sqlite_connect();
        $pdo->exec("INSERT INTO ".self::TESTTB." (id, name) VALUES (1, 'Alice'), (2, 'Bob')");
        $count = $pdo->query("SELECT COUNT(*) FROM ".self::TESTTB)->fetchColumn();
        $this->assertEquals(2, $count);
        $this->sqlite_close($pdo);

        // Execute Test - deleteData()
        $db = new SQLite($this->filename);
        $db->deleteData(self::TESTTB);

        // verify 
        $pdo = $this->sqlite_connect();
        $result = $pdo->query("SELECT ID AS CNT FROM ".self::TESTTB.";")->fetch();
        $this->assertFalse($result);

        // clean up
        $this->sqlite_close($pdo);
        unlink($this->filename);
    }

    /** @test */
    public function testInsertData()
    {
        // prepare schema
        $schemas = [
            self::TESTTB => "CREATE TABLE ".self::TESTTB." (
                id INTEGER PRIMARY KEY,
                numeric_col NUMERIC,
                integer_col INTEGER,
                real_col REAL,
                text_col text);",
        ];
        $this->sqlite_createSchema($schemas);

        $columns = ['id', 'numeric_col', 'integer_col', 'real_col', 'text_col'];
        $data = [
            [1, 1, 1, 0.1, 'a'],
            [2, 2, 2, 2, 'あ'],
            [null, null, null, null, null],
            [null, '', '', '', ''],
        ];

        $sqlite = new SQLite($this->filename);
        $sqlite->insertData(self::TESTTB, $columns, $data);

        $pdo = $this->sqlite_connect();
        $stmt = $pdo->query("SELECT * FROM ".self::TESTTB.";");
        $row = $stmt->fetch();
        $this->assertSame(1, $row['id']);
        $this->assertSame(1, $row['numeric_col']);
        $this->assertSame(1, $row['integer_col']);
        $this->assertSame(0.1, $row['real_col']);
        $this->assertSame('a', $row['text_col']);
        $row = $stmt->fetch();
        $this->assertSame(2, $row['id']);
        $this->assertSame(2, $row['numeric_col']);
        $this->assertSame(2, $row['integer_col']);
        $this->assertSame(2.0, $row['real_col']);
        $this->assertSame('あ', $row['text_col']);
        $row = $stmt->fetch();
        $this->assertSame(3, $row['id']);
        $this->assertSame(null, $row['numeric_col']);
        $this->assertSame(null, $row['integer_col']);
        $this->assertSame(null, $row['real_col']);
        $this->assertSame(null, $row['text_col']);
        $row = $stmt->fetch();
        $this->assertSame(4, $row['id']);
        $this->assertSame('', $row['numeric_col']);
        $this->assertSame('', $row['integer_col']);
        $this->assertSame('', $row['real_col']);
        $this->assertSame('', $row['text_col']);

        // clean up
        $this->sqlite_close($pdo);
        unlink($this->filename);
    }

    /** @test   */
    public function testColumnMeta()
    {
        // prepare schema
        $schemas = [
        self::TESTTB => "CREATE TABLE ".self::TESTTB." (
                id INT PRIMARY KEY,
                name VARCHAR(100)
            )"
        ];
        $this->sqlite_createSchema($schemas);

        // prepare data
        $columns = ['id', 'name'];
        $data = [
            [1, 'Alice'],
            [2, 'Bob'],
        ];
           
        // Execute Test - insertData
        $db = new SQLite($this->filename);
        $db->insertData(self::TESTTB, $columns, $data);
   
        $pdo = $this->sqlite_connect();
        $stmt = $pdo->query("SELECT * FROM ".self::TESTTB);
        for ($i=0; $i<$stmt->columnCount(); $i++) {
            $columns = $stmt->getColumnMeta($i);
            print_r($columns);
        }
        $this->assertTrue(true);
        $this->sqlite_close($pdo);
    }    
}