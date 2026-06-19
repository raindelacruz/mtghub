<?php

require_once APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Notification.php';
require_once APP_PATH . DIRECTORY_SEPARATOR . 'services' . DIRECTORY_SEPARATOR . 'Mailer.php';

class NotificationService
{
    public static function send(int $userId, string $type, string $title, string $body, ?string $actionPath = null): void
    {
        $notifications = new Notification();
        $id = $notifications->create([
            'user_id' => $userId,
            'type' => mb_substr($type, 0, 50),
            'title' => mb_substr($title, 0, 150),
            'body' => mb_substr($body, 0, 500),
            'action_url' => $actionPath === null ? null : mb_substr($actionPath, 0, 255),
        ]);

        $preferenceKey = self::preferenceKey($type);
        $preferences = $notifications->preferences($userId);
        if ($preferenceKey === null || empty($preferences[$preferenceKey])) {
            return;
        }

        $statement = Database::connection()->prepare('SELECT email FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $userId]);
        $user = $statement->fetch();
        $email = strtolower((string) ($user['email'] ?? ''));
        if (!$user || !filter_var($email, FILTER_VALIDATE_EMAIL) || str_ends_with($email, '.local') || str_ends_with($email, '.invalid')) {
            return;
        }

        $message = $body;
        if ($actionPath !== null) {
            $config = require CONFIG_PATH . DIRECTORY_SEPARATOR . 'app.php';
            $message .= "\n\nOpen MTGHub:\n" . rtrim($config['public_origin'], '/') . url($actionPath);
        }

        try {
            Mailer::send($user['email'], $title . ' - MTGHub PH', $message);
            $notifications->markEmailed($id);
        } catch (Throwable $exception) {
            error_log('MTGHub notification email failed: ' . $exception->getMessage());
        }
    }

    private static function preferenceKey(string $type): ?string
    {
        if ($type === 'order_message') return 'email_messages';
        if ($type === 'buylist_offer') return 'email_offers';
        if (str_starts_with($type, 'order_') || str_starts_with($type, 'payment_') || str_starts_with($type, 'fulfillment_')) return 'email_order_updates';
        return null;
    }
}
