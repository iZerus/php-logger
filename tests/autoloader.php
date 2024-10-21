<?php

declare(strict_types=1);

spl_autoload_register(function ($class) {
    $file = str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    $path = __DIR__ . '/../src/' . $file;
    if (file_exists($path)) {
        require $path;
        return true;
    }
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        require $path;
        return true;
    }
    return false;
});