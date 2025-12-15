# PhpSpreadsheetDB

PhpSpreadsheet is a library written in pure PHP and offers a function to export data
from DB to Spreadsheet and a function to import data from Spreadsheet to DB

## Supported Databases

Microsoft SQL Server And SQLite, PostgreSQL, MariaDB is Supported.

## Supported Spreadsheet

Only Microsoft Excel is Supported.

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
- SQL Server
```php
$db = new SQLSrv($host, $port, $database, $user, $password);
$xlsx = new Xlsx($path);
$phpSpreadsheetDB = new PHPSpreadsheetDB($SQLSrv, $xlsx);
$phpSpreadsheetDB->import();
```

- Postgres
```php
$db = new Postgres($host, $port, $database, $user, $password);
$xlsx = new Xlsx($path);
$phpSpreadsheetDB = new PHPSpreadsheetDB($postgres, $xlsx);
$phpSpreadsheetDB->import();
```

- MariaDB
```php
$db = new MariaDB($host, $port, $database, $user, $password);
$xlsx = new Xlsx($path);
$phpSpreadsheetDB = new PHPSpreadsheetDB($db, $xlsx);
$phpSpreadsheetDB->import();
```

- SQLite
```php
$db = new SQLite($filename);
$xlsx = new Xlsx($path);
$phpSpreadsheetDB = new PHPSpreadsheetDB($db, $xlsx);
$phpSpreadsheetDB->import();
```