<?php

declare(strict_types=1);

chdir(__DIR__);

use PhpLoggerTests\Debug\LogDebugCorrect;
use PhpLoggerTests\Error\LogErrorCorrect;
use PhpLoggerTests\Info\LogInfoCorrect;
use PhpLoggerTests\isInitialized\IsInitializedAfterSetup;
use PhpLoggerTests\isInitialized\IsInitializedBeforeSetup;
use PhpLoggerTests\Log\LogWithoutSetup;
use PhpLoggerTests\Rotate\Rotate;
use PhpLoggerTests\SetDefaultName\SetDefaultName;
use PhpLoggerTests\SetLogDisplayLevel\SetLogDisplayLevel;
use PhpLoggerTests\SetLogDisplayLevelByName\SetLogDisplayLevelByName;
use PhpLoggerTests\SetLogFileLevel\SetLogFileLevel;
use PhpLoggerTests\SetLogFileLevelByName\SetLogFileLevelByName;
use PhpLoggerTests\SetPhpDisplayErrors\SetPhpDisplayErrors;
use PhpLoggerTests\SetPhpErrorReportingLevel\SetPhpErrorReportingLevel;
use PhpLoggerTests\Setup\SetupCorrect;
use PhpLoggerTests\Setup\SetupWithIncorrectMaxRotatedFilesCount;
use PhpLoggerTests\Setup\SetupWithIncorrectMaxSizeForRotate;
use PhpLoggerTests\Setup\SetupWithIncorrectPath;
use PhpLoggerTests\Setup\SetupWithNewLogFile;
use PhpLoggerTests\SetupByConfig\SetupByConfigIncorrectMaxRotatedFilesCount;
use PhpLoggerTests\SetupByConfig\SetupByConfigIncorrectMaxSizeForRotate;
use PhpLoggerTests\SetupByConfig\SetupByConfigIncorrectPhpDisplayErrors;
use PhpLoggerTests\SetupByConfig\SetupByConfigWithErrorFile;
use PhpLoggerTests\Test;
use PhpLoggerTests\Timer\TimeGetIncorrectKey;
use PhpLoggerTests\Timer\TimerCorrect;
use PhpLoggerTests\Timer\TimerStartIncorrectKey;
use PhpLoggerTests\Timer\TimerStopIncorrectKey;
use PhpLoggerTests\Warning\LogWarningCorrect;

require_once 'autoloader.php';

const LOG_FOLDER = '../logs/';

/** @var Test[] $tests */
$tests = [
    // IsInitialized
    new IsInitializedBeforeSetup(),
    // Log
    new LogWithoutSetup(),
    // Setup
    new SetupWithIncorrectPath(),
    new SetupWithIncorrectMaxSizeForRotate(),
    new SetupWithIncorrectMaxRotatedFilesCount(),
    new SetupWithNewLogFile(),
    new SetupCorrect(),
    // Setup by config
    new SetupByConfigWithErrorFile(),
    new SetupByConfigIncorrectMaxSizeForRotate(),
    new SetupByConfigIncorrectMaxRotatedFilesCount(),
    new SetupByConfigIncorrectPhpDisplayErrors(),
    // IsInitialized
    new IsInitializedAfterSetup(),
    // Log
    new LogDebugCorrect(),
    new LogInfoCorrect(),
    new LogWarningCorrect(),
    new LogErrorCorrect(),
    // Rotate
    new Rotate(),
    // Timer
    new TimerCorrect(),
    new TimerStartIncorrectKey(),
    new TimerStopIncorrectKey(),
    new TimeGetIncorrectKey(),
    // SetDefaultName
    new SetDefaultName(),
    // SetPhpErrorReportingLevel
    new SetPhpErrorReportingLevel(),
    // SetLogFileLevel
    new SetLogFileLevel(),
    // SetLogFileLevelByName
    new SetLogFileLevelByName()
];

print 'Начинается модульное тестирование...' . PHP_EOL;
$allSuccess = true;
foreach ($tests as $test) {
    $success = $test->run();
    $allSuccess = $allSuccess && $success;
    $msg = "{$test->name()} - " . ($success ? 'ок' : 'провален');
    print $msg . PHP_EOL;
}

print PHP_EOL;

/** @var Test[] $tests */
$visualTests = [
    // SetPhpDisplayErrors
    new SetPhpDisplayErrors(),
    // SetLogDisplayLevel
    new SetLogDisplayLevel(),
    // SetLogDisplayLevelByName
    new SetLogDisplayLevelByName(),
];
print 'Начинается визуальное тестирование...' . PHP_EOL;
foreach ($visualTests as $test) {
    $test->startVisualTest();
    $test->run();
    print PHP_EOL;
}
print 'Визуальное тестирование завершено' . PHP_EOL;
print $allSuccess ? '+ Модульные тесты выполнены успешно' . PHP_EOL : '- Модульные тесты провалились' . PHP_EOL;