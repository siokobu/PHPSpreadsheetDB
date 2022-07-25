<?php

namespace PHPSpreadsheetDBTest;

use PDO;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PHPSpreadsheetDB\DB\SQLite;
use PHPSpreadsheetDB\DB\SQLSrv;
use PHPSpreadsheetDB\PHPSpreadsheetDB;
use PHPSpreadsheetDB\Spreadsheet\Xlsx;

/**
 * データベース：SQLite、スプレッドシート：Excelの場合のテスト
 */
class PHPSpreadsheetDBTest_Xlsx_SQLite extends TestCase
{
    /**
     * @var string|mixed SQLiteデータベースファイルの格納用パス
     */
    private string $filename;

    /**
     * 事前準備．envファイルからテスト用の環境遠陬を取得する
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->filename = $this->getEnv('SQLITE_FILENAME');
    }

    /** @test */
    public function testImportFromSpreadsheet()
    {
        $tableName = ['TESTTB01', 'TESTTB02'];
        $path = __DIR__.DIRECTORY_SEPARATOR."temp".DIRECTORY_SEPARATOR."/Xlsx_SQLite-ImportFromSpreadsheet.xlsx";

        if(file_exists($path)) unlink($path);
        $sourceSS = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        $sheet = new Worksheet($sourceSS, $tableName[0]);
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'NUMCOL');
        $sheet->setCellValue('C1', 'INTCOL');
        $sheet->setCellValue('D1', 'REALCOL');
        $sheet->setCellValue('E1', 'TEXTCOL');
        $sheet->setCellValue('A2', '<null>');
        $sheet->setCellValue('B2', 1);
        $sheet->setCellValue('C2', 1);
        $sheet->setCellValue('D2', 0.001);
        $sheet->setCellValue('E2', '日本語');
        $sheet->setCellValue('A3', 2);
        $sheet->setCellValue('B3', '<null>');
        $sheet->setCellValue('C3', '<null>');
        $sheet->setCellValue('D3', '<null>');
        $sheet->setCellValue('E3', '<null>');
        $sourceSS->addSheet($sheet);

        $sheet = new Worksheet($sourceSS, $tableName[1]);
        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'NUMCOL');
        $sheet->setCellValue('C1', 'INTCOL');
        $sheet->setCellValue('D1', 'REALCOL');
        $sheet->setCellValue('E1', 'TEXTCOL');
        $sheet->setCellValue('A2', '<null>');
        $sheet->setCellValue('B2', 1);
        $sheet->setCellValue('C2', 1);
        $sheet->setCellValue('D2', 0.001);
        $sheet->setCellValue('E2', '日本語');
        $sheet->setCellValue('A3', 2);
        $sheet->setCellValue('B3', '<null>');
        $sheet->setCellValue('C3', '<null>');
        $sheet->setCellValue('D3', '<null>');
        $sheet->setCellValue('E3', '<null>');
        $sourceSS->addSheet($sheet);

        $sourceSS->removeSheetByIndex($sourceSS->getIndex($sourceSS->getSheetByName('Worksheet')));
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($sourceSS))->save($path);

        $pdo = new PDO('sqlite:'.$this->filename);
        $pdo->exec($this->getDropStmt($tableName[0]));
        $pdo->exec($this->getCreateStmt($tableName[0]));
        $pdo->exec($this->getDropStmt($tableName[1]));
        $pdo->exec($this->getCreateStmt($tableName[1]));

        /** テスト実行を実施 */
        $SQLite = new SQLite($this->getEnv('SQLITE_FILENAME'));
        $xlsx = new Xlsx($path);

        $phpSpreadsheetDB = new PHPSpreadsheetDB($SQLite, $xlsx);
        $phpSpreadsheetDB->importFromSpreadsheet();

        $stmt = $pdo->query('SELECT * FROM '.$tableName[0].';');
        $row = $stmt->fetch();
        $this->assertSame('1', $row['ID']);
        $this->assertSame('1', $row['NUMCOL']);
        $this->assertSame('1', $row['INTCOL']);
        $this->assertSame('0.001', $row['REALCOL']);
        $this->assertSame('日本語', $row['TEXTCOL']);
        $row = $stmt->fetch();
        $this->assertSame('2', $row['ID']);
        $this->assertNull($row['NUMCOL']);
        $this->assertNull($row['INTCOL']);
        $this->assertNull($row['REALCOL']);
        $this->assertNull($row['TEXTCOL']);
        $this->assertFalse($stmt->fetch());

        $stmt = $pdo->query('SELECT * FROM '.$tableName[1].';');
        $row = $stmt->fetch();
        $this->assertSame('1', $row['ID']);
        $this->assertSame('1', $row['NUMCOL']);
        $this->assertSame('1', $row['INTCOL']);
        $this->assertSame('0.001', $row['REALCOL']);
        $this->assertSame('日本語', $row['TEXTCOL']);
        $row = $stmt->fetch();
        $this->assertSame('2', $row['ID']);
        $this->assertNull($row['NUMCOL']);
        $this->assertNull($row['INTCOL']);
        $this->assertNull($row['REALCOL']);
        $this->assertNull($row['TEXTCOL']);
        $this->assertFalse($stmt->fetch());
    }

    /**
     * テーブル削除用SQLを生成する
     * @param string $tableName テーブル名
     * @return string テーブル削除用SQL
     */
    private function getDropStmt(string $tableName): string
    {
        return "DROP TABLE IF EXISTS ".$tableName.";";
    }

    /**
     * テーブル作成用SQLを生成する．SQLiteはカラムの種類が少ないためテーブルは１種類のみ
     * @param string $tableName テーブル名
     * @return string テーブル作成用SQL
     */
    private function getCreateStmt(string $tableName): string
    {
        return "CREATE TABLE ".$tableName
            ."(ID INTEGER PRIMARY KEY, NUMCOL NUMERIC, INTCOL INTEGER, REALCOL REAL, TEXTCOL text);";

    }
}