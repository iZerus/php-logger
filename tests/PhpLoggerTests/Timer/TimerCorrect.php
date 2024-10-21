<?php

declare(strict_types=1);


namespace PhpLoggerTests\Timer;

use PhpLogger\Log;
use PhpLoggerTests\Test;

class TimerCorrect extends Test
{

    protected function test(): bool
    {
        Log::startTimer('foo');
        sleep(1);
        Log::stopTimer('foo');
        $time = Log::getTime('foo');
        if ($time !== 1) {
            return false;
        }
        return true;
    }
}