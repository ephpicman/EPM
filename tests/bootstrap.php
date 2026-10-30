<?php

declare(strict_types=1);

$sourceDirectory = dirname(__DIR__) . '/src';

if (!is_dir($sourceDirectory)) {
    throw new RuntimeException(
        'Framework source directory not found: ' . $sourceDirectory
    );
}

spl_autoload_register(
    static function (string $class) use ($sourceDirectory): void {
        $prefix = 'EPM\\';

        if (0 !== strpos($class, $prefix)) {
            return;
        }

        $relativeClass = substr($class, strlen($prefix));
        $file = $sourceDirectory . DIRECTORY_SEPARATOR
            . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass)
            . '.php';

        if (is_file($file) && is_readable($file)) {
            require_once $file;
        }
    }
);
