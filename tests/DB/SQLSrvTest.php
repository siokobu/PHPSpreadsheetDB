<?php

namespace PHPSpreadsheetDBTest\DB;

use PHPSpreadsheetDB\DB\DB;
use PHPSpreadsheetDB\DB\SQLSrv;
use PHPSpreadsheetDB\PHPSpreadsheetDBException;
use PHPSpreadsheetDBTest\TestCase;

class SQLSrvTest extends TestCase
{
    private $host;

    private $database;

    private $uid;

    private $pwd;

    private $charset;

    private $serverName;

    private $connectionInfo;

    public function setUp(): void
    {
        parent::setUp();
        $file = file(__DIR__.'/'.'env');
        foreach($file as $line) {
            if(str_contains($line, "=")) {
                switch(explode("=", $line, 2)[0]) {
                    case "SQLSRV_HOST":
                        $this->host = trim(explode("=", $line, 2)[1]);
                        break;
                    case "SQLSRV_DATABASE":
                        $this->database = trim(explode("=", $line, 2)[1]);
                        break;
                    case "SQLSRV_UID":
                        $this->uid = trim(explode("=", $line, 2)[1]);
                        break;
                    case "SQLSRV_PWD":
                        $this->pwd = trim(explode("=", $line, 2)[1]);
                        break;
                    case "SQLSRV_CHARSET":
                        $this->charset = trim(explode("=", $line, 2)[1]);
                        break;
                    default:
                }
            }
        }
        $this->serverName = $this->host;
        $this->connectionInfo = array(
            "Database" => $this->database,
            "UID" => $this->uid,
            "PWD" => $this->pwd,
            "CharacterSet" => $this->charset);
    }

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
        $conn = sqlsrv_connect(self::DBHOST, self::CONNINFO);

        $stmt = sqlsrv_query($conn, self::DROP_TESTTB02);
        if($stmt == false) { die( print_r( sqlsrv_errors(), true));   }

        $stmt = sqlsrv_query($conn, self::CREATE_TESTTB02);
        if($stmt == false) { die( print_r( sqlsrv_errors(), true));   }

        $sql = "INSERT INTO TESTTB VALUES(?, ?, ?, ?, ?, ?)";
        $stmt = sqlsrv_prepare($conn, $sql, array(1, 1, 3.14, 'abcde', '日本語文字列', '2021/01/01'));
        if(sqlsrv_execute($stmt) === false) { die( print_r( sqlsrv_errors(), true));   }
        $stmt = sqlsrv_prepare($conn, $sql, array(2, 2, 0.01, 'cdefg', 'ひらがな文字列', '2021/12/31'));
        if(sqlsrv_execute($stmt) === false) { die( print_r( sqlsrv_errors(), true));   }

        sqlsrv_close($conn);

        $SQLSrv = new SQLSrv($this->serverName, $this->connectionInfo);
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
        $serverName = $this->host;
        $connectionInfo = array(
            "Database" => $this->database,
            "UID" => $this->uid,
            "PWD" => $this->pwd,
            "CharacterSet" => $this->charset);

        $conn = sqlsrv_connect($serverName, $connectionInfo);
        if($conn === false) {
            die(print_r(sqlsrv_errors()));
        }

        $stmt = sqlsrv_query($conn, self::DROP_TESTTB01);
        if($stmt == false) { die( print_r( sqlsrv_errors(), true));   }

        $stmt = sqlsrv_query($conn, self::CREATE_TESTTB01);
        if($stmt == false) { die( print_r( sqlsrv_errors(), true));   }

        sqlsrv_close($conn);

        $table = 'TESTTB01';

        $data = [
            ['primary_key', 'int_col', 'float_col', 'char_col', 'str_col', 'datetime_col'],
            ['1', '1', '3.14', 'abced', '日本語文字列',  '2021-01-01 00:00:00'],
            ['2', null, null, null, null, null],
            ['3', '', '', '', '', ''],
            ['4', '2', '0.01', 'fghij', 'ひらがな文字列', '2050-12-31 00:00:00']
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
        $this->assertEquals(null,$row['int_col']);
        $this->assertEquals(null,$row['float_col']);
        $this->assertEquals(null,$row['char_col']);
        $this->assertEquals(null,$row['str_col']);
        $this->assertEquals(null,$row['datetime_col']);

        $result = sqlsrv_query($conn, "SELECT * FROM ".$table." WHERE primary_key = ?;", [3]);
        $row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
        $this->assertEquals($data[3][0],$row['primary_key']);
        $this->assertEquals(0,$row['int_col']);
        $this->assertEquals(0,$row['float_col']);
        $this->assertEquals('          ',$row['char_col']);
        $this->assertEquals('',$row['str_col']);
        $this->assertEquals('1900-01-01 00:00:00',date_format($row['datetime_col'], 'Y-m-d H:i:s'));

        $result = sqlsrv_query($conn, "SELECT * FROM ".$table." WHERE primary_key = ?;", [4]);
        $row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
        $this->assertEquals($data[4][0],$row['primary_key']);
        $this->assertEquals($data[4][1],$row['int_col']);
        $this->assertEquals($data[4][2],$row['float_col']);
        $this->assertEquals(str_pad($data[4][3],10),$row['char_col']);
        $this->assertEquals($data[4][4],$row['str_col']);
        $this->assertEquals($data[4][5],date_format($row['datetime_col'], 'Y-m-d H:i:s'));
    }

