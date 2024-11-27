<?php

declare(strict_types=1);


namespace PhpLoggerTests\SetDefaultName;

use InvalidArgumentException;
use PhpLogger\Log;
use PhpLoggerTests\Test;

class SetDefaultName extends Test
{

    protected function test(): bool
    {
        $path = LOG_FOLDER . 'SetDefaultName.log';
        Log::setup($path);
        Log::setDefaultName('NewName');
        Log::info('Foo');
        Log::setDefaultName('Application');
        if (!$this->matchLastLog($path, '/^NewName Info: Foo/')) {
            return false;
        }
        try {
            Log::setDefaultName('Foo Bar');
        } catch (InvalidArgumentException $e) {
            return $e->getCode() == Log::ERROR_INVALID_LOG_NAME;
        }
        try {
            Log::setDefaultName('');
        } catch (InvalidArgumentException $e) {
            return $e->getCode() == Log::ERROR_EMPTY_LOG_NAME;
        }
        return false;
    }
}