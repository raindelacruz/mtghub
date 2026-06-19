<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/init.php';
require APP_PATH . '/controllers/OrderController.php';
require APP_PATH . '/models/User.php';
require APP_PATH . '/models/AccountDeletion.php';
require APP_PATH . '/models/LoginAttempt.php';
require APP_PATH . '/services/UploadSecurity.php';

$db = Database::connection();
$cleanup = ['users' => [], 'listings' => [], 'orders' => []];
$passed = 0;

function assert_ok(bool $condition, string $message): void
{
    global $passed;
    if (!$condition) throw new RuntimeException($message);
    $passed++;
}

function temp_user(PDO $db, array &$cleanup, string $role, string $password = 'Production123!'): int
{
    $token = bin2hex(random_bytes(4));
    $statement = $db->prepare("INSERT INTO users (username,first_name,middle_initial,last_name,email,email_verified_at,contact_number,password_hash,address_number,address_street,address_barangay,address_province,address_city,address_postal_code,shipping_same_as_complete,shipping_number,shipping_street,shipping_barangay,shipping_province,shipping_city,shipping_postal_code,delivery_mode,payment_mode,city,province,role,account_status) VALUES (?, 'Production','T','Test',?,NOW(),'09990000000',?,'1','Test','Test','Metro Manila','Manila','1000',1,'1','Test','Test','Metro Manila','Manila','1000','meetup','gcash','Manila','Metro Manila',?,'active')");
    $statement->execute(['p7_' . $role . '_' . $token, 'p7_' . $role . '_' . $token . '@example.invalid', password_hash($password, PASSWORD_DEFAULT), $role]);
    $id = (int) $db->lastInsertId(); $cleanup['users'][] = $id;
    $db->prepare('INSERT INTO wallets (user_id,store_credit_balance) VALUES (?,0)')->execute([$id]);
    return $id;
}

function listing(PDO $db, array &$cleanup, int $sellerId, int $cardId, int $quantity, float $price): int
{
    $statement = $db->prepare("INSERT INTO listings (user_id,card_id,quantity,card_condition,price_php,seller_location,delivery_options,status,notes) VALUES (?, ?, ?, 'near_mint', ?, 'Manila', 'Meetup', 'active', 'Phase 7 test')");
    $statement->execute([$sellerId, $cardId, $quantity, $price]);
    $id = (int) $db->lastInsertId(); $cleanup['listings'][] = $id; return $id;
}

