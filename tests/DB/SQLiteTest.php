<?php

namespace PHPSpreadsheetDBTest\DB;

use PDO;
use PHPSpreadsheetDB\DB\SQLite;

class SQLiteTest extends TestCase
{
    private string $filename;

    private string $createSql = "CREATE TABLE TESTTB(id INTEGER PRIMARY KEY, int_col INTEGER, real_col REAL, text_col text)";

    private PDO $pdo;

    public function setUp(): void
    {
        parent::setUp();
        $this->filename =  getEnv("TEST_SQLITE_FILENAME");

    }
    public function testDeleteData()
    {
        $tableName = 'TESTTB';

        $pdo = new PDO('sqlite:'.$this->filename);
        $pdo->exec("DROP TABLE IF EXISTS ".$tableName.";");
        $pdo->exec("CREATE TABLE ".$tableName." (ID integer, COL text);");
        $pdo->exec("INSERT INTO ".$tableName." (ID, COL) VALUES (1, 'a');");

        $sqlite = new SQLite($this->filename);
        $sqlite->deleteData($tableName);

        $stmt = $pdo->query("SELECT ID AS CNT FROM ".$tableName.";");
        $this->assertFalse($stmt->fetch());
    }

    public function testInsertData()
    {
        $columns = ['ID', 'NUMCOL', 'INTCOL', 'REALCOL', 'TEXTCOL'];
        $data = [
            [1, 1, 1, 0.1, 'a'],
            [2, 2, 2, 2, 'あ'],
            [null, null, null, null, null],
            [null, '', '', '', ''],
        ];
        $tableName = "TESTTB";

        $pdo = new PDO('sqlite:'.$this->filename);
        $pdo->exec("DROP TABLE IF EXISTS ".$tableName.";");
        $pdo->exec("CREATE TABLE ".$tableName."(ID INTEGER PRIMARY KEY, NUMCOL NUMERIC, INTCOL INTEGER, REALCOL REAL, TEXTCOL text);");

        $sqlite = new SQLite($this->filename);
        $sqlite->insertData($tableName, $columns, $data);

        $stmt = $pdo->query("SELECT * FROM ".$tableName.";");
        $row = $stmt->fetch();
        $this->assertSame(1, $row['ID']);
        $this->assertSame(1, $row['NUMCOL']);
        $this->assertSame(1, $row['INTCOL']);
        $this->assertSame(0.1, $row['REALCOL']);
        $this->assertSame('a', $row['TEXTCOL']);
        $row = $stmt->fetch();
        $this->assertSame(2, $row['ID']);
        $this->assertSame(2, $row['NUMCOL']);
        $this->assertSame(2, $row['INTCOL']);
        $this->assertSame(2.0, $row['REALCOL']);
        $this->assertSame('あ', $row['TEXTCOL']);
        $row = $stmt->fetch();
        $this->assertSame(3, $row['ID']);
        $this->assertSame(null, $row['NUMCOL']);
        $this->assertSame(null, $row['INTCOL']);
        $this->assertSame(null, $row['REALCOL']);
        $this->assertSame(null, $row['TEXTCOL']);
        $row = $stmt->fetch();
        $this->assertSame(4, $row['ID']);
        $this->assertSame('', $row['NUMCOL']);
        $this->assertSame('', $row['INTCOL']);
        $this->assertSame('', $row['REALCOL']);
        $this->assertSame('', $row['TEXTCOL']);

        unlink($this->filename);
    }
}