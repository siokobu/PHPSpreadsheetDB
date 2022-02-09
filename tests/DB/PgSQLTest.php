<?php

namespace PHPSpreadsheetDBTest\DB;

use PHPSpreadsheetDB\DB\MySQL;
use PHPSpreadsheetDB\DB\PgSQL;
use PHPSpreadsheetDB\PHPSpreadsheetDBException;
use PHPUnit\Framework\TestCase;

class PgSQLTest extends TestCase
{
    private $host = "serv";
    private $user = "tomocky1";
    private $pass = "siokobu8400";
    private $db = "TESTDB";
    private $charset = "UTF8";

    private $ddlCreateTableNumber = "CREATE TABLE TESTTB01 (".
    "id integer PRIMARY KEY,".
    "smallint_col smallint,".
    "integer_col integer,".
    "bigint_col bigint,".
    "decimal_col decimal,".
    "numeric_col numeric,".
    "real_col real,".
    "doubleprecision_col double precision,".
    "money_col money);";

    private $testdataNumber = [
        ['id', 'smallint_col', 'integer_col', 'bigint_col', 'decimal_col', 'numeric_col',
            'real_col','doubleprecision_col','money_col'],
        [1, -32768, -2147483648, -9223372036854775807, 10000, 0, 10000, 0, -92233720368547758],
        [2, 32767, 2147483647, 9223372036854775807, 0.0001, -1, 0.0001, -1, 92233720368547758],
        [3, null, null, null, null, null, null, null, null],
    ];


    private $ddlCreateTableText = "CREATE TABLE TESTTB02 (".
    "id integer PRIMARY KEY,".
    "varchar_col varchar(10),".
    "char_col char(10),".
    "text_col text);";

    private $testdataText = [
        ['id', 'varchar_col', 'char_col', 'text_col'],
        [1, "ソ能表あいうえおかき", 'ソ能表あいうえおかき', 'ソ能表あいうえおかき'],
        [2, "", "", ""],
        [3, "abcdeあいうえお", 'abcdeあいうえお', 'abcdeあいうえお'],
        [4, null, null, null],
        // [5, "12345678901", "12345678901", "12345678901"],
    ];

    private $ddlCreateTableDate = "CREATE TABLE TESTTB03 (".
    "id integer PRIMARY KEY,".
    "timestamp_col timestamp,".
    "timestampwithtz_col timestamp with time zone,".
    "date_col date,".
    "time_col time,".
    "timewithtz_col time with time zone,".
    "interval_col interval);";

    private $testdataDate = [
        ['id', 'timestamp_col', 'timestampwithtz_col', 'date_col', 'time_col', 'timewithtz_col', 'interval_col'],
        [1, "2021/12/31 23:59:59", "2021/12/31 23:59:59", "2021/12/31", "23:59:59", "23:59:59", 10000],
        [2, "1970/1/1 00:00:00", '1970/1/1 00:00:00', '1970/1/1', "00:00:00", "00:00:00", 0],
        [3, null, null, null, null, null, null],
        // [4, "", "", "", "", "", ""],
        // [5, "2022/2/29 00:00:00", '2022/2/29 00:00:00', '2022/2/29', "00:00:00", "00:00:00", 0],
    ];

    private $ddlCreateTableSerial = "CREATE TABLE TESTTB04 (".
    "id serial PRIMARY KEY,".
    "col1 varchar);";

    private $testdataSerial = [
        ['id', 'col1'],
        [1, 'a'],
        [3, 'b'],
        [4, 'c'],
    ];

    public function testConstruct()
    {
        new PgSQL($this->host, $this->user, $this->pass, $this->db, $this->charset);
        $this->assertTrue(true);
    }

    public function testConstructException1()
    {
        $this->expectException(PHPSpreadsheetDBException::class);
        $this->expectExceptionMessage('not connected:');
        new PgSQL("nohost", $this->user, $this->pass, $this->db, $this->charset);
    }

    public function testConstructException2()
    {
        $this->expectException(PHPSpreadsheetDBException::class);
        $this->expectExceptionMessage('not connected:');
        new PgSQL($this->host, "nouser", $this->pass, $this->db, $this->charset);
    }

