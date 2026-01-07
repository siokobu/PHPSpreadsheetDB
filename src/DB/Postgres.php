<?php

namespace PHPSpreadsheetDB\DB;

use PHPSpreadsheetDB\PHPSpreadsheetDBException;
use PDO;
use PDOException;

class Postgres extends DB
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
        string $port = '5432', 
        string $dbname, 
        string $user, 
        string $password)
    {
        $dsn = 'pgsql:dbname='.$dbname.' host='.$host.' port='.$port;
        try {
            $this->pdo = new PDO($dsn, $user, $password);
            $this->pdo->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_UTF8);
        } catch (PDOException $e){
            throw new PHPSpreadsheetDBException("Connection Error: " . $e->getMessage());
        }
    }
}