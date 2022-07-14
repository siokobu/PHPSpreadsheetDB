<?php

namespace PHPSpreadsheetDBTest\DB;

use PHPSpreadsheetDB\DB\DB;
use PHPSpreadsheetDB\DB\SQLSrv;
use PHPSpreadsheetDB\PHPSpreadsheetDBException;


class SQLSrvTest extends TestCase
{
    private string $database;

    private string $uid;

    private string $pwd;

    private string $charset;

    private array $connectionInfo;

    private string $serverName;

    public function setUp(): void
    {
        parent::setUp();
        $this->database = $this->getEnv("SQLSRV_DATABASE");
        $this->uid = $this->getEnv("SQLSRV_UID");
        $this->pwd = $this->getEnv("SQLSRV_PWD");
        $this->charset = $this->getEnv("SQLSRV_CHARSET");
        $this->connectionInfo['Database'] = $this->database;
        $this->connectionInfo['UID'] = $this->uid;
        $this->connectionInfo['PWD'] = $this->pwd;
        $this->connectionInfo['CharacterSet'] = $this->charset;
        $this->serverName = $this->getEnv("SQLSRV_HOST");
    }

    private function prepareTable($table, $ddl)
    {
        // 事前準備
        $conn = sqlsrv_connect($this->serverName, $this->connectionInfo);
        if($conn === false) { die(print_r(sqlsrv_errors())); }

        $stmt = sqlsrv_query($conn, "DROP TABLE IF EXISTS ".$table);
        if($stmt == false) { die( print_r( sqlsrv_errors(), true));   }

        $stmt = sqlsrv_query($conn, $ddl);
        if($stmt == false) { die( print_r( sqlsrv_errors(), true));   }

        sqlsrv_close($conn);
    }

