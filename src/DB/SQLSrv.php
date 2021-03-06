<?php

namespace PHPSpreadsheetDB\DB;

use phpDocumentor\Reflection\Types\Resource_;
use PHPSpreadsheetDB\PHPSpreadsheetDBException;

class SQLSrv extends DB
{
    /**
     * @var Resource_ DBへのコネクション情報
     */
    private $conn;

    /**
     * PHPSpreadsheetDBで利用するためのDBオブジェクトを生成する
     * @param string <<サーバ名>>\\<<インスタンス名>> の形式でサーバ名を設定する
     * @param string array("Database" => "<<データベース名>>", "UID" => "<<ユーザ名>>", "PWD" => "<<パスワード>>"); の形式でログイン情報を設定する
     * @throws PHPSpreadsheetDBException SQLServerへ接続できなかった場合にスローする
     */
    public function __construct($serverName, $connectionInfo)
    {
        $this->conn = sqlsrv_connect($serverName, $connectionInfo);

        if(!$this->conn) {
            $sqlErrors = sqlsrv_errors();
            $message = "";
            if($sqlErrors[0]['SQLSTATE'] == '28000') {
                $message = "Invalid User, Password";
            } else if ($sqlErrors[0]['SQLSTATE'] == '08001') {
                $message = "Invalid Host";
            } else {
                $message = "Connection Error";
            }
            $e = new PHPSpreadsheetDBException($message);
            $e->sqlErrors = $sqlErrors;
            throw $e;
        }
    }


    /**
     * @param string $tableName
     * @return array
     * @throws PHPSpreadsheetDBException
     */
    public function getColumns(string $tableName): array
    {
        $return = array();

        if(!$stmt = sqlsrv_prepare($this->conn, "SELECT TOP(1) * FROM " . $tableName)) {
            $e = new PHPSpreadsheetDBException();
            $e->sqlErrors = sqlsrv_errors();
            throw $e;
        }

        foreach(sqlsrv_field_metadata($stmt) as $fieldMetaData)
        {
            $metaData = array('Name' => $fieldMetaData['Name'], 'Type' => $this->getDBColumnType($fieldMetaData['Type']));
            array_push($return, $metaData);
//            $return[$fieldMetaData['Name']] = $this->getDBColumnType($fieldMetaData['Type']);
        }

        return $return;
    }

    /**
     * @throws PHPSpreadsheetDBException
     */
    public function getTableData($tableName): array
    {
        $result = array();
        $sql = "SELECT * FROM " . $tableName;
        if(!$stmt = sqlsrv_query($this->conn, $sql)) {
            $e = new PHPSpreadsheetDBException();
            $e->sqlErrors = sqlsrv_errors();
            throw $e;
        }
        while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            array_push($result, $row);
        }
        sqlsrv_free_stmt($stmt);
        return $result;

    }

    public function deleteData(string $tableName): void
    {
        if(!sqlsrv_query($this->conn, 'DELETE FROM '.$tableName.';')){
            $sqlErrors = sqlsrv_errors();
            if($sqlErrors[0]['SQLSTATE'] == '42S02') {
                $message = "Invalid TableName. TableName:".$tableName;
            } else {
                $message = "Error while Deleting Data. tablename:".$tableName;
            }
            $e = new PHPSpreadsheetDBException($message);
            $e->sqlErrors = $sqlErrors;
            throw $e;
        }
    }

    /**
     * @inheritDoc
     */
    public function insertData($tableName, $data)
    {
        if(count($data) === 0) return;

        sqlsrv_query($this->conn, "SET IDENTITY_INSERT ".$tableName." ON;");

        $sql = $this->createPreparedStatement($tableName, $data[0]);

        for($i=1; $i<count($data); $i++) {
            $stmt = sqlsrv_prepare($this->conn, $sql, $data[$i]);
            if(!sqlsrv_execute($stmt)) {
                $e = new PHPSpreadsheetDBException("Invalid Data. TableName:$tableName,Line:$i");
                $e->sqlErrors = sqlsrv_errors();
                throw $e;
            }
        }

        sqlsrv_query($this->conn, "SET IDENTITY_INSERT ".$tableName." OFF;");
    }

    /**
     * @throws PHPSpreadsheetDBException
     */
    private function getDBColumnType($type): int
    {
        $return = null;
        if($type == -5  // bigint
            or $type == -7  // bit
            or $type == 3   // decimal,money,Smalloney
            or $type == 6   // float
            or $type == 4   // int
            or $type == 2   // numeric
            or $type == 5   // smallint
            or $type == 7   // real
            or $type == -6  // tinyint
        ) {
            $return = DB::TYPE_NUMBER;
        } else if($type == -8   // nchar
            or $type == -10     // ntext
            or $type == -9      // nvarchar
            or $type == -1      // text
            or $type == 12      // varchar
            or $type == -10     // ntext
        ) {
            $return = DB::TYPE_STRING;
        } else if($type == 91   // 日付
            or $type == 93      // DATETIME, datetime2,smalldatetime
            or $type == -155    // datetimeoffset
            or $type == -154    // time
            or $type == -2      // timestamp
        ) {
            $return = DB::TYPE_DATETIME;
        } else if($type == -2   // binary
            or $type == -4      // image
            or $type == -3      // varbinary
            or $type == 11      //
        ) {
            $return = DB::TYPE_LOB;
        } else {
            throw new PHPSpreadsheetDBException();
        }

        return $return;
    }
}