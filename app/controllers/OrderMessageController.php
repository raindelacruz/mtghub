<?php

require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Order.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'OrderMessage.php';
require_once APP_PATH . DIRECTORY_SEPARATOR . 'services' . DIRECTORY_SEPARATOR . 'NotificationService.php';

class OrderMessageController extends Controller
{
    public function store(): void
    {
        require_trade_access();
        $order = (new Order())->find((int) ($_GET['order_id'] ?? 0));
        $userId = (int) current_user()['id'];
        if ($order === null || ((int) $order['buyer_id'] !== $userId && (int) $order['seller_id'] !== $userId)) {
            http_response_code(404); echo '404 - Order not found'; return;
        }
        $body = trim($_POST['body'] ?? '');
        if (mb_strlen($body) < 1 || mb_strlen($body) > 2000) {
            flash('error', 'Message must be between 1 and 2,000 characters.');
            redirect('/orders/show?id=' . (int) $order['id']);
        }
        $isBuyer = (int) $order['buyer_id'] === $userId;
        (new OrderMessage())->create((int) $order['id'], $userId, $body, $isBuyer);
        $recipientId = $isBuyer ? (int) $order['seller_id'] : (int) $order['buyer_id'];
        try {
            NotificationService::send($recipientId, 'order_message', 'New message for order #' . (int) $order['id'], current_user()['username'] . ' sent you an order message.', '/orders/show?id=' . (int) $order['id']);
        } catch (Throwable $exception) {
            error_log('MTGHub message notification failed: ' . $exception->getMessage());
        }
        flash('success', 'Message sent.');
        redirect('/orders/show?id=' . (int) $order['id']);
    }
}
