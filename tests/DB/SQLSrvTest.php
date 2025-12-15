<?php

namespace PHPSpreadsheetDBTest\DB;

use PHPSpreadsheetDB\DB\DB;
use PHPSpreadsheetDB\DB\SQLSrv;
use PHPSpreadsheetDB\PHPSpreadsheetDBException;
use PDO;
use PDOException;


class SQLSrvTest extends TestCase
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

        $this->db = getenv('TEST_SQLSRV_DB');
        $this->port = getenv('TEST_SQLSRV_DBPORT');
        $this->host = getenv('TEST_SQLSRV_DBHOST');
        $this->user = getenv('TEST_SQLSRV_DBUSER');
        $this->pass = getenv('TEST_SQLSRV_DBPASS');
    }

    /** @test */
    public function testConenection()
    {
        $db = new SQLSrv($this->host, $this->port, $this->db, $this->user, $this->pass);

        $this->assertInstanceOf(DB::class, $db);
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
        $this->sqlsrv_createSchema($schemas);

        // prepare data
        $pdo = $this->sqlsrv_connect();
        $pdo->exec("INSERT INTO ".self::TESTTB." (id, name) VALUES (1, 'Alice'), (2, 'Bob')");
        
        $count = $pdo->query("SELECT COUNT(*) FROM ".self::TESTTB)->fetchColumn();
        $this->assertEquals(2, $count);
        $this->sqlsrv_close($pdo);

        // Execute Test - deleteData()
        $db = new SQLSrv($this->host, $this->port,$this->db, $this->user, $this->pass);
        $db->deleteData(self::TESTTB);

        // Verify deletion
        $pdo = $this->sqlsrv_connect();
        $count = $pdo->query("SELECT COUNT(*) FROM ".self::TESTTB)->fetchColumn();
        $this->assertEquals(0, $count);

        // clean up
        $this->sqlsrv_close($pdo);
    }

    /** @test */
    public function testDeleteData_存在しないテーブル()
    {
        // Delete Table If Exists
        $pdo = $this->sqlsrv_connect();
        $pdo->exec("DROP TABLE IF EXISTS ".self::TESTTB);
        $this->sqlsrv_close($pdo);

        // Expect Exception
        $this->expectException(PHPSpreadsheetDBException::class);
        $this->expectExceptionMessage("Delete Data Failed. Table:".self::TESTTB.", Message:SQLSTATE[42S02]: [Microsoft][ODBC Driver 18 for SQL Server][SQL Server]オブジェクト名 '".self::TESTTB."' が無効です。");

        // Execute deleteData test
        $db = new SQLSrv($this->host, $this->port,$this->db, $this->user, $this->pass);
        $db->deleteData(self::TESTTB);
    }

    public function testInsertData_テスト無し_IDENTITYの確認()
    {
        // prepare pdo
        $pdo = $this->sqlsrv_connect();

        // Prepare Test Table
        $pdo->exec("DROP TABLE IF EXISTS ".self::TESTTB);
        $pdo->exec("CREATE TABLE ".self::TESTTB." (
            id integer PRIMARY KEY,
            name VARCHAR(100)
        )");
        $pdo->exec("INSERT INTO ".self::TESTTB." (id, name) VALUES (1, 'Alice'), (2, 'Bob')");

        $ident = $pdo->query("SELECT IDENT_CURRENT('".self::TESTTB."')")->fetchAll()[0][0];
        print_r($ident);
        $this->assertSame(null, $ident);

        // Prepare Test Table
        $pdo->exec("DROP TABLE IF EXISTS ".self::TESTTB);
        $pdo->exec("CREATE TABLE ".self::TESTTB." (
            id INT IDENTITY(1,1) PRIMARY KEY,
            name VARCHAR(100)
        )");
        $pdo->exec("INSERT INTO ".self::TESTTB." (name) VALUES ('Alice'), ('Bob')");

        $ident = $pdo->query("SELECT IDENT_CURRENT('".self::TESTTB."')")->fetchAll()[0][0];
        print_r($ident);
        $this->assertSame('2', $ident);

        // clean up
        $this->sqlsrv_close($pdo);
    }
    
    /** @test */
    public function testInsertData_通常パターン()
    {
        // prepare schema
        $schemas = [
            self::TESTTB => "CREATE TABLE ".self::TESTTB." (
                id INT PRIMARY KEY,
                name NVARCHAR(100)
            )"
        ];
        $this->sqlsrv_createSchema($schemas);

        // prepare data
        $columns = ['id', 'name'];
        $data = [
            [1, 'Alice'],
            [2, 'Bob'],
        ];

        // Execute Test - insertData
        $db = new SQLSrv($this->host, $this->port,$this->db, $this->user, $this->pass);
        $db->insertData(self::TESTTB, $columns, $data);

        // Verify insertion
        $pdo = $this->sqlsrv_connect();
        $result = $pdo->query("SELECT * FROM ".self::TESTTB)->fetchAll(PDO::FETCH_ASSOC);

        $this->assertCount(2, $result);
        $this->assertEquals(['id' => 1, 'name' => 'Alice'], $result[0]);
        $this->assertEquals(['id' => 2, 'name' => 'Bob'], $result[1]);
    }

    /** @test */
    public function testInsertData_Identityがある場合()
    {
        // prepare schema
        $schemas = [
            self::TESTTB => "CREATE TABLE ".self::TESTTB." (
                id INT IDENTITY(1,1) PRIMARY KEY,
                name NVARCHAR(100)
            )"
        ];
        $this->sqlsrv_createSchema($schemas);

        // prepare data
        $columns = ['id', 'name'];
        $data = [
            [10, 'Alice'],
            [20, 'Bob'],
        ];

        // Insert data using the method
        $db = new SQLSrv($this->host, $this->port,$this->db, $this->user, $this->pass);
        $db->insertData(self::TESTTB, $columns, $data);

        // Verify insertion
        $pdo = $this->sqlsrv_connect();
        $result = $pdo->query("SELECT * FROM ".self::TESTTB)->fetchAll(PDO::FETCH_ASSOC);

        $this->assertCount(2, $result);
        $this->assertEquals(['id' => 10, 'name' => 'Alice'], $result[0]);
        $this->assertEquals(['id' => 20, 'name' => 'Bob'], $result[1]);

        // clean up
        $this->sqlsrv_close($pdo);
    }

    /** @test */
    public function testInsertData_種々の型_NULLの確認()
    {
        // prepare schema
        $schemas = [
            self::TESTTB => "CREATE TABLE ".self::TESTTB." (
                id INT IDENTITY(1,1) PRIMARY KEY,
                name NVARCHAR(100),
                age INTEGER,
                created_at DATETIME2
            )"
        ];
        $this->sqlsrv_createSchema($schemas);

        // prepare data
        $columns = ['id', 'name', 'age', 'created_at'];
        $data = [
            [10, 'Alice', null, '2024-01-01 10:00:00'],
            [20, 'Bob', 30, null],
            [30, null, null, null],
        ];

        // Insert data using the method
        $db = new SQLSrv($this->host, $this->port,$this->db, $this->user, $this->pass);
        $db->insertData(self::TESTTB, $columns, $data);

        // Verify insertion
        $pdo = $this->sqlsrv_connect();
        $result = $pdo->query("SELECT * FROM ".self::TESTTB)->fetchAll(PDO::FETCH_ASSOC);

        $this->assertCount(3, $result);
        $this->assertEquals(['id' => 10, 'name' => 'Alice', 'age' => null, 'created_at' => '2024-01-01 10:00:00.0000000'], $result[0]);
        $this->assertEquals(['id' => 20, 'name' => 'Bob', 'age' => 30, 'created_at' => null], $result[1]);
        $this->assertEquals(['id' => 30, 'name' => null, 'age' => null, 'created_at' => null], $result[2]);

        // clean up
        $this->sqlsrv_connect($pdo);
    }

    /** @test */
    public function testInsertData_一意制約違反()
    {
        // prepare schema
        $schemas = [
            self::TESTTB => "CREATE TABLE ".self::TESTTB." (
                id INT IDENTITY(1,1) NOT NULL,
                name NVARCHAR(100),
                CONSTRAINT PK_TESTTB PRIMARY KEY (id)
            )"
        ];
        $this->sqlsrv_createSchema($schemas);

        // prepare data
        $columns = ['id', 'name'];
        $data = [
            [1, 'Alice'],
            [1, 'Bob'], // Duplicate ID to trigger unique violation
        ];

        $this->expectException(PHPSpreadsheetDBException::class);
        $msg = "Invalid Data. Table:".self::TESTTB." Line:2, Message:SQLSTATE[23000]: [Microsoft][ODBC Driver 18 for SQL Server][SQL Server]制約 'PK_TESTTB' の PRIMARY KEY 違反。オブジェクト 'dbo.".self::TESTTB."' には重複するキーを挿入できません。重複するキーの値は (1) です。";
        $this->expectExceptionMessage($msg);

        // Insert data using the method
        $db = new SQLSrv($this->host, $this->port,$this->db, $this->user, $this->pass);
        $db->insertData(self::TESTTB, $columns, $data);
    }

    /** @test */
    public function testInsertData_数値型()
    {
        // prepare schema
        $schemas = [
            self::TESTTB => "CREATE TABLE ".self::TESTTB." (
                id INT IDENTITY(1,1) PRIMARY KEY,
                tinyint_col TINYINT,
                smallint_col SMALLINT,
                int_col INT,
                bigint_col BIGINT,
                bit_col BIT,
                decimal_col DECIMAL(5,2),
                numeric_col NUMERIC(5,2)
            )"
        ];
        $this->sqlsrv_createSchema($schemas);

        // prepare data
        $columns = ['id', 'tinyint_col', 'smallint_col', 'int_col', 'bigint_col', 'bit_col', 'decimal_col', 'numeric_col'];
        $data = [
            [1, 0, -32768, -2147483648, -9223372036854775807, 0, -999.99, -999.99],
            [2, 255, 32767, 2147483647, 9223372036854775807, 1, 999.99, 999.99],
            [3, null, null, null, null, null, null, null],
        ];

        // Excute test - insertData
        $db = new SQLSrv($this->host, $this->port,$this->db, $this->user, $this->pass);
        $db->insertData(self::TESTTB, $columns, $data);

        // Verify insertion
        $pdo = $this->sqlsrv_connect();
        $result = $pdo->query("SELECT * FROM ".self::TESTTB)->fetchAll(PDO::FETCH_ASSOC);

        $this->assertCount(3, $result);
        $this->assertSame('1', $result[0]['id']);
        $this->assertSame('0', $result[0]['tinyint_col']);
        $this->assertSame('-32768', $result[0]['smallint_col']);
        $this->assertSame('-2147483648', $result[0]['int_col']);
        $this->assertSame('-9223372036854775807', $result[0]['bigint_col']);
        $this->assertSame('0', $result[0]['bit_col']);
        $this->assertSame('-999.99', $result[0]['decimal_col']);
        $this->assertSame('-999.99', $result[0]['numeric_col']);
        $this->assertSame('2', $result[1]['id']);
        $this->assertSame('255', $result[1]['tinyint_col']);
        $this->assertSame('32767', $result[1]['smallint_col']);
        $this->assertSame('2147483647', $result[1]['int_col']);
        $this->assertSame('9223372036854775807', $result[1]['bigint_col']);
        $this->assertSame('1', $result[1]['bit_col']);
        $this->assertSame('999.99', $result[1]['decimal_col']);
        $this->assertSame('999.99', $result[1]['numeric_col']);
        $this->assertSame('3', $result[2]['id']);
        $this->assertSame(null, $result[2]['tinyint_col']);
        $this->assertSame(null, $result[2]['smallint_col']);
        $this->assertSame(null, $result[2]['int_col']);
        $this->assertSame(null, $result[2]['bigint_col']);
        $this->assertSame(null, $result[2]['bit_col']);
        $this->assertSame(null, $result[2]['decimal_col']);
        $this->assertSame(null, $result[2]['numeric_col']);
    }
}