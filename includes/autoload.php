<?php

spl_autoload_register(function ($class) {
    $map = [
        'Config\\' => __DIR__ . '/../config/',
        'Models\\' => __DIR__ . '/../models/',
        'Controllers\\' => __DIR__ . '/../controllers/',
    ];

    foreach ($map as $prefix => $baseDir)
    {
        if (strncmp($prefix, $class, strlen($prefix)) === 0)
        {
            $relativeClass = substr($class, strlen($prefix));
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

            if (file_exists($file))
            {
                require_once $file;
                return;
            }
        }
    }
});
