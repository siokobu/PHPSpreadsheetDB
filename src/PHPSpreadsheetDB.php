<?php

namespace PHPSpreadsheetDB;

use PHPSpreadsheetDB\DB\DB;
use PHPSpreadsheetDB\Spreadsheet\Spreadsheet;

/**
 * Excel等のスプレッドシートデータをDBに反映する
 */
class PHPSpreadsheetDB {

    private $pdo;

    private $db;

    private $spreadsheet;
    /**
     * コンストラクタ DSN・ユーザ名・パスワードを受け取り
     */
    public function __construct(DB $db, Spreadsheet $spreadsheet)
    {
        $this->db = $db;
        $this->spreadsheet = $spreadsheet;
    }

    public function importFromSpreadsheet()
    {
        $tables = $this->spreadsheet->getAllTables();

        foreach($tables as $table) {
            $datas = $this->spreadsheet->getDatasFromTable($table);

            $this->db->insertData($table, $datas);
        }
    }

    public function exportToSpreadsheet($targetTables)
    {
        // 対象のブックからすべてのシートを削除する
        $this->spreadsheet->deleteAllSheets();

        foreach($targetTables as $table)
        {
            // 対象のテーブルからカラム情報を取得する
            $columns = $this->db->getColumns($table);

            // 新しくシートを作成し、カラム情報をセットする
            $this->spreadsheet->createSheet($table, $columns);

            // 対象のデータを取得する
            $datas = $this->db->getTableData($table);

            // すべてのデータを書き込む
            $this->spreadsheet->setTableDatas($table, $datas);
        }
    }
}