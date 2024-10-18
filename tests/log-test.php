<?php
declare(strict_types=1);

use iZerus\Log;

chdir(__DIR__);

require_once "../src/Log.php";

const LOG_PATH = "../logs/log-test.log";
const LOG_ROTATE_PATH = "../logs/log-test-rotate.log";

function printFailedTest(string $testName, ?string $expected = null, ?string $current = null) {
    echo "$testName провален" . PHP_EOL;
    if ($expected !== null) {
        echo "Ожидаемо: " . $expected . PHP_EOL;
    }
    if ($current !== null) {
        echo "Результат: " . $current . PHP_EOL;
    }
    exit;
}

function readLastLine(string $filename, int $offset = 0): string
{
    $data = file($filename);
    if ($data === false) {
        throw new Error('Не удалось прочитать файл лога');
    }
    return trim($data[count($data) - 1 - $offset]);
}

function matchLogLine(string $pattern, string $logLine, bool $matchDate = true)
{
    if ($matchDate && !preg_match("/^\[\d+-\S+-\d+ \d+:\d+:\d+\s\S+] /", $logLine, $matches)) {
        return false;
    }
    return preg_match($pattern, !empty($matches) ? str_replace($matches[0], '', $logLine) : $logLine);
}

/*
 * Инициализация лога
 */
// Тест использования без инициализации
{
    $caught = false;
    try {
        Log::info("Тестовое сообщение");
    } catch (Error $e) {
        if (preg_match("/не инициализирован. Используйте метод setup/", $e->getMessage())) {
            $caught = true;
        } else {
            throw $e;
        }
    }
    if (!$caught) {
        printFailedTest("Тест на инициализацию",
            "Будет брошено исключение",
            "Не брошено"
        );
    }
}

/*
 * Конфигурации файла лога
 */
// Тест на невозможность создать файл лога
{
    $caught = false;
    $failedPath = __DIR__ . "../logs/failed.log";
    try {
        LOG::setup($failedPath);
    } catch (Error $e) {
        if (preg_match("/^Не удается создать файл лога по пути/", $e->getMessage())) {
            $caught = true;
        } else {
            throw $e;
        }
    }
    if (!$caught) {
        printFailedTest("Тест на невозможность создать файл лога",
            "Будет брошено исключение",
            "Не брошено"
        );
    }

}
// Тест установки директивы файла логов
{
    $tmpValue = "tmp" . time();
    $previousValue = ini_set("error_log", $tmpValue);
    Log::setup(LOG_PATH);
    if (basename(ini_get("error_log")) !== basename(LOG_PATH)) {
        printFailedTest("Тест установки директивы файла логов",
            LOG_PATH,
            basename(ini_get("error_log"))
        );
    }
}
// Тест установки директивы записи ошибок в лог
{
    ini_set("log_errors", "0");
    Log::setup(LOG_PATH);
    if (ini_get("log_errors") != "1") {
        printFailedTest("Тест установки директивы записи ошибок в лог",
            "1",
            ini_get("log_errors")
        );
    }
}
Log::setup(LOG_PATH);
// Тест установки размера файла для условия ротации лога
{
    $caught = false;
    try {
        Log::setup(LOG_PATH, 0);
    } catch (Error $e) {
        if (preg_match("/^Значение 'maxSizeForRotate' не может быть меньше или равно нулю/", $e->getMessage())) {
            $caught = true;
        } else {
            throw $e;
        }
    }
    if (!$caught) {
        printFailedTest("Тест на неадекватный параметр 'maxSizeForRotate'",
            "Будет брошено исключение",
            "Исключение не брошено"
        );
    }
}
// Тест установки размера файла для условия ротации лога
{
    $caught = false;
    try {
        Log::setup(LOG_PATH, 5, 0);
    } catch (Error $e) {
        if (preg_match("/^Значение 'maxRotatedFilesCount' не может быть меньше или равно нулю/", $e->getMessage())) {
            $caught = true;
        } else {
            throw $e;
        }
    }
    if (!$caught) {
        printFailedTest("Тест на неадекватный параметр 'maxRotatedFilesCount'",
            "Будет брошено исключение",
            "Исключение не брошено"
        );
    }
}

/*
 * Прочие конфигурации
 */
