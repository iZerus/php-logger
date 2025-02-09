<?php

declare(strict_types=1);

namespace PhpLogger;

use DomainException;
use InvalidArgumentException;
use LogicException;
use OutOfRangeException;
use RuntimeException;
use UnexpectedValueException;

/**
 * Статический логгер с ротацией
 * @author iZerus
 * @version 3.0
 *
 * @todo Автоконфигурация
 * @todo Добавить README.md с инструкцией применения (самоинициализацией внутри файла с классом)
 * @todo Аудит тестирования
 *
 * @todo 4.0
 * @todo Продумать расширяемость класса для новых уровней лога
 * @todo Проверять версию средствами php и проверить работу на других версиях
 */
class Log
{
    public const A_NONE = 0;
    public const A_DEBUG = 2;
    public const A_INFO = 4;
    public const A_WARNING = 8;
    public const A_ERROR = 16;
    public const A_ALL = 32767;
    /**
     * Все сообщения
     */
    public const S_DEBUG = 'debug';
    /**
     * Все сообщения, кроме отладочных
     */
    public const S_INFO = 'info';
    /**
     * Только сообщения об ошибках и предупреждениях
     */
    public const S_WARNING = 'warning';
    /**
     * Только сообщения об ошибках
     */
    public const S_ERROR = 'error';
    public const S_NONE = 'none';
    public const CFG_MAX_SIZE_FOR_ROTATE = 'maxSizeForRotate';
    public const CFG_MAX_ROTATED_FILES_COUNT = 'maxRotatedFilesCount';
    public const CFG_PHP_DISPLAY_ERRORS = 'phpDisplayErrors';
    public const CFG_PHP_ERROR_REPORTING_LEVEL = 'phpErrorReportingLevel';
    public const CFG_PHP_DISABLE_XDEBUG_LOG = 'phpDisableXDebugLog';
    public const CFG_LOG_DISPLAY_LEVEL = 'logDisplayLevel';
    public const CFG_LOG_FILE_LEVEL = 'logFileLevel';
    public const ERROR_LOG_WITHOUT_SETUP = 1000;
    public const ERROR_SETUP_INCORRECT_LOG_PATH = 1001;
    public const ERROR_SETUP_INCORRECT_MAX_SIZE_FOR_ROTATE = 1002;
    public const ERROR_SETUP_INCORRECT_MAX_ROTATED_FILES_COUNT = 1003;
    public const ERROR_TIMER_START_INCORRECT_KEY = 1004;
    public const ERROR_TIMER_STOP_INCORRECT_KEY = 1005;
    public const ERROR_TIME_GET_INCORRECT_KEY = 1006;
    public const ERROR_INVALID_LOG_NAME = 1007;
    public const ERROR_EMPTY_LOG_NAME = 1008;
    public const ERROR_SETUP_BY_CFG_INCORRECT_INI_PATH = 1009;
    public const ERROR_SETUP_BY_CFG_INCORRECT_INI_FORMAT = 1010;
    public const ERROR_SETUP_BY_CFG_INCORRECT_INI_VALUE = 1011;
    public const ERROR_INVALID_LEVEL_NAME = 1012;

    /** @var int */
    private static $logFileLevel = self::A_ALL;
    /** @var int */
    private static $logDisplayLevel = self::A_NONE;
    /** @var string */
    private static $defaultName = "Application";
    /** @var bool */
    private static $initialized = false;
    /** @var int[] */
    private static $timers;
    /** @var int[] */
    private static $timings;

