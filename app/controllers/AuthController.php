<?php

require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'User.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'LoginAttempt.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'PasswordReset.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'EmailVerification.php';
require APP_PATH . DIRECTORY_SEPARATOR . 'services' . DIRECTORY_SEPARATOR . 'Mailer.php';

class AuthController extends Controller
{
    public function showRegister(): void
    {
        if (is_logged_in()) {
            redirect('/');
        }

        $this->view('auth.register', [
            'title' => 'Register',
            'errors' => [],
            'old' => [],
        ]);
    }

    public function register(): void
    {
        if (is_logged_in()) {
            redirect('/');
        }

        $old = [
            'first_name' => trim($_POST['first_name'] ?? ''),
            'middle_initial' => trim($_POST['middle_initial'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'contact_number' => trim($_POST['contact_number'] ?? ''),
            'address_number' => trim($_POST['address_number'] ?? ''),
            'address_street' => trim($_POST['address_street'] ?? ''),
            'address_barangay' => trim($_POST['address_barangay'] ?? ''),
            'address_province' => trim($_POST['address_province'] ?? ''),
            'address_city' => trim($_POST['address_city'] ?? ''),
            'address_postal_code' => trim($_POST['address_postal_code'] ?? ''),
            'shipping_same_as_complete' => isset($_POST['shipping_same_as_complete']) ? '1' : '',
            'shipping_number' => trim($_POST['shipping_number'] ?? ''),
            'shipping_street' => trim($_POST['shipping_street'] ?? ''),
            'shipping_barangay' => trim($_POST['shipping_barangay'] ?? ''),
            'shipping_province' => trim($_POST['shipping_province'] ?? ''),
            'shipping_city' => trim($_POST['shipping_city'] ?? ''),
            'shipping_postal_code' => trim($_POST['shipping_postal_code'] ?? ''),
            'delivery_mode' => trim($_POST['delivery_mode'] ?? ''),
            'payment_mode' => trim($_POST['payment_mode'] ?? 'gcash'),
        ];

        if ($old['shipping_same_as_complete'] === '1') {
            $old['shipping_number'] = $old['address_number'];
            $old['shipping_street'] = $old['address_street'];
            $old['shipping_barangay'] = $old['address_barangay'];
            $old['shipping_province'] = $old['address_province'];
            $old['shipping_city'] = $old['address_city'];
            $old['shipping_postal_code'] = $old['address_postal_code'];
        }

        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        $errors = $this->validateRegistration($old, $password, $passwordConfirm);

        $userModel = new User();

        if ($old['email'] !== '' && $userModel->findByEmail($old['email'])) {
            $errors[] = 'Email is already registered.';
        }

        if ($errors !== []) {
            $this->view('auth.register', [
                'title' => 'Register',
                'errors' => $errors,
                'old' => $old,
            ]);
            return;
        }

        $username = $userModel->generateUsername($old['email'], $old['first_name'], $old['last_name']);

        $userModel->create([
            'username' => $username,
            'first_name' => $old['first_name'],
            'middle_initial' => $old['middle_initial'],
            'last_name' => $old['last_name'],
            'email' => $old['email'],
            'contact_number' => $old['contact_number'],
            'password' => $password,
            'address_number' => $old['address_number'],
            'address_street' => $old['address_street'],
            'address_barangay' => $old['address_barangay'],
            'address_province' => $old['address_province'],
            'address_city' => $old['address_city'],
            'address_postal_code' => $old['address_postal_code'],
            'shipping_same_as_complete' => $old['shipping_same_as_complete'] === '1',
            'shipping_number' => $old['shipping_number'],
            'shipping_street' => $old['shipping_street'],
            'shipping_barangay' => $old['shipping_barangay'],
            'shipping_province' => $old['shipping_province'],
            'shipping_city' => $old['shipping_city'],
            'shipping_postal_code' => $old['shipping_postal_code'],
            'delivery_mode' => $old['delivery_mode'],
            'payment_mode' => $old['payment_mode'],
            'city' => $old['address_city'],
            'province' => $old['address_province'],
            'role' => 'user',
        ]);

        $createdUser = $userModel->findByEmail($old['email']);
        if ($createdUser) {
            $this->sendVerification($createdUser);
        }
        flash('success', 'Account created. Log in and verify your email before trading.');
        redirect('/login');
    }

    public function showLogin(): void
    {
        if (is_logged_in()) {
            redirect('/');
        }

        $this->view('auth.login', [
            'title' => 'Login',
            'errors' => [],
            'old' => [],
        ]);
    }

    public function login(): void
    {
        if (is_logged_in()) {
            redirect('/');
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $errors = [];
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $attempts = new LoginAttempt();

        if ($attempts->isBlocked($email, $ipAddress)) {
            $this->view('auth.login', [
                'title' => 'Login',
                'errors' => ['Too many unsuccessful login attempts. Try again in 15 minutes.'],
                'old' => ['email' => $email],
            ]);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid email address.';
        }

        if ($password === '') {
            $errors[] = 'Password is required.';
        }

        $user = null;
        if ($errors === []) {
            $userModel = new User();
            $user = $userModel->findByEmail($email);

            if (!$user || !password_verify($password, $user['password_hash'])) {
                $errors[] = 'Invalid email or password.';
                $attempts->recordFailure($email, $ipAddress);
            }

            if ($user && in_array($user['account_status'] ?? 'active', ['suspended', 'banned'], true)) {
                $errors[] = 'This account is ' . $user['account_status'] . '. Contact MTGHub support.';
                $user = null;
            }
        }

        if ($errors !== []) {
            $this->view('auth.login', [
                'title' => 'Login',
                'errors' => $errors,
                'old' => ['email' => $email],
            ]);
            return;
        }

        $attempts->clear($email, $ipAddress);
        session_regenerate_id(true);
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
            'city' => $user['city'],
            'province' => $user['province'],
            'account_status' => $user['account_status'] ?? 'active',
            'email_verified_at' => $user['email_verified_at'] ?? null,
        ];

        redirect(empty($user['email_verified_at']) ? '/verify-email' : '/');
    }

    public function showVerifyEmail(): void
    {
        $this->requireLogin();
        if (!empty(current_user()['email_verified_at'])) {
            flash('success', 'Your email is already verified.');
            redirect('/');
        }
        $this->view('auth.verify_email', ['title' => 'Verify Email']);
    }

    public function resendVerification(): void
    {
        $this->requireLogin();
        $user = (new User())->findById((int) current_user()['id']);
        if ($user && empty($user['email_verified_at'])) {
            $verification = new EmailVerification();
            if ($verification->canRequest((int) $user['id'])) {
                $this->sendVerification($user);
            }
        }
        flash('success', 'If eligible, a fresh verification link has been sent.');
        redirect('/verify-email');
    }

    public function confirmEmail(): void
    {
        $userId = (new EmailVerification())->verify(trim($_GET['token'] ?? ''));
        if ($userId === null) {
            flash('error', 'That verification link is invalid or expired.');
            redirect(is_logged_in() ? '/verify-email' : '/login');
        }
        if (is_logged_in() && (int) current_user()['id'] === $userId) {
            refresh_current_user_security();
        }
        flash('success', 'Email verified. Marketplace trading is now enabled.');
        redirect(is_logged_in() ? '/' : '/login');
    }

    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }

        session_destroy();
        redirect('/login');
    }

