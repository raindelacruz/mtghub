<?php

require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Order.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Wallet.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'PaymentProof.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'services' . DIRECTORY_SEPARATOR . 'UploadSecurity.php';

class PaymentProofController extends Controller
{
    private Order $orders;
    private PaymentProof $proofs;

    public function __construct()
    {
        $this->orders = new Order();
        $this->proofs = new PaymentProof();
    }

    public function upload(): void
    {
        require_trade_access();
        $order = $this->findOrder();
        $buyerId = (int) current_user()['id'];
        if ((int) $order['buyer_id'] !== $buyerId || $order['status'] !== 'pending_payment' || (float) $order['cash_amount_due'] <= 0) {
            flash('error', 'A payment proof cannot be uploaded for this order.');
            redirect('/orders/show?id=' . (int) $order['id']);
        }
        if ($this->proofs->hasPending((int) $order['id'])) {
            flash('error', 'This order already has a proof awaiting review.');
            redirect('/orders/show?id=' . (int) $order['id']);
        }

        $method = trim($_POST['external_payment_method'] ?? '');
        $reference = trim($_POST['payment_reference'] ?? '');
        if (mb_strlen($method) < 2 || mb_strlen($method) > 80 || mb_strlen($reference) < 2 || mb_strlen($reference) > 150) {
            flash('error', 'Enter a valid payment method and reference.');
            redirect('/orders/show?id=' . (int) $order['id']);
        }

        try {
            $file = UploadSecurity::validatePaymentProof($_FILES['payment_proof'] ?? []);
            $directory = ROOT_PATH . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'payment_proofs';
            if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
                throw new RuntimeException('Payment-proof storage is unavailable.');
            }
            $storedName = bin2hex(random_bytes(24)) . '.' . $file['extension'];
            $destination = $directory . DIRECTORY_SEPARATOR . $storedName;
            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                throw new RuntimeException('The payment proof could not be stored.');
            }

            try {
                $proofId = $this->proofs->create([
                    'order_id' => (int) $order['id'], 'uploaded_by' => $buyerId,
                    'original_name' => $file['name'], 'stored_name' => $storedName,
                    'mime_type' => $file['mime_type'], 'file_size' => $file['size'],
                    'image_width' => $file['width'], 'image_height' => $file['height'], 'sha256' => hash_file('sha256', $destination),
                ]);
                $this->orders->submitPaymentWithProof($order, $method, $reference, $buyerId, $proofId);
            } catch (Throwable $exception) {
                if (isset($proofId)) {
                    $this->proofs->delete($proofId);
                }
                @unlink($destination);
                throw $exception;
            }
            flash('success', 'Payment proof uploaded for seller review.');
        } catch (RuntimeException $exception) {
            flash('error', $exception->getMessage());
        }
        redirect('/orders/show?id=' . (int) $order['id']);
    }

    public function review(): void
    {
        require_trade_access();
        $proof = $this->proofs->find((int) ($_GET['id'] ?? 0));
        if ($proof === null || (int) $proof['seller_id'] !== (int) current_user()['id']) {
            http_response_code(404); echo '404 - Payment proof not found'; return;
        }
        $decision = trim($_POST['decision'] ?? '');
        $notes = trim($_POST['review_notes'] ?? '');
        if (!in_array($decision, ['approve','reject'], true) || mb_strlen($notes) > 1000 || ($decision === 'reject' && mb_strlen($notes) < 5)) {
            flash('error', 'Choose a valid decision and provide rejection notes when rejecting.');
            redirect('/orders/show?id=' . (int) $proof['order_id']);
        }
        try {
            $this->orders->reviewPaymentProof((int) $proof['order_id'], (int) $proof['id'], $decision, $notes, (int) current_user()['id']);
            flash('success', $decision === 'approve' ? 'Payment proof approved.' : 'Payment proof rejected; the buyer may resubmit.');
        } catch (RuntimeException $exception) {
            flash('error', $exception->getMessage());
        }
        redirect('/orders/show?id=' . (int) $proof['order_id']);
    }

    public function showFile(): void
    {
        if (!is_logged_in()) { http_response_code(404); return; }
        $proof = $this->proofs->find((int) ($_GET['id'] ?? 0));
        $userId = (int) current_user()['id'];
        if ($proof === null || (!is_admin() && (int) $proof['buyer_id'] !== $userId && (int) $proof['seller_id'] !== $userId)) {
            http_response_code(404); return;
        }
        $path = ROOT_PATH . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'payment_proofs' . DIRECTORY_SEPARATOR . basename($proof['stored_name']);
        if (!is_file($path)) { http_response_code(404); return; }
        header('Content-Type: ' . $proof['mime_type']);
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: inline; filename="payment-proof-' . (int) $proof['id'] . '.' . pathinfo($proof['stored_name'], PATHINFO_EXTENSION) . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        readfile($path);
        exit;
    }

    private function findOrder(): array
    {
        $order = $this->orders->find((int) ($_GET['order_id'] ?? 0));
        if ($order === null) { http_response_code(404); echo '404 - Order not found'; exit; }
        return $order;
    }
}
