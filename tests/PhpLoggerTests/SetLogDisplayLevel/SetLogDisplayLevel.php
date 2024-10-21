<?php

declare(strict_types=1);


namespace PhpLoggerTests\SetLogDisplayLevel;

use PhpLogger\Log;
use PhpLoggerTests\Test;

class SetLogDisplayLevel extends Test
{

    private const MSG = 'foo';

    protected function test(): bool
    {
        Log::setup(LOG_FOLDER . 'SetLogDisplayLevel.log');
        print 'Режим A_NONE:' . PHP_EOL;
        $this->printLevel(Log::A_NONE);
        print 'Режим A_DEBUG:' . PHP_EOL;
        $this->printLevel(Log::A_DEBUG);
        print 'Режим A_INFO:' . PHP_EOL;
        $this->printLevel(Log::A_INFO);
        print 'Режим A_WARNING:' . PHP_EOL;
        $this->printLevel(Log::A_WARNING);
        print 'Режим A_ERROR:' . PHP_EOL;
        $this->printLevel(Log::A_ERROR);
        print 'Режим A_ERROR | A_WARNING:' . PHP_EOL;
        $this->printLevel(Log::A_ERROR | Log::A_WARNING);
        return true;
    }

    private function printLevel(int $level): void
    {
        Log::setLogDisplayLevel($level);
        Log::debug(self::MSG);
        Log::info(self::MSG);
        Log::warning(self::MSG);
        Log::error(self::MSG);
    }
}