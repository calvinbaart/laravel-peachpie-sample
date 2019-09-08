<?php

require_once __DIR__ . "/../src/init.php";

global $__laravelHttpKernel;

$response = $__laravelHttpKernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$response->send();

$__laravelHttpKernel->terminate($request, $response);