// Тест установки директивы вывода ошибок
{
    ini_set("display_errors", "off");
    Log::setPhpDisplayErrors(true);
    if (ini_get("display_errors") === "off") {
        printFailedTest("Тест установки директивы вывода ошибок",
            "on",
            ini_get("display_errors")
        );
    }
    ini_set("display_errors", "on");
}
Log::setPhpDisplayErrors(false);
// Тест установки директивы уровня вывода ошибок PHP
{
    error_reporting(E_ALL);
    Log::setPhpErrorReportingLevel(E_NOTICE);
    if ((int)ini_get("error_reporting") !== E_NOTICE) {
        printFailedTest("Тест установки директивы вывода ошибок",
            (string)E_NOTICE,
            ini_get("error_reporting")
        );
    }
}
error_reporting(E_ALL);

/*
 * Вывод логов
 */
// Тест на вывод Debug
{
    Log::debug("Debug message");
    if (!matchLogLine("/^Application Debug: Debug message/", readLastLine(LOG_PATH))) {
        printFailedTest("Тест на лог 'Application Debug'",
            "[Дата] Application Debug: Debug message",
            readLastLine(LOG_PATH));
    }
}
// Тест на вывод Info
{
    Log::info("Info message");
    if (!matchLogLine("/^Application Info: Info message/", readLastLine(LOG_PATH))) {
        printFailedTest("Тест на лог 'Application Info'",
            "[Дата] Application Info: Info message",
            readLastLine(LOG_PATH));
    }
}
// Тест на вывод Warning
{
    Log::warning("Warning message");
    if (!matchLogLine("/^Application Warning: Warning message/", readLastLine(LOG_PATH))) {
        printFailedTest("Тест на лог 'Application Warning'",
            "[Дата] Application Warning: Warning message",
            readLastLine(LOG_PATH));
    }
}
// Тест на вывод Error
{
    Log::error("Error message");
    if (!matchLogLine("/^Application Error: Error message/", readLastLine(LOG_PATH))) {
        printFailedTest("Тест на лог 'Application Error'",
            "[Дата] Application Error: Error message",
            readLastLine(LOG_PATH));
    }
}

/*
 * Вывод с данными
 */
// Тест на вывод с данными
{
    Log::error("Error message", null, 128);
    if (!matchLogLine("/^Data: int\(128\)$/", readLastLine(LOG_PATH), false)) {
        printFailedTest("Тест на вывод с данными",
            "Data: int(128)",
            readLastLine(LOG_PATH));
    }
}

/*
 * Уровни логирования
 */
