<?php

declare(strict_types=1);


namespace PhpLoggerTests\SetupByConfig;

use PhpLogger\Log;
use PhpLoggerTests\Test;

class SetupByConfigCorrect extends Test
{
    protected function test(): bool
    {
        $path = __DIR__ . '/test.ini';
        unlink($path);
        Log::setupByConfig(__DIR__ . '/latest.log', $path);
        return true;
    }
}