<?php

declare(strict_types=1);


namespace PhpLoggerTests\SetupByConfig;

use PhpLogger\Log;
use UnexpectedValueException;

class SetupByConfigIncorrectPhpDisplayErrors extends SetupByConfigIncorrectValue
{

    protected function test(): bool
    {
        $this->createConfig(Log::CFG_PHP_DISPLAY_ERRORS, 'foo');
        try {
            Log::setupByConfig($this->getConfigPath());
        } catch (UnexpectedValueException $e) {
            return $e->getCode() === Log::ERROR_SETUP_BY_CFG_INCORRECT_INI_VALUE;
        }
        return false;
    }
}