    public function showForgotPassword(): void
    {
        if (is_logged_in()) {
            redirect('/change-password');
        }

        $this->view('auth.forgot_password', ['title' => 'Forgot Password']);
    }

    public function forgotPassword(): void
    {
        if (is_logged_in()) {
            redirect('/change-password');
        }

        $email = trim($_POST['email'] ?? '');
        $user = filter_var($email, FILTER_VALIDATE_EMAIL) ? (new User())->findByEmail($email) : null;

        $resets = new PasswordReset();
        if ($user && $resets->canRequest((int) $user['id'])) {
            $token = $resets->create((int) $user['id']);
            $resetUrl = $this->absoluteUrl('/reset-password?token=' . urlencode($token));
            try {
                Mailer::send(
                    $user['email'],
                    'Reset your MTGHub PH password',
                    "A password reset was requested for your account.\n\nReset it within one hour:\n" . $resetUrl
                );
            } catch (Throwable $exception) {
                error_log('MTGHub password reset link for ' . $user['email'] . ': ' . $resetUrl);
                error_log('MTGHub SMTP error: ' . $exception->getMessage());
            }

            if (APP_ENV !== 'production') {
                flash('success', 'Development reset link: ' . $resetUrl);
                redirect('/login');
            }
        }

        flash('success', 'If that email belongs to an account, a password reset link has been sent.');
        redirect('/login');
    }

    public function showResetPassword(): void
    {
        if (is_logged_in()) {
            redirect('/change-password');
        }

        $token = trim($_GET['token'] ?? '');
        if (!(new PasswordReset())->findValid($token)) {
            flash('error', 'That password reset link is invalid or has expired.');
            redirect('/forgot-password');
        }

        $this->view('auth.reset_password', [
            'title' => 'Reset Password',
            'token' => $token,
            'errors' => [],
        ]);
    }

