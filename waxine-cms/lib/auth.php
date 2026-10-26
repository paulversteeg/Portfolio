<?php
/**
 * Minimal session-based admin auth + CSRF helpers.
 */

require_once __DIR__ . '/db.php';

function auth_boot(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => $secure,
        ]);
        session_start();
    }
}

function current_user(): ?array
{
    auth_boot();
    if (empty($_SESSION['uid'])) return null;
    return q1('SELECT id, username, display_name FROM users WHERE id = ?', [$_SESSION['uid']]);
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        header('Location: /admin/login.php');
        exit;
    }
    return $u;
}

function attempt_login(string $username, string $password): bool
{
    $u = q1('SELECT * FROM users WHERE username = ?', [$username]);
    if ($u && password_verify($password, $u['password_hash'])) {
        auth_boot();
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)$u['id'];
        return true;
    }
    return false;
}

function logout(): void
{
    auth_boot();
    $_SESSION = [];
    session_destroy();
}

/* ---- CSRF ---- */
function csrf_token(): string
{
    auth_boot();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES) . '">';
}

function csrf_check(): void
{
    auth_boot();
    $sent = $_POST['_csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        http_response_code(400);
        exit('Bad CSRF token. Reload the form and try again.');
    }
}
