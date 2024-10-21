<?php

declare(strict_types=1);


namespace PhpLoggerTests\SetDefaultName;

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
        return true;
    }
}