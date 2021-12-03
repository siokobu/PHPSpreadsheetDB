<?php

namespace PHPSpreadsheetDBTest;

class TestCase extends \PHPUnit\Framework\TestCase
{
    const TESTTB01 = 'TESTTB01';

    const TESTTB02 = 'TESTTB02';

    const TESTCOLUMNS = ['primary_key', 'int_col', 'float_col', 'char_col', 'str_col', 'datetime_col'];

    const TESTDATADB1 = [
        ['primary_key', 'int_col', 'float_col', 'char_col', 'str_col', 'datetime_col'],
        ['1', '1', '3.14', 'abced', '日本語文字列',  '2021-01-01 00:00:00'],
        ['2', null, null, null, null, null]
    ];

    const TESTDATARESULT1 = [
        ['primary_key', 'int_col', 'float_col', 'char_col', 'str_col', 'datetime_col'],
        [1, 1, 3.14, 'abced     ', '日本語文字列',  '2021-01-01 00:00:00'],
        [2, null, null, null, null, null]
    ];

    const TESTDATADB2 = [
        ['primary_key', 'int_col', 'float_col', 'char_col', 'str_col', 'datetime_col'],
        ['1', '2', '0.01', 'fghij', 'ひらがな文字列', '2050-12-31 00:00:00'],
        ['2', '', '', '', '', '']
    ];

    const TESTDATARESULT2 = [
        ['primary_key', 'int_col', 'float_col', 'char_col', 'str_col', 'datetime_col'],
        ['1', '2', '0.01', 'fghij     ', 'ひらがな文字列', '2050-12-31 00:00:00'],
        ['2', 0, 0, '          ', '', '1900-01-01 00:00:00']
    ];

    const TESTDATASS1 = [
        ['primary_key', 'int_col', 'float_col', 'char_col', 'str_col', 'datetime_col'],
        ['1', '2', '0.01', 'fghij', 'ひらがな文字列', '2050-12-31 00:00:00'],
        ['2', '<null>', '<null>', '<null>', '<null>', '<null>']
    ];



    const TESTDATASS2 = [
        ['primary_key', 'int_col', 'float_col', 'char_col', 'str_col', 'datetime_col'],
        ['1', '2', '0.01', 'fghij', 'ひらがな文字列', '2050-12-31 00:00:00'],
        ['2', '', '', '', '', '']
    ];


    const SQLSRV_DBHOST = "SERV";

    const SQLSRV_DATABASE = "TESTDB";

    const SQLSRV_DBUSER = "sa";

    const SQLSRV_DBPASS = "siokobu8400";

    const SQLSRV_DBCHAR = "UTF-8";

    const SQLSRV_CONNINFO = array("Database" => self::SQLSRV_DATABASE, "UID" => self::SQLSRV_DBUSER, "PWD" => self::SQLSRV_DBPASS, "CharacterSet" => self::SQLSRV_DBCHAR);

    const SQLSRV_DROP_TESTTB01 = "DROP TABLE IF EXISTS TESTTB01";

    const SQLSRV_DROP_TESTTB02 = "DROP TABLE IF EXISTS TESTTB02";

    const SQLSRV_CREATE_TESTTB01 = "CREATE TABLE TESTTB01 ("
            ."primary_key integer NOT NULL PRIMARY kEY,"
            ."int_col integer, "
            ."float_col float,"
            ."char_col nchar(10),"
            ."str_col nvarchar(100),"
            ."datetime_col datetime2 "
            .");";

    const SQLSRV_CREATE_TESTTB02 = "CREATE TABLE TESTTB02 ("
    ."primary_key integer NOT NULL PRIMARY kEY,"
    ."int_col integer, "
    ."float_col float,"
    ."char_col nchar(10),"
    ."str_col nvarchar(100),"
    ."datetime_col datetime2 "
    .");";

    protected function refreshDB_SQLSRV()
    {
        $conn = sqlsrv_connect(self::SQLSRV_DBHOST, self::SQLSRV_CONNINFO);

        $stmt = sqlsrv_query($conn, self::SQLSRV_DROP_TESTTB01);
        if($stmt == false) { die( print_r( sqlsrv_errors(), true));   }

        $stmt = sqlsrv_query($conn, self::SQLSRV_CREATE_TESTTB01);
        if($stmt == false) { die( print_r( sqlsrv_errors(), true));   }

        $stmt = sqlsrv_query($conn, self::SQLSRV_DROP_TESTTB02);
        if($stmt == false) { die( print_r( sqlsrv_errors(), true));   }

        $stmt = sqlsrv_query($conn, self::SQLSRV_CREATE_TESTTB02);
        if($stmt == false) { die( print_r( sqlsrv_errors(), true));   }

        sqlsrv_close($conn);
    }

    protected function insertDB_SQLSRV()
    {
        $conn = sqlsrv_connect(self::SQLSRV_DBHOST, self::SQLSRV_CONNINFO);

        $columns = "";
        $values = "";
        foreach(self::TESTCOLUMNS as $column) {
            $columns = $columns.$column.",";
            $values = $values."?,";
        }
        $columns = substr($columns, 0,strlen($columns)-1);
        $values = substr($values, 0,strlen($values)-1);

        $sql = "INSERT INTO ".self::TESTTB01." (".$columns.") VALUES (".$values.");";
        for($i=0; $i<count(self::TESTDATADB1); $i++) {
            if($i == 0) continue;
            $stmt = sqlsrv_prepare($conn, $sql, array(self::TESTDATADB1[$i][0], self::TESTDATADB1[$i][1],
                self::TESTDATADB1[$i][2], self::TESTDATADB1[$i][3], self::TESTDATADB1[$i][4],
                self::TESTDATADB1[$i][5]));
            if(sqlsrv_execute($stmt) === false) { die( print_r( sqlsrv_errors(), true));   }
        }
        $sql = "INSERT INTO ".self::TESTTB02." (".$columns.") VALUES (".$values.");";
        for($i=0; $i<count(self::TESTDATADB2); $i++) {
            if($i == 0) continue;
            $stmt = sqlsrv_prepare($conn, $sql, array(self::TESTDATADB2[$i][0],
                self::TESTDATADB2[$i][1], self::TESTDATADB2[$i][2],
                self::TESTDATADB2[$i][3], self::TESTDATADB2[$i][4],
                self::TESTDATADB2[$i][5]));
            if(sqlsrv_execute($stmt) === false) { die( print_r( sqlsrv_errors(), true));   }
        }
    }



}
