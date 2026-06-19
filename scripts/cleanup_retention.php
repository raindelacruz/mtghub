<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/init.php';
$db = Database::connection();
$removedProofs = 0;
$proofs = $db->query("SELECT payment_proofs.id,payment_proofs.stored_name FROM payment_proofs INNER JOIN orders ON orders.id=payment_proofs.order_id WHERE orders.status IN ('completed','cancelled','expired','refunded') AND payment_proofs.created_at<NOW()-INTERVAL 180 DAY AND NOT EXISTS (SELECT 1 FROM order_disputes WHERE order_disputes.order_id=orders.id AND order_disputes.status IN ('open','reviewing'))")->fetchAll();
foreach ($proofs as $proof) {
    $path = ROOT_PATH . '/storage/payment_proofs/' . basename($proof['stored_name']);
    if (is_file($path) && !unlink($path)) { ErrorMonitor::record('warning','retention_file_failed','Could not remove expired payment proof',['proof_id'=>(int)$proof['id']]); continue; }
    $db->prepare('DELETE FROM payment_proofs WHERE id=?')->execute([(int)$proof['id']]); $removedProofs++;
}
$db->exec("DELETE FROM login_attempts WHERE attempted_at<NOW()-INTERVAL 30 DAY");
$db->exec("DELETE FROM password_resets WHERE expires_at<NOW()-INTERVAL 7 DAY");
$db->exec("DELETE FROM email_verification_tokens WHERE expires_at<NOW()-INTERVAL 7 DAY");
$db->exec("DELETE FROM system_events WHERE resolved_at IS NOT NULL AND resolved_at<NOW()-INTERVAL 90 DAY");
foreach (glob(ROOT_PATH . '/storage/logs/app-*.log') ?: [] as $log) if (filemtime($log) < time()-90*86400) @unlink($log);
echo "Retention cleanup complete. Payment proofs removed: $removedProofs.\n";
