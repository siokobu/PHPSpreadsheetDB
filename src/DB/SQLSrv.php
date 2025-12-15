<?php

namespace PHPSpreadsheetDB\DB;

use phpDocumentor\Reflection\Types\Resource_;
use PHPSpreadsheetDB\PHPSpreadsheetDBException;
use PDO;
use PDOException;

class SQLSrv extends DB
{
    /**
     * PHPSpreadsheetDBで利用するためのDBオブジェクトを生成する
     * @param string $host ホスト名
     * @param string $port ポート番号
     * @param string $dbname データベース名
     * @param string $user ユーザ名
     * @param string $password パスワード
     * @throws PHPSpreadsheetDBException PostgreSQLへ接続できなかった場合にスローする
     */
    public function __construct(
                string $host, 
                string $port = '1433', 
                string $dbname, 
                string $user, 
                string $password
    ) {
        try {
            $this->pdo = new PDO(
                'sqlsrv:server='.$host.','.$port.';Database='.$dbname.';TrustServerCertificate=true',
                $user,
                $password
            );
            $this->pdo->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_UTF8);
        } catch (\PDOException $e) {
            throw new PHPSpreadsheetDBException("Connection Error: " . $e->getMessage());
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

    /**
     * @inheritDoc
     */
    public function insertData(string $tableName, array $columns, array $data): void
    {
        // Check if the table has an IDENTITY column
        $ident = $this->pdo->query("SELECT IDENT_CURRENT('".$tableName."')")->fetchAll()[0][0];

        // If there is an IDENTITY column, enable IDENTITY_INSERT
        $ident !== null && $this->pdo->exec("SET IDENTITY_INSERT ".$tableName." ON;");

        // Call the parent method to perform the insertion
        parent::insertData($tableName, $columns, $data);

        // Disable IDENTITY_INSERT after insertion
        $ident !== null && $this->pdo->exec("SET IDENTITY_INSERT ".$tableName." OFF;");
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