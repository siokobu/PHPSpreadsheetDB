<?php

namespace PHPSpreadsheetDB;

class PHPSpreadsheetDBException extends \Exception
{
    public array $sqlErrors;
}