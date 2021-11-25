<?php

namespace PHPSpreadsheetDB\Spreadsheet;

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

    public function getAllTables();
}