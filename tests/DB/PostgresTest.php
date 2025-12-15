<?php

namespace PHPSpreadsheetDBTest\DB;

use PDO;
use PHPSpreadsheetDB\DB\DB;
use PHPSpreadsheetDB\DB\Postgres;
use PHPSpreadsheetDB\PHPSpreadsheetDBException;

class PostgresTest extends TestCase
{
    const TESTTB = "testtb";

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

    /** @test */
    public function testConenection()
    {
        $db = new Postgres($this->host, $this->port, $this->db, $this->user, $this->pass);

        $this->assertInstanceOf(DB::class, $db);
    }

    /** @test */
    public function testDeleteData()
    {
        // prepare data
        $schemas = [
            self::TESTTB => "CREATE TABLE ".self::TESTTB." (
                id integer PRIMARY KEY,
                name VARCHAR(100)
            )"
        ];
        $this->pgsql_createSchema($schemas);

        // Insert Data
        $pdo = $this->pgsql_connect();
        $pdo->exec("INSERT INTO ".self::TESTTB." (id, name) VALUES (1, 'Alice'), (2, 'Bob')");

        $count = $pdo->query("SELECT COUNT(*) FROM ".self::TESTTB)->fetchColumn();
        $this->assertEquals(2, $count);
        $this->pgsql_close($pdo);

        // Execute test - deleteData()
        $db = new Postgres($this->host, $this->port, $this->db, $this->user, $this->pass);
        $db->deleteData(self::TESTTB);

        // Verify deletion
        $pdo = $this->pgsql_connect();
        $count = $pdo->query("SELECT COUNT(*) FROM ".self::TESTTB)->fetchColumn();
        $this->assertEquals(0, $count);

