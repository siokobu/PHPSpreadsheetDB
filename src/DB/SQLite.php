<?php

namespace PHPSpreadsheetDB\DB;

use PDO;
use PHPSpreadsheetDB\PHPSpreadsheetDBException;

class SQLite extends DB
{

    public function __construct($filename)
    {
        $this->pdo = new PDO('sqlite:'.$filename);
    }
}