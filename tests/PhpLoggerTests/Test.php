<?php

declare(strict_types=1);


namespace PhpLoggerTests;

use Error;
use Throwable;

abstract class Test
{
    public function name(): string
    {
        return static::class;
    }

    public function run(): bool
    {
        try {
            return $this->test();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function startVisualTest(): void
    {
        print "+ Визуальный тест '{$this->name()}' +" . PHP_EOL;
    }

    abstract protected function test(): bool;

    private function matchLogLine(string $pattern, string $logLine, bool $matchDate = true): bool
    {
        if ($matchDate && !preg_match("/^\[\d+-\S+-\d+ \d+:\d+:\d+\s\S+] /", $logLine, $matches)) {
            return false;
        }
        return (bool)preg_match($pattern, !empty($matches) ? str_replace($matches[0], '', $logLine) : $logLine);
    }

    private function readLastLogLine(string $path, int $offset = 0): string
    {
        $data = file($path);
        if ($data === false) {
            throw new Error('Не удалось прочитать файл лога');
        }
        return trim($data[count($data) - 1 - $offset]);
    }

    protected function matchLastLog(string $path, string $pattern, int $offset = 0, bool $matchDate = true): bool
    {
        return $this->matchLogLine(
            $pattern,
            $this->readLastLogLine($path, $offset),
            $matchDate
        );
    }
}