    public static function setup(string $path, int $maxSizeForRotate = 10000000, int $maxRotatedFilesCount = 9): void
    {
        list($major, $minor) = explode(".", phpversion());
        $version = "$major.$minor";
        $supportedVersions = ["7.3", "7.4"];
        if (!in_array($version, $supportedVersions)) {
            throw new LogicException(sprintf("%s не поддерживает версию PHP %s", __CLASS__, $version));
        }
        if (!file_exists($path) && (@file_put_contents($path, '') === false)) {
            throw new RuntimeException(sprintf("Не удается создать файл лога по пути %s", $path), self::ERROR_SETUP_INCORRECT_LOG_PATH);
        }
        if ($maxSizeForRotate < 1) {
            throw new InvalidArgumentException("Значение 'maxSizeForRotate' не может быть меньше или равно нулю", self::ERROR_SETUP_INCORRECT_MAX_SIZE_FOR_ROTATE);
        }
        if ($maxRotatedFilesCount < 1) {
            throw new InvalidArgumentException("Значение 'maxRotatedFilesCount' не может быть меньше или равно нулю", self::ERROR_SETUP_INCORRECT_MAX_ROTATED_FILES_COUNT);
        }
        if (ini_set("error_log", $path) === false) {
            throw new RuntimeException('Не удалось установить значение error_log в ini');
        }
        if (ini_set("log_errors", "1") === false) {
            throw new RuntimeException('Не удалось установить значение log_errors в ini');
        }
        self::setPhpErrorReportingLevel(E_ALL);
        self::setPhpDisplayErrors(false);
        self::$initialized = true;
        self::rotate($path, $maxSizeForRotate, $maxRotatedFilesCount);
    }

    public static function isInitialized(): bool
    {
        return self::$initialized;
    }

