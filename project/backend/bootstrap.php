<?php
declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/';

    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $parts = explode('\\', $relativeClass);
    $fileName = array_pop($parts) . '.php';
    $directory = $parts === [] ? '' : strtolower(implode('/', $parts)) . '/';
    $file = $baseDir . $directory . $fileName;

    if (is_file($file)) {
        require_once $file;
    }
});
