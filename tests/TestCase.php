<?php

namespace PHPSpreadsheetDBTest;

class TestCase extends \PHPUnit\Framework\TestCase
{
    const TEMPDIR = __DIR__.DIRECTORY_SEPARATOR;

    public function setUp(): void
    {
        parent::setUp();
        $this->readEnv();
    }

    protected array $env = Array();

    protected function readEnv()
    {
        $lines = file(__DIR__.DIRECTORY_SEPARATOR."env");
        foreach($lines as $line) {
            if(str_starts_with(trim($line), "#")) { continue; }
            if(!strpos($line, "=")) { continue; }
            $this->env[trim(explode("=", $line, 2)[0])] = trim(explode("=", $line, 2)[1]);
        }
    }

    protected function getEnv(string $param)
    {
        return $this->env[$param];
    }
}
