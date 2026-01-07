<?php

namespace PHPSpreadsheetDB\Spreadsheet;

use PHPSpreadsheetDB\PHPSpreadsheetDBException;

interface Spreadsheet
{
    /**
     * used by "import" method. create a sheet with given table name and columns
     * @return array array of sheet names
     * @throws PHPSpreadsheetDBException
     */
    public function getTableNames(): array;

    /**
     * use by "import" method. below return data structure is expected
     * [
     *   "columns" => ["colName1", "colName2", ...],
     *   "data" => [
     *       [val11, val12, ...],
     *       [val21, val22, ...],
     *   ]
     * ]
     * 
     * @param string $table Table name
     * @return array Extracted Data From Spreadsheet
     * @throws PHPSpreadsheetDBException
     */
    public function getData(string $table): array;

    /**
     * use by "export" method. delete all sheets in the spreadsheet
     * @throws PHPSpreadsheetDBException 
     */
    public function deleteAllSheets(): void;

    /**
     * use by "export" method. set data to the given table(sheet) name
     * below $data variables structure is expected
     * [
     *   "columns" => ["colName1", "colName2", ...],
     *   "data" => [
     *       [val11, val12, ...],
     *       [val21, val22, ...],
     *   ]
     * ]
     * 
     * @param string $table Table Name
     * @param array $data Data to Set. See Above for Structure
     * @return void
     */
    public function setTableData(string $table, array $data): void;
}