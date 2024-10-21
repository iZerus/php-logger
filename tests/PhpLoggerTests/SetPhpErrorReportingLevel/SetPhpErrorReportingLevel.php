<?php

declare(strict_types=1);


namespace PhpLoggerTests\SetPhpErrorReportingLevel;

use PhpLogger\Log;
use PhpLoggerTests\Test;

class SetPhpErrorReportingLevel extends Test
{

    protected function test(): bool
    {
        error_reporting(E_ALL);
        Log::setPhpErrorReportingLevel(E_NOTICE);
        if (error_reporting() !== E_NOTICE) {
            return false;
        }
        return true;
    }
}