<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/init.php';
require APP_PATH . '/models/User.php';
require APP_PATH . '/models/Wallet.php';
require APP_PATH . '/models/AccountDeletion.php';
$result = (new AccountDeletion())->processDue();
echo 'Completed: ' . $result['completed'] . '; blocked: ' . $result['blocked'] . PHP_EOL;
