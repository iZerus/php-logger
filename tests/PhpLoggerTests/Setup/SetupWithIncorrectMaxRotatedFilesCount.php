<?php

declare(strict_types=1);


namespace PhpLoggerTests\Setup;

use InvalidArgumentException;
use PhpLogger\Log;
use PhpLoggerTests\Test;

class SetupWithIncorrectMaxRotatedFilesCount extends Test
{

    protected function test(): bool
    {
        $testWithZero = $this->testWithZero();
        $testWithNegative = $this->testWithNegative();
        return $testWithZero && $testWithNegative;
    }

    private function testWithZero(): bool
    {
        try {
            Log::setup(LOG_FOLDER . 'SetupWithIncorrectMaxRotatedFilesCount.log', 10_000, 0);
        } catch (InvalidArgumentException $e) {
            return $e->getCode() === Log::ERROR_SETUP_INCORRECT_MAX_ROTATED_FILES_COUNT;
        }
        return false;
    }

    private function testWithNegative(): bool
    {
        try {
            Log::setup(LOG_FOLDER . 'SetupWithIncorrectMaxRotatedFilesCount.log', 10_000, -1);
        } catch (InvalidArgumentException $e) {
            return $e->getCode() === Log::ERROR_SETUP_INCORRECT_MAX_ROTATED_FILES_COUNT;
        }
        return false;
    }
}