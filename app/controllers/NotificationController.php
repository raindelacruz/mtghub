<?php

require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Notification.php';

class NotificationController extends Controller
{
    private Notification $notifications;

    public function __construct()
    {
        $this->notifications = new Notification();
    }

    public function index(): void
    {
        $this->requireLogin();
        $this->view('notifications.index', ['title' => 'Notifications', 'notifications' => $this->notifications->forUser((int) current_user()['id'])]);
    }

    public function markRead(): void
    {
        $this->requireLogin();
        $this->notifications->markRead((int) ($_GET['id'] ?? 0), (int) current_user()['id']);
        $target = trim($_POST['target'] ?? '/notifications');
        redirect(str_starts_with($target, '/') && !str_starts_with($target, '//') ? $target : '/notifications');
    }

    public function markAllRead(): void
    {
        $this->requireLogin();
        $this->notifications->markAllRead((int) current_user()['id']);
        redirect('/notifications');
    }

    public function preferences(): void
    {
        $this->requireLogin();
        $this->view('notifications.preferences', ['title' => 'Notification Preferences', 'preferences' => $this->notifications->preferences((int) current_user()['id'])]);
    }

    public function savePreferences(): void
    {
        $this->requireLogin();
        $this->notifications->savePreferences((int) current_user()['id'], [
            'email_order_updates' => isset($_POST['email_order_updates']) ? 1 : 0,
            'email_messages' => isset($_POST['email_messages']) ? 1 : 0,
            'email_offers' => isset($_POST['email_offers']) ? 1 : 0,
        ]);
        flash('success', 'Notification preferences updated.');
        redirect('/notifications/preferences');
    }

    private function requireLogin(): void
    {
        if (!is_logged_in()) { flash('error', 'Please log in to view notifications.'); redirect('/login'); }
    }
}
