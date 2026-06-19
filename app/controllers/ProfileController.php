<?php

require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'User.php';

class ProfileController extends Controller
{
    private User $users;

    public function __construct()
    {
        $this->users = new User();
    }

    public function edit(): void
    {
        $this->requireLogin();

        $this->view('profile.edit', [
            'title' => 'My Profile',
            'user' => $this->users->findById((int) current_user()['id']),
            'errors' => [],
        ]);
    }

    public function update(): void
    {
        $this->requireLogin();
        $user = $this->users->findById((int) current_user()['id']);
        $data = [
            'username' => trim($_POST['username'] ?? ''),
            'city' => trim($_POST['city'] ?? ''),
            'province' => trim($_POST['province'] ?? ''),
            'seller_bio' => trim($_POST['seller_bio'] ?? ''),
        ];
        $errors = $this->validate($data, (int) $user['id']);

        if ($errors !== []) {
            $this->view('profile.edit', [
                'title' => 'My Profile',
                'user' => array_merge($user, $data),
                'errors' => $errors,
            ]);
            return;
        }

        $this->users->updateProfile((int) $user['id'], $data);
        $_SESSION['user']['username'] = $data['username'];
        $_SESSION['user']['city'] = $data['city'];
        $_SESSION['user']['province'] = $data['province'];

        flash('success', 'Profile updated.');
        redirect('/profile');
    }

    private function validate(array $data, int $userId): array
    {
        $errors = [];

        if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $data['username'])) {
            $errors[] = 'Username must be 3-30 characters and use letters, numbers, or underscores only.';
        }

        $existing = $this->users->findByUsername($data['username']);
        if ($existing && (int) $existing['id'] !== $userId) {
            $errors[] = 'Username is already taken.';
        }

        if (mb_strlen($data['city']) < 2 || mb_strlen($data['city']) > 100) {
            $errors[] = 'City must be between 2 and 100 characters.';
        }

        if (mb_strlen($data['province']) < 2 || mb_strlen($data['province']) > 100) {
            $errors[] = 'Province must be between 2 and 100 characters.';
        }

        if (mb_strlen($data['seller_bio']) > 500) {
            $errors[] = 'Seller bio must be 500 characters or fewer.';
        }

        return $errors;
    }

    private function requireLogin(): void
    {
        if (!is_logged_in()) {
            flash('error', 'Please log in to manage your profile.');
            redirect('/login');
        }
    }
}