    public function testConstructException3()
    {
        $this->expectException(PHPSpreadsheetDBException::class);
        $this->expectExceptionMessage('not connected:');
        new PgSQL($this->host, $this->user, "nopass", $this->db, $this->charset);
    }

    public function testConstructException4()
    {
        $this->expectException(PHPSpreadsheetDBException::class);
        $this->expectExceptionMessage('not connected:');
        new PgSQL($this->host, $this->user, $this->pass, "nodb", $this->charset);
    }

    public function testConstructException5()
    {
        $this->expectException(PHPSpreadsheetDBException::class);
        $this->expectExceptionMessage('invalid charset:');
        new PgSQL($this->host, $this->user, $this->pass, $this->db, "nocharset");
    }

    public function testInsertData()
    {
        $pdo = $this->getConnection();
        $this->refreshDB($pdo);

        $pgsql = new PgSQL($this->host, $this->user, $this->pass, $this->db);
        $pgsql->insertData('TESTTB01', $this->testdataNumber);

        $rows = $pdo->query("SELECT * FROM TESTTB01 ORDER BY id")->fetchAll();
        $this->assertEquals(1, $rows[0]['id']);
        $this->assertEquals(-32768, $rows[0]['smallint_col']);
        $this->assertEquals(-2147483648, $rows[0]['integer_col']);
        $this->assertEquals(-9223372036854775807, $rows[0]['bigint_col']);
        $this->assertEquals(10000, $rows[0]['decimal_col']);
        $this->assertEquals(0, $rows[0]['numeric_col']);
        $this->assertEquals(10000, $rows[0]['real_col']);
        $this->assertEquals(0, $rows[0]['doubleprecision_col']);
        $this->assertEquals('-\92,233,720,368,547,758', $rows[0]['money_col']);
        $this->assertEquals(2, $rows[1]['id']);
        $this->assertEquals(32767, $rows[1]['smallint_col']);
        $this->assertEquals(2147483647, $rows[1]['integer_col']);
        $this->assertEquals(9223372036854775807, $rows[1]['bigint_col']);
        $this->assertEquals(0.0001, $rows[1]['decimal_col']);
        $this->assertEquals(-1, $rows[1]['numeric_col']);
        $this->assertEquals(0.0001, $rows[1]['real_col']);
        $this->assertEquals(-1, $rows[1]['doubleprecision_col']);
        $this->assertEquals('\92,233,720,368,547,758', $rows[1]['money_col']);
        $this->assertEquals(3, $rows[2]['id']);
        $this->assertEquals(null, $rows[2]['smallint_col']);
        $this->assertEquals(null, $rows[2]['integer_col']);
        $this->assertEquals(null, $rows[2]['bigint_col']);
        $this->assertEquals(null, $rows[2]['decimal_col']);
        $this->assertEquals(null, $rows[2]['numeric_col']);
        $this->assertEquals(null, $rows[2]['real_col']);
        $this->assertEquals(null, $rows[2]['doubleprecision_col']);
        $this->assertEquals(null, $rows[2]['money_col']);

        $pgsql = new PgSQL($this->host, $this->user, $this->pass, $this->db);
        $pgsql->insertData('TESTTB02', $this->testdataText);

        $rows = $pdo->query("SELECT * FROM TESTTB02 ORDER BY id")->fetchAll();
        $this->assertEquals(1, $rows[0]['id']);
        $this->assertEquals("ソ能表あいうえおかき", $rows[0]['varchar_col']);
        $this->assertEquals('ソ能表あいうえおかき', $rows[0]['char_col']);
        $this->assertEquals('ソ能表あいうえおかき', $rows[0]['text_col']);
        $this->assertEquals(2, $rows[1]['id']);
        $this->assertEquals("", $rows[1]['varchar_col']);
        $this->assertEquals('          ', $rows[1]['char_col']);
        $this->assertEquals('', $rows[1]['text_col']);
        $this->assertEquals(3, $rows[2]['id']);
        $this->assertEquals("abcdeあいうえお", $rows[2]['varchar_col']);
        $this->assertEquals('abcdeあいうえお', $rows[2]['char_col']);
        $this->assertEquals('abcdeあいうえお', $rows[2]['text_col']);
        $this->assertEquals(4, $rows[3]['id']);
        $this->assertEquals(null, $rows[3]['varchar_col']);
        $this->assertEquals(null, $rows[3]['char_col']);
        $this->assertEquals(null, $rows[3]['text_col']);

        $pgsql = new PgSQL($this->host, $this->user, $this->pass, $this->db);
        $pgsql->insertData('TESTTB03', $this->testdataDate);

        $rows = $pdo->query("SELECT * FROM TESTTB03 ORDER BY id")->fetchAll();
        $this->assertEquals(1, $rows[0]['id']);
        $this->assertEquals("2021-12-31 23:59:59", $rows[0]['timestamp_col']);
        $this->assertEquals("2021-12-31 23:59:59+09", $rows[0]['timestampwithtz_col']);
        $this->assertEquals("2021-12-31", $rows[0]['date_col']);
        $this->assertEquals("23:59:59", $rows[0]['time_col']);
        $this->assertEquals("23:59:59+09", $rows[0]['timewithtz_col']);
        $this->assertEquals('02:46:40', $rows[0]['interval_col']);
        $this->assertEquals(2, $rows[1]['id']);
        $this->assertEquals("1970-01-01 00:00:00", $rows[1]['timestamp_col']);
        $this->assertEquals('1970-01-01 00:00:00+09', $rows[1]['timestampwithtz_col']);
        $this->assertEquals('1970-01-01', $rows[1]['date_col']);
        $this->assertEquals("00:00:00", $rows[1]['time_col']);
        $this->assertEquals("00:00:00+09", $rows[1]['timewithtz_col']);
        $this->assertEquals("00:00:00", $rows[1]['interval_col']);
        $this->assertEquals(3, $rows[2]['id']);
        $this->assertEquals(null, $rows[2]['timestamp_col']);
        $this->assertEquals(null, $rows[2]['timestampwithtz_col']);
        $this->assertEquals(null, $rows[2]['date_col']);
        $this->assertEquals(null, $rows[2]['time_col']);
        $this->assertEquals(null, $rows[2]['timewithtz_col']);
        $this->assertEquals(null, $rows[2]['interval_col']);

        $pgsql = new PgSQL($this->host, $this->user, $this->pass, $this->db);
        $pgsql->insertData('TESTTB04', $this->testdataSerial);

        $rows = $pdo->query("SELECT * FROM TESTTB04 ORDER BY id")->fetchAll();
        $this->assertEquals(1, $rows[0]['id']);
        $this->assertEquals("a", $rows[0]['col1']);
        $this->assertEquals(3, $rows[1]['id']);
        $this->assertEquals("b", $rows[1]['col1']);
        $this->assertEquals(4, $rows[2]['id']);
        $this->assertEquals("c", $rows[2]['col1']);
//        $pdo->exec("INSERT INTO TESTTB04 (col1) VALUES('d');");
    }

    private function refreshDB($pdo)
    {
        $pdo->exec('DROP TABLE IF EXISTS TESTTB01;');
        $pdo->exec('DROP TABLE IF EXISTS TESTTB02;');
        $pdo->exec('DROP TABLE IF EXISTS TESTTB03;');
        $pdo->exec('DROP TABLE IF EXISTS TESTTB04;');
        $pdo->exec($this->ddlCreateTableNumber);
        $pdo->exec($this->ddlCreateTableText);
        $pdo->exec($this->ddlCreateTableDate);
        $pdo->exec($this->ddlCreateTableSerial);

    }

    private function getConnection()
    {
        $dsn = 'pgsql:dbname='.$this->db.';host='.$this->host.';';
        $pdo = new \PDO($dsn, $this->user, $this->pass);
        $pdo->query("SET NAMES '".$this->charset."'");
        return $pdo;
    }
}
