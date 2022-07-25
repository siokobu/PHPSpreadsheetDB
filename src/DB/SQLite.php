<?php

namespace PHPSpreadsheetDB\DB;

use PDO;
use PHPSpreadsheetDB\PHPSpreadsheetDBException;

class SQLite extends DB
{

    private PDO $conn;

    public function __construct($filename)
    {
        $this->conn = new PDO('sqlite:'.$filename);
    }

    /**
     * @inheritDoc
     */
    public function getColumns(string $tableName): iterable
    {
        // TODO: Implement getColumns() method.
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getTableData(string $tableName): iterable
    {
        // TODO: Implement getTableData() method.
        return [];
    }

    /**
     * @inheritDoc
     */
    public function deleteData(string $tableName): void
    {
        $result = $this->conn->exec("DELETE FROM ".$tableName.";");
        if ($result === false) {
            throw new PHPSpreadsheetDBException($this->conn->errorInfo(), $this->conn->errorCode());
        }
    }

    /**
     * @inheritDoc
     */
    public function insertData(string $tableName, array $data)
    {
        if (count($data) === 0) return;

        $stmt = $this->conn->prepare($this->createPreparedStatement($tableName, $data[0]));

        for($i=1; $i<count($data); $i++) {
            $stmt->execute($data[$i]);
        }
    }
}