<?php

declare(strict_types=1);


namespace PhpLoggerTests\SetLogFileLevel;

use PhpLogger\Log;
use PhpLoggerTests\Test;

class SetLogFileLevel extends Test
{
    private const MSG = 'foo';
    private const PATH = LOG_FOLDER . 'SetLogFileLevel.log';

    protected function test(): bool
    {
        $msg = self::MSG;
        Log::setup(self::PATH);

        // DEBUG
        $this->testLevel(Log::A_DEBUG);
        if (!$this->matchLastLog(self::PATH, "/^Application Debug: {$msg}/")) {
            return false;
        }

        // INFO
        $this->testLevel(Log::A_INFO);
        if ($this->matchLastLog(self::PATH, "/^Application Debug: {$msg}/", 1)) {
            return false;
        }
        if (!$this->matchLastLog(self::PATH, "/^Application Info: {$msg}/")) {
            return false;
        }

        // WARNING
        $this->testLevel(Log::A_WARNING);
        if ($this->matchLastLog(self::PATH, "/^Application Debug: {$msg}/", 2)) {
            return false;
        }
        if ($this->matchLastLog(self::PATH, "/^Application Info: {$msg}/", 1)) {
            return false;
        }
        if (!$this->matchLastLog(self::PATH, "/^Application Warning: {$msg}/")) {
            return false;
        }

        // ERROR
        $this->testLevel(Log::A_ERROR);
        if ($this->matchLastLog(self::PATH, "/^Application Debug: {$msg}/", 3)) {
            return false;
        }
        if ($this->matchLastLog(self::PATH, "/^Application Info: {$msg}/", 2)) {
            return false;
        }
        if ($this->matchLastLog(self::PATH, "/^Application Warning: {$msg}/", 1)) {
            return false;
        }
        if (!$this->matchLastLog(self::PATH, "/^Application Error: {$msg}/")) {
            return false;
        }

        // ERROR | WARNING
        $this->testLevel(Log::A_ERROR | Log::A_WARNING);
        if ($this->matchLastLog(self::PATH, "/^Application Debug: {$msg}/", 3)) {
            return false;
        }
        if ($this->matchLastLog(self::PATH, "/^Application Info: {$msg}/", 2)) {
            return false;
        }
        if (!$this->matchLastLog(self::PATH, "/^Application Warning: {$msg}/", 1)) {
            return false;
        }
        if (!$this->matchLastLog(self::PATH, "/^Application Error: {$msg}/")) {
            return false;
        }


        return true;
    }

    private function testLevel(int $level): void
    {
        Log::setLogFileLevel(Log::A_ALL);
        Log::debug('test');
        Log::debug('test');
        Log::debug('test');
        Log::debug('test');

        Log::setLogFileLevel($level);
        Log::debug(self::MSG);
        Log::info(self::MSG);
        Log::warning(self::MSG);
        Log::error(self::MSG);
    }
}