    public function resetPassword(): void
    {
        if (is_logged_in()) {
            redirect('/change-password');
        }

        $token = trim($_GET['token'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmation = $_POST['password_confirm'] ?? '';
        $errors = $this->validateNewPassword($password, $confirmation);
        $resets = new PasswordReset();

        if (!$resets->findValid($token)) {
            flash('error', 'That password reset link is invalid or has expired.');
            redirect('/forgot-password');
        }

        if ($errors !== []) {
            $this->view('auth.reset_password', [
                'title' => 'Reset Password',
                'token' => $token,
                'errors' => $errors,
            ]);
            return;
        }

        if (!$resets->consume($token, password_hash($password, PASSWORD_DEFAULT))) {
            flash('error', 'That password reset link is invalid or has expired.');
            redirect('/forgot-password');
        }

        flash('success', 'Your password has been reset. You can now log in.');
        redirect('/login');
    }

    public function showChangePassword(): void
    {
        $this->requireLogin();
        $this->view('auth.change_password', ['title' => 'Change Password', 'errors' => []]);
    }

    public function changePassword(): void
    {
        $this->requireLogin();
        $currentPassword = $_POST['current_password'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmation = $_POST['password_confirm'] ?? '';
        $errors = $this->validateNewPassword($password, $confirmation);
        $users = new User();
        $user = $users->findById((int) current_user()['id']);

        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        }

        if ($currentPassword !== '' && hash_equals($currentPassword, $password)) {
            $errors[] = 'New password must be different from your current password.';
        }

        if ($errors !== []) {
            $this->view('auth.change_password', ['title' => 'Change Password', 'errors' => $errors]);
            return;
        }

        $users->updatePassword((int) $user['id'], $password);
        session_regenerate_id(true);
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        flash('success', 'Password updated successfully.');
        redirect('/profile');
    }

    private function requireLogin(): void
    {
        if (!is_logged_in()) {
            flash('error', 'Please log in to continue.');
            redirect('/login');
        }
    }

    private function validateNewPassword(string $password, string $confirmation): array
    {
        $errors = [];
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if ($password !== $confirmation) {
            $errors[] = 'Password confirmation does not match.';
        }
        return $errors;
    }

    private function absoluteUrl(string $path): string
    {
        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $scheme = $https ? 'https' : 'http';
        $host = preg_replace('/[^A-Za-z0-9.\-:\[\]]/', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
        return $scheme . '://' . $host . url($path);
    }

    private function sendVerification(array $user): void
    {
        $verification = new EmailVerification();
        $token = $verification->create((int) $user['id']);
        $verifyUrl = $this->absoluteUrl('/verify-email/confirm?token=' . urlencode($token));
        try {
            Mailer::send(
                $user['email'],
                'Verify your MTGHub PH email',
                "Welcome to MTGHub PH.\n\nVerify your email within 24 hours:\n" . $verifyUrl
            );
        } catch (Throwable $exception) {
            error_log('MTGHub email verification link for ' . $user['email'] . ': ' . $verifyUrl);
            error_log('MTGHub SMTP error: ' . $exception->getMessage());
        }
        if (APP_ENV !== 'production') {
            flash('verification_link', $verifyUrl);
        }
    }

    private function validateRegistration(array $data, string $password, string $passwordConfirm): array
    {
        $errors = [];

        $requiredFields = [
            'first_name' => 'First name',
            'last_name' => 'Last name',
            'contact_number' => 'Contact number',
            'address_number' => 'Complete address number',
            'address_street' => 'Complete address building/street/unit',
            'address_barangay' => 'Complete address barangay',
            'address_province' => 'Complete address province',
            'address_city' => 'Complete address city',
            'address_postal_code' => 'Complete address postal code',
            'shipping_number' => 'Shipping address number',
            'shipping_street' => 'Shipping address building/street/unit',
            'shipping_barangay' => 'Shipping address barangay',
            'shipping_province' => 'Shipping address province',
            'shipping_city' => 'Shipping address city',
            'shipping_postal_code' => 'Shipping address postal code',
        ];

        foreach ($requiredFields as $field => $label) {
            if (($data[$field] ?? '') === '') {
                $errors[] = $label . ' is required.';
            }
        }

        if (mb_strlen($data['first_name']) > 80) {
            $errors[] = 'First name must be 80 characters or fewer.';
        }

        if ($data['middle_initial'] !== '' && mb_strlen($data['middle_initial']) > 5) {
            $errors[] = 'Middle initial must be 5 characters or fewer.';
        }

        if (mb_strlen($data['last_name']) > 80) {
            $errors[] = 'Last name must be 80 characters or fewer.';
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid email address.';
        }

        if (!preg_match('/^[0-9+() .-]{7,30}$/', $data['contact_number'])) {
            $errors[] = 'Enter a valid contact number.';
        }

        foreach (['address_city', 'address_province', 'shipping_city', 'shipping_province'] as $field) {
            if (mb_strlen($data[$field]) > 100) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' must be 100 characters or fewer.';
            }
        }

        foreach (['address_postal_code', 'shipping_postal_code'] as $field) {
            if (!preg_match('/^[A-Za-z0-9 -]{4,20}$/', $data[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' must be 4-20 letters or numbers.';
            }
        }

        if (!in_array($data['delivery_mode'], ['lbc', 'meetup'], true)) {
            $errors[] = 'Choose LBC or Meetup for mode of delivery.';
        }

        if ($data['payment_mode'] !== 'gcash') {
            $errors[] = 'Choose GCash for mode of payment.';
        }

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }

        if ($password !== $passwordConfirm) {
            $errors[] = 'Password confirmation does not match.';
        }

        return $errors;
    }
}
