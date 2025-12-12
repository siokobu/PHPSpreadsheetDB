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
     * (new PHPSpreadsheetDB($db, $spreadsheet))->importFromSpreadsheet(); で実行することができる．
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

    public function export($targetTables)
    {
        throw new PHPSpreadsheetDBException("Not Implemented Yet");
        // // 対象のブックからすべてのシートを削除する
        // $this->spreadsheet->deleteAllSheets();

        // foreach($targetTables as $table)
        // {
        //     // 対象のテーブルからカラム情報を取得する
        //     $columns = $this->db->getColumns($table);

        //     // 新しくシートを作成し、カラム情報をセットする
        //     $this->spreadsheet->createSheet($table, $columns);

        //     // 対象のデータを取得する
        //     $datas = $this->db->getTableData($table);

        //     // すべてのデータを書き込む
        //     $this->spreadsheet->setTableDatas($table, $datas);
        // }
    }
}