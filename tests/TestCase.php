<?php

namespace PHPSpreadsheetDBTest;

class TestCase extends \PHPUnit\Framework\TestCase
{
    const DBHOST = "SERV";

    const DATABASE = "TESTDB";

    const DBUSER = "sa";

    const DBPASS = "siokobu8400";

    const DBCHAR = "UTF-8";

    const CONNINFO = array("Database" => self::DATABASE, "UID" => self::DBUSER, "PWD" => self::DBPASS, "CharacterSet" => self::DBCHAR);

    const DROP_TESTTB01 = "DROP TABLE IF EXISTS TESTTB01";

    const DROP_TESTTB02 = "DROP TABLE IF EXISTS TESTTB02";

    const CREATE_TESTTB01 = "CREATE TABLE TESTTB01 ("
            ."primary_key integer NOT NULL PRIMARY kEY,"
            ."int_col integer, "
            ."float_col float,"
            ."char_col nchar(10),"
            ."str_col nvarchar(100),"
            ."datetime_col datetime2 "
            .");";

    const CREATE_TESTTB02 = "CREATE TABLE TESTTB02 ("
    ."primary_key integer NOT NULL PRIMARY kEY,"
    ."int_col integer, "
    ."float_col float,"
    ."char_col nchar(10),"
    ."str_col nvarchar(100),"
    ."datetime_col datetime2 "
    .");";

    protected function refreshDB()
    {
        $conn = sqlsrv_connect(self::DBHOST, self::CONNINFO);

        $stmt = sqlsrv_query($conn, self::DROP_TESTTB01);
        if($stmt == false) { die( print_r( sqlsrv_errors(), true));   }

        $stmt = sqlsrv_query($conn, self::CREATE_TESTTB01);
        if($stmt == false) { die( print_r( sqlsrv_errors(), true));   }

        $stmt = sqlsrv_query($conn, self::DROP_TESTTB02);
        if($stmt == false) { die( print_r( sqlsrv_errors(), true));   }

        $stmt = sqlsrv_query($conn, self::CREATE_TESTTB02);
        if($stmt == false) { die( print_r( sqlsrv_errors(), true));   }

        sqlsrv_close($conn);
    }
}
