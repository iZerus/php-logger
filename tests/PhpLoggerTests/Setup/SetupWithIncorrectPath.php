<?php

declare(strict_types=1);


namespace PhpLoggerTests\Setup;

use PhpLogger\Log;
use PhpLoggerTests\Test;
use RuntimeException;

class SetupWithIncorrectPath extends Test
{

    protected function test(): bool
    {
        try {
            Log::setup(rand(1000, 9999) . '/incorrect.log');
        } catch (RuntimeException $e) {
            return $e->getCode() === Log::ERROR_SETUP_INCORRECT_LOG_PATH;
        }
        return false;
    }
}