<?php

declare(strict_types=1);


namespace PhpLoggerTests\Log;

use InvalidArgumentException;
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
        $testMessageWithNameWithIncorrectName = $this->testMessageWithNameWithIncorrectName();
        $testMessageWithNameWithEmptyName = $this->testMessageWithNameWithEmptyName();
        return
            $testMessage &&
            $testMessageWithName &&
            $testMessageWithData &&
            $testMessageWithNameWithIncorrectName &&
            $testMessageWithNameWithEmptyName;
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
        $testMessage = $this->matchLastLog($path, "/^Application {$this->getType()}: {$this->getMessage()}/", 5);
        $testData = $this->matchLastLog($path, "/^Array$/", 4, false);
        $testData = $testData && $this->matchLastLog($path, "/^\($/", 3, false);
        $testData = $testData && $this->matchLastLog($path, "/^\s{4}\[foo] => bar$/", 2, false);
        $testData = $testData && $this->matchLastLog($path, "/^\)$/", 1, false);
        return $testMessage && $testData;
    }

    final protected function getPath(): string
    {
        return (new ReflectionClass($this))->getShortName() . '.log';
    }

    private function testMessageWithNameWithIncorrectName(): bool
    {
        try {
            $this->logWithIncorrectName();
        } catch (InvalidArgumentException $e) {
            return $e->getCode() == Log::ERROR_INVALID_LOG_NAME;
        }
        return false;
    }

    private function testMessageWithNameWithEmptyName(): bool
    {
        try {
            $this->logWithEmptyName();
        } catch (InvalidArgumentException $e) {
            return $e->getCode() == Log::ERROR_EMPTY_LOG_NAME;
        }
        return false;
    }

    abstract protected function log(): void;

    abstract protected function logWithName(): void;

    abstract protected function logWithIncorrectName(): void;

    abstract protected function logWithEmptyName(): void;

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

    final protected function getIncorrectName(): string
    {
        return 'Foo Bar';
    }

    final protected function getEmptyName(): string
    {
        return '';
    }

    final protected function getData(): array
    {
        return ['foo' => 'bar'];
    }
}