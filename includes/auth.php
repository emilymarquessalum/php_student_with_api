<?php
// includes/auth.php
// Centralized session authentication and expiry logic for all protected pages

function require_auth($role = null) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $token_lifetime = 1800; // 30 minutes
    if (
        !isset($_SESSION['user_type']) ||
        !isset($_SESSION['session_token']) ||
        !isset($_SESSION['token_expiry']) ||
        $_SESSION['token_expiry'] < time() ||
        ($role && $_SESSION['user_type'] !== $role)
    ) {
        // Save intended URL for redirect after login
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
        $full_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        //session_unset();
        //session_destroy();
        $_SESSION['redirect_after_login'] = $full_url;
        header('Location: ' . (isset($role) && $role === 'student' ? '../login.php' : 'login.php'));
        exit();
    }
    // Refresh expiry
    $_SESSION['token_expiry'] = time() + $token_lifetime;
}
