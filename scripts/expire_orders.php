<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'init.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Order.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Wallet.php';

$expired = (new Order())->expireOverdue();
$settled = (new Order())->settleEligibleOrders();
echo 'Expired orders: ' . $expired . PHP_EOL;
echo 'Automatically settled orders: ' . $settled . PHP_EOL;
