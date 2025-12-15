<?php

namespace PHPSpreadsheetDB\Spreadsheet;

use PHPSpreadsheetDB\PHPSpreadsheetDBException;

interface Spreadsheet
{
    Const EXCEL = "EXCEL";

    /**
     * 対象のテーブルを登録するためのシートを作成し、カラムを入力しておく
     * @param string $table シートを作成する対象のテーブル名
     * @param array $columns 作成したシートに入力するカラム
     * @return void
     */
    public function createSheet($table, $columns);

    /**
     * 引数 $datas で受け取ったデータをスプレッドシート上に保存する
     * @param $tableName string 保存先のスプレッドシートのパス
     * @param $datas array スプレッドシートに書き込むデータ
     * @throws
     */
    public function setTableDatas($tableName, $datas);

    
    public function deleteAllSheets();

    /**
     * "import"で利用する．スプレッドシートに記載のすべての登録対象となるテーブル名を配列として返す
     * @return array テーブル名の配列
     * @throws PHPSpreadsheetDBException スプレッドシート読み込み時に発生するException
     */
    public function getTableNames(): array;

    /**
     * "import"で利用する．Spreadsheetから指定されたテーブル名のデータを抽出して返す
     * [
     *   "columns" => ["colName1", "colName2", ...],
     *   "datas" => [
     *       [val11, val12, ...],
     *       [val21, val22, ...],
     *   ]
     * ]
     * 
     * @param $tableName string Table name
     * @return array Extracted Data From Spreadsheet
     * @throws PHPSpreadsheetDBException スプレッドシート読み込み時に発生するException
     */
    public function getData(string $tableName): array;
}