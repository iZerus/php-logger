<?php

declare(strict_types=1);


namespace PhpLoggerTests\SetupByConfig;

use InvalidArgumentException;
use PhpLogger\Log;

class SetupByConfigIncorrectAppName extends SetupByConfigIncorrectValue
{

    protected function test(): bool
    {
        $result = $this->testSpacedName() && $this->testEmptyName();
        Log::setDefaultName(Log::LOG_DEFAULT_NAME);
        return $result;
    }

    protected function testSpacedName(): bool
    {
        $this->createConfig(Log::CFG_LOG_NAME, 'foo bar');
        try {
            Log::setupByConfig($this->getLogPath(), $this->getConfigPath());
        } catch (InvalidArgumentException $e) {
            return $e->getCode() === Log::ERROR_INVALID_LOG_NAME;
        }
        return false;
    }

    protected function testEmptyName(): bool
    {
        $this->createConfig(Log::CFG_LOG_NAME, '');
        try {
            Log::setupByConfig($this->getLogPath(), $this->getConfigPath());
        } catch (InvalidArgumentException $e) {
            return $e->getCode() === Log::ERROR_EMPTY_LOG_NAME;
        }
        return false;
    }
}