<?php
/**
 * Created by PhpStorm.
 * User: bitzo
 * Date: 2019/2/22
 * Time: 21:45
 */
//require_once "/opt/ci123/www/html/sc-edu/com/inc/config.php";

$gdbconf['scapp'] = [
    'port' => 3306,
    'host' => 'localhost',
    'dbname' => 'scapp',
    'username' => 'root',
    'password' => ''
];

return [
    'fetch' => PDO::FETCH_CLASS,
    'default' => env('DB_CONNECTION', 'mysql'),
    'connections' => [
        'mysql' => [
            'name'      => 'mysql',
            'driver'    => 'mysql',
            'host'      => $gdbconf['scapp']['host'],
            'port'      => $gdbconf['scapp']['port'],
            'database'  => $gdbconf['scapp']['dbname'],
            'username'  => $gdbconf['scapp']['username'],
            'password'  => $gdbconf['scapp']['password'],
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => 'repair_',
            'timezone'  => '+00:00',
            'strict'    => false,
        ],
        'ecp' => [
            'driver'    => 'mysql',
            'host'      => 'localhost',
            'port'      => 3306,
            'database'  => 'ecp',
            'username'  => 'root',
            'password'  => '',
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => 'ecp_',
            'timezone'  => '+00:00',
            'strict'    => false,
        ],
    ],
];
