<?php

namespace PHPSpreadsheetDB\Spreadsheet;

interface Spreadsheet
{
    Const EXCEL = "EXCEL";

    public function createSheet($table, $columns);

    public function setTableDatas($tableName, $datas);

    public function deleteAllSheets();

    public function getAllTables();
}