<?php
/**
 * Created by PhpStorm.
 * User: bitzo
 * Date: 2019/2/23
 * Time: 16:06
 */

return [
    'driver' => env('SESSION_DRIVER', 'cookie'),
    'lifetime' => 120,//缓存失效时间
    'expire_on_close' => false,
    'encrypt' => false,
    'files' => storage_path('framework/session'),//file缓存保存路径
    'connection' => null,
    'table' => 'sessions',
    'lottery' => [2, 100],
    'cookie' => 'visitor_session',
    'path' => '/',
    'domain' => null,
    'secure' => false,
];