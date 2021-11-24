<?php

namespace PHPSpreadsheetDB\DB;

interface DB
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
    public function getColumns(string $tableName): iterable;


    /**
     * 指定したテーブルのデータをすべて返す
     * @param string $tableName データ取得対象とするテーブル名
     * @return mixed それぞれの要素に、ASSOCタイプの連想配列を格納したデータの配列
     */
    public function getTableData(string $tableName): iterable;


}