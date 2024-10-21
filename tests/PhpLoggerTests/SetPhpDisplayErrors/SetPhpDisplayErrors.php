<?php

declare(strict_types=1);


namespace PhpLoggerTests\SetPhpDisplayErrors;

use PhpLogger\Log;
use PhpLoggerTests\Test;

class SetPhpDisplayErrors extends Test
{

    /** @noinspection PhpUndefinedVariableInspection */
    protected function test(): bool
    {
        Log::setPhpDisplayErrors(true);
        print 'Вывод включен. Должен отобразиться Notice:' . PHP_EOL;
        $a = $b;
        Log::setPhpDisplayErrors(false);
        print 'Вывод выключен. Ничего не должно быть:' . PHP_EOL;
        $b = $c;
        return true;
    }
}