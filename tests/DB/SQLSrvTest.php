<?php

namespace PHPSpreadsheetDBTest\DB;

use PHPSpreadsheetDB\DB\DB;
use PHPSpreadsheetDB\DB\SQLSrv;
use PHPSpreadsheetDBTest\TestCase;

class SQLSrvTest extends TestCase
{
    public function testGetColumns()
    {
        $this->refreshDB();

        $SQLSrv = new SQLSrv(self::DBHOST, self::CONNINFO);
        $columns = $SQLSrv->getColumns("TESTTB01");

        $this->assertEquals("primary_key",  $columns[0]['Name']);
        $this->assertEquals(DB::TYPE_NUMBER,  $columns[0]['Type']);
        $this->assertEquals("int_col",      $columns[1]['Name']);
        $this->assertEquals(DB::TYPE_NUMBER,  $columns[1]['Type']);
        $this->assertEquals("float_col",      $columns[2]['Name']);
        $this->assertEquals(DB::TYPE_NUMBER,  $columns[2]['Type']);
        $this->assertEquals("char_col",     $columns[3]['Name']);
        $this->assertEquals(DB::TYPE_STRING,  $columns[3]['Type']);
        $this->assertEquals("str_col",      $columns[4]['Name']);
        $this->assertEquals(DB::TYPE_STRING,  $columns[4]['Type']);
        $this->assertEquals("datetime_col", $columns[5]['Name']);
        $this->assertEquals(DB::TYPE_DATETIME,  $columns[5]['Type']);

    }

    public function testGetTableDatas()
    {
        $serverName = "SERV";
        $connectionInfo = array("Database" => "TESTDB", "UID" => "sa", "PWD" => "siokobu8400", "CharacterSet" => "UTF-8");

        $conn = sqlsrv_connect($serverName, $connectionInfo);

        $stmt = sqlsrv_query($conn, self::DROP_TESTTB02);
        if($stmt == false) { die( print_r( sqlsrv_errors(), true));   }

        $stmt = sqlsrv_query($conn, self::CREATE_TESTTB02);
        if($stmt == false) { die( print_r( sqlsrv_errors(), true));   }

        $sql = "INSERT INTO TESTTB02 VALUES(?, ?, ?, ?, ?, ?)";
        $stmt = sqlsrv_prepare($conn, $sql, array(1, 1, 3.14, 'abcde', '日本語文字列', '2021/01/01'));
        if(sqlsrv_execute($stmt) === false) { die( print_r( sqlsrv_errors(), true));   }
        $stmt = sqlsrv_prepare($conn, $sql, array(2, 2, 0.01, 'cdefg', 'ひらがな文字列', '2021/12/31'));
        if(sqlsrv_execute($stmt) === false) { die( print_r( sqlsrv_errors(), true));   }

        sqlsrv_close($conn);

        $SQLSrv = new SQLSrv($serverName, $connectionInfo);
        $datas = $SQLSrv->getTableData("TESTTB02");

        $this->assertEquals(1, $datas[0]['primary_key']);
        $this->assertEquals(1, $datas[0]['int_col']);
        $this->assertEquals(3.14, $datas[0]['float_col']);
        $this->assertEquals('abcde     ', $datas[0]['char_col']);
        $this->assertEquals('日本語文字列', $datas[0]['str_col']);
        $this->assertEquals(date_format(new \DateTime('2021/01/01'), 'YmdHis'), date_format($datas[0]['datetime_col'], 'YmdHis'));
        $this->assertEquals(2, $datas[1]['primary_key']);
        $this->assertEquals(2, $datas[1]['int_col']);
        $this->assertEquals(0.01, $datas[1]['float_col']);
        $this->assertEquals('cdefg     ', $datas[1]['char_col']);
        $this->assertEquals('ひらがな文字列', $datas[1]['str_col']);
        $this->assertEquals(date_format(new \DateTime('2021/12/31'), 'YmdHis'), date_format($datas[1]['datetime_col'], 'YmdHis'));

    }

    /** @test */
    public function testInsertData()
    {
        $serverName = "SERV";
        $connectionInfo = array("Database" => "TESTDB", "UID" => "sa", "PWD" => "siokobu8400", "CharacterSet" => "UTF-8");

        $conn = sqlsrv_connect($serverName, $connectionInfo);

        $stmt = sqlsrv_query($conn, self::DROP_TESTTB01);
        if($stmt == false) { die( print_r( sqlsrv_errors(), true));   }

        $stmt = sqlsrv_query($conn, self::CREATE_TESTTB01);
        if($stmt == false) { die( print_r( sqlsrv_errors(), true));   }

        sqlsrv_close($conn);

        $table = 'TESTTB01';

        $data = [
            ['primary_key', 'int_col', 'float_col', 'char_col', 'str_col', 'datetime_col'],
            [1, 1, 3.14, 'abced', '日本語文字列',  '2021-01-01 00:00:00'],
            [2, 2, 0.01, 'fghij', 'ひらがな文字列', '2050-12-31 00:00:00'],
        ];

        $sqlSrv = new SQLSrv($serverName, $connectionInfo);
        $sqlSrv->insertData($table, $data);

        $conn = sqlsrv_connect($serverName, $connectionInfo);

        $result = sqlsrv_query($conn, "SELECT * FROM ".$table." WHERE primary_key = ?;", [1]);
        $row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
        $this->assertEquals($data[1][0],$row['primary_key']);
        $this->assertEquals($data[1][1],$row['int_col']);
        $this->assertEquals($data[1][2],$row['float_col']);
        $this->assertEquals(str_pad($data[1][3],10),$row['char_col']);
        $this->assertEquals($data[1][4],$row['str_col']);
        $this->assertEquals($data[1][5],date_format($row['datetime_col'], 'Y-m-d H:i:s'));

        $result = sqlsrv_query($conn, "SELECT * FROM ".$table." WHERE primary_key = ?;", [2]);
        $row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
        $this->assertEquals($data[2][0],$row['primary_key']);
        $this->assertEquals($data[2][1],$row['int_col']);
        $this->assertEquals($data[2][2],$row['float_col']);
        $this->assertEquals(str_pad($data[2][3],10),$row['char_col']);
        $this->assertEquals($data[2][4],$row['str_col']);
        $this->assertEquals($data[2][5],date_format($row['datetime_col'], 'Y-m-d H:i:s'));
    }
}