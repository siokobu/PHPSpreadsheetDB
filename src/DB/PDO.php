<?php

namespace PHPSpreadsheetDB\DB;

use PHPSpreadsheetDB\PHPSpreadsheetDBException;

abstract class PDO implements DB
{
    protected $pdo;

    public function insertData(string $tableName, array $data)
    {

        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare("DELETE FROM " . $tableName . ";");
            $stmt->execute();
        } catch (\PDOException $e) {
            throw new PHPSpreadsheetDBException($e);
        }

        // Prepare Statement.
        $cols = "";
        $placeHolders = "";
        foreach($data[0] as $col) {
            $cols .= $col.",";
            $placeHolders .= ":".$col.",";
        }
        $cols = substr($cols, 0, -1);
        $placeHolders = substr($placeHolders, 0, -1);
        $sql = "INSERT INTO ".$tableName." (".$cols.") VALUES (".$placeHolders.");";

        try {
            $stmt = $this->pdo->prepare($sql);
            for ($i = 1; $i < count($data); $i++) {
                for ($j = 0; $j < count($data[0]); $j++) {
                    $stmt->bindparam(':' . $data[0][$j], $data[$i][$j]);
                }
                $stmt->execute();
            }
        } catch (PDOException $e) {
            throw new PHPSpreadsheetDBException($e);
        }
        $this->pdo->commit();
    }

    /**
     * @inheritDoc
     */
    public function getColumns(string $tableName): iterable
    {
        // TODO: Implement getColumns() method.
    }

    /**
     * @inheritDoc
     */
    public function getTableData(string $tableName): iterable
    {
        // TODO: Implement getTableData() method.
    }
}