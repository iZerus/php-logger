<?php
declare(strict_types=1);

namespace iZerus;

use Error;

/**
 * Статический логгер с ротацией
 * @author iZerus
 * @version 2.0
 */
final class Log
{
    const A_NONE = 0;
    const A_DEBUG = 2;
    const A_INFO = 4;
    const A_WARNING = 8;
    const A_ERROR = 16;
    const A_ALL = 32767;
    private static int $logReportingLevel = self::A_ALL;
    private static int $logDisplayLevel = self::A_NONE;
    private static string $defaultName = "Application";
    private static bool $initialized = false;
    private static array $timers;
    private static array $timings;

    public static function setup(string $path, int $maxSizeForRotate = 10_000_000, int $maxRotatedFilesCount = 10): void
    {
        list($major, $minor) = explode(".", phpversion());
        $version = "$major.$minor";
        $supportedVersions = ["7.4"];
        if (!in_array($version, $supportedVersions)) {
            throw new Error(sprintf("%s не поддерживает версию PHP %s", __CLASS__, $version));
        }
        if (!file_exists($path) && (@file_put_contents($path, '') === false)) {
            throw new Error(sprintf("Не удается создать файл лога по пути %s", $path));
        }
        if ($maxSizeForRotate < 1) {
            throw new Error("Значение 'maxSizeForRotate' не может быть меньше или равно нулю");
        }
        if ($maxRotatedFilesCount < 1) {
            throw new Error("Значение 'maxRotatedFilesCount' не может быть меньше или равно нулю");
        }
        ini_set("error_log", $path);
        ini_set("log_errors", "1");
        self::setPhpErrorReportingLevel(E_ALL);
        self::setPhpDisplayErrors(false);
        self::$initialized = true;
        self::rotate($path, $maxSizeForRotate, $maxRotatedFilesCount);
    }

    public static function startTimer(string $key): void
    {
        if (isset(self::$timers[$key])) {
            throw new Error("Таймер с ключом '$key' уже задан");
        }
        self::$timers[$key] = time();
    }

    public static function stopTimer(string $key): void
    {
        if (!isset(self::$timers[$key])) {
            throw new Error("Таймер с ключом '$key' не задан");
        }
        $timing = self::$timers[$key];
        unset(self::$timers[$key]);
        self::$timings[$key] = time() - $timing;
    }

    public static function getTime(string $key): int
    {
        if (!isset(self::$timings[$key])) {
            throw new Error("Тайминг с ключом '$key' не найден или не завершён методом stopTimer()");
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
                        throw new Error('Не удалось увеличить (переименовать) индекс ротированного лога');
                    }
                }
            }
        }
        // Ротируем текущий файл лога
        if (!rename($path, $directory . '/' . $logName . "." . 1)) {
            throw new Error('Не удалось ротировать текущий лог');
        }
        if (@file_put_contents($path, '') === false) {
            throw new Error(sprintf("Не удается создать файл лога по пути %s", $path));
        }
    }

    public static function setDefaultName(string $name): void
    {
        self::$defaultName = $name;
    }

    /**
     * @param int $level константа семейства уровня логов данного класса.
     * Значение при инициализации Log::A_ALL
     */
    public static function setLogFileLevel(int $level): void
    {
        self::$logReportingLevel = $level;
    }

    /**
     * @param int $level константа семейства уровня ошибок PHP.
     * Значение при инициализации E_ALL
     * @link https://www.php.net/manual/ru/errorfunc.constants.php
     */
    public static function setPhpErrorReportingLevel(int $level): void
    {
        error_reporting($level);
    }

    public static function debug(string $message, string $name = null, $data = null): void
    {
        self::log(self::A_DEBUG, $message, $name, $data);
    }

    private static function log(int $level, string $message, string $name = null, $data = null): void
    {
        if (!self::$initialized) {
            throw new Error(__CLASS__ . " не инициализирован. Используйте метод setup");
        }
        if (!($level & self::$logReportingLevel)) {
            return;
        }
        $levelNames = [
            self::A_DEBUG => "Debug",
            self::A_INFO => "Info",
            self::A_WARNING => "Warning",
            self::A_ERROR => "Error"
        ];
        if (!key_exists($level, $levelNames)) {
            throw new Error("Неизвестный уровень логирования");
        }
        if ($data) {
            $data = PHP_EOL . "Data: " . self::varDump($data);
        }
        $name = !empty($name) ? $name : self::$defaultName;
        $text = "$name $levelNames[$level]: " . $message . $data;
        error_log($text);
        if (!($level & self::$logDisplayLevel)) {
            return;
        }
        print $text . PHP_EOL;
    }

    private static function varDump($value): string
    {
        ob_start();
        var_dump($value);
        $content = trim(ob_get_contents());
        ob_end_clean();
        return $content;
    }

    public static function info(string $message, string $name = null, $data = null): void
    {
        self::log(self::A_INFO, $message, $name, $data);
    }

    public static function warning(string $message, string $name = null, $data = null): void
    {
        self::log(self::A_WARNING, $message, $name, $data);
    }

    public static function error(string $message, string $name = null, $data = null): void
    {
        self::log(self::A_ERROR, $message, $name, $data);
    }

    /**
     * @param bool $displayPhpErrors выводить ли ошибки.
     * Значение при инициализации false
     * @return void
     */
    public static function setPhpDisplayErrors(bool $displayPhpErrors): void
    {
        ini_set("display_errors", $displayPhpErrors ? "on" : "off");
    }

    /**
     * @param int $level константа семейства уровня логов данного класса.
     * Значение при инициализации Log::A_NONE
     */
    public static function setLogDisplayLevel(int $level): void
    {
        self::$logDisplayLevel = $level;
    }
}