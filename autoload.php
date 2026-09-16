<?php

declare(strict_types=1);

spl_autoload_register(
    static function (string $class): void {
        $prefix = 'Alnaseeg\\BranchManager\\';
        $baseDir = __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;

        if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
            return;
        }

        $relativeClass = substr($class, strlen($prefix));
        $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    }
);
