<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

foreach (['/tmp/views', '/tmp/cache'] as $directory) {
    if (! is_dir($directory)) {
        mkdir($directory, 0777, true);
    }
}

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
