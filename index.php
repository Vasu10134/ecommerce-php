<?php

define('LARAVEL_START', microtime(true));

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
