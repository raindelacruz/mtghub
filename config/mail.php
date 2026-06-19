<?php

return [
    'host' => config_env('MTGHUB_SMTP_HOST', 'smtp.gmail.com'),
    'port' => (int) config_env('MTGHUB_SMTP_PORT', 587),
    'encryption' => config_env('MTGHUB_SMTP_ENCRYPTION', 'tls'),
    'username' => config_env('MTGHUB_SMTP_USERNAME', ''),
    'password' => config_env('MTGHUB_SMTP_PASSWORD', ''),
    'from_email' => config_env('MTGHUB_MAIL_FROM_EMAIL', ''),
    'from_name' => config_env('MTGHUB_MAIL_FROM_NAME', 'MTGHub PH'),
    'timeout' => (int) config_env('MTGHUB_SMTP_TIMEOUT', 15),
];
