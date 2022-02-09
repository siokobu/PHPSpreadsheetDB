<?php

namespace PHPSpreadsheetDB\DB;

use mysqli;
use PDOException;
use phpDocumentor\Reflection\Types\Resource_;
use PHPSpreadsheetDB\PHPSpreadsheetDBException;

class MySQL extends PDO
{

    /**
     * @throws PHPSpreadsheetDBException
     */
    public function __construct(string $host, string $user, string $pass, string $db, string $charset = 'utf8mb4')
    {
        $dsn = 'mysql:dbname='.$db.';host='.$host.';charset='.$charset;
        try {
            $this->pdo = new \PDO($dsn, $user, $pass);
        } catch (PDOException $e) {
            if($e->getCode() == '2002') {
                throw new PHPSpreadsheetDBException('invalid host:'.$e->getMessage(), $e->getCode(), $e);
            } else if($e->getCode() == '1045') {
                throw new PHPSpreadsheetDBException('autorization failed:'.$e->getMessage(), $e->getCode(), $e);
            } else if($e->getCode() == '1049') {
                throw new PHPSpreadsheetDBException('no database:'.$e->getMessage(), $e->getCode(), $e);
            } else if($e->getCode() == '2019') {
                throw new PHPSpreadsheetDBException('no charset:'.$e->getMessage(), $e->getCode(), $e);
            } else {
                throw new PHPSpreadsheetDBException($e);
            }
        }
    }

}