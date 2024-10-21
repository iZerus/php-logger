<?php

declare(strict_types=1);


namespace PhpLoggerTests\SetLogFileLevelByName;

use PhpLogger\Log;
use PhpLoggerTests\Test;

class SetLogFileLevelByName extends Test
{
    private const MSG = 'foo';
    private const PATH = LOG_FOLDER . 'SetLogFileLevelByName.log';

    protected function test(): bool
    {
        $msg = self::MSG;
        Log::setup(self::PATH);

        // DEBUG
        $this->testLevel(Log::S_DEBUG);
        if (!$this->matchLastLog(self::PATH, "/^Application Debug: {$msg}/", 3)) {
            return false;
        }
        if (!$this->matchLastLog(self::PATH, "/^Application Info: {$msg}/", 2)) {
            return false;
        }
        if (!$this->matchLastLog(self::PATH, "/^Application Warning: {$msg}/", 1)) {
            return false;
        }
        if (!$this->matchLastLog(self::PATH, "/^Application Error: {$msg}/")) {
            return false;
        }

        // INFO
        $this->testLevel(Log::S_INFO);
        if ($this->matchLastLog(self::PATH, "/^Application Debug: {$msg}/", 3)) {
            return false;
        }
        if (!$this->matchLastLog(self::PATH, "/^Application Info: {$msg}/", 2)) {
            return false;
        }
        if (!$this->matchLastLog(self::PATH, "/^Application Warning: {$msg}/", 1)) {
            return false;
        }
        if (!$this->matchLastLog(self::PATH, "/^Application Error: {$msg}/")) {
            return false;
        }

        // WARNING
        $this->testLevel(Log::S_WARNING);
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

        // ERROR
        $this->testLevel(Log::S_ERROR);
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

        return true;
    }

    private function testLevel(string $level): void
    {
        Log::setLogFileLevel(Log::A_ALL);
        Log::debug('test');
        Log::debug('test');
        Log::debug('test');
        Log::debug('test');

        Log::setLogFileLevelByName($level);
        Log::debug(self::MSG);
        Log::info(self::MSG);
        Log::warning(self::MSG);
        Log::error(self::MSG);
    }
}