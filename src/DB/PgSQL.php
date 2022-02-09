<?php

namespace PHPSpreadsheetDB\DB;

use PHPSpreadsheetDB\PHPSpreadsheetDBException;

class PgSQL extends PDO
{

    /**
     * @throws PHPSpreadsheetDBException
     */
    public function __construct(string $host, string $user, string $pass, string $db, string $charset = 'UTF8')
    {
        $dsn = 'pgsql:dbname='.$db.';host='.$host.';user='.$user.';password='.$pass;
        try {
            $this->pdo = new \PDO($dsn, $user, $pass);
            $this->pdo->query("SET NAMES '".$charset."'");
        } catch (\PDOException $e) {
            if($e->getCode() == 7) {
                throw new PHPSpreadsheetDBException('not connected:' . $e->getMessage(), $e->getCode(), $e);
            } if($e->getCode() == 22023) {
                throw new PHPSpreadsheetDBException('invalid charset:'.$e->getMessage(), $e->getCode(), $e);
            } else {
                throw new PHPSpreadsheetDBException($e);
            }
        }
    }
}