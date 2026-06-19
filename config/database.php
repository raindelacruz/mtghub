<?php

return [
    'host' => config_env('MTGHUB_DB_HOST', '127.0.0.1'),
    'database' => config_env('MTGHUB_DB_NAME', 'mtghub'),
    'username' => config_env('MTGHUB_DB_USER', 'root'),
    'password' => config_env('MTGHUB_DB_PASSWORD', ''),
    'charset' => config_env('MTGHUB_DB_CHARSET', 'utf8mb4'),
];