    /** @test */
    public function testInsertData_IDENTITYカラムを含む場合()
    {
        $conn = sqlsrv_connect($this->serverName, $this->connectionInfo);
        if($conn === false) { die(print_r(sqlsrv_errors())); }

        $stmt = sqlsrv_query($conn, self::DROP_TABLE_IDENTITY);
        if($stmt == false) { die( print_r( sqlsrv_errors(), true));   }

        $stmt = sqlsrv_query($conn, self::CREATE_TABLE_IDENTITY);
        if($stmt == false) { die( print_r( sqlsrv_errors(), true));   }

        sqlsrv_close($conn);

        $data = [['primary_key', 'int_col'],['1', '1'],['2', '2']];

        $sqlSrv = new SQLSrv($this->serverName, $this->connectionInfo);
        $sqlSrv->insertData(self::TABLE_IDENTITY, $data);

        $conn = sqlsrv_connect($this->serverName, $this->connectionInfo);

        $result = sqlsrv_query($conn, "SELECT * FROM ".self::TABLE_IDENTITY." WHERE primary_key = ?;", [1]);
        $row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
        $this->assertEquals(1,$row['primary_key']);
        $this->assertEquals($data[1][0],$row['int_col']);

        $result = sqlsrv_query($conn, "SELECT * FROM ".self::TABLE_IDENTITY." WHERE primary_key = ?;", [2]);
        $row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
        $this->assertEquals(2,$row['primary_key']);
        $this->assertEquals($data[2][0],$row['int_col']);

    }

    /**@test コネクション確立でExceptionを発生させるテスト */
    public function testNoHost()
    {
        $this->expectException(PHPSpreadsheetDBException::class);
        $this->expectExceptionMessage("Invalid Host");

        $serverName = "NOHOST";
        $connectionInfo = self::CONNINFO;
        $db = new SQLSrv($serverName, $connectionInfo);
    }

    /**@test 認証情報が異なる場合のテスト */
    public function testNoUser()
    {
        $this->expectException(PHPSpreadsheetDBException::class);
        $this->expectExceptionMessage("Invalid User, Password");

        $serverName = self::DBHOST;
        $connectionInfo = array("Database" => self::DATABASE, "UID" => "NOUSER", "PWD" => self::DBPASS, "CharacterSet" => self::DBCHAR);
        $db = new SQLSrv($serverName, $connectionInfo);
    }

    /**@test パスワードが異なる場合のテスト */
    public function testInvalidPassword()
    {
        $this->expectException(PHPSpreadsheetDBException::class);
        $this->expectExceptionMessage("Invalid Host");

        $serverName = self::DBHOST;
        $connectionInfo = array("Database" => self::DATABASE, "UID" => self::DBUSER, "PWD" => "invalidPassword", "CharacterSet" => self::DBCHAR);
        $db = new SQLSrv($serverName, $connectionInfo);
    }

    /**@test パスワードが異なる場合のテスト */
    public function testNoTable()
    {
        $tableName = "NOTABLE";
        $this->expectException(PHPSpreadsheetDBException::class);
        $this->expectExceptionMessage("Invalid TableName. TableName:".$tableName);

        $db = new SQLSrv(self::DBHOST, self::CONNINFO);
        $db->insertData($tableName, []);
    }

    const TABLE_IDENTITY = "TESTTB_IDENTITY";

    const CREATE_TABLE_IDENTITY =
        "CREATE TABLE TESTTB_IDENTITY ("
        ."primary_key int IDENTITY(1,1) NOT NULL PRIMARY KEY,"
        ."int_col integer"
        .");";

    const DROP_TABLE_IDENTITY = "DROP TABLE IF EXISTS TESTTB_IDENTITY";

}