<?php
spl_autoload_register(function (string $class) {
    $base = dirname(__DIR__) . DIRECTORY_SEPARATOR;

    $map = [
        'App\\Controllers\\'  => $base . 'app' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR,
        'App\\Models\\'       => $base . 'app' . DIRECTORY_SEPARATOR . 'Models' . DIRECTORY_SEPARATOR,
        'App\\Middleware\\'   => $base . 'app' . DIRECTORY_SEPARATOR . 'Middleware' . DIRECTORY_SEPARATOR,
        'App\\Helpers\\'      => $base . 'app' . DIRECTORY_SEPARATOR . 'Helpers' . DIRECTORY_SEPARATOR,
    ];

    foreach ($map as $prefix => $dir) {
        if (strncmp($class, $prefix, strlen($prefix)) === 0) {
            $classPath = $dir . str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix))) . '.php';
            if (file_exists($classPath)) {
                require_once $classPath;
                return;
            }
        }
    }
});
