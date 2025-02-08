<?php

declare(strict_types=1);


namespace PhpLoggerTests\SetupByConfig;

use PhpLogger\Log;
use UnexpectedValueException;

class SetupByConfigIncorrectMaxRotatedFilesCount extends SetupByConfigIncorrectValue
{

    protected function test(): bool
    {
        $this->createConfig(Log::CFG_MAX_ROTATED_FILES_COUNT, 'foo');
        try {
            Log::setupByConfig($this->getLogPath(), $this->getConfigPath());
        } catch (UnexpectedValueException $e) {
            return $e->getCode() === Log::ERROR_SETUP_BY_CFG_INCORRECT_INI_VALUE;
        }
        return false;
    }
}