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
 * @version 2.0
 * @todo Автоконфигурация
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
    private const CFG_PATH = 'path';
    private const CFG_MAX_SIZE_FOR_ROTATE = 'maxSizeForRotate';
    private const CFG_MAX_ROTATED_FILES_COUNT = 'maxRotatedFilesCount';
    public const ERROR_LOG_WITHOUT_SETUP = 1000;
    public const ERROR_SETUP_INCORRECT_LOG_PATH = 1001;
    public const ERROR_SETUP_INCORRECT_MAX_SIZE_FOR_ROTATE = 1002;
    public const ERROR_SETUP_INCORRECT_MAX_ROTATED_FILES_COUNT = 1003;
    public const ERROR_TIMER_START_INCORRECT_KEY = 1004;
    public const ERROR_TIMER_STOP_INCORRECT_KEY = 1005;
    public const ERROR_TIME_GET_INCORRECT_KEY = 1006;
    public const ERROR_INVALID_LOG_NAME = 1007;
    public const ERROR_EMPTY_LOG_NAME = 1008;

    /** @var int */
    private static $logReportingLevel = self::A_ALL;
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

    private static function setupByConfig(string $path = 'logger.ini'): void
    {
        // TODO Тестировать метод
        $defaultConfig = [
            self::CFG_PATH => 'latest.log',
            self::CFG_MAX_SIZE_FOR_ROTATE => 10000000,
            self::CFG_MAX_ROTATED_FILES_COUNT => 10,
        ];
        if (!file_exists($path)) {
            self::createConfigFile($path, $defaultConfig);
        }
        $config = parse_ini_file($path);
        if ($config === false) {
            throw new RuntimeException("Ошибка чтения файла конфигурации '$path'");
        }
        $config = array_merge($defaultConfig, $config);
        $maxSizeForRotate = filter_var($config[self::CFG_MAX_SIZE_FOR_ROTATE], FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
        if ($maxSizeForRotate === null) {
            throw new UnexpectedValueException(sprintf('Ошибка чтения параметра %s', self::CFG_MAX_SIZE_FOR_ROTATE));
        }
        $maxRotatedFilesCount = filter_var($config[self::CFG_MAX_ROTATED_FILES_COUNT], FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
        if ($maxRotatedFilesCount === null) {
            throw new UnexpectedValueException(sprintf('Ошибка чтения параметра %s', self::CFG_MAX_ROTATED_FILES_COUNT));
        }
        self::setup($config[self::CFG_PATH], $maxSizeForRotate, $maxRotatedFilesCount);
    }

    private static function createConfigFile(string $path, array $defaultConfig): void
    {
        $pathName = self::CFG_PATH;
        $maxSizeForRotateName = self::CFG_MAX_SIZE_FOR_ROTATE;
        $maxRotatedFilesCountName = self::CFG_MAX_ROTATED_FILES_COUNT;
        $config = <<<CFG
        # Путь к файлу лога
        $pathName={$defaultConfig[self::CFG_PATH]}
        
        # Максимальный размер файла лога в байтах
        ;$maxSizeForRotateName={$defaultConfig[self::CFG_MAX_SIZE_FOR_ROTATE]}
        
        # Максимальное количество ротаций файла лога 
        ;$maxRotatedFilesCountName={$defaultConfig[self::CFG_MAX_ROTATED_FILES_COUNT]}
        CFG;
        if (@file_put_contents($path, $config) === false) {
            throw new RuntimeException(sprintf("Не удается создать файл конфигурации по пути %s", $path));
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
        self::$logReportingLevel = $level;
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
        if ($level & self::$logReportingLevel) {
            error_log($text);
        }
        if ($level & self::$logDisplayLevel) {
            print $text . PHP_EOL;
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
     * - Log::S_ERROR
     * - Log::S_WARNING
     * - Log::S_INFO
     * - Log::S_DEBUG
     * @return int
     */
    private static function getLogLevelByName(string $level): int
    {
        switch ($level) {
            case Log::S_ERROR:
                return self::A_ERROR;
            case Log::S_WARNING:
                return self::A_ERROR | self::A_WARNING;
            case Log::S_INFO:
                return self::A_ALL & ~self::A_DEBUG;
            case Log::S_DEBUG:
                return self::A_ALL;
            default:
                throw new DomainException('Неизвестный уровень логов');
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