    public function testGetColumns()
    {
        $this->refreshDB();

        $SQLSrv = new SQLSrv($this->serverName, $this->connectionInfo);
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
        $conn = sqlsrv_connect($this->serverName, $this->connectionInfo);

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
        $table = "TESTTB01";
        $conn = sqlsrv_connect($this->serverName, $this->connectionInfo);
        if($conn === false) { die(print_r(sqlsrv_errors())); }

        $stmt = sqlsrv_query($conn, "DROP TABLE IF EXISTS ".$table);
        if($stmt == false) { die( print_r( sqlsrv_errors(), true));   }

        $stmt = sqlsrv_query($conn, self::CREATE_TESTTB01);
        if($stmt == false) { die( print_r( sqlsrv_errors(), true));   }

        sqlsrv_close($conn);

        $data = [
            ['primary_key', 'int_col', 'float_col', 'char_col', 'str_col', 'datetime_col'],
            ['1', '1', '3.14', 'abced', '日本語文字列',  '2021-01-01 00:00:00'],
            ['2', null, null, null, null, null],
            ['3', '', '', '', '', ''],
            ['4', '2', '0.01', 'fghij', 'ひらがな文字列', '2050-12-31 00:00:00']
        ];

        $sqlSrv = new SQLSrv($this->serverName, $this->connectionInfo);
        $sqlSrv->insertData($table, $data);

        $conn = sqlsrv_connect($this->serverName, $this->connectionInfo);

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

    private function testInsertData_数値系カラム確認用のテーブル準備($table)
    {
        $conn = sqlsrv_connect($this->serverName, $this->connectionInfo);
        if($conn === false) { die(print_r(sqlsrv_errors())); }

        $stmt = sqlsrv_query($conn, "DROP TABLE IF EXISTS ".$table);
        if($stmt == false) { die( print_r( sqlsrv_errors(), true));   }

        $sql = "CREATE TABLE ".$table." ("
            ."primary_key INTEGER IDENTITY(1,1) NOT NULL PRIMARY KEY, "
            ."bigint_col bigint, "
            ."bit_col bit, "
            ."decimal_col decimal, "
            ."money_col money, "
            ."smallmoney_col smallmoney, "
            ."float_col float, "
            ."int_col int, "
            ."numeric_col numeric, "
            ."smallint_col smallint, "
            ."real_col real, "
            ."tinyint_col tinyint);";
        $stmt = sqlsrv_query($conn, $sql);
        if($stmt == false) { die( print_r( sqlsrv_errors(), true));   }

        sqlsrv_close($conn);
    }

    /** @test */
    public function testInsertData_数値系カラムの確認()
    {
        $table = "TESTTB01";
        $this->testInsertData_数値系カラム確認用のテーブル準備($table);

        // see https://docs.microsoft.com/ja-jp/sql/t-sql/data-types/numeric-types?view=sql-server-ver16
        // 上記のURLに記載の最大値、最小値を確認
        //　decimal、numericについては、最大と思われる桁数を確認
        $data = [
            ['primary_key', 'bigint_col', 'bit_col', 'decimal_col', 'money_col', 'smallmoney_col', 'float_col', 'int_col', 'numeric_col', 'smallint_col', 'real_col', 'tinyint_col'],
            [1, '1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '1'],
            [2, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1],
            [3, null, null, null, null, null, null, null, null, null, null, null],
//            [4, -9223372036854775808, 0, 0.000000000000001, -922337203685477.5808, -214748.3648, -1.79E+308, -2147483648, 0.000000000000001, -32768, -3.40E+38, 0]
            [4, -9223372036854775808, 0, 0.000000000000001, -922337203685477.5625, -214748.3648, -1.79E+308, -2147483648, 0.000000000000001, -32768, 1, 0],
//            [5, 9223372036854775807, 1, 99999999999999999, 922337203685477.5807, 214748.3647, 1.79E+308, 2147483647, 99999999999999999, 32767, 3.40E+38, 255]
            [5, 9223372036854775807, 1, 999999999999999999, 922337203685477.5624, 214748.3647, 1.79E+308, 2147483647, 999999999999999999, 32767, 1, 255]
        ];

        $sqlSrv = new SQLSrv($this->serverName, $this->connectionInfo);
        $sqlSrv->insertData($table, $data);

        $conn = sqlsrv_connect($this->serverName, $this->connectionInfo);

        $i = 1;
        $result = sqlsrv_query($conn, "SELECT * FROM ".$table." WHERE primary_key = ?;", [$i]);
        $row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
        $this->assertEquals($data[$i][0],$row['primary_key']);
        $this->assertEquals($data[$i][1],$row['bigint_col']);
        $this->assertEquals($data[$i][2],$row['bit_col']);
        $this->assertEquals($data[$i][3],$row['decimal_col']);
        $this->assertEquals(number_format($data[$i][4], 4),$row['money_col']);
        $this->assertEquals(number_format($data[$i][5], 4),$row['smallmoney_col']);
        $this->assertEquals($data[$i][6],$row['float_col']);
        $this->assertEquals($data[$i][7],$row['int_col']);
        $this->assertEquals($data[$i][8],$row['numeric_col']);
        $this->assertEquals($data[$i][9],$row['smallint_col']);
        $this->assertEquals($data[$i][10],$row['real_col']);
        $this->assertEquals($data[$i][11],$row['tinyint_col']);

        $i = 2;
        $result = sqlsrv_query($conn, "SELECT * FROM ".$table." WHERE primary_key = ?;", [$i]);
        $row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
        $this->assertEquals($data[$i][0],$row['primary_key']);
        $this->assertEquals($data[$i][1],$row['bigint_col']);
        $this->assertEquals($data[$i][2],$row['bit_col']);
        $this->assertEquals($data[$i][3],$row['decimal_col']);
        $this->assertEquals(number_format($data[$i][4], 4),$row['money_col']);
        $this->assertEquals(number_format($data[$i][5], 4),$row['smallmoney_col']);
        $this->assertEquals($data[$i][6],$row['float_col']);
        $this->assertEquals($data[$i][7],$row['int_col']);
        $this->assertEquals($data[$i][8],$row['numeric_col']);
        $this->assertEquals($data[$i][9],$row['smallint_col']);
        $this->assertEquals($data[$i][10],$row['real_col']);
        $this->assertEquals($data[$i][11],$row['tinyint_col']);

        $i = 3;
        $result = sqlsrv_query($conn, "SELECT * FROM ".$table." WHERE primary_key = ?;", [$i]);
        $row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
        $this->assertEquals($data[$i][0],$row['primary_key']);
        $this->assertEquals($data[$i][1],$row['bigint_col']);
        $this->assertEquals($data[$i][2],$row['bit_col']);
        $this->assertEquals($data[$i][3],$row['decimal_col']);
        $this->assertEquals($data[$i][4],$row['money_col']);
        $this->assertEquals($data[$i][5],$row['smallmoney_col']);
        $this->assertEquals($data[$i][6],$row['float_col']);
        $this->assertEquals($data[$i][7],$row['int_col']);
        $this->assertEquals($data[$i][8],$row['numeric_col']);
        $this->assertEquals($data[$i][9],$row['smallint_col']);
        $this->assertEquals($data[$i][10],$row['real_col']);
        $this->assertEquals($data[$i][11],$row['tinyint_col']);

        $i = 4;
        $result = sqlsrv_query($conn, "SELECT * FROM ".$table." WHERE primary_key = ?;", [$i]);
        $row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
        $this->assertEquals($data[$i][0],$row['primary_key']);
        $this->assertEquals($data[$i][1],$row['bigint_col']);
        $this->assertEquals($data[$i][2],$row['bit_col']);
        $this->assertEquals($data[$i][3],$row['decimal_col']);
        $this->assertEquals($data[$i][4],$row['money_col']);
        $this->assertEquals($data[$i][5],$row['smallmoney_col']);
        $this->assertEquals($data[$i][6],$row['float_col']);
        $this->assertEquals($data[$i][7],$row['int_col']);
        $this->assertEquals($data[$i][8],$row['numeric_col']);
        $this->assertEquals($data[$i][9],$row['smallint_col']);
        $this->assertEquals($data[$i][10],$row['real_col']);
        $this->assertEquals($data[$i][11],$row['tinyint_col']);

        $i = 5;
        $result = sqlsrv_query($conn, "SELECT * FROM ".$table." WHERE primary_key = ?;", [$i]);
        $row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
        $this->assertEquals($data[$i][0],$row['primary_key']);
        $this->assertEquals($data[$i][1],$row['bigint_col']);
        $this->assertEquals($data[$i][2],$row['bit_col']);
        $this->assertEquals($data[$i][3],$row['decimal_col']);
        $this->assertEquals($data[$i][4],$row['money_col']);
        $this->assertEquals($data[$i][5],$row['smallmoney_col']);
        $this->assertEquals($data[$i][6],$row['float_col']);
        $this->assertEquals($data[$i][7],$row['int_col']);
        $this->assertEquals($data[$i][8],$row['numeric_col']);
        $this->assertEquals($data[$i][9],$row['smallint_col']);
        $this->assertEquals($data[$i][10],$row['real_col']);
        $this->assertEquals($data[$i][11],$row['tinyint_col']);
    }

    /** @test */
    public function testInsertData_数値系カラムの確認_DecimalNumeric以外が空文字()
    {
        $table = "TESTTB01";
        $this->testInsertData_数値系カラム確認用のテーブル準備($table);

        $data = [
            ['primary_key', 'bigint_col', 'bit_col', 'decimal_col', 'money_col', 'smallmoney_col', 'float_col', 'int_col', 'numeric_col', 'smallint_col', 'real_col', 'tinyint_col'],
            [1, '', '', 1, '', '', '', '', 1, '', '', '']
        ];

        $sqlSrv = new SQLSrv($this->serverName, $this->connectionInfo);
        $sqlSrv->insertData($table, $data);

        $this->assertTrue(true);
    }

    /** @test */
    public function testInsertData_数値系カラムの確認_DecimalがNULL()
    {
        $table = "TESTTB01";
        $this->testInsertData_数値系カラム確認用のテーブル準備($table);

        $this->expectException(PHPSpreadsheetDBException::class);
        $this->expectExceptionMessage("Invalid Data. TableName:".$table.",Line:1");

        $data = [
            ['primary_key', 'bigint_col', 'bit_col', 'decimal_col', 'money_col', 'smallmoney_col', 'float_col', 'int_col', 'numeric_col', 'smallint_col', 'real_col', 'tinyint_col'],
            [1, 1, 1, '', 1, 1, 1, 1, 1, 1, 1, 1]
        ];

        $sqlSrv = new SQLSrv($this->serverName, $this->connectionInfo);
        $sqlSrv->insertData($table, $data);
    }

    /** @test */
    public function testInsertData_数値系カラムの確認_NumericがNULL()
    {
        $table = "TESTTB01";
        $this->testInsertData_数値系カラム確認用のテーブル準備($table);

        $this->expectException(PHPSpreadsheetDBException::class);
        $this->expectExceptionMessage("Invalid Data. TableName:".$table.",Line:1");

        $data = [
            ['primary_key', 'bigint_col', 'bit_col', 'decimal_col', 'money_col', 'smallmoney_col', 'float_col', 'int_col', 'numeric_col', 'smallint_col', 'real_col', 'tinyint_col'],
            [1, 1, 1, 1, 1, 1, 1, 1, '', 1, 1, 1]
        ];

        $sqlSrv = new SQLSrv($this->serverName, $this->connectionInfo);
        $sqlSrv->insertData($table, $data);
    }

    private function testInsertData_文字列系カラム確認用のテーブル準備($table)
    {
        $conn = sqlsrv_connect($this->serverName, $this->connectionInfo);
        if($conn === false) { die(print_r(sqlsrv_errors())); }

        $stmt = sqlsrv_query($conn, "DROP TABLE IF EXISTS ".$table);
        if($stmt == false) { die( print_r( sqlsrv_errors(), true));   }

        $sql = "CREATE TABLE ".$table." ("
            ."primary_key INTEGER IDENTITY(1,1) NOT NULL PRIMARY KEY, "
            ."char_col char, "
            ."char3_col char(3), "
            ."nchar_col nchar, "
            ."nchar3_col nchar(3), "
            ."varchar_col varchar , "
            ."varchar3_col varchar(3) , "
            ."nvarchar_col nvarchar , "
            ."nvarchar3_col nvarchar(3) , "
            ."text_col text);";
        $stmt = sqlsrv_query($conn, $sql);
        if($stmt == false) { die( print_r( sqlsrv_errors(), true));   }

        sqlsrv_close($conn);
    }

    /** @test */
    public function testInsertData_文字列系カラムの確認()
    {
        $table = "TESTTB";
        $this->testInsertData_文字列系カラム確認用のテーブル準備($table);

        // see https://docs.microsoft.com/ja-jp/sql/t-sql/data-types/char-and-varchar-transact-sql?view=sql-server-ver16
        // 上記のURLに記載の最大値、最小値を確認
        //　decimal、numericについては、最大と思われる桁数を確認
        $data = [
            ['primary_key', 'char_col', 'char3_col', 'nchar_col', 'nchar3_col', 'varchar_col', 'varchar3_col', 'nvarchar_col', 'nvarchar3_col', 'text_col'],
            [1, '1', '1', '1', '1', '1', '1', '1', '1', '1'],
            [2, null, null, null, null, null, null, null, null, null],
            [3, '', '', '', '', '', '', '', '', ''],
            [4, 'a', 'aaa', 'a', 'aaa', 'a', 'aaa', 'a', 'aaa', 'aaa'],
            [5, 'a', 'aaa', 'あ', 'あああ', 'a', 'aaa', 'あ', 'あああ', 'あああ'],
        ];

        $sqlSrv = new SQLSrv($this->serverName, $this->connectionInfo);
        $sqlSrv->insertData($table, $data);

        $conn = sqlsrv_connect($this->serverName, $this->connectionInfo);

        $i = 1;
        $result = sqlsrv_query($conn, "SELECT * FROM " . $table . " WHERE primary_key = ?;", [$i]);
        $row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
        $this->assertEquals($data[$i][0], $row['primary_key']);
        $this->assertEquals($data[$i][1], $row['char_col']);
        $this->assertEquals(str_pad($data[$i][2], 3), $row['char3_col']);
        $this->assertEquals($data[$i][3], $row['nchar_col']);
        $this->assertEquals(str_pad($data[$i][4], 3), $row['nchar3_col']);
        $this->assertEquals($data[$i][5], $row['varchar_col']);
        $this->assertEquals($data[$i][6], $row['varchar3_col']);
        $this->assertEquals($data[$i][7], $row['nvarchar_col']);
        $this->assertEquals($data[$i][8], $row['nvarchar3_col']);
        $this->assertEquals($data[$i][9], $row['text_col']);

        $i = 2;
        $result = sqlsrv_query($conn, "SELECT * FROM " . $table . " WHERE primary_key = ?;", [$i]);
        $row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
        $this->assertEquals($data[$i][0], $row['primary_key']);
        $this->assertEquals($data[$i][1], $row['char_col']);
        $this->assertEquals($data[$i][2], $row['char3_col']);
        $this->assertEquals($data[$i][3], $row['nchar_col']);
        $this->assertEquals($data[$i][4], $row['nchar3_col']);
        $this->assertEquals($data[$i][5], $row['varchar_col']);
        $this->assertEquals($data[$i][6], $row['varchar3_col']);
        $this->assertEquals($data[$i][7], $row['nvarchar_col']);
        $this->assertEquals($data[$i][8], $row['nvarchar3_col']);
        $this->assertEquals($data[$i][9], $row['text_col']);

        $i = 3;
        $result = sqlsrv_query($conn, "SELECT * FROM " . $table . " WHERE primary_key = ?;", [$i]);
        $row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
        $this->assertEquals($data[$i][0], $row['primary_key']);
        $this->assertEquals(str_pad($data[$i][1], 1), $row['char_col']);
        $this->assertEquals(str_pad($data[$i][2], 3), $row['char3_col']);
        $this->assertEquals(str_pad($data[$i][3], 1), $row['nchar_col']);
        $this->assertEquals(str_pad($data[$i][4], 3), $row['nchar3_col']);
        $this->assertEquals($data[$i][5], $row['varchar_col']);
        $this->assertEquals($data[$i][6], $row['varchar3_col']);
        $this->assertEquals($data[$i][7], $row['nvarchar_col']);
        $this->assertEquals($data[$i][8], $row['nvarchar3_col']);
        $this->assertEquals($data[$i][9], $row['text_col']);

        $i = 4;
        $result = sqlsrv_query($conn, "SELECT * FROM " . $table . " WHERE primary_key = ?;", [$i]);
        $row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
        $this->assertEquals($data[$i][0], $row['primary_key']);
        $this->assertEquals($data[$i][1], $row['char_col']);
        $this->assertEquals(str_pad($data[$i][2], 3), $row['char3_col']);
        $this->assertEquals($data[$i][3], $row['nchar_col']);
        $this->assertEquals(str_pad($data[$i][4], 3), $row['nchar3_col']);
        $this->assertEquals($data[$i][5], $row['varchar_col']);
        $this->assertEquals($data[$i][6], $row['varchar3_col']);
        $this->assertEquals($data[$i][7], $row['nvarchar_col']);
        $this->assertEquals($data[$i][8], $row['nvarchar3_col']);
        $this->assertEquals($data[$i][9], $row['text_col']);

        $i = 5;
        $result = sqlsrv_query($conn, "SELECT * FROM " . $table . " WHERE primary_key = ?;", [$i]);
        $row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
        $this->assertEquals($data[$i][0], $row['primary_key']);
        $this->assertEquals($data[$i][1], $row['char_col']);
        $this->assertEquals(str_pad($data[$i][2], 3), $row['char3_col']);
        $this->assertEquals($data[$i][3], $row['nchar_col']);
        $this->assertEquals(str_pad($data[$i][4], 3), $row['nchar3_col']);
        $this->assertEquals($data[$i][5], $row['varchar_col']);
        $this->assertEquals($data[$i][6], $row['varchar3_col']);
        $this->assertEquals($data[$i][7], $row['nvarchar_col']);
        $this->assertEquals($data[$i][8], $row['nvarchar3_col']);
        $this->assertEquals($data[$i][9], $row['text_col']);
    }

    private function testInsertData_文字列系カラムの確認_Exceptionテストの準備($data)
    {
        $table = "TESTTB";
        $this->testInsertData_文字列系カラム確認用のテーブル準備($table);

        $this->expectException(PHPSpreadsheetDBException::class);
        $this->expectExceptionMessage("Invalid Data. TableName:".$table.",Line:1");

        $sqlSrv = new SQLSrv($this->serverName, $this->connectionInfo);
        $sqlSrv->insertData($table, $data);
    }

    /** @test */
    public function testInsertData_文字列系カラムの確認_char_col_文字数越え()
    {
        $data = [
            ['primary_key', 'char_col', 'char3_col', 'nchar_col', 'nchar3_col', 'varchar_col', 'varchar3_col', 'nvarchar_col', 'nvarchar3_col', 'text_col'],
            [1, 'aa', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a'],
        ];
        $this->testInsertData_文字列系カラムの確認_Exceptionテストの準備($data);
    }

    /** @test */
    public function testInsertData_文字列系カラムの確認_char_col_日本語()
    {
        $data = [
            ['primary_key', 'char_col', 'char3_col', 'nchar_col', 'nchar3_col', 'varchar_col', 'varchar3_col', 'nvarchar_col', 'nvarchar3_col', 'text_col'],
            [1, 'あ', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a'],
        ];
        $this->testInsertData_文字列系カラムの確認_Exceptionテストの準備($data);
    }

    /** @test */
    public function testInsertData_文字列系カラムの確認_nchar_col_文字数越え()
    {
        $data = [
            ['primary_key', 'char_col', 'char3_col', 'nchar_col', 'nchar3_col', 'varchar_col', 'varchar3_col', 'nvarchar_col', 'nvarchar3_col', 'text_col'],
            [1, 'a', 'a', 'aa', 'a', 'a', 'a', 'a', 'a', 'a'],
        ];
        $this->testInsertData_文字列系カラムの確認_Exceptionテストの準備($data);
    }

    /** @test */
    public function testInsertData_文字列系カラムの確認_varchar_文字数越え()
    {
        $data = [
            ['primary_key', 'char_col', 'char3_col', 'nchar_col', 'nchar3_col', 'varchar_col', 'varchar3_col', 'nvarchar_col', 'nvarchar3_col', 'text_col'],
            [1, 'a', 'a', 'a', 'a', 'aa', 'a', 'a', 'a', 'a'],
        ];
        $this->testInsertData_文字列系カラムの確認_Exceptionテストの準備($data);
    }

    /** @test */
    public function testInsertData_文字列系カラムの確認_varchar_日本語()
    {
        $data = [
            ['primary_key', 'char_col', 'char3_col', 'nchar_col', 'nchar3_col', 'varchar_col', 'varchar3_col', 'nvarchar_col', 'nvarchar3_col', 'text_col'],
            [1, 'a', 'a', 'a', 'a', 'あ', 'a', 'a', 'a', 'a'],
        ];
        $this->testInsertData_文字列系カラムの確認_Exceptionテストの準備($data);
    }

    /** @test */
    public function testInsertData_文字列系カラムの確認_nvarchar_文字数越え()
    {
        $data = [
            ['primary_key', 'char_col', 'char3_col', 'nchar_col', 'nchar3_col', 'varchar_col', 'varchar3_col', 'nvarchar_col', 'nvarchar3_col', 'text_col'],
            [1, 'a', 'a', 'a', 'a', 'a', 'a', 'aa', 'a', 'a'],
        ];
        $this->testInsertData_文字列系カラムの確認_Exceptionテストの準備($data);
    }

    /** @test */
    public function testInsertData_IDENTITYカラムを含む場合()
    {
        // このテストで利用するテーブル
        $table = "TESTTB_IDENTITY";

        $ddl = "CREATE TABLE $table ("
            ."primary_key integer IDENTITY(1,1) NOT NULL PRIMARY KEY,"
            ."int_col integer"
            .");";
        $this->prepareTable($table, $ddl);

        // テスト実施
        $data = [['primary_key', 'int_col'],['1', '1'],['2', '2']];

        $sqlSrv = new SQLSrv($this->serverName, $this->connectionInfo);
        $sqlSrv->insertData($table, $data);

        // 結果検証
        $conn = sqlsrv_connect($this->serverName, $this->connectionInfo);

        $result = sqlsrv_query($conn, "SELECT * FROM ".$table." WHERE primary_key = ?;", [1]);
        $row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
        $this->assertEquals(1,$row['primary_key']);
        $this->assertEquals($data[1][0],$row['int_col']);

        $result = sqlsrv_query($conn, "SELECT * FROM ".$table." WHERE primary_key = ?;", [2]);
        $row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
        $this->assertEquals(2,$row['primary_key']);
        $this->assertEquals($data[2][0],$row['int_col']);
    }

    /**@test コネクション確立でExceptionを発生させるテスト */
    public function testNoHost()
    {
        $this->expectException(PHPSpreadsheetDBException::class);
        $this->expectExceptionMessage("Invalid Host");

        new SQLSrv("NOHOST", $this->connectionInfo);
    }

    /**@test 認証情報が異なる場合のテスト */
    public function testNoUser()
    {
        $this->expectException(PHPSpreadsheetDBException::class);
        $this->expectExceptionMessage("Invalid User, Password");

        new SQLSrv(
            $this->serverName,
            array(
                "Database" => $this->database,
                "UID" => "NOUSER",
                "PWD" => $this->pwd,
                "CharacterSet" => $this->charset
            ));
    }

    /**@test パスワードが異なる場合のテスト */
    public function testInvalidPassword()
    {
        $this->expectException(PHPSpreadsheetDBException::class);
        $this->expectExceptionMessage("Invalid User, Password");

        new SQLSrv(
            $this->serverName,
            array(
                "Database" => $this->database,
                "UID" => $this->uid,
                "PWD" => "InvalidPassword",
                "CharacterSet" => $this->charset
            ));
    }

    /**@test パスワードが異なる場合のテスト */
    public function testNoTable()
    {
        $tableName = "NOTABLE";
        $this->expectException(PHPSpreadsheetDBException::class);
        $this->expectExceptionMessage("Invalid TableName. TableName:".$tableName);

        $db = new SQLSrv($this->serverName, $this->connectionInfo);
        $db->insertData($tableName, []);
    }

    private function n(string $c, int $n): string
    {
        $r = "";
        for($i = 0; $i < $n; $i++) {
            $r = $r . $c;
        }
        return $r;
    }

}