# PhpSpreadsheetDB

PhpSpreadsheet is a library written in pure PHP and offers a function to export data
from DB to Spreadsheet and a function to import data from Spreadsheet to DB

## Supported Databases

Microsoft SQL Server And SQLite is Supported For 
importing to Microsoft SQL Server from Microsoft Excel.

## Supported Spreadsheet

Only Microsoft Excel is Supported.
And only import to Microsoft SQL Server from Microsoft Excel.

## Installation

Use [composer](https://getcomposer.org) to install PhpSpreadsheetDB into your project:

```sh
composer require siokobu/phpspreadsheetdb
```

Often PhpSpreadsheetDB to help PHPUnit test.

```sh
composer require --dev siokobu/phpspreadsheetdb
```

### How to use 

```php
$connectionInfo = array(
    "Database" => $database,
    "UID" => $uid,
    "PWD" => $pwd,
    "CharacterSet" => $charset);
$sqlSrv = new SQLSrv($hostname, $connectionInfo);
$sqlSrv->insertData($tableName, $excel);
```
