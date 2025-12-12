<?php

namespace PHPSpreadsheetDBTest\DB;

use PHPSpreadsheetDB\DB\DB;
use PHPSpreadsheetDB\DB\SQLSrv;
use PHPSpreadsheetDB\PHPSpreadsheetDBException;
use PDO;
use PDOException;


class SQLSrvTest extends TestCase
{
    private string $database;

    private string $port;

    private string $host;

    private string $user;

    private string $password;

    private PDO $pdo;

    public function setUp(): void
    {
        $this->database = getenv('TEST_SQLSRV_DB');
        $this->port = getenv('TEST_SQLSRV_DBPORT');
        $this->host = getenv('TEST_SQLSRV_DBHOST');
        $this->user = getenv('TEST_SQLSRV_DBUSER');
        $this->password = getenv('TEST_SQLSRV_DBPASS');

        $this->pdo = new PDO(
            'sqlsrv:server='.$this->host.','.$this->port.';Database='.$this->database.';TrustServerCertificate=true',
            $this->user,
            $this->password
        );
    }

    /** @test */
    public function testConenection()
    {
        $db = new SQLSrv(
            $this->host,
            $this->port,
            $this->database,
            $this->user,
            $this->password
        );

        $this->assertInstanceOf(DB::class, $db);
    }

    public function testDeleteData()
    {
        $tableName = 'test_table';

        $this->pdo->exec("DROP TABLE IF EXISTS {$tableName}");
        $this->pdo->exec("CREATE TABLE {$tableName} (
            id integer PRIMARY KEY,
            name VARCHAR(100)
        )");
        $this->pdo->exec("INSERT INTO {$tableName} (id, name) VALUES (1, 'Alice'), (2, 'Bob')");

        // Verify Insert Records
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM {$tableName}");
        $count = $stmt->fetchColumn();

        $this->assertEquals(2, $count);

        $db = new SQLSrv(
            $this->host,
            $this->port,
            $this->database,
            $this->user,
            $this->password
        );

        // Delete data using the method
        $db->deleteData($tableName);

        // Verify deletion
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM {$tableName}");
        $count = $stmt->fetchColumn();

        $this->assertEquals(0, $count);
    }

    /** @test */
    public function testDeleteData_存在しないテーブル()
    {
        $tableName = 'non_existent_table';

        $db = new SQLSrv(
            $this->host,
            $this->port,
            $this->database,
            $this->user,
            $this->password
        );

        $this->expectException(PHPSpreadsheetDBException::class);
        $this->expectExceptionMessage("Delete Data Failed. Table:non_existent_table, Message:SQLSTATE[42S02]: [Microsoft][ODBC Driver 18 for SQL Server][SQL Server]オブジェクト名 'non_existent_table' が無効です。");

        $this->pdo->exec("DROP TABLE IF EXISTS {$tableName}");
        $db->deleteData($tableName);
    }

    
    /** @test */
    public function testInsertData_通常パターン()
    {
        $tableName = 'test_table_insert';

        $this->pdo->exec("DROP TABLE IF EXISTS {$tableName}");
        $this->pdo->exec("CREATE TABLE {$tableName} (
            id integer PRIMARY KEY,
            name NVARCHAR(100)
        )");

        $db = new SQLSrv(
            $this->host,
            $this->port,
            $this->database,
            $this->user,
            $this->password
        );

        $columns = ['id', 'name'];
        $data = [
            [1, 'Alice'],
            [2, 'Bob'],
        ];

        // Insert data using the method
        $db->insertData($tableName, $columns, $data);

        // Verify insertion
        $stmt = $this->pdo->query("SELECT * FROM {$tableName}");
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->assertCount(2, $result);
        $this->assertEquals(['id' => 1, 'name' => 'Alice'], $result[0]);
        $this->assertEquals(['id' => 2, 'name' => 'Bob'], $result[1]);
    }

    /** @test */
    public function testInsertData_Serialがある場合()
    {
        $tableName = 'test_table_serial';

        $this->pdo->exec("DROP TABLE IF EXISTS {$tableName}");
        $this->pdo->exec("CREATE TABLE {$tableName} (
            id INT IDENTITY(1,1) PRIMARY KEY,
            name NVARCHAR(100)
        )");

        $db = new SQLSrv(
            $this->host,
            $this->port,
            $this->database,
            $this->user,
            $this->password
        );

        $columns = ['id', 'name'];
        $data = [
            [10, 'Alice'],
            [20, 'Bob'],
        ];

        // Insert data using the method
        $db->insertData($tableName, $columns, $data);

        // Verify insertion
        $stmt = $this->pdo->query("SELECT * FROM {$tableName}");
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->assertCount(2, $result);
        $this->assertEquals(['id' => 10, 'name' => 'Alice'], $result[0]);
        $this->assertEquals(['id' => 20, 'name' => 'Bob'], $result[1]);
    }

    /** @test */
    public function testInsertData_種々の型_NULLの確認()
    {
        $tableName = 'test_table_various_types';

        $this->pdo->exec("DROP TABLE IF EXISTS {$tableName}");
        $this->pdo->exec("CREATE TABLE {$tableName} (
            id INT IDENTITY(1,1) PRIMARY KEY,
            name NVARCHAR(100),
            age INTEGER,
            created_at DATETIME2
        )");

        $db = new SQLSrv(
            $this->host,
            $this->port,
            $this->database,
            $this->user,
            $this->password
        );

        $columns = ['id', 'name', 'age', 'created_at'];
        $data = [
            [10, 'Alice', null, '2024-01-01 10:00:00'],
            [20, 'Bob', 30, null],
           [30, null, null, null],
        ];

        // Insert data using the method
        $db->insertData($tableName, $columns, $data);

        // Verify insertion
        $stmt = $this->pdo->query("SELECT * FROM {$tableName}");
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->assertCount(3, $result);
        $this->assertEquals(['id' => 10, 'name' => 'Alice', 'age' => null, 'created_at' => '2024-01-01 10:00:00.0000000'], $result[0]);
        $this->assertEquals(['id' => 20, 'name' => 'Bob', 'age' => 30, 'created_at' => null], $result[1]);
        $this->assertEquals(['id' => 30, 'name' => null, 'age' => null, 'created_at' => null], $result[2]);
    }

    /** @test */
    public function testInsertData_一意制約違反()
    {

        $tableName = 'test_table_unique_violation';

        $this->pdo->exec("DROP TABLE IF EXISTS {$tableName}");
        $this->pdo->exec("CREATE TABLE {$tableName} (
            id INT IDENTITY(1,1) NOT NULL,
            name NVARCHAR(100),
            CONSTRAINT PK_TESTTB PRIMARY KEY (id)
        )");

        $db = new SQLSrv(
            $this->host,
            $this->port,
            $this->database,
            $this->user,
            $this->password
        );

        $columns = ['id', 'name'];
        $data = [
            [1, 'Alice'],
            [1, 'Bob'], // Duplicate ID to trigger unique violation
        ];

        $this->expectException(PHPSpreadsheetDBException::class);
        $msg = "Invalid Data. Table:test_table_unique_violation Line:2, Message:SQLSTATE[23000]: [Microsoft][ODBC Driver 18 for SQL Server][SQL Server]制約 'PK_TESTTB' の PRIMARY KEY 違反。オブジェクト 'dbo.test_table_unique_violation' には重複するキーを挿入できません。重複するキーの値は (1) です。";
        $this->expectExceptionMessage($msg);

        // Insert data using the method
        $db->insertData($tableName, $columns, $data);
    }
}