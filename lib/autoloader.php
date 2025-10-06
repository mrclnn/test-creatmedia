<?php

$projectRoot = dirname(__DIR__);

spl_autoload_register(function ($class) use ($projectRoot) {
    $file = $projectRoot . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
