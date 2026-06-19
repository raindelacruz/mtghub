<?php

require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'Wallet.php';

class WalletController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();

        $userId = (int) current_user()['id'];

        $this->view('wallet.index', [
            'title' => 'Store Credit Wallet',
            'wallet' => Wallet::getOrCreateByUserId($userId),
            'transactions' => Wallet::transactionsByUser($userId),
        ]);
    }

    private function requireLogin(): void
    {
        if (!is_logged_in()) {
            flash('error', 'Please log in to view your store credit wallet.');
            redirect('/login');
        }
    }
}
