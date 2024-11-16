<?php

declare(strict_types=1);


namespace PhpLoggerTests\Timer;

use OutOfRangeException;
use PhpLogger\Log;
use PhpLoggerTests\Test;

class TimeGetIncorrectKey extends Test
{

    protected function test(): bool
    {
        try {
            Log::getTime('foobar');
        } catch (OutOfRangeException $e) {
            return $e->getCode() === Log::ERROR_TIME_GET_INCORRECT_KEY;
        }
        return false;
    }
}