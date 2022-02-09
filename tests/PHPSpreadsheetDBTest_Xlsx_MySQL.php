<?php

namespace PHPSpreadsheetDBTest;

use PHPSpreadsheetDB\DB\MySQL;
use PHPSpreadsheetDB\PHPSpreadsheetDB;
use PHPSpreadsheetDB\Spreadsheet\Xlsx;
use PHPUnit\Framework\TestCase;

class PHPSpreadsheetDBTest_Xlsx_MySQL extends TestCase
{
    private $host = "serv";
    private $user = "tomocky1";
    private $pass = "siokobu8400";
    private $db = "TESTDB";
    private $charset = "utf8mb4";

    private $ddlCreateTable01 = "CREATE TABLE TESTTB01 ("
    ."primary_key integer PRIMARY KEY,"
    ."int_col integer,"
    ."float_col float,"
    ."decimal_col decimal,"
    ."char_col char(10),"
    ."str_col varchar(100)"
    .");";

    private $ddlCreateTable02 = "CREATE TABLE TESTTB02 ("
    ."primary_key integer PRIMARY KEY,"
    ."date_col date,"
    ."time_col time, "
    ."datetime_col datetime,"
    ."timestamp_col timestamp"
    .");";

    public function testInsertData()
    {
        $pdo = $this->getConnection();
        $this->refreshDB($pdo);

        /** テスト実行を実施 */
        $MySQL = new MySQL($this->host, $this->user, $this->pass, $this->db, $this->charset);
        $xlsx = new Xlsx(__DIR__.'\Xlsx_MySQL_insertData.xlsx');

        $phpSpreadsheetDB = new PHPSpreadsheetDB($MySQL, $xlsx);
        $phpSpreadsheetDB->importFromSpreadsheet();

        /** テストデータの検証 */
        $rows = $pdo->query('SELECT * FROM TESTTB01;')->fetchAll();
        $this->assertEquals('1', $rows[0]['primary_key']);
        $this->assertEquals('1', $rows[0]['int_col']);
        $this->assertEquals('0.1', $rows[0]['float_col']);
        $this->assertEquals('0', $rows[0]['decimal_col']);
        $this->assertEquals('a b', $rows[0]['char_col']);
        $this->assertEquals('日本語　もじれつ', $rows[0]['str_col']);
        $this->assertEquals('2', $rows[1]['primary_key']);
        $this->assertEquals(null, $rows[1]['int_col']);
        $this->assertEquals(null, $rows[1]['float_col']);
        $this->assertEquals(null, $rows[1]['decimal_col']);
        $this->assertEquals(null, $rows[1]['char_col']);
        $this->assertEquals(null, $rows[1]['str_col']);

        $rows = $pdo->query('SELECT * FROM TESTTB02;')->fetchAll();
        $this->assertEquals('1', $rows[0]['primary_key']);
        $this->assertEquals('2021-12-31', $rows[0]['date_col']);
        $this->assertEquals('23:59:59', $rows[0]['time_col']);
        $this->assertEquals('2021-12-31 23:59:59', $rows[0]['datetime_col']);
        $this->assertEquals('2021-12-31 23:59:59', $rows[0]['timestamp_col']);
        $this->assertEquals('2', $rows[1]['primary_key']);
        $this->assertEquals(null, $rows[1]['date_col']);
        $this->assertEquals(null, $rows[1]['time_col']);
        $this->assertEquals(null, $rows[1]['datetime_col']);
//        $this->assertEquals(null, $rows[1]['timestamp_col']);
    }

    private function refreshDB($pdo)
    {
        $pdo->exec('DROP TABLE IF EXISTS TESTTB01;');
        $pdo->exec('DROP TABLE IF EXISTS TESTTB02;');
        $pdo->exec($this->ddlCreateTable01);
        $pdo->exec($this->ddlCreateTable02);
    }

    private function getConnection()
    {
        $dsn = 'mysql:dbname='.$this->db.';host='.$this->host.';charset='.$this->charset;
        return new \PDO($dsn, $this->user, $this->pass);
    }
}
