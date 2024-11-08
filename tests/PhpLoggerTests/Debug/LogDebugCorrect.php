<?php

declare(strict_types=1);


namespace PhpLoggerTests\Debug;

use PhpLogger\Log;
use PhpLoggerTests\Log\LogCorrect;

class LogDebugCorrect extends LogCorrect
{

    protected function log(): void
    {
        Log::debug($this->getMessage());
    }

    protected function getType(): string
    {
        return 'Debug';
    }

    protected function logWithName(): void
    {
        Log::debug($this->getMessage(), $this->getName());
    }

    protected function logWithData(): void
    {
        Log::debug($this->getMessage(), null, $this->getData());
    }
}