try {
    $buyer = temp_user($db, $cleanup, 'buyer');
    $seller = temp_user($db, $cleanup, 'seller');
    $outsider = temp_user($db, $cleanup, 'outsider');
    $cardId = (int) $db->query('SELECT id FROM cards ORDER BY id LIMIT 1')->fetchColumn();
    assert_ok($cardId > 0, 'A seed card is required.');

    $user = (new User())->findById($buyer);
    assert_ok(password_verify('Production123!', $user['password_hash']), 'Authentication password verification failed.');
    $attempts = new LoginAttempt(); $loginEmail = 'rate_' . bin2hex(random_bytes(4)) . '@example.invalid'; $loginIp = '192.0.2.' . random_int(1, 200);
    for ($i=0;$i<5;$i++) $attempts->recordFailure($loginEmail,$loginIp);
    assert_ok($attempts->isBlocked($loginEmail,$loginIp), 'Authentication rate limit did not block repeated failures.');
    $attempts->clear($loginEmail,$loginIp);
    assert_ok(!$attempts->isBlocked($loginEmail,$loginIp), 'Authentication rate limit did not clear after successful-login cleanup.');
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    assert_ok(csrf_is_valid($_SESSION['csrf_token']) && !csrf_is_valid('wrong'), 'CSRF token acceptance/rejection failed.');

    $firstListing = listing($db, $cleanup, $seller, $cardId, 1, 100);
    $db->prepare('UPDATE wallets SET store_credit_balance=100 WHERE user_id=?')->execute([$buyer]);
    $db->prepare('INSERT INTO cart_items (buyer_id,listing_id,quantity) VALUES (?,?,1)')->execute([$buyer, $firstListing]);
    $orders = new Order();
    $orderId = $orders->createFromCart($buyer, ['buyer_location'=>'Manila','delivery_details'=>'Meetup at agreed location','logistics_method'=>'meetup','external_payment_method'=>'GCash','store_credit_to_use'=>100,'notes'=>'']);
    $cleanup['orders'][] = $orderId;
    $order = $orders->find($orderId);
    assert_ok($order['status'] === 'payment_verified' && abs(Wallet::getBalance($buyer)) < 0.001, 'Checkout did not atomically debit store credit.');
    $listingRow = $db->query('SELECT quantity,status FROM listings WHERE id=' . $firstListing)->fetch();
    assert_ok((int) $listingRow['quantity'] === 0 && $listingRow['status'] === 'reserved', 'Checkout did not reserve inventory.');

    $controller = new OrderController();
    $authorization = new ReflectionMethod(OrderController::class, 'canSetStatus');
    $_SESSION['user'] = ['id'=>$seller,'role'=>'user','account_status'=>'active','email_verified_at'=>date('Y-m-d H:i:s')];
    assert_ok($authorization->invoke($controller, $order, 'ready_for_meetup') === true, 'Seller authorization rejected a valid transition.');
    $_SESSION['user']['id'] = $outsider;
    assert_ok($authorization->invoke($controller, $order, 'ready_for_meetup') === false, 'Outsider authorization allowed an order transition.');

    $orders->updateStatus($order, 'ready_for_meetup', $seller, 'Ready');
    $order = $orders->find($orderId); $orders->updateStatus($order, 'delivered', $buyer, 'Received');
    $order = $orders->find($orderId); $orders->updateStatus($order, 'buyer_confirmed', $buyer, 'Confirmed');
    $order = $orders->find($orderId); $orders->updateStatus($order, 'completed', $seller, 'Completed');
    $order = $orders->find($orderId);
    assert_ok($order['status'] === 'completed' && abs(Wallet::getBalance($seller) - 100) < 0.001, 'Buyer-seller E2E settlement failed.');
    assert_ok($db->query('SELECT status FROM listings WHERE id=' . $firstListing)->fetchColumn() === 'sold', 'Completed order did not mark depleted inventory sold.');

    $secondListing = listing($db, $cleanup, $seller, $cardId, 2, 50);
    $db->prepare('UPDATE wallets SET store_credit_balance=50 WHERE user_id=?')->execute([$buyer]);
    $db->prepare('INSERT INTO cart_items (buyer_id,listing_id,quantity) VALUES (?,?,1)')->execute([$buyer, $secondListing]);
    $cancelId = $orders->createFromCart($buyer, ['buyer_location'=>'Manila','delivery_details'=>'Meetup','logistics_method'=>'meetup','external_payment_method'=>'GCash','store_credit_to_use'=>50,'notes'=>'']);
    $cleanup['orders'][] = $cancelId;
    $cancel = $orders->find($cancelId); $orders->updateStatus($cancel, 'cancelled', $buyer, 'Cancelled test');
    $restored = $db->query('SELECT quantity,status FROM listings WHERE id=' . $secondListing)->fetch();
    assert_ok((int) $restored['quantity'] === 2 && $restored['status'] === 'active' && abs(Wallet::getBalance($buyer) - 50) < 0.001, 'Cancellation did not restore inventory and wallet atomically.');
    assert_ok((int) $db->query("SELECT COUNT(*) FROM wallet_transactions WHERE idempotency_key='order:$cancelId:buyer-refund'")->fetchColumn() === 1, 'Refund ledger is not idempotent.');

    $pngPath = tempnam(sys_get_temp_dir(), 'p7upload');
    file_put_contents($pngPath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));
    $valid = UploadSecurity::validatePaymentProof(['error'=>UPLOAD_ERR_OK,'tmp_name'=>$pngPath,'size'=>filesize($pngPath),'name'=>'../../proof.php.png'], false);
    assert_ok($valid['mime_type'] === 'image/png' && $valid['extension'] === 'png' && $valid['name'] === 'proof.php.png', 'Safe image validation or path normalization failed.');
    file_put_contents($pngPath, '<?php echo "unsafe";');
    try { UploadSecurity::validatePaymentProof(['error'=>UPLOAD_ERR_OK,'tmp_name'=>$pngPath,'size'=>filesize($pngPath),'name'=>'proof.png'], false); throw new RuntimeException('Executable upload accepted.'); }
    catch (RuntimeException $error) { assert_ok($error->getMessage() !== 'Executable upload accepted.', $error->getMessage()); }
    try { UploadSecurity::validatePaymentProof(['error'=>UPLOAD_ERR_OK,'tmp_name'=>$pngPath,'size'=>UploadSecurity::MAX_IMAGE_SIZE+1,'name'=>'large.png'], false); throw new RuntimeException('Oversized upload accepted.'); }
    catch (RuntimeException $error) { assert_ok($error->getMessage() !== 'Oversized upload accepted.', $error->getMessage()); }
    @unlink($pngPath);

    $db->prepare('UPDATE wallets SET store_credit_balance=0 WHERE user_id=?')->execute([$outsider]);
    (new AccountDeletion())->request($outsider, 'Production123!', '127.0.0.1');
    $pending = (new AccountDeletion())->pendingForUser($outsider);
    assert_ok($pending !== null, 'Account deletion request was not scheduled.');
    (new AccountDeletion())->cancel($outsider);
    assert_ok((new AccountDeletion())->pendingForUser($outsider) === null, 'Account deletion cancellation failed.');

    echo "Production readiness tests passed: $passed assertions.\n";
} finally {
    unset($_SESSION['user']);
    if ($cleanup['orders']) { $marks=implode(',',array_fill(0,count($cleanup['orders']),'?')); $db->prepare("DELETE FROM orders WHERE id IN ($marks)")->execute($cleanup['orders']); }
    if ($cleanup['listings']) { $marks=implode(',',array_fill(0,count($cleanup['listings']),'?')); $db->prepare("DELETE FROM listings WHERE id IN ($marks)")->execute($cleanup['listings']); }
    if ($cleanup['users']) { $marks=implode(',',array_fill(0,count($cleanup['users']),'?')); $db->prepare("DELETE FROM users WHERE id IN ($marks)")->execute($cleanup['users']); }
}
