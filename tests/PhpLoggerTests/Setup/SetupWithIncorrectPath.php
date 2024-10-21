<?php

declare(strict_types=1);


namespace PhpLoggerTests\Setup;

use Error;
use PhpLogger\Log;
use PhpLoggerTests\Test;

class SetupWithIncorrectPath extends Test
{

    protected function test(): bool
    {
        try {
            Log::setup(rand(1000, 9999) . '/incorrect.log');
        } catch (Error $e) {
            return $e->getCode() === Log::ERROR_SETUP_INCORRECT_LOG_PATH;
        }
        return false;
    }
}