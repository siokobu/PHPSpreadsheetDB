<?php

namespace PHPSpreadsheetDBTest\DB;

use PDO;
use PHPSpreadsheetDB\DB\DB;
use PHPSpreadsheetDB\DB\MariaDB;
use PHPSpreadsheetDB\PHPSpreadsheetDBException;

class MariaDBTest extends TestCase
{
    const TESTTB = "testtb";

    private string $db;

    private string $port;

    private string $host;

    private string $user;

    private string $pass;

    public function setUp(): void
    {
        parent::setUp();
        
        $this->db = getenv('TEST_MARIA_DB');
        $this->port = getenv('TEST_MARIA_DBPORT');
        $this->host = getenv('TEST_MARIA_DBHOST');
        $this->user = getenv('TEST_MARIA_DBUSER');
        $this->pass = getenv('TEST_MARIA_DBPASS');
    }

    /** @test */
    public function testConenection()
    {
        $db = new MariaDB($this->host, $this->port, $this->db, $this->user, $this->pass);

        $this->assertInstanceOf(DB::class, $db);
    }

    /** @test */
    public function testDeleteData()
    {
        $schemas = [
            self::TESTTB => "CREATE TABLE ".self::TESTTB." (
                id integer PRIMARY KEY,
                name VARCHAR(100)
            )"
        ];
        $this->mariadb_createSchema($schemas);
        
        $pdo = $this->mariadb_connect();
        $pdo->exec("INSERT INTO ".self::TESTTB." (id, name) VALUES (1, 'Alice'), (2, 'Bob')");

        // Verify Insert Records
        $stmt = $pdo->query("SELECT COUNT(*) FROM ".self::TESTTB);
        $count = $stmt->fetchColumn();

        $this->assertEquals(2, $count);
        $this->mariadb_close($pdo);

        // Execute test - deleteData()
        $db = new MariaDB($this->host, $this->port, $this->db, $this->user, $this->pass);
        $db->deleteData(self::TESTTB);

        // Verify deletion
        $pdo = $this->mariadb_connect();
        $count = $pdo->query("SELECT COUNT(*) FROM ".self::TESTTB)->fetchColumn();

        $this->assertEquals(0, $count);

        $this->mariadb_close($pdo);
    }

    /**
     * @test
     */
    public function testInsertData_通常パターン(): void
    {
        // prepare schema
        $schemas = [
            self::TESTTB => "CREATE TABLE ".self::TESTTB." (
                id integer PRIMARY KEY,
                name VARCHAR(100)
            )"
        ];
        $this->mariadb_createSchema($schemas);

        // prepare data
        $columns = ['id', 'name'];
        $data = [
            [1, 'Alice'],
            [2, 'Bob'],
        ];

        // execute test - insertData()
        $db = new MariaDB($this->host, $this->port, $this->db, $this->user, $this->pass); 
        $db->insertData(self::TESTTB, $columns, $data);

        // verify results
        $pdo = $this->mariadb_connect();
        $stmt = $pdo->query("SELECT * FROM ".self::TESTTB." ORDER BY id ASC;");
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->assertCount(2, $result);
        $this->assertSame(1, $result[0]['id']);
        $this->assertSame('Alice', $result[0]['name']);
        $this->assertSame(2, $result[1]['id']);
        $this->assertSame('Bob', $result[1]['name']);

        // cleanup
        $this->mariadb_close($pdo);
    }

    /** @test */
    public function testInsertData_autoincrementの対応(): void
    {
        // prepare schema
        $schemas = [
            self::TESTTB => "CREATE TABLE ".self::TESTTB." (
                id integer PRIMARY KEY auto_increment,
                name VARCHAR(10)
            )",
        ];
        $this->mariadb_createSchema($schemas);

        // prepare data
        $columns = ['id', 'name'];
        $data = [
            [10, 'Alice'],
            [20, 'Bob'],
        ];

        // execute test - insertData()
        $db = new MariaDB($this->host, $this->port, $this->db, $this->user, $this->pass); 
        $db->insertData(self::TESTTB, $columns, $data);

        // verify results
        $pdo = $this->mariadb_connect();
        $stmt = $pdo->query("SELECT * FROM ".self::TESTTB." ORDER BY id ASC;");
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->assertCount(2, $result);
        $this->assertSame(10, $result[0]['id']);
        $this->assertSame('Alice', $result[0]['name']);
        $this->assertSame(20, $result[1]['id']);
        $this->assertSame('Bob', $result[1]['name']);

        // cleanup
        $this->mariadb_close($pdo);
    }

    /** @test */
    public function testInsertData_日付関連カラム(): void
    {
        // prepare schema
        $schemas = [
            self::TESTTB => "CREATE TABLE ".self::TESTTB." (
                id integer PRIMARY KEY auto_increment,
                date_col DATE,
                time_col TIME,
                datetime_col DATETIME,
                timestamp_col TIMESTAMP
            )",
        ];
        $this->mariadb_createSchema($schemas);

        // prepare data
        $columns = ['date_col', 'time_col', 'datetime_col', 'timestamp_col'];
        $data = [
            ['1970-01-01', '00:00:00', '1970-01-01 00:00:00', '1970-01-01 09:00:01'],
            ['2099-12-31', '23:59:59', '2099-12-31 23:59:59', '2099-12-31 23:59:59'],
            [null,null,null,null],
        ];

        // execute test - insertData()
        $db = new MariaDB($this->host, $this->port, $this->db, $this->user, $this->pass); 
        $db->insertData(self::TESTTB, $columns, $data);

        // verify results
        $pdo = $this->mariadb_connect();
        $stmt = $pdo->query("SELECT * FROM ".self::TESTTB." ORDER BY id ASC;");
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->assertCount(3, $result);
        $this->assertSame('1970-01-01'         , $result[0]['date_col']);
        $this->assertSame('00:00:00'           , $result[0]['time_col']);
        $this->assertSame('1970-01-01 00:00:00', $result[0]['datetime_col']);
        $this->assertSame('1970-01-01 09:00:01', $result[0]['timestamp_col']);
        $this->assertSame('2099-12-31'         , $result[1]['date_col']);
        $this->assertSame('23:59:59'           , $result[1]['time_col']);
        $this->assertSame('2099-12-31 23:59:59', $result[1]['datetime_col']);
        $this->assertSame('2099-12-31 23:59:59', $result[1]['timestamp_col']);
        $this->assertNull($result[2]['date_col']);
        $this->assertNull($result[2]['time_col']);
        $this->assertNull($result[2]['datetime_col']);
        $this->assertNull($result[2]['timestamp']);

        // cleanup
        $this->mariadb_close($pdo);
    }

}
