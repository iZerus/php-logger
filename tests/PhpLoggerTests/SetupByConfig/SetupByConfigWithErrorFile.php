<?php

declare(strict_types=1);


namespace PhpLoggerTests\SetupByConfig;

use PhpLogger\Log;
use PhpLoggerTests\Test;
use RuntimeException;

class SetupByConfigWithErrorFile extends Test
{

    protected function test(): bool
    {
        return $this->testIncorrectPath() && $this->testIncorrectFileFormat();
    }

    private function testIncorrectPath(): bool
    {
        try {
            Log::setupByConfig($this->getLogPath(), rand(1000, 9999) . '/incorrect.ini');
        } catch (RuntimeException $e) {
            return $e->getCode() === Log::ERROR_SETUP_BY_CFG_INCORRECT_INI_PATH;
        }
        return true;
    }

    private function testIncorrectFileFormat(): bool
    {
        $path = __DIR__ . '/test.ini';
        // Создадим файл с неверным форматом JSON
        file_put_contents($path, '{}');
        try {
            Log::setupByConfig($this->getLogPath(), $path);
        } catch (RuntimeException $e) {
            return $e->getCode() === Log::ERROR_SETUP_BY_CFG_INCORRECT_INI_FORMAT;
        }
        return true;
    }

    protected function getLogPath(): string
    {
        return __DIR__ . '/latest.log';
    }
}