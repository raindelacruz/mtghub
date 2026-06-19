<?php

return [
    'app_name' => config_env('MTGHUB_APP_NAME', 'MTGHub PH'),
    'base_url' => config_env('MTGHUB_BASE_URL', '/mtghub/public'),
    'asset_url' => config_env('MTGHUB_ASSET_URL', '/mtghub/assets'),
    'environment' => config_env('MTGHUB_ENV', 'production'),
    'password_reset_ttl' => (int) config_env('MTGHUB_PASSWORD_RESET_TTL', 3600),
    'public_origin' => config_env('MTGHUB_PUBLIC_ORIGIN', 'http://localhost'),
];
