<?php

declare(strict_types=1);


namespace PhpLoggerTests\Error;

use PhpLogger\Log;
use PhpLoggerTests\Log\LogCorrect;

class LogErrorCorrect extends LogCorrect
{

    protected function log(): void
    {
        Log::error($this->getMessage());
    }

    protected function getType(): string
    {
        return 'Error';
    }

    protected function logWithName(): void
    {
        Log::error($this->getMessage(), $this->getName());
    }

    protected function logWithData(): void
    {
        Log::error($this->getMessage(), null, $this->getData());
    }

    protected function logWithIncorrectName(): void
    {
        Log::debug($this->getMessage(), $this->getIncorrectName());
    }
}