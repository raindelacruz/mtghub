<?php

require APP_PATH . '/models/User.php';
require APP_PATH . '/models/Wallet.php';
require APP_PATH . '/models/AccountDeletion.php';

class AccountController extends Controller
{
    public function deletion(): void
    {
        $this->requireLogin();
        $this->view('account.deletion', ['title' => 'Delete Account', 'request' => (new AccountDeletion())->pendingForUser((int) current_user()['id'])]);
    }
    public function requestDeletion(): void
    {
        $this->requireLogin();
        try { (new AccountDeletion())->request((int) current_user()['id'], (string) ($_POST['current_password'] ?? ''), $_SERVER['REMOTE_ADDR'] ?? ''); flash('success', 'Deletion scheduled. You have 30 days to cancel. Marketplace trading is now disabled.'); }
        catch (RuntimeException $error) { flash('error', $error->getMessage()); }
        redirect('/account/deletion');
    }
    public function cancelDeletion(): void
    {
        $this->requireLogin(); (new AccountDeletion())->cancel((int) current_user()['id']); unset($_SESSION['user']['deletion_requested_at']); flash('success', 'Account deletion cancelled.'); redirect('/profile');
    }
    private function requireLogin(): void { if (!is_logged_in()) redirect('/login'); }
}
