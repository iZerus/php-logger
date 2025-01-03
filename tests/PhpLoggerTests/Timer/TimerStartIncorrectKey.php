<?php

declare(strict_types=1);


namespace PhpLoggerTests\Timer;

use LogicException;
use PhpLogger\Log;
use PhpLoggerTests\Test;

class TimerStartIncorrectKey extends Test
{

    protected function test(): bool
    {
        try {
            Log::startTimer('foo');
            Log::startTimer('foo');
        } catch (LogicException $e) {
            return $e->getCode() === Log::ERROR_TIMER_START_INCORRECT_KEY;
        }
        return true;
    }
}