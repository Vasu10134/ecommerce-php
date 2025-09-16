<?php

define('LARAVEL_START', microtime(true));

// On PHP 8.2, many legacy libraries trigger deprecation notices for dynamic
// properties. Suppress deprecation-level notices so the app runs cleanly
// while keeping other errors visible.
if (function_exists('error_reporting')) {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
}

require __DIR__.'/project/vendor/autoload.php';

$app = require_once __DIR__.'/project/bootstrap/app.php';
$app->bind('path.public', function() {
    return __DIR__;
});

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$response->send();

$kernel->terminate($request, $response);