        // clean up
        $this->pgsql_close($pdo);
    }

    /** @test */
    public function testDeleteData_存在しないテーブル()
    {
        // Delete Table If Exists
        $pdo = $this->pgsql_connect();
        $pdo->exec("DROP TABLE IF EXISTS ".self::TESTTB);

        // Expect Exception
        $this->expectException(PHPSpreadsheetDBException::class);
        $this->expectExceptionMessage('Delete Data Failed. Table:'.self::TESTTB.', Message:SQLSTATE[42P01]: Undefined table: 7 ERROR:  リレーション"'.self::TESTTB.'"は存在しません LINE 1: DELETE FROM '.self::TESTTB.'                     ^');

        // Execute Test - deleteData()
        $db = new Postgres($this->host, $this->port, $this->db, $this->user, $this->pass);
        $db->deleteData(self::TESTTB);

        // clean up
        $this->pgsql_close($pdo);
    }

    /** @test */
    public function testInsertData_通常パターン()
    {
        $schemas = [
            self::TESTTB => "CREATE TABLE ".self::TESTTB." (
                id integer PRIMARY KEY,
                name VARCHAR(100)
            )"
        ];
        $this->pgsql_createSchema($schemas);

        // prepare data
        $columns = ['id', 'name'];
        $data = [
            [1, 'Alice'],
            [2, 'Bob'],
        ];

        // Execute Test -insertData()
        $db = new Postgres($this->host, $this->port, $this->db, $this->user, $this->pass);
        $db->insertData(self::TESTTB, $columns, $data);

                // verify results
        $pdo = $this->pgsql_connect();
        $stmt = $pdo->query("SELECT * FROM ".self::TESTTB." ORDER BY id ASC;");
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->assertCount(2, $result);
        $this->assertSame(1, $result[0]['id']);
        $this->assertSame('Alice', $result[0]['name']);
        $this->assertSame(2, $result[1]['id']);
        $this->assertSame('Bob', $result[1]['name']);

        // cleanup
        $this->pgsql_close($pdo);

    }

    /** @test */
    public function testInsertData_Serialがある場合()
    {
        $schemas = [
            self::TESTTB => "CREATE TABLE ".self::TESTTB." (
                id integer PRIMARY KEY,
                name VARCHAR(100)
            )"
        ];
        $this->pgsql_createSchema($schemas);

        // prepare data
        $columns = ['id', 'name'];
        $data = [
            [10, 'Alice'],
            [20, 'Bob'],
        ];

        // Execute Test -insertData()
        $db = new Postgres($this->host, $this->port, $this->db, $this->user, $this->pass);
        $db->insertData(self::TESTTB, $columns, $data);

        // verify results
        $pdo = $this->pgsql_connect();
        $result = $pdo->query("SELECT * FROM ".self::TESTTB." ORDER BY id ASC;")->fetchAll(PDO::FETCH_ASSOC);

        $this->assertCount(2, $result);
        $this->assertSame(10     , $result[0]['id']);
        $this->assertSame('Alice', $result[0]['name']);
        $this->assertSame(20     , $result[1]['id']);
        $this->assertSame('Bob'  , $result[1]['name']);

        // cleanup
        $this->pgsql_close($pdo);
    }

    /** @test */
    public function testInsertData_種々の型_NULLの確認()
    {
        // prepare schema
        $schemas = [
            self::TESTTB =>"CREATE TABLE ". self::TESTTB . "(
                id SERIAL PRIMARY KEY,
                name VARCHAR(100),
                age INTEGER,
                created_at TIMESTAMP
            )"
        ];
        $this->pgsql_createSchema($schemas);

        // Data to insert
        $columns = ['id', 'name', 'age', 'created_at'];
        $data = [
            [10, 'Alice', null, '2024-01-01 10:00:00'],
            [20, 'Bob', 30, null],
            [30, null, null, null],
        ];

        // Insert data using the method
        $db = new Postgres($this->host, $this->port, $this->db, $this->user, $this->pass);
        $db->insertData(self::TESTTB, $columns, $data);

        // Verify insertion
        $pdo = $this->pgsql_connect();
        $result = $pdo->query("SELECT * FROM " . self::TESTTB)->fetchAll(PDO::FETCH_ASSOC);

        $this->assertCount(3, $result);
        $this->assertEquals(['id' => 10, 'name' => 'Alice', 'age' => null, 'created_at' => '2024-01-01 10:00:00'], $result[0]);
        $this->assertEquals(['id' => 20, 'name' => 'Bob', 'age' => 30, 'created_at' => null], $result[1]);
        $this->assertEquals(['id' => 30, 'name' => null, 'age' => null, 'created_at' => null], $result[2]);

        // clean up 
        $this->pgsql_close($pdo);
    }

    /** @test */
    public function testInsertData_一意制約違反()
    {
        // prepare schema
        $schemas = [
            self::TESTTB => "CREATE TABLE ". self::TESTTB . " (
                id INTEGER PRIMARY KEY,
                name VARCHAR(100)
            )"
        ];
        $this->pgsql_createSchema($schemas);

        // Data to insert
        $columns = ['id', 'name'];
        $data = [
            [1, 'Alice'],
            [1, 'Bob'], // Duplicate ID to trigger unique violation
        ];

        // Expect exception
        $this->expectException(PHPSpreadsheetDBException::class);
        $this->expectExceptionMessage('Invalid Data. Table:'.self::TESTTB.' Line:2, Message:SQLSTATE[23505]: Unique violation: 7 ERROR:  重複したキー値は一意性制約"testtb_pkey"違反となります DETAIL:  キー (id)=(1) はすでに存在します。');

        // Insert data using the method
        $db = new Postgres($this->host, $this->port, $this->db, $this->user, $this->pass);
        $db->insertData(self::TESTTB, $columns, $data);

        // cleanup
        $pdo = $this->pgsql_coonnect();
        $pdo->exec("DROP TABLE IF EXISTS " . self::TESTTB);
        $this->pgsql_close($pdo);
    }

    /**
     * @test
     * @Todo エラーとなる値の検証、任意の精度を持つ数(numeric,decimal)の値の検証
     */
    public function testInsertData_数値データ型(): void
    {
        // prepare schema
        $schemas = [
            self::TESTTB => "CREATE TABLE " . self::TESTTB . " (
                id SERIAL PRIMARY KEY,
                smallint_col smallint,
                integer_col integer,
                bigint_col bigint,
                decimal_col decimal(10,2),
                numeric_col numeric(10,2),
                real_col real,
                double_precision_col double precision
            )"
        ];
        $this->pgsql_createSchema($schemas);

        // Data to insert
        $columns = ['smallint_col','integer_col','bigint_col','decimal_col','numeric_col','real_col','double_precision_col'];
        $data = [
            [-32768, -2147483648, -9223372036854775807, -99999999.99, -99999999.99, -999999, -999999999999999],
            [32767, 2147483647, 9223372036854775807, 9999.99, 9999.99, 999999, 999999999999999],
        ];

        // Execute Test
        $db = new Postgres($this->host, $this->port, $this->db, $this->user, $this->pass);
        $db->insertData(self::TESTTB, $columns, $data);

        // Verify insertion
        $pdo = $this->pgsql_connect();
        $result = $pdo->query("SELECT * FROM ".self::TESTTB)->fetchAll(PDO::FETCH_ASSOC);

        $this->assertCount(2, $result);
        $this->assertEquals(-32768, $result[0]['smallint_col']);
        $this->assertEquals(-2147483648, $result[0]['integer_col']);
        $this->assertEquals(-9223372036854775807, $result[0]['bigint_col']);
        $this->assertEquals(-99999999.99, $result[0]['decimal_col']);
        $this->assertEquals(-99999999.99, $result[0]['numeric_col']);
        $this->assertEquals(-999999, $result[0]['real_col']);
        $this->assertEquals(-999999999999999, $result[0]['double_precision_col']);
        $this->assertEquals(32767, $result[1]['smallint_col']);
        $this->assertEquals(2147483647, $result[1]['integer_col']);
        $this->assertEquals(9223372036854775807, $result[1]['bigint_col']);
        $this->assertEquals(9999.99, $result[1]['decimal_col']);
        $this->assertEquals(9999.99, $result[1]['numeric_col']);
        $this->assertEquals(999999, $result[1]['real_col']);
        $this->assertEquals(999999999999999, $result[1]['double_precision_col']);

        // cleanup
        $pdo->exec("DROP TABLE IF EXISTS " . self::TESTTB);
        $this->pgsql_close($pdo);
    }

    /**
     * @test
     * @Todo エラーとなる値の検証、任意の精度を持つ数(numeric,decimal)の値の検証
     */
    public function testInsertData_通貨型_文字型(): void
    {
        // prepare schema
        $schemas = [
            self::TESTTB => "CREATE TABLE " . self::TESTTB . " (
                id SERIAL PRIMARY KEY,
                money_col money,
                varchar_col varchar(10),
                char_col char(4),
                text_col text
            )"
        ];
        $this->pgsql_createSchema($schemas);

        // Data to insert
        $columns = ['money_col','varchar_col','char_col','text_col'];
        $data = [
            [-92233720368547758, 'あいうえおあいうえお', 'abcd', 'テキストテキストテキスト'],
            [92233720368547758, null, null, null],
            [10.1, '', '', ''],
        ];

        // Execute Test
        $db = new Postgres($this->host, $this->port, $this->db, $this->user, $this->pass);
        $db->insertData(self::TESTTB, $columns, $data);

        // Verify insertion
        $pdo = $this->pgsql_connect();
        $result = $pdo->query("SELECT * FROM ".self::TESTTB)->fetchAll(PDO::FETCH_ASSOC);

        $this->assertCount(3, $result);
        $this->assertEquals('￥-92,233,720,368,547,758', $result[0]['money_col']);
        $this->assertEquals('あいうえおあいうえお'      , $result[0]['varchar_col']);
        $this->assertEquals('abcd'                    , $result[0]['char_col']);
        $this->assertEquals('テキストテキストテキスト'  , $result[0]['text_col']);
        $this->assertEquals('￥92,233,720,368,547,758', $result[1]['money_col']);
        $this->assertEquals(null, $result[1]['varchar_col']);
        $this->assertEquals(null, $result[1]['char_col']);
        $this->assertEquals(null, $result[1]['text_col']);
        $this->assertEquals('￥10' , $result[2]['money_col']);
        $this->assertEquals(''    , $result[2]['varchar_col']);
        $this->assertEquals('    ', $result[2]['char_col']);
        $this->assertEquals(''    , $result[2]['text_col']);

        // cleanup
        $pdo->exec("DROP TABLE IF EXISTS " . self::TESTTB);
        $this->pgsql_close($pdo);
    }
}