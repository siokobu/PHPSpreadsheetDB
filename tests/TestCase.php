<?php

namespace PHPSpreadsheetDBTest;

use PDO;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TestCase extends \PHPUnit\Framework\TestCase
{
    const COLUMNS_STR = \PHPSpreadsheetDB\Spreadsheet\Xlsx::COLUMNS_STR;

    const DATA_STR = \PHPSpreadsheetDB\Spreadsheet\Xlsx::DATA_STR;

    const TEMPDIR = __DIR__.DIRECTORY_SEPARATOR."temp".DIRECTORY_SEPARATOR;

    protected function sqlite_connect(): PDO
    {
        return new PDO('sqlite:'.TestCase::TEMPDIR . getEnv("TEST_SQLITE_FILENAME"));
    }

    protected function sqlite_close(PDO $PDO): void
    {
        $PDO = null;
    }

    protected function sqlite_createSchema(array $schemas): void
    {
        $pdo = $this->sqlite_connect();
        foreach ($schemas as $table => $stmt) {
            $pdo->exec("DROP TABLE IF EXISTS ".$table.";");
            $pdo->exec($stmt);
        }
        $this->sqlite_close($pdo);
    }

    protected function pgsql_connect(): PDO
    {
        $pdo = new PDO(
            'pgsql:dbname='.getenv('TEST_PG_DB').' host='.getenv('TEST_PG_DBHOST').' port='.getenv('TEST_PG_DBPORT'),
            getenv('TEST_PG_DBUSER'),
            getenv('TEST_PG_DBPASS')
        );
        $pdo->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_UTF8);
        return $pdo;
    }

    protected function pgsql_close(PDO $pdo): void
    {   
        $pdo = null;
    }

    protected function pgsql_createSchema(array $schemas): void
    {
        $pdo = $this->pgsql_connect();
        foreach ($schemas as $table => $stmt) {
            $pdo->exec("DROP TABLE IF EXISTS ".$table.";");
            $pdo->exec($stmt);
        }
        $this->pgsql_close($pdo);
    }

    protected function mariadb_connect(): PDO
    {
        $pdo = new PDO(
            "mysql:dbname=".getenv('TEST_MARIA_DB').";host=".getenv('TEST_MARIA_DBHOST').";port=".getenv('TEST_MARIA_DBPORT'),
            getenv('TEST_MARIA_DBUSER'),
            getenv('TEST_MARIA_DBPASS')
        );
        $pdo->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_UTF8);
        return $pdo;
    }

    protected function mariadb_close(PDO $pdo): void
    {   
        $pdo = null;
    }

    protected function mariadb_createSchema(array $schemas): void
    {
        $pdo = $this->mariadb_connect();
        foreach ($schemas as $table => $stmt) {
            $pdo->exec("DROP TABLE IF EXISTS ".$table.";");
            $pdo->exec($stmt);
        }
        $this->mariadb_close($pdo);
    }

    protected function sqlsrv_connect(): PDO
    {
        $pdo = new PDO(
            'sqlsrv:server='.getenv('TEST_SQLSRV_DBHOST').','.getenv('TEST_SQLSRV_DBPORT').';Database='.getenv('TEST_PG_DB').';TrustServerCertificate=true',
            getenv('TEST_SQLSRV_DBUSER'),
            getenv('TEST_SQLSRV_DBPASS')
        );
        $pdo->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_UTF8);
        return $pdo;
    }

    protected function sqlsrv_close(PDO $pdo): void
    {   
        $pdo = null;
    }

    protected function sqlsrv_createSchema(array $schemas): void
    {
        $pdo = $this->sqlsrv_connect();
        foreach ($schemas as $table => $stmt) {
            $pdo->exec("DROP TABLE IF EXISTS ".$table.";");
            $pdo->exec($stmt);
        }
        $this->sqlsrv_close($pdo);
    }

    protected function xlsx_createFile(string $path, array $tables): void
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        foreach ($tables as $tableName => $tableData) {
            $sheet = new Worksheet($spreadsheet, $tableName);
            $this->xlsx_setColumnsRow($sheet, 1, $tableData['columns']);
            $sheet->setCellValue('A2', self::DATA_STR);
            foreach ($tableData['data'] as $index => $dataRow) {
                $this->xlsx_setDataRow($sheet, $index + 3, $dataRow);
            }
            $spreadsheet->addSheet($sheet);
        }
        $spreadsheet->removeSheetByIndex($spreadsheet->getIndex($spreadsheet->getSheetByName('Worksheet')));
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);
    }

    protected function xlsx_setColumnsRow(Worksheet $sheet, int $rowNumber, array $columns): void
    {
        $sheet->setCellValue('A'.$rowNumber, self::COLUMNS_STR);
        for ($i = 0; $i <= count($columns); $i++) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($i + 2).$rowNumber, $columns[$i]);
        } 
    }

    protected function xlsx_setDataRow(Worksheet $sheet, int $rowNumber, array $data): void
    {
        for ($i = 0; $i <= count($data); $i++) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($i + 2).$rowNumber, $data[$i]);
        } 
    }
}
