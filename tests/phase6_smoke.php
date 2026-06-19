<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/init.php';
require APP_PATH . '/models/Dispute.php';
require APP_PATH . '/models/MarketplaceReview.php';
require APP_PATH . '/models/Report.php';

$db = Database::connection();
$ids = ['users' => [], 'orders' => []];

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

try {
    $seed = $db->query("SELECT * FROM users WHERE role='user' ORDER BY id LIMIT 1")->fetch();
    $listingId = (int) $db->query('SELECT id FROM listings ORDER BY id LIMIT 1')->fetchColumn();
    $adminId = (int) $db->query("SELECT id FROM users WHERE role='admin' ORDER BY id LIMIT 1")->fetchColumn();
    check((bool) $seed && $listingId > 0 && $adminId > 0, 'Seed users, admin, and a listing are required.');

    $insertUser = $db->prepare("INSERT INTO users (username,first_name,middle_initial,last_name,email,email_verified_at,contact_number,password_hash,address_number,address_street,address_barangay,address_province,address_city,address_postal_code,shipping_same_as_complete,shipping_number,shipping_street,shipping_barangay,shipping_province,shipping_city,shipping_postal_code,delivery_mode,payment_mode,city,province,role,account_status) VALUES (:username,'Phase','T','Six',:email,NOW(),'09990000000',:password_hash,'1','Test','Test','Test','Test','1000',1,'1','Test','Test','Test','Test','1000','meetup','gcash','Test','Test','user','active')");
    foreach (['buyer', 'seller'] as $role) {
        $token = bin2hex(random_bytes(4));
        $insertUser->execute(['username' => 'p6_' . $role . '_' . $token, 'email' => 'p6_' . $role . '_' . $token . '@example.invalid', 'password_hash' => $seed['password_hash']]);
        $ids['users'][$role] = (int) $db->lastInsertId();
    }
    $buyerId = $ids['users']['buyer'];
    $sellerId = $ids['users']['seller'];
    $db->prepare('INSERT INTO wallets (user_id,store_credit_balance) VALUES (?,0),(?,100)')->execute([$buyerId, $sellerId]);

    $insertOrder = $db->prepare("INSERT INTO orders (listing_id,buyer_id,seller_id,quantity,unit_price_php,total_price_php,buyer_location,delivery_preference,logistics_method,logistics_fee_php,payment_method,external_payment_method,payment_reference,payment_status,fulfillment_status,status,completed_at,store_credit_used,cash_amount_due,store_credit_settled) VALUES (:listing,:buyer,:seller,1,100,100,'Test','Test','meetup',0,'mixed','GCash','P6TEST','verified','completed','completed',NOW(),60,40,:settled)");
    $insertOrder->execute(['listing' => $listingId, 'buyer' => $buyerId, 'seller' => $sellerId, 'settled' => 1]);
    $refundOrderId = (int) $db->lastInsertId();
    $ids['orders'][] = $refundOrderId;

    $order = $db->query('SELECT * FROM orders WHERE id=' . $refundOrderId)->fetch();
    $dispute = new Dispute();
    $disputeId = $dispute->open($order, $buyerId, 'condition_mismatch', 'The received card condition differs materially.', 'See the order discussion.');
    check($db->query('SELECT status FROM orders WHERE id=' . $refundOrderId)->fetchColumn() === 'disputed', 'Opening a dispute must freeze the order.');
    $dispute->resolve($disputeId, 'full_refund', 0, 'Full refund approved after reviewing the supplied evidence.', $adminId);
    $resolved = $db->query('SELECT * FROM order_disputes WHERE id=' . $disputeId)->fetch();
    check($resolved['refund_store_credit'] === '60.00' && $resolved['refund_external'] === '40.00', 'Refund allocation is incorrect.');
    check(abs(Wallet::getBalance($buyerId) - 60.0) < 0.001 && abs(Wallet::getBalance($sellerId) - 40.0) < 0.001, 'Wallet reversal balances are incorrect.');
    check($db->query('SELECT status FROM orders WHERE id=' . $refundOrderId)->fetchColumn() === 'refunded', 'Resolved refund must mark the order refunded.');

    $insertOrder->execute(['listing' => $listingId, 'buyer' => $buyerId, 'seller' => $sellerId, 'settled' => 0]);
    $reviewOrderId = (int) $db->lastInsertId();
    $ids['orders'][] = $reviewOrderId;
    $reviews = new MarketplaceReview();
    $reviewId = $reviews->create($reviewOrderId, $buyerId, 5, 'Reliable seller and accurate card condition.');
    try {
        $reviews->create($reviewOrderId, $buyerId, 4, 'This duplicate must not be accepted.');
        throw new RuntimeException('Duplicate review was accepted.');
    } catch (RuntimeException $expected) {
        check($expected->getMessage() !== 'Duplicate review was accepted.', $expected->getMessage());
    }
    $metrics = $reviews->metrics($sellerId);
    check((int) $metrics['review_count'] === 1 && (float) $metrics['average_rating'] === 5.0, 'Seller review metrics are incorrect.');
    (new Report())->create(['reporter_id' => $sellerId, 'subject_type' => 'review', 'subject_id' => $reviewId, 'reason' => 'inappropriate', 'details' => 'Phase 6 review reporting smoke test.']);
    $reported = array_values(array_filter((new Report())->allForAdmin(), static fn (array $row): bool => $row['subject_type'] === 'review' && (int) $row['subject_id'] === $reviewId));
    check(count($reported) === 1 && str_contains($reported[0]['subject_label'], 'Review #'), 'Review report labeling failed.');
    $reviews->moderate($reviewId, 'hidden', 'Hidden by the Phase 6 moderation smoke test.', $adminId);
    check($reviews->forSeller($sellerId) === [], 'Hidden reviews must not appear publicly.');

    echo "Phase 6 smoke tests passed.\n";
} finally {
    if ($ids['orders'] !== []) {
        $marks = implode(',', array_fill(0, count($ids['orders']), '?'));
        $db->prepare("DELETE FROM reports WHERE subject_type='review' AND subject_id IN (SELECT id FROM marketplace_reviews WHERE order_id IN ($marks))")->execute($ids['orders']);
        $db->prepare("DELETE FROM orders WHERE id IN ($marks)")->execute($ids['orders']);
    }
    if ($ids['users'] !== []) {
        $userIds = array_values($ids['users']);
        $marks = implode(',', array_fill(0, count($userIds), '?'));
        $db->prepare("DELETE FROM notifications WHERE user_id IN ($marks)")->execute($userIds);
        $db->prepare("DELETE FROM users WHERE id IN ($marks)")->execute($userIds);
    }
}
