<?php

declare(strict_types=1);


namespace PhpLoggerTests\isInitialized;

use PhpLogger\Log;
use PhpLoggerTests\Test;

class IsInitializedAfterSetup extends Test
{

    protected function test(): bool
    {
        return Log::isInitialized();
    }
}