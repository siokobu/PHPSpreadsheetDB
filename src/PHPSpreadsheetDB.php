<?php

namespace PHPSpreadsheetDB;

use PHPSpreadsheetDB\DB\DB;
use PHPSpreadsheetDB\Spreadsheet\Spreadsheet;

/**
 * Excel等のスプレッドシートデータをDBに反映する
 */
class PHPSpreadsheetDB {

    /** @var DB 接続対象のDBオブジェクトを保管する */
    private $db;

    /** @var Spreadsheet インポート・エクスポート対象となるExcelなどのスプレッドシートオブジェクトを保持する */
    private $spreadsheet;

    /**
     * コンストラクタ．インポート・エクスポート対象となるデータベース・スプレッドシートを設定する．
     * 現在サポートしているデータベース・スプレッドシートは以下の通り
     * $db・・・Microsoft SQL Server(PHPSpreadsheetDB\DB\SQLSrv）
     * $spread・・・Excel（PHPSpreadsheetDB\Spreadsheet\Xlsx）
     * @param DB $db PHPSpreadsheetDB\DB\DBインターフェースを実装したDBクラス
     * @param Spreadsheet $spreadsheet PHPSpreadsheetDB\Spreadsheet\Spreadsheetインターフェースを実装したクラス
     */
    public function __construct(DB $db, Spreadsheet $spreadsheet)
    {
        $this->db = $db;
        $this->spreadsheet = $spreadsheet;
    }

    /**
     * スプレッドシートの内容をデータベースにインポートする．
     * (new PHPSpreadsheetDB($db, $spreadsheet))->import(); で実行することができる．
     * @throws PHPSpreadsheetDBException
     */
    public function import()
    {
        $tables = $this->spreadsheet->getTableNames();

        foreach($tables as $table) {
            $data = $this->spreadsheet->getData($table);

            $this->db->deleteData($table);

            $this->db->insertData($table, $data['columns'], $data['data']);
        }
    }

    /**
     * export data with tableNames from database to spreadsheet
     *
     * @param $tables
     * @return void
     */
    public function exportByTables(...$tables): void
    {
        $sqls = [];

        foreach ($tables as $table) {
            $sqls[$table] = "SELECT * FROM $table";
        }

        $this->export($sqls);
    }

    /**
     * export data with sqls from database to spreadsheet
     *
     * @param array $sqls [<tableName> => <sql>, ...] 
     * @return void
     */
    public function export(array $sqls)
    {
        $this->spreadsheet->deleteAllSheets();

        foreach($sqls as $table => $sql)
        {
            $data = $this->db->getTableData($sql);

            $this->spreadsheet->setTableData($table, $data);
        }
    }
}