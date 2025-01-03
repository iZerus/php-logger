<?php

declare(strict_types=1);


namespace PhpLoggerTests\Log;

use LogicException;
use PhpLogger\Log;
use PhpLoggerTests\Test;

class LogWithoutSetup extends Test
{

    public function test(): bool
    {
        try {
            Log::debug('');
            Log::info('');
            Log::warning('');
            Log::error('');
        } catch (LogicException $e) {
            return $e->getCode() === Log::ERROR_LOG_WITHOUT_SETUP;
        }
        return false;
    }
}