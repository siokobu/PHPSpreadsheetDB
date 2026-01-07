<?php

namespace PHPSpreadsheetDB\DB;

use PHPSpreadsheetDB\PHPSpreadsheetDBException;
use PDO;
use PDOException;

abstract class DB
{
    /** @var int 数値型を表す定数 */
    public const TYPE_NUMBER = 0;

    /** @var int 文字列型を表す定数 */
    public const TYPE_STRING = 1;

    /** @var int 日付型を表す定数 */
    public const TYPE_DATETIME = 2;

    /** @var int 真偽値を表す定数 */
    public const TYPE_BOOL = 3;

    /** @var int LOB型を表す定数 */
    public const TYPE_LOB = 4;

    protected PDO $pdo;

    /**
     * 指定したテーブルのデータをすべて返す
     * @param string $tableName データ取得対象とするテーブル名
     * @return mixed それぞれの要素に、ASSOCタイプの連想配列を格納したデータの配列
     */
    public function getTableData(string $sql): array
    {
        $columns = [];
        $data = [];

        $stmt = $this->pdo->query($sql);

        for ($i = 0; $i < $stmt->columnCount(); $i++) {
            $meta = $stmt->getColumnMeta($i);
            $columns[$i] = $meta['name'];
        }

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($result as $row) {
            $line = [];
            for ($i = 0; $i < count($row); $i++) {
                array_push($line, $row[$columns[$i]]);
            }
            array_push($data, $line);
        }

        return [
            'columns' => $columns,
            'data' => $data,
        ];   
    }   

    /**
     * "import"で利用する．対象となるテーブルのデータを登録前に全削除する
     * @param $tableName string 削除対象のテーブル名
     * @throws PHPSpreadsheetDBException データ削除時に発生したException
     */
    public function deleteData(string $tableNamne): void
    {
        try {
            $this->pdo->beginTransaction();
            $this->pdo->exec("DELETE FROM " . $tableNamne);
            $this->pdo->commit();
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            $errmes = "Delete Data Failed. Table:" . $tableNamne . ", Message:" .  str_replace("\n", " ", $e->getMessage());
            throw new PHPSpreadsheetDBException($errmes);
        }
    }

    /**
     * "import"で利用する．対象となるデータをデータベースにインポートする
     * @param $tableName string インポート対象のテーブル名
     * @param $data array インポートするデータ。2x2配列となっており、1行目にはカラム名の列と認識する
     * @throws PHPSpreadsheetDBException DB登録時に発生したException
     */
    public function insertData(string $tableName, array $columns, array $data): void
    {
        if(count($data) === 0) return;

        $line = 0;
        try {
            $this->pdo->beginTransaction();
            $sql = $this->createPreparedStatement($tableName, $columns);
            $stmt = $this->pdo->prepare($sql);
            foreach($data as $row) {
                $line++;
                $stmt->execute($row);
            }
            $this->pdo->commit();
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            $errmes = "Invalid Data. Table:" . $tableName . " Line:" . $line . ", Message:" .  str_replace("\n", " ", $e->getMessage());
            throw new PHPSpreadsheetDBException($errmes);
        }
    }

    protected function createPreparedStatement(string $tableName, array $columns): string
    {
        $cols = "";
        $placeHolders = "";
        foreach($columns as $col) {
            $cols .= $col.",";
            $placeHolders .= "?,";
        }
        $cols = substr($cols, 0, -1);
        $placeHolders = substr($placeHolders, 0, -1);
        return "INSERT INTO ".$tableName." (".$cols.") VALUES (".$placeHolders.");";

    }

}