<?php

namespace PHPSpreadsheetDBTest\DB;

use PDO;
use PHPSpreadsheetDB\DB\DB;
use PHPSpreadsheetDB\DB\Postgres;
use PHPSpreadsheetDB\PHPSpreadsheetDBException;


class PostgresTest extends TestCase
{
    private string $database;

    private string $port;

    private string $host;

    private string $user;

    private string $password;

    private PDO $pdo;

    public function setUp(): void
    {
        parent::setUp();
        
        $this->database = getenv('TEST_PG_DB');
        $this->port = getenv('TEST_PG_DBPORT');
        $this->host = getenv('TEST_PG_DBHOST');
        $this->user = getenv('TEST_PG_DBUSER');
        $this->password = getenv('TEST_PG_DBPASS');

        $this->pdo = new PDO(
            'pgsql:host='.$this->host.';port='.$this->port.';dbname='.$this->database,
            $this->user,
            $this->password
        );
    }

    public function testConenection()
    {
        $db = new Postgres(
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

        $db = new Postgres(
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

        $db = new Postgres(
            $this->host,
            $this->port,
            $this->database,
            $this->user,
            $this->password
        );

        $this->expectException(PHPSpreadsheetDBException::class);
        $this->expectExceptionMessage('Delete Data Failed. Table:non_existent_table, Message:SQLSTATE[42P01]: Undefined table: 7 ERROR:  リレーション"non_existent_table"は存在しません LINE 1: DELETE FROM non_existent_table                     ^');

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
            name VARCHAR(100)
        )");

        $db = new Postgres(
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
            id SERIAL PRIMARY KEY,
            name VARCHAR(100)
        )");

        $db = new Postgres(
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
            id SERIAL PRIMARY KEY,
            name VARCHAR(100),
            age INTEGER,
            created_at TIMESTAMP
        )");

        $db = new Postgres(
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
        $this->assertEquals(['id' => 10, 'name' => 'Alice', 'age' => null, 'created_at' => '2024-01-01 10:00:00'], $result[0]);
        $this->assertEquals(['id' => 20, 'name' => 'Bob', 'age' => 30, 'created_at' => null], $result[1]);
        $this->assertEquals(['id' => 30, 'name' => null, 'age' => null, 'created_at' => null], $result[2]);
    }

    /** @test */
    public function testInsertData_一意制約違反()
    {

        $tableName = 'test_table_unique_violation';

        $this->pdo->exec("DROP TABLE IF EXISTS {$tableName}");
        $this->pdo->exec("CREATE TABLE {$tableName} (
            id INTEGER PRIMARY KEY,
            name VARCHAR(100)
        )");

        $db = new Postgres(
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
        $this->expectExceptionMessage('Invalid Data. Table:test_table_unique_violation Line:2, Message:SQLSTATE[23505]: Unique violation: 7 ERROR:  重複したキー値は一意性制約"test_table_unique_violation_pkey"違反となります DETAIL:  キー (id)=(1) はすでに存在します。');

        // Insert data using the method
        $db->insertData($tableName, $columns, $data);
    }
}