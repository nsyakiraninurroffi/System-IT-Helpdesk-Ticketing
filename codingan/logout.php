<?php
// ============================================================
// logout.php - Destroy Session & Redirect ke Login
// IT Helpdesk & Ticketing System
// ============================================================

session_start();

// Hapus semua variabel session
$_SESSION = [];

// Hapus session cookie jika ada
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Hancurkan session
session_destroy();

// Redirect ke halaman login dengan pesan
header('Location: login.php?msg=logout_success');
exit;
