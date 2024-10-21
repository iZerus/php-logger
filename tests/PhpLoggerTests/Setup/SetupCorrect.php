<?php

declare(strict_types=1);


namespace PhpLoggerTests\Setup;

use PhpLogger\Log;
use PhpLoggerTests\Test;

class SetupCorrect extends Test
{

    protected function test(): bool
    {
        // Заранее изменим параметры
        ini_set('error_log', LOG_FOLDER . 'tmp.log');
        ini_set('log_errors', '0');
        ini_set('display_errors', 'on');
        error_reporting(E_ALL & ~E_NOTICE);

        $path = LOG_FOLDER . 'SetupCorrect.log';
        Log::setup($path);
        if (!file_exists($path)) {
            return false;
        }
        if (ini_get('error_log') != $path) {
            return false;
        }
        if (ini_get('log_errors') != "1") {
            return false;
        }
        if (error_reporting() !== E_ALL) {
            return false;
        }
        if (ini_get('display_errors') != "off") {
            return false;
        }
        return true;
    }
}