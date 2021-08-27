<?php

$dsn = getenv('DB_DSN');
if ($dsn === false) {
    $dsn = 'sqlite::memory:';
}

return [
    'app_name' => 'AppTest',

    'secret_key' => 'change-me',

    'url_options' => [
        'host' => 'localhost',
    ],

    'database' => [
        'dsn' => $dsn,
        'username' => getenv('DB_USERNAME'),
        'password' => getenv('DB_PASSWORD'),
    ],

    'mailer' => [
        'type' => 'test',
        'from' => 'root@localhost',
    ],

    'no_syslog_output' => true,
];
