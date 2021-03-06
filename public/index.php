<?php

ini_set("display_errors","on");
error_reporting(501);

header('Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');
//header('Access-Control-Allow-Origin: https://campus.sc-edu.com');
//header('Access-Control-Allow-Origin: http://localhost:8089');
header('Access-Control-Allow-Credentials: true');


$allow_origin = array(
    'http://localhost',
    'https://127.0.0.1:3000',
    'https://campus.sc-edu.com',
    'https://127.0.0.1:8080',
    'null'
);
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if (in_array($origin, $allow_origin)) {
    header('Access-Control-Allow-Origin:' . $origin);
}

//header('Access-Control-Allow-Origin: *');
/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| First we need to get an application instance. This creates an instance
| of the application / container and bootstraps the application so it
| is ready to receive HTTP / Console requests from the environment.
|
*/
$app = require __DIR__.'/../bootstrap/app.php';

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request
| through the kernel, and send the associated response back to
| the client's browser allowing them to enjoy the creative
| and wonderful application we have prepared for them.
|
*/

$app->run();
