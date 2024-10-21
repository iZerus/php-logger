<?php

declare(strict_types=1);


namespace PhpLoggerTests\Info;

use PhpLogger\Log;
use PhpLoggerTests\Log\LogCorrect;

class LogInfoCorrect extends LogCorrect
{
    protected function log(): void
    {
        Log::info($this->getMessage());
    }

    protected function getType(): string
    {
        return 'Info';
    }

    protected function logWithName(): void
    {
        Log::info($this->getMessage(), $this->getName());
    }

    protected function logWithData(): void
    {
        Log::info($this->getMessage(), null, $this->getData());
    }
}