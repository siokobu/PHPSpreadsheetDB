<?php

namespace PHPSpreadsheetDBTest\DB;

use PHPSpreadsheetDB\DB\DB;
use PHPSpreadsheetDB\DB\SQLSrv;
use PHPSpreadsheetDB\PHPSpreadsheetDBException;
use PHPSpreadsheetDBTest\TestCase;

class SQLSrvTest extends TestCase
{
    public function testConstruct()
    {
            $this->expectException(PHPSpreadsheetDBException::class);
            $this->expectExceptionMessage('Invalid User, Password');

            $connectionInfo = array(
                "Database" => self::SQLSRV_DATABASE,
                "UID" => self::SQLSRV_DBUSER,
                "PWD" => 'InvalidPass',
                "CharacterSet" => self::SQLSRV_DBCHAR);
            $SQLSrv = new SQLSrv(self::SQLSRV_DBHOST, $connectionInfo);
    }

    public function testGetColumns()
    {
        $this->refreshDB_SQLSRV();

        $SQLSrv = new SQLSrv(self::SQLSRV_DBHOST, self::SQLSRV_CONNINFO);
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
        $conn = sqlsrv_connect(self::SQLSRV_DBHOST, self::SQLSRV_CONNINFO);

        $stmt = sqlsrv_query($conn, self::SQLSRV_DROP_TESTTB02);
        if($stmt == false) { die( print_r( sqlsrv_errors(), true));   }

        $stmt = sqlsrv_query($conn, self::SQLSRV_CREATE_TESTTB02);
        if($stmt == false) { die( print_r( sqlsrv_errors(), true));   }

        $sql = "INSERT INTO TESTTB01 VALUES(?, ?, ?, ?, ?, ?)";
        $stmt = sqlsrv_prepare($conn, $sql, array(1, 1, 3.14, 'abcde', '日本語文字列', '2021/01/01'));
        if(sqlsrv_execute($stmt) === false) { die( print_r( sqlsrv_errors(), true));   }
        $stmt = sqlsrv_prepare($conn, $sql, array(2, 2, 0.01, 'cdefg', 'ひらがな文字列', '2021/12/31'));
        if(sqlsrv_execute($stmt) === false) { die( print_r( sqlsrv_errors(), true));   }

        sqlsrv_close($conn);

        $SQLSrv = new SQLSrv(self::SQLSRV_DBHOST, self::SQLSRV_CONNINFO);
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
    public function testInsertData1()
    {
        $this->refreshDB_SQLSRV();

        $sqlSrv = new SQLSrv(self::SQLSRV_DBHOST, self::SQLSRV_CONNINFO);
        $sqlSrv->insertData(self::TESTTB01, self::TESTDATADB1);
        $sqlSrv->insertData(self::TESTTB02, self::TESTDATADB2);

        $conn = sqlsrv_connect(self::SQLSRV_DBHOST, self::SQLSRV_CONNINFO);

        $result = sqlsrv_query($conn, "SELECT * FROM ".self::TESTTB01.";");
        $row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
        $this->assertEquals(self::TESTDATARESULT1[1][0],$row['primary_key']);
        $this->assertEquals(self::TESTDATARESULT1[1][1],$row['int_col']);
        $this->assertEquals(self::TESTDATARESULT1[1][2],$row['float_col']);
        $this->assertEquals(self::TESTDATARESULT1[1][3],$row['char_col']);
        $this->assertEquals(self::TESTDATARESULT1[1][4],$row['str_col']);
        $this->assertEquals(self::TESTDATARESULT1[1][5],date_format($row['datetime_col'], 'Y-m-d H:i:s'));
        $row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
        $this->assertEquals(self::TESTDATARESULT1[2][0],$row['primary_key']);
        $this->assertEquals(self::TESTDATARESULT1[2][1],$row['int_col']);
        $this->assertEquals(self::TESTDATARESULT1[2][2],$row['float_col']);
        $this->assertEquals(self::TESTDATARESULT1[2][3],$row['char_col']);
        $this->assertEquals(self::TESTDATARESULT1[2][4],$row['str_col']);
        $this->assertEquals(self::TESTDATARESULT1[2][5],$row['datetime_col']);

        $result = sqlsrv_query($conn, "SELECT * FROM ".self::TESTTB02.";");
        $row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
        $this->assertEquals(self::TESTDATARESULT2[1][0],$row['primary_key']);
        $this->assertEquals(self::TESTDATARESULT2[1][1],$row['int_col']);
        $this->assertEquals(self::TESTDATARESULT2[1][2],$row['float_col']);
        $this->assertEquals(self::TESTDATARESULT2[1][3],$row['char_col']);
        $this->assertEquals(self::TESTDATARESULT2[1][4],$row['str_col']);
        $this->assertEquals(self::TESTDATARESULT2[1][5],date_format($row['datetime_col'], 'Y-m-d H:i:s'));
        $row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
        $this->assertEquals(self::TESTDATARESULT2[2][0],$row['primary_key']);
        $this->assertEquals(self::TESTDATARESULT2[2][1],$row['int_col']);
        $this->assertEquals(self::TESTDATARESULT2[2][2],$row['float_col']);
        $this->assertEquals(self::TESTDATARESULT2[2][3],$row['char_col']);
        $this->assertEquals(self::TESTDATARESULT2[2][4],$row['str_col']);
        $this->assertEquals(self::TESTDATARESULT2[2][5],date_format($row['datetime_col'], 'Y-m-d H:i:s'));
    }

    /**@test コネクション確立でExceptionを発生させるテスト */
    public function testNoHost()
    {
        $this->expectException(PHPSpreadsheetDBException::class);
        $this->expectExceptionMessage("Invalid Host");

        $serverName = "NOHOST";
        $connectionInfo = self::SQLSRV_CONNINFO;
        $db = new SQLSrv($serverName, $connectionInfo);
    }

    /**@test 認証情報が異なる場合のテスト */
    public function testNoUser()
    {
        $this->expectException(PHPSpreadsheetDBException::class);
        $this->expectExceptionMessage("Invalid User, Password");

        $serverName = self::SQLSRV_DBHOST;
        $connectionInfo = array("Database" => self::SQLSRV_DATABASE, "UID" => "NOUSER", "PWD" => self::SQLSRV_DBPASS, "CharacterSet" => self::SQLSRV_DBCHAR);
        $db = new SQLSrv($serverName, $connectionInfo);
    }

    /**@test パスワードが異なる場合のテスト */
    public function testInvalidPassword()
    {
        $this->expectException(PHPSpreadsheetDBException::class);
        $this->expectExceptionMessage("Invalid Host");

        $serverName = self::SQLSRV_DBHOST;
        $connectionInfo = array("Database" => self::SQLSRV_DATABASE, "UID" => self::SQLSRV_DBUSER, "PWD" => "invalidPassword", "CharacterSet" => self::SQLSRV_DBCHAR);
        $db = new SQLSrv($serverName, $connectionInfo);
    }

    /**@test 対象のテーブルが存在しない場合のテスト */
    public function testNoTable()
    {
        $tableName = "NOTABLE";
        $this->expectException(PHPSpreadsheetDBException::class);
        $this->expectExceptionMessage("Invalid TableName. TableName:".$tableName);

        $db = new SQLSrv(self::SQLSRV_DBHOST, self::SQLSRV_CONNINFO);
        $db->insertData($tableName, []);
    }
}