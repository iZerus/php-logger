<?php

declare(strict_types=1);


namespace PhpLoggerTests\Timer;

use Error;
use PhpLogger\Log;
use PhpLoggerTests\Test;

class TimerStopIncorrectKey extends Test
{

    protected function test(): bool
    {
        $testWithoutStart = $this->testWithoutStart();
        $testWithIncorrectKeyAfterStart = $this->testWithIncorrectKeyAfterStart();
        $testWithDoubleStop = $this->testWithDoubleStop();
        return $testWithoutStart && $testWithIncorrectKeyAfterStart && $testWithDoubleStop;
    }

    private function testWithoutStart(): bool
    {
        try {
            Log::stopTimer('foo');
        } catch (Error $e) {
            return $e->getCode() === Log::ERROR_TIMER_STOP_INCORRECT_KEY;
        }
        return true;
    }

    private function testWithIncorrectKeyAfterStart(): bool
    {
        try {
            Log::startTimer('foo');
            Log::stopTimer('bar');
        } catch (Error $e) {
            return $e->getCode() === Log::ERROR_TIMER_STOP_INCORRECT_KEY;
        }
        return true;
    }

    private function testWithDoubleStop(): bool
    {
        try {
            Log::stopTimer('foo');
            Log::stopTimer('foo');
        } catch (Error $e) {
            return $e->getCode() === Log::ERROR_TIMER_STOP_INCORRECT_KEY;
        }
        return true;
    }
}