// Тест на уровень Info
{
    Log::setLogFileLevel(Log::A_INFO);
    Log::info("Info message");
    Log::warning("Warning message");
    Log::error("Error message");
    if (!matchLogLine("/^Application Info: Info message/", readLastLine(LOG_PATH))) {
        printFailedTest("Тест на уровень Info",
            "[Дата] Application Info: Info message",
            readLastLine(LOG_PATH));
    }
}
// Тест на уровень Info и Error
{
    Log::setLogFileLevel(Log::A_INFO | Log::A_ERROR);
    Log::info("Info message");
    Log::warning("Warning message");
    Log::error("Error message");
    if (!matchLogLine("/^Application Info: Info message/", readLastLine(LOG_PATH, 1))) {
        printFailedTest("Тест на уровень Info и Error",
            "[Дата] Application Info: Info message",
            readLastLine(LOG_PATH));
    }
    if (!matchLogLine("/^Application Error: Error message/", readLastLine(LOG_PATH))) {
        printFailedTest("Тест на уровень Info и Error",
            "[Дата] Application Error: Error message",
            readLastLine(LOG_PATH));
    }
}
// Тест на строковый уровень error
{
    Log::setLogFileLevelByName(Log::S_ERROR);
    Log::debug("Debug message");
    Log::error("Error message");
    Log::info("Info message");
    Log::warning("Warning message");
    if (!matchLogLine("/^Application Error: Error message/", readLastLine(LOG_PATH))) {
        printFailedTest("Тест на строковый уровень error",
            "[Дата] Application Error: Error message",
            readLastLine(LOG_PATH));
    }
    Log::setLogFileLevel(Log::A_ALL);
    Log::debug("");
}
// Тест на строковый уровень warning
{
    Log::setLogFileLevelByName(Log::S_WARNING);
    Log::debug("Debug message");
    Log::error("Error message");
    Log::info("Info message");
    Log::warning("Warning message");
    if (!matchLogLine("/^Application Error: Error message/", readLastLine(LOG_PATH, 1))) {
        printFailedTest("Тест на строковый уровень warning",
            "[Дата] Application Error: Error message",
            readLastLine(LOG_PATH));
    }
    if (!matchLogLine("/^Application Warning: Warning message/", readLastLine(LOG_PATH))) {
        printFailedTest("Тест на строковый уровень warning",
            "[Дата] Application Warning: Warning message",
            readLastLine(LOG_PATH));
    }
    Log::setLogFileLevel(Log::A_ALL);
    Log::debug("");
}
// Тест на строковый уровень info
{
    Log::setLogFileLevelByName(Log::S_INFO);
    Log::debug("Debug message");
    Log::error("Error message");
    Log::info("Info message");
    Log::warning("Warning message");
    if (!matchLogLine("/^Application Error: Error message/", readLastLine(LOG_PATH, 2))) {
        printFailedTest("Тест на строковый уровень info",
            "[Дата] Application Error: Error message",
            readLastLine(LOG_PATH));
    }
    if (!matchLogLine("/^Application Info: Info message/", readLastLine(LOG_PATH, 1))) {
        printFailedTest("Тест на строковый уровень info",
            "[Дата] Application Info: Info message",
            readLastLine(LOG_PATH));
    }
    if (!matchLogLine("/^Application Warning: Warning message/", readLastLine(LOG_PATH))) {
        printFailedTest("Тест на строковый уровень info",
            "[Дата] Application Warning: Warning message",
            readLastLine(LOG_PATH));
    }
    Log::setLogFileLevel(Log::A_ALL);
    Log::debug("");
}
// Тест на строковый уровень debug
{
    Log::setLogFileLevelByName(Log::S_DEBUG);
    Log::debug("Debug message");
    Log::error("Error message");
    Log::info("Info message");
    Log::warning("Warning message");
    if (!matchLogLine("/^Application Debug: Debug message/", readLastLine(LOG_PATH, 3))) {
        printFailedTest("Тест на строковый уровень debug",
            "[Дата] Application Debug: Debug message",
            readLastLine(LOG_PATH));
    }
    if (!matchLogLine("/^Application Error: Error message/", readLastLine(LOG_PATH, 2))) {
        printFailedTest("Тест на строковый уровень debug",
            "[Дата] Application Error: Error message",
            readLastLine(LOG_PATH));
    }
    if (!matchLogLine("/^Application Info: Info message/", readLastLine(LOG_PATH, 1))) {
        printFailedTest("Тест на строковый уровень debug",
            "[Дата] Application Info: Info message",
            readLastLine(LOG_PATH));
    }
    if (!matchLogLine("/^Application Warning: Warning message/", readLastLine(LOG_PATH))) {
        printFailedTest("Тест на строковый уровень debug",
            "[Дата] Application Warning: Warning message",
            readLastLine(LOG_PATH));
    }
    Log::setLogFileLevel(Log::A_ALL);
    Log::debug("");
}

/*
 * Именованный вывод логов
 */
// Вывод лога от имени TestApp
{
    Log::info("Info message", "TestApp");
    if (!matchLogLine("/^TestApp Info: Info message/", readLastLine(LOG_PATH))) {
        printFailedTest("Тест на именованный лог 'TestApp Info'",
            "[Дата] TestApp Info: Info message",
            readLastLine(LOG_PATH));
    }
}
// Вывод лога от имени App
{
    Log::setDefaultName("App");
    Log::info("Info message");
    if (!matchLogLine("/^App Info: Info message/", readLastLine(LOG_PATH))) {
        printFailedTest("Тест на именованный дефолтный лог 'App Info'",
            "[Дата] App Info: Info message",
            readLastLine(LOG_PATH));
    }
}
Log::setDefaultName("Application");

/*
 * Тест таймеров
 */
