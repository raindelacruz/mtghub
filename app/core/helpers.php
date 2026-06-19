<?php

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return ASSET_URL . '/' . ltrim($path, '/');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_is_valid(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && is_string($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function refresh_current_user_security(): void
{
    if (!isset($_SESSION['user']['id'])) {
        return;
    }

    $statement = Database::connection()->prepare(
        'SELECT role, account_status, email_verified_at, deletion_requested_at FROM users WHERE id = :id LIMIT 1'
    );
    $statement->execute(['id' => (int) $_SESSION['user']['id']]);
    $security = $statement->fetch();

    if (!$security || in_array($security['account_status'], ['suspended', 'banned'], true)) {
        $reason = $security['account_status'] ?? 'unavailable';
        unset($_SESSION['user']);
        flash('error', 'Your account is ' . $reason . '. Contact MTGHub support if you believe this is a mistake.');
        return;
    }

    $_SESSION['user'] = array_merge($_SESSION['user'], $security);
}

function can_trade(): bool
{
    return is_logged_in()
        && (current_user()['account_status'] ?? 'pending') === 'active'
        && !empty(current_user()['email_verified_at'])
        && empty(current_user()['deletion_requested_at']);
}

function require_trade_access(): void
{
    if (!is_logged_in()) {
        flash('error', 'Please log in to continue.');
        redirect('/login');
    }

    if (!can_trade()) {
        flash('error', 'Verify your email before buying, selling, or making marketplace offers.');
        redirect('/verify-email');
    }
}

function unread_notification_count(): int
{
    if (!is_logged_in()) return 0;
    $statement = Database::connection()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND read_at IS NULL');
    $statement->execute(['user_id' => (int) current_user()['id']]);
    return (int) $statement->fetchColumn();
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function is_admin(): bool
{
    if (!is_logged_in()) {
        return false;
    }

    try {
        $statement = Database::connection()->prepare('SELECT role FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => (int) current_user()['id']]);
        $role = $statement->fetchColumn();
        $_SESSION['user']['role'] = is_string($role) ? $role : 'user';
        return $role === 'admin';
    } catch (Throwable) {
        return false;
    }
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }

    if (!isset($_SESSION['flash'][$key])) {
        return null;
    }

    $value = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);

    return $value;
}
