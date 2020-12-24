<?php

namespace marcocesarato\amwscan;

// Autoload
spl_autoload_register(function ($name) {
    $file = str_replace(__NAMESPACE__ . '\\', '', $name) . '.php';
    $file = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $file);
    require_once $file;
});

if (!Scanner::isCli()) {
    trigger_error('This file should run from a console session.', E_USER_WARNING);
}

// Settings
ini_set('memory_limit', '1G');
ini_set('xdebug.max_nesting_level', 500);
ob_implicit_flush(false);
set_time_limit(-1);

// Errors
error_reporting(0);
ini_set('display_errors', 0);

$app = new Scanner();
$app->run();
