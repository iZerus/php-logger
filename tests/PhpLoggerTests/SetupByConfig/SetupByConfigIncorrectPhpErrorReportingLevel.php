<?php

declare(strict_types=1);


namespace PhpLoggerTests\SetupByConfig;

use PhpLogger\Log;
use UnexpectedValueException;

class SetupByConfigIncorrectPhpErrorReportingLevel extends SetupByConfigIncorrectValue
{

    protected function test(): bool
    {
        $this->createConfig(Log::CFG_PHP_ERROR_REPORTING_LEVEL, 'foo');
        try {
            Log::setupByConfig($this->getLogPath(), $this->getConfigPath());
        } catch (UnexpectedValueException $e) {
            return $e->getCode() === Log::ERROR_SETUP_BY_CFG_INCORRECT_INI_VALUE;
        }
        return false;
    }
}