<?php

namespace PHPSpreadsheetDB\DB;

use PHPSpreadsheetDB\PHPSpreadsheetDBException;

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

    /**
     * 指定したデータベースのカラム一覧を返す．
     * @param $tableName string カラム情報を取得する対象となるテーブル名
     * @return mixed array Name（カラム名）とType（型）を持つ連想配列の配列．Typeは、本クラスに定義されており、TYPE_NUMBER,TYPE_STRING,TYPE_DATETIME,TYPE_BOOL,TYPE_LOGのいずれか。
     */
    public abstract function getColumns(string $tableName): iterable;


    /**
     * 指定したテーブルのデータをすべて返す
     * @param string $tableName データ取得対象とするテーブル名
     * @return mixed それぞれの要素に、ASSOCタイプの連想配列を格納したデータの配列
     */
    public abstract function getTableData(string $tableName): iterable;

    /**
     * "import"で利用する．対象となるテーブルのデータを登録前に全削除する
     * @param $tableName string 削除対象のテーブル名
     * @throws PHPSpreadsheetDBException データ削除時に発生したException
     */
    public abstract function deleteData(string $tableName): void;

    /**
     * "import"で利用する．対象となるデータをデータベースにインポートする
     * @param $tableName string インポート対象のテーブル名
     * @param $data array インポートするデータ。2x2配列となっており、1行目にはカラム名の列と認識する
     * @throws PHPSpreadsheetDBException DB登録時に発生したException
     */
    public abstract function insertData(string $tableName, array $columns, array $data): void;

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