// Тест на корректность работы таймера
{
    Log::startTimer('test');
    sleep(1);
    Log::stopTimer('test');
    $time = Log::getTime('test');
    if ($time !== 1) {
        printFailedTest("Тест на таймер", '1', "$time");
    }
}
// Тест на неверный ключ завершения таймера
{

    $caught = false;
    try {
        Log::stopTimer('stop');
    } catch (Error $e) {
        if (preg_match("/^Таймер с ключом 'stop' не задан/", $e->getMessage())) {
            $caught = true;
        } else {
            throw $e;
        }
    }
    if (!$caught) {
        printFailedTest("Тест на неверный ключ завершения таймера",
            "Будет брошено исключение",
            "Исключение не брошено"
        );
    }
}
// Тест на неверный ключ получения времени
{

    $caught = false;
    try {
        Log::getTime('time');
    } catch (Error $e) {
        if (preg_match("/^Тайминг с ключом 'time' не найден или не завершён методом stopTimer/", $e->getMessage())) {
            $caught = true;
        } else {
            throw $e;
        }
    }
    if (!$caught) {
        printFailedTest("Тест на неверный ключ получения времени",
            "Будет брошено исключение",
            "Исключение не брошено"
        );
    }
}

/*
 * Тест ротации
 */
{
    for ($j = 1; $j <= 10; $j++) {
        Log::setup(LOG_ROTATE_PATH, 1, 5);
        for ($i = 1; $i <= 10; $i++) {
            Log::info("Ротация $j#$i");
        }
    }
    if (!matchLogLine("/^Application Info: Ротация 10#10/", readLastLine(LOG_ROTATE_PATH))) {
        printFailedTest("Тест ротации логов",
            "[Дата] Application Info: Ротация 10#10",
            readLastLine(LOG_ROTATE_PATH));
    }
    $fileNames = scandir(dirname(LOG_ROTATE_PATH));
    $successRotatedLogs = 0;
    foreach ($fileNames as $fileName) {
        if (!preg_match(sprintf("/%s.\d+/", basename(LOG_ROTATE_PATH)), $fileName)) {
            continue;
        }
        $index = str_replace(basename(LOG_ROTATE_PATH) . '.', '', $fileName);
        if (!is_numeric($index)) {
            continue;
        }
        $value = 10 - $index;
        if (!matchLogLine("/^Application Info: Ротация $value#10/", readLastLine(dirname(LOG_ROTATE_PATH) . '/' . $fileName))) {
            printFailedTest("Тест ротации логов",
                "[Дата] Application Info: Ротация $value#10",
                readLastLine(LOG_ROTATE_PATH));
        }
        $successRotatedLogs++;
    }
    if ($successRotatedLogs !== 5) {
        printFailedTest("Тест ротации логов",
            "5 ротаций лога",
            $successRotatedLogs . " ротаций лога");
    }
}

/*
 * Вывод ошибок PHP
 */
Log::setup(LOG_PATH);
// Тест на лог 'PHP Notice'
{
    $arr = [1, 2, 3];
    echo $arr[10]; // Вызывает E_NOTICE
    if (!matchLogLine("/^PHP Notice: .*/", readLastLine(LOG_PATH))) {
        printFailedTest("Тест на лог 'PHP Notice'",
            "[Дата] PHP Notice: Уведомление",
            readLastLine(LOG_PATH));
    }
}
// Тест на лог 'PHP Warning'
{
    filemtime("testFile" . time()); // Вызывает E_WARNING
    if (!matchLogLine("/^PHP Warning: .*/", readLastLine(LOG_PATH))) {
        printFailedTest("Тест на лог 'PHP Warning'",
            "[Дата] PHP Warning: Предупреждение",
            readLastLine(LOG_PATH));
    }
}
// Завершающий тест на лог 'PHP Error'
{
    register_shutdown_function(function () {
        if (!matchLogLine("/^PHP Fatal error:\s\s.*/", readLastLine(LOG_PATH, 3))) {
            printFailedTest("Тест на лог 'PHP Fatal error'",
                "[Дата] PHP Fatal error:\s\s.*",
                readLastLine(LOG_PATH));
        }
        print "Тесты пройдены успешно";
    });
    $funName = "testFunction";
    $funName(); // Вызывает E_ERROR
}
