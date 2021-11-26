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
     * "importFromSpreadsheet"で利用する．スプレッドシートに記載のすべての登録対象となるテーブル名を配列として返す
     * @return array テーブル名の配列
     * @throws PHPSpreadsheetDBException スプレッドシート読み込み時に発生するException
     */
    public function getTableNames(): array;

    /**
     * "importFromSpreadsheet"で利用する．スプレッドシートから抽出した登録対象のデータを２×２配列で取得する
     * @param $tableName string データを抽出したテーブル名
     * @return array スプレッドシートから抽出したインポート用の２×２配列データ
     * @throws PHPSpreadsheetDBException スプレッドシート読み込み時に発生するException
     */
    public function getData(string $tableName): array;
}