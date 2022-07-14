<?php

namespace PHPSpreadsheetDBTest\DB;

class TestCase extends \PHPSpreadsheetDBTest\TestCase
{
    protected array $env = Array();

    protected function readEnv()
    {
        $lines = file("env");
        foreach($lines as $line) {
            if(str_starts_with(trim($line), "#")) { continue; }
            if(!strpos($line, "=")) { continue; }
            $this->env[trim(explode("=", $line, 2)[0])] = trim(explode("=", $line, 2)[1]);
        }
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->readEnv();
    }

    protected function getEnv(string $param)
    {
        return $this->env[$param];
    }
}