<?php

declare(strict_types=1);


namespace PhpLoggerTests\Setup;

use PhpLogger\Log;
use PhpLoggerTests\Test;

class SetupWithNewLogFile extends Test
{

    public function test(): bool
    {
        $path = LOG_FOLDER . 'SetupWithNewLogFile.log';
        if (file_exists($path)) {
            unlink($path);
        }
        Log::setup($path);
        if (!file_exists($path)) {
            return false;
        }
        return true;
    }
}