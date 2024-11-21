<?php

declare(strict_types=1);


namespace PhpLoggerTests\Warning;

use PhpLogger\Log;
use PhpLoggerTests\Log\LogCorrect;

class LogWarningCorrect extends LogCorrect
{

    protected function log(): void
    {
        Log::warning($this->getMessage());
    }

    protected function getType(): string
    {
        return 'Warning';
    }

    protected function logWithName(): void
    {
        Log::warning($this->getMessage(), $this->getName());
    }

    protected function logWithData(): void
    {
        Log::warning($this->getMessage(), null, $this->getData());
    }

    protected function logWithIncorrectName(): void
    {
        Log::debug($this->getMessage(), $this->getIncorrectName());
    }
}