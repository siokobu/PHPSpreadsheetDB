<?php

namespace PHPSpreadsheetDB\DB;

use PHPSpreadsheetDB\PHPSpreadsheetDBException;
use PDO;
use PDOException;
use PDOStatement;

class Postgres extends DB
{
    /**
     * @var Resource_ DBへのコネクション情報
     */
    private $conn;

    /**
     * PHPSpreadsheetDBで利用するためのDBオブジェクトを生成する
     * @param string $host ホスト名
     * @param string $port ポート番号
     * @param string $dbname データベース名
     * @param string $user ユーザ名
     * @param string $password パスワード
     * @throws PHPSpreadsheetDBException PostgreSQLへ接続できなかった場合にスローする
     */
    public function __construct(
        string $host, 
        string $port = '5432', 
        string $dbname, 
        string $user, 
        string $password)
    {
        $dsn = 'pgsql:dbname='.$dbname.' host='.$host.' port='.$port;
        try {
            $this->conn = new PDO($dsn, $user, $password);
        }catch (PDOException $e){
            throw new PHPSpreadsheetDBException("Connection Error: " . $e->getMessage());
        }
    }

    public function getColumns(string $tableName): array
    {
        throw new PHPSpreadsheetDBException("Not Implemented Yet");
    }   

    public function getTableData(string $tableName): iterable
    {
        throw new PHPSpreadsheetDBException('Not implemented');
    }

    public function deleteData(string $tableNamne): void
    {
        try {
            $this->conn->beginTransaction();
            $this->conn->exec("DELETE FROM " . $tableNamne);
            $this->conn->commit();
        } catch (PDOException $e) {
            $this->conn->rollBack();
            $errmes = "Delete Data Failed. Table:" . $tableNamne . ", Message:" .  str_replace("\n", " ", $e->getMessage());
            throw new PHPSpreadsheetDBException($errmes);
        }
    }

    public function insertData(string $tableName, array $columns, array $data): void
    {
        if(count($data) === 0) return;

        $line = 0;
        try {
            $this->conn->beginTransaction();
            $sql = $this->createPreparedStatement($tableName, $columns);
            $stmt = $this->conn->prepare($sql);
            foreach($data as $row) {
                $line++;
                $stmt->execute($row);
            }
            $this->conn->commit();
        } catch (PDOException $e) {
            $this->conn->rollBack();
            $errmes = "Invalid Data. Table:" . $tableName . " Line:" . $line . ", Message:" .  str_replace("\n", " ", $e->getMessage());
            throw new PHPSpreadsheetDBException($errmes);
        }
    }

}