    /**
     * @param string $logPath путь к файлу логов
     * @param string $configPath путь к INI конфигурации логгера
     * @return void
     */
    public static function setupByConfig(string $logPath, string $configPath): void
    {
        $defaultConfig = [
            self::CFG_MAX_SIZE_FOR_ROTATE => 10000000,
            self::CFG_MAX_ROTATED_FILES_COUNT => 9,
            self::CFG_PHP_DISPLAY_ERRORS => false,
            self::CFG_PHP_ERROR_REPORTING_LEVEL => E_ALL & ~E_NOTICE,
            self::CFG_PHP_DISABLE_XDEBUG_LOG => false,
            self::CFG_LOG_DISPLAY_LEVEL => self::S_NONE,
            self::CFG_LOG_FILE_LEVEL => self::S_INFO,
        ];
        if (!file_exists($configPath)) {
            self::createConfigFile($configPath, $defaultConfig);
        }
        $config = parse_ini_file($configPath);
        if ($config === false) {
            throw new RuntimeException("Ошибка чтения файла конфигурации '$configPath'", self::ERROR_SETUP_BY_CFG_INCORRECT_INI_FORMAT);
        }
        $config = array_merge($defaultConfig, $config);
        $maxSizeForRotate = filter_var($config[self::CFG_MAX_SIZE_FOR_ROTATE], FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
        if ($maxSizeForRotate === null) {
            throw new UnexpectedValueException(sprintf('Ошибка чтения параметра %s', self::CFG_MAX_SIZE_FOR_ROTATE), self::ERROR_SETUP_BY_CFG_INCORRECT_INI_VALUE);
        }
        $maxRotatedFilesCount = filter_var($config[self::CFG_MAX_ROTATED_FILES_COUNT], FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
        if ($maxRotatedFilesCount === null) {
            throw new UnexpectedValueException(sprintf('Ошибка чтения параметра %s', self::CFG_MAX_ROTATED_FILES_COUNT), self::ERROR_SETUP_BY_CFG_INCORRECT_INI_VALUE);
        }
        $phpDisplayErrors = filter_var($config[self::CFG_PHP_DISPLAY_ERRORS], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($phpDisplayErrors === null) {
            throw new UnexpectedValueException(sprintf('Ошибка чтения параметра %s', self::CFG_PHP_DISPLAY_ERRORS), self::ERROR_SETUP_BY_CFG_INCORRECT_INI_VALUE);
        }
        $phpErrorReportingLevel = filter_var($config[self::CFG_PHP_ERROR_REPORTING_LEVEL], FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
        if ($phpErrorReportingLevel === null) {
            throw new UnexpectedValueException(sprintf('Ошибка чтения параметра %s', self::CFG_PHP_ERROR_REPORTING_LEVEL), self::ERROR_SETUP_BY_CFG_INCORRECT_INI_VALUE);
        }
        $phpDisableXDebugLog = filter_var($config[self::CFG_PHP_DISABLE_XDEBUG_LOG], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($phpDisableXDebugLog === null) {
            throw new UnexpectedValueException(sprintf('Ошибка чтения параметра %s', self::CFG_PHP_DISABLE_XDEBUG_LOG), self::ERROR_SETUP_BY_CFG_INCORRECT_INI_VALUE);
        }
        $logDisplayLevel = $config[self::CFG_LOG_DISPLAY_LEVEL];
        if (empty($logDisplayLevel)) {
            $logDisplayLevel = self::S_NONE;
        }
        $logFileLevel = $config[self::CFG_LOG_FILE_LEVEL];
        if (empty($logFileLevel)) {
            $logFileLevel = self::S_NONE;
        }
        self::setup($logPath, $maxSizeForRotate, $maxRotatedFilesCount);
        self::setPhpDisplayErrors($phpDisplayErrors);
        self::setPhpErrorReportingLevel($phpErrorReportingLevel);
        if ($phpDisableXDebugLog) {
            self::disableXDebugLogs();
        }
        self::setLogDisplayLevelByName($logDisplayLevel);
        self::setLogFileLevelByName($logFileLevel);
    }

    private static function createConfigFile(string $path, array $defaultConfig): void
    {
        $maxSizeForRotateName = self::CFG_MAX_SIZE_FOR_ROTATE;
        $maxRotatedFilesCountName = self::CFG_MAX_ROTATED_FILES_COUNT;
        $phpDisplayErrors = self::CFG_PHP_DISPLAY_ERRORS;
        $phpErrorReportingLevel = self::CFG_PHP_ERROR_REPORTING_LEVEL;
        $phpDisableXDebugLog = self::CFG_PHP_DISABLE_XDEBUG_LOG;
        $logDisplayLevel = self::CFG_LOG_DISPLAY_LEVEL;
        $logFileLevel = self::CFG_LOG_FILE_LEVEL;
        $defaultConfig = array_map(function ($x) {
            return var_export($x, true);
        }, $defaultConfig);
        $config = <<<CFG
        ; Максимальный размер файла лога в байтах
        ; По умолчанию: {$defaultConfig[self::CFG_MAX_SIZE_FOR_ROTATE]}
        ;$maxSizeForRotateName={$defaultConfig[self::CFG_MAX_SIZE_FOR_ROTATE]}
        
        ; Максимальное количество ротаций файла лога
        ; По умолчанию: {$defaultConfig[self::CFG_MAX_ROTATED_FILES_COUNT]}
        ;$maxRotatedFilesCountName={$defaultConfig[self::CFG_MAX_ROTATED_FILES_COUNT]}
        
        ; Отображать ошибки PHP на экране
        ; По умолчанию: {$defaultConfig[self::CFG_PHP_DISPLAY_ERRORS]}
        ;$phpDisplayErrors={$defaultConfig[self::CFG_PHP_DISPLAY_ERRORS]}
        
        ; Уровень вывода ошибок PHP в файл
        ; Вычислить можно с помощью констант E_ERROR, E_NOTICE и т.д.
        ; Примеры:
        ; E_ALL = 32767
        ; E_ALL & ~E_NOTICE = 32759
        ; По умолчанию: {$defaultConfig[self::CFG_PHP_ERROR_REPORTING_LEVEL]}
        ;$phpErrorReportingLevel={$defaultConfig[self::CFG_PHP_ERROR_REPORTING_LEVEL]}
        
        ; Отключение вывода логов PHP XDebug
        ; Отключать следует только при необходимости, если используется XDebug
        ; По умолчанию: {$defaultConfig[self::CFG_PHP_DISABLE_XDEBUG_LOG]}
        ;$phpDisableXDebugLog={$defaultConfig[self::CFG_PHP_DISABLE_XDEBUG_LOG]}
        
        ; Уровень отображения логов на экране
        ; Варианты: none, error, warning, info, debug
        ; По умолчанию: {$defaultConfig[self::CFG_LOG_DISPLAY_LEVEL]}
        ;$logDisplayLevel={$defaultConfig[self::CFG_LOG_DISPLAY_LEVEL]}
        
        ; Уровень вывода логов в файл
        ; Варианты: none, error, warning, info, debug
        ; По умолчанию: {$defaultConfig[self::CFG_LOG_FILE_LEVEL]}
        ;$logFileLevel={$defaultConfig[self::CFG_LOG_FILE_LEVEL]}
        CFG;
        if (@file_put_contents($path, $config) === false) {
            throw new RuntimeException(sprintf("Не удается создать файл конфигурации по пути %s", $path), self::ERROR_SETUP_BY_CFG_INCORRECT_INI_PATH);
        }
    }

    public static function startTimer(string $key): void
    {
        if (isset(self::$timers[$key])) {
            throw new LogicException("Таймер с ключом '$key' уже запущен", self::ERROR_TIMER_START_INCORRECT_KEY);
        }
        self::$timers[$key] = time();
    }

    public static function stopTimer(string $key): void
    {
        if (!isset(self::$timers[$key])) {
            throw new OutOfRangeException("Таймер с ключом '$key' не задан", self::ERROR_TIMER_STOP_INCORRECT_KEY);
        }
        $timing = self::$timers[$key];
        unset(self::$timers[$key]);
        self::$timings[$key] = time() - $timing;
    }

    /**
     * Возвращает время в секундах
     */
    public static function getTime(string $key): int
    {
        if (!isset(self::$timings[$key])) {
            throw new OutOfRangeException("Тайминг с ключом '$key' не найден или не завершён методом stopTimer()", self::ERROR_TIME_GET_INCORRECT_KEY);
        }
        return self::$timings[$key];
    }

    private static function rotate(string $path, int $maxSizeForRotate, int $maxRotatedFilesCount): void
    {
        clearstatcache();
        if (!file_exists($path)) {
            return;
        }
        if (filesize($path) < $maxSizeForRotate) {
            return;
        }
        $directory = dirname($path);
        $logName = basename($path);
        // Сохраним в массив все ротированные файлы логов
        $rotatedFileNames = [];
        $fileNames = scandir($directory);
        foreach ($fileNames as $fileName) {
            if (!preg_match(sprintf("/%s.\d+/", $logName), $fileName)) {
                continue;
            }
            $index = str_replace($logName . '.', '', $fileName);
            if (!is_numeric($index)) {
                continue;
            }
            $rotatedFileNames[$index] = $fileName;
        }
        // Если ротированные файлы есть, то переименуем их, увеличив индекс
        if (!empty($rotatedFileNames)) {
            krsort($rotatedFileNames);
            foreach ($rotatedFileNames as $index => $rotatedFileName) {
                $newIndex = $index + 1;
                // Если новый индекс больше максимального количества файлов, то удалим файл
                if ($newIndex > $maxRotatedFilesCount) {
                    unlink($directory . '/' . $rotatedFileName);
                } else {
                    if (!rename($directory . '/' . $rotatedFileName, $path . "." . $newIndex)) {
                        throw new RuntimeException('Не удалось увеличить (переименовать) индекс ротированного лога');
                    }
                }
            }
        }
        // Ротируем текущий файл лога
        if (!rename($path, $directory . '/' . $logName . "." . 1)) {
            throw new RuntimeException('Не удалось ротировать текущий лог');
        }
        if (@file_put_contents($path, '') === false) {
            throw new RuntimeException(sprintf("Не удается создать файл лога по пути %s", $path));
        }
    }

    /**
     * @throws InvalidArgumentException если имя пустое или содержит пробел
     */
    public static function setDefaultName(string $name): void
    {
        self::$defaultName = self::filterName($name);
    }

    /**
     * Установить уровень вывода логов в файле лога
     * @param int $level константа семейства уровня логов данного класса.
     * Значение при инициализации Log::A_ALL
     */
    public static function setLogFileLevel(int $level): void
    {
        self::$logFileLevel = $level;
    }

    /**
     * Установить уровень вывода ошибок PHP в файле лога
     * @param int $level константа семейства уровня ошибок PHP.
     * Значение при инициализации E_ALL
     * @link https://www.php.net/manual/ru/errorfunc.constants.php
     */
    public static function setPhpErrorReportingLevel(int $level): void
    {
        error_reporting($level);
    }

    /**
     * @throws InvalidArgumentException если имя пустое или содержит пробел
     */
    public static function debug(string $message, string $name = null, $data = null): void
    {
        self::log(self::A_DEBUG, $message, $name, $data);
    }

    /**
     * @throws InvalidArgumentException если имя пустое или содержит пробел
     */
    private static function log(int $level, string $message, string $name = null, $data = null): void
    {
        if (!self::$initialized) {
            throw new LogicException(__CLASS__ . " не инициализирован. Используйте метод setup или setupByConfig", static::ERROR_LOG_WITHOUT_SETUP);
        }
        $levelNames = [
            self::A_DEBUG => "Debug",
            self::A_INFO => "Info",
            self::A_WARNING => "Warning",
            self::A_ERROR => "Error"
        ];
        if (!key_exists($level, $levelNames)) {
            throw new DomainException("Неизвестный уровень логирования");
        }
        if ($data) {
            $data = PHP_EOL . print_r($data, true);
        }
        $name = $name !== null ? self::filterName($name) : self::$defaultName;
        $text = "$name $levelNames[$level]: " . $message . $data;
        if ($level & self::$logDisplayLevel) {
            print $text . PHP_EOL;
        }
        $pid = getmypid();
        if ($pid === false) {
            $pid = '-';
        }
        if ($level & self::$logFileLevel) {
            error_log("$pid $text");
        }
    }

    /**
     * @throws InvalidArgumentException если имя пустое или содержит пробел
     */
    private static function filterName(string $name): string
    {
        $filteredName = trim($name);
        if (empty($filteredName)) {
            throw new InvalidArgumentException("Имя логгера '$filteredName' не может быть пустым", self::ERROR_EMPTY_LOG_NAME);
        }
        if (preg_match('/\s+/', $filteredName)) {
            throw new InvalidArgumentException("Имя логгера '$filteredName' содержит пробельные символы", self::ERROR_INVALID_LOG_NAME);
        }
        return $filteredName;
    }

    /**
     * @throws InvalidArgumentException если имя пустое или содержит пробел
     */
    public static function info(string $message, string $name = null, $data = null): void
    {
        self::log(self::A_INFO, $message, $name, $data);
    }

    /**
     * @throws InvalidArgumentException если имя пустое или содержит пробел
     */
    public static function warning(string $message, string $name = null, $data = null): void
    {
        self::log(self::A_WARNING, $message, $name, $data);
    }

    /**
     * @throws InvalidArgumentException если имя пустое или содержит пробел
     */
    public static function error(string $message, string $name = null, $data = null): void
    {
        self::log(self::A_ERROR, $message, $name, $data);
    }

    /**
     * Отображать ли ошибки PHP в выходном потоке приложения
     * @param bool $displayPhpErrors значение при инициализации false
     * @return void
     */
    public static function setPhpDisplayErrors(bool $displayPhpErrors): void
    {
        if (ini_set("display_errors", $displayPhpErrors ? "on" : "off") === false) {
            throw new RuntimeException('Не удалось установить значение display_errors в ini');
        }
    }

    /**
     * Установить уровень отображения логов в выходном потоке приложения
     * @param int $level константа семейства уровня логов данного класса.
     * Значение при инициализации Log::A_NONE
     */
    public static function setLogDisplayLevel(int $level): void
    {
        self::$logDisplayLevel = $level;
    }

    /**
     * Установить уровень отображения логов в выходном потоке приложения
     * @param string $level значения описаны в методе getLogLevelByName
     * @see getLogLevelByName
     */
    public static function setLogDisplayLevelByName(string $level): void
    {
        self::setLogDisplayLevel(self::getLogLevelByName($level));
    }

    /**
     * Установить уровень вывода логов в файле лога
     * @param string $level значения описаны в методе getLogLevelByName
     * @see getLogLevelByName
     */
    public static function setLogFileLevelByName(string $level): void
    {
        self::setLogFileLevel(self::getLogLevelByName($level));
    }

    /**
     * @param string $level уровень логов в виде строки или константы:
     * - Log::S_NONE
     * - Log::S_ERROR
     * - Log::S_WARNING
     * - Log::S_INFO
     * - Log::S_DEBUG
     * @return int
     */
    private static function getLogLevelByName(string $level): int
    {
        switch ($level) {
            case Log::S_NONE:
                return self::A_NONE;
            case Log::S_ERROR:
                return self::A_ERROR;
            case Log::S_WARNING:
                return self::A_ERROR | self::A_WARNING;
            case Log::S_INFO:
                return self::A_ALL & ~self::A_DEBUG;
            case Log::S_DEBUG:
                return self::A_ALL;
            default:
                throw new DomainException('Неизвестный уровень логов', self::ERROR_INVALID_LEVEL_NAME);
        }
    }

    public static function disableXDebugLogs(bool $disabled = true): void
    {
        if ($disabled) {
            if (ini_set("xdebug.log_level", "0") === false) {
                throw new RuntimeException('Не удалось установить значение xdebug.log_level в ini');
            }
        } else {
            ini_restore("xdebug.log_level");
        }
    }
}