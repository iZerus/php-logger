<?php

declare(strict_types=1);


namespace PhpLoggerTests\SetLogDisplayLevelByName;

use PhpLogger\Log;
use PhpLoggerTests\Test;

class SetLogDisplayLevelByName extends Test
{

    private const MSG = 'foo';

    protected function test(): bool
    {
        Log::setup(LOG_FOLDER . 'SetLogDisplayLevel.log');
        print 'Режим debug:' . PHP_EOL;
        $this->printLevel(Log::S_DEBUG);
        print 'Режим info:' . PHP_EOL;
        $this->printLevel(Log::S_INFO);
        print 'Режим warning:' . PHP_EOL;
        $this->printLevel(Log::S_WARNING);
        print 'Режим error:' . PHP_EOL;
        $this->printLevel(Log::S_ERROR);
        return true;
    }

    private function printLevel(string $level): void
    {
        Log::setLogDisplayLevelByName($level);
        Log::debug(self::MSG);
        Log::info(self::MSG);
        Log::warning(self::MSG);
        Log::error(self::MSG);
    }
}