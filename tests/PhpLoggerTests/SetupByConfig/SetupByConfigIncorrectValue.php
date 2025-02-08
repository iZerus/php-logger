<?php

declare(strict_types=1);


namespace PhpLoggerTests\SetupByConfig;

use PhpLoggerTests\Test;

abstract class SetupByConfigIncorrectValue extends Test
{
    protected function createConfig(string $property, string $value): void
    {
        file_put_contents($this->getConfigPath(), "$property=$value");
    }

    protected function getConfigPath(): string
    {
        return __DIR__ . '/test.ini';
    }

    protected function getLogPath(): string
    {
        return __DIR__ . '/latest.log';
    }
}