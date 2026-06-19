<?php

declare(strict_types=1);

$isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
session_name('mtghub_session');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);

$sessionPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'sessions';
if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0775, true);
}
session_save_path($sessionPath);
session_start();

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'app');
define('CONFIG_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'config');

require CONFIG_PATH . DIRECTORY_SEPARATOR . 'env.php';
$appConfig = require CONFIG_PATH . DIRECTORY_SEPARATOR . 'app.php';

define('APP_NAME', $appConfig['app_name']);
define('BASE_URL', rtrim($appConfig['base_url'], '/'));
define('ASSET_URL', rtrim($appConfig['asset_url'], '/'));
define('APP_ENV', $appConfig['environment']);
define('PASSWORD_RESET_TTL', $appConfig['password_reset_ttl']);

require APP_PATH . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'helpers.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'Database.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'services' . DIRECTORY_SEPARATOR . 'ErrorMonitor.php';
ErrorMonitor::register();
refresh_current_user_security();
require APP_PATH . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'Controller.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'Router.php';
