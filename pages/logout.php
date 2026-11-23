<?php
require_once __DIR__ . '/../includes/auth.php';

// 1. Unset all session variables
$_SESSION = [];

// 2. Delete the session cookie
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}

// 3. Destroy the session
session_destroy();

// 4. Redirect to login page immediately
header('Location: ' . url('pages/login.php'));
exit;