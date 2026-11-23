<?php
// Auth helpers: session bootstrap, login/logout, role checks.
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

function current_user(): ?array { return $_SESSION['user'] ?? null; }
function is_logged_in(): bool   { return current_user() !== null; }
function is_admin(): bool       { return (current_user()['role'] ?? '') === 'admin'; }

function login_user(array $row): void {
    session_regenerate_id(true);           // prevent session fixation
    $_SESSION['user'] = [
        'id'          => (int)$row['id'],
        'email'       => $row['email'],
        'full_name'   => $row['full_name'],
        'role'        => $row['role'],
        'profile_pic' => $row['profile_pic'] ?? null,
    ];
    log_activity('login', 'User #' . $row['id'] . ' (' . $row['email'] . ')');
}

function logout_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function require_login(): void {
    if (!is_logged_in()) { flash('error', 'Please sign in first.'); redirect('pages/login.php'); }
}
function require_admin(): void {
    require_login();
    if (!is_admin()) { 
        flash('error', 'Access denied. Administrators only.');
        redirect('pages/home.php'); 
    }
}
function require_guest(): void {
    if (is_logged_in()) redirect('pages/home.php');
}