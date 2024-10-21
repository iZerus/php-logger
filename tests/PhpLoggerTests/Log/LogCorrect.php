<?php

declare(strict_types=1);


namespace PhpLoggerTests\Log;

use PhpLogger\Log;
use PhpLoggerTests\Test;
use ReflectionClass;

abstract class LogCorrect extends Test
{
    final public function test(): bool
    {
        $path = LOG_FOLDER . $this->getPath();
        Log::setup($path);
        $testMessage = $this->testMessage($path);
        $testMessageWithName = $this->testMessageWithName($path);
        $testMessageWithData = $this->testMessageWithData($path);
        return $testMessage && $testMessageWithName && $testMessageWithData;
    }

    private function testMessage(string $path): bool
    {
        $this->log();
        return $this->matchLastLog($path, "/^Application {$this->getType()}: {$this->getMessage()}/");
    }

    private function testMessageWithName(string $path): bool
    {
        $this->logWithName();
        return $this->matchLastLog($path, "/^{$this->getName()} {$this->getType()}: {$this->getMessage()}/");
    }

    private function testMessageWithData(string $path): bool
    {
        $this->logWithData();
        $testMessage = $this->matchLastLog($path, "/^Application {$this->getType()}: {$this->getMessage()}/", 1);
        $testData = $this->matchLastLog($path, "/^Data: int\({$this->getData()}\)$/", 0, false);
        return $testMessage && $testData;
    }

    final protected function getPath(): string
    {
        return (new ReflectionClass($this))->getShortName() . '.log';
    }

    abstract protected function log(): void;

    abstract protected function logWithName(): void;

    abstract protected function logWithData(): void;

    abstract protected function getType(): string;

    final protected function getMessage(): string
    {
        return 'Foo';
    }

    final protected function getName(): string
    {
        return 'Bar';
    }

    final protected function getData(): int
    {
        return 128;
    }
}