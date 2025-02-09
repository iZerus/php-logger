<?php

declare(strict_types=1);


namespace PhpLoggerTests\SetupByConfig;

use DomainException;
use PhpLogger\Log;

class SetupByConfigIncorrectLogDisplayLevel extends SetupByConfigIncorrectValue
{

    protected function test(): bool
    {
        $result = !$this->testLevel(Log::S_NONE);
        $result = $result && !$this->testLevel(Log::S_ERROR);
        $result = $result && !$this->testLevel(Log::S_WARNING);
        $result = $result && !$this->testLevel(Log::S_INFO);
        $result = $result && !$this->testLevel(Log::S_DEBUG);
        $result = $result && $this->testLevel('foo');
        $result = $result && $this->testLevel('1');
        Log::setLogDisplayLevelByName(Log::S_NONE);
        return $result;
    }

    protected function testLevel(string $level): bool
    {
        $this->createConfig(Log::CFG_LOG_DISPLAY_LEVEL, $level);
        try {
            Log::setupByConfig($this->getLogPath(), $this->getConfigPath());
        } catch (DomainException $e) {
            return $e->getCode() === Log::ERROR_INVALID_LEVEL_NAME;
        }
        return false;
    }
}