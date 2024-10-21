<?php

declare(strict_types=1);


namespace PhpLoggerTests\Rotate;

use PhpLogger\Log;
use PhpLoggerTests\Test;

class Rotate extends Test
{

    protected function test(): bool
    {
        $path = LOG_FOLDER . 'Rotate.log';
        for ($j = 1; $j <= 10; $j++) {
            Log::setup($path, 1, 5);
            for ($i = 1; $i <= 10; $i++) {
                Log::info("Ротация $j#$i");
            }
        }
        if (!$this->matchLastLog($path, "/^Application Info: Ротация 10#10/")) {
            return false;
        }

        $fileNames = scandir(dirname($path));
        $successRotatedLogs = 0;
        foreach ($fileNames as $fileName) {
            if (!preg_match(sprintf("/%s.\d+/", basename($path)), $fileName)) {
                continue;
            }
            $index = str_replace(basename($path) . '.', '', $fileName);
            if (!is_numeric($index)) {
                continue;
            }
            $value = 10 - $index;
            if (!$this->matchLastLog(
                dirname($path) . '/' . $fileName,
                "/^Application Info: Ротация $value#10/"
            )) {
                return false;
            }
            $successRotatedLogs++;
        }
        if ($successRotatedLogs !== 5) {
            return false;
        }
        return true;
    }
}