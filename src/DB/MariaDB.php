<?php

namespace PHPSpreadsheetDB\DB;

use PHPSpreadsheetDB\PHPSpreadsheetDBException;
use PDO;
use PDOException;

class MariaDB extends DB
{
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
        string $port = '3306', 
        string $dbname, 
        string $user, 
        string $password)
    {
        $dsn = "mysql:dbname=$dbname;host=$host;port=$port";
        try {
            $this->pdo = new PDO($dsn, $user, $password);
            $this->pdo->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_UTF8);
        } catch (PDOException $e){
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

}