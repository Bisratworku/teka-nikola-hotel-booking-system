<?php
// auth.php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session_handler.php';

$handler = new DbSessionHandler($pdo);
session_set_save_handler($handler, true);

if (session_status() === PHP_SESSION_NONE) session_start();

/** Helpers */
function current_user() {
  return $_SESSION['user'] ?? null;
}

function is_logged_in() {
  return !empty($_SESSION['user']);
}

function generate_csrf_token() {
  if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf_token'];
}

function csrf_token() {
  return $_SESSION['csrf_token'] ?? '';
}

function next_after_login(): string {
  // 1. If a specific page was requested before login, redirect there.
  if (!empty($_SESSION['redirect_after_login'])) {
    $target = $_SESSION['redirect_after_login'];
    unset($_SESSION['redirect_after_login']);

    // Prevent open redirects by only allowing relative paths
    $url_parts = parse_url($target);
    if (isset($url_parts['host']) || isset($url_parts['scheme'])) {
        // Fallback to role-based default if target is an absolute URL
        $target = next_after_login();
    }
    return $target;
  }

  // 2. Otherwise, default based on role
  $user = current_user();
  if (!$user) return 'index.php';
  switch ($user['role']) {
    case 'admin': return 'admin_dashboard.php';
    case 'staff': return 'staff_dashboard.php';
    default: return 'index.php';
  }
}

function require_login(): void {
  if (!is_logged_in()) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit;
  }
}

function require_role(array $allowed): void {
  if (!is_logged_in()) {
    header('Location: login.php');
    exit;
  }
  $user = current_user();
  if (!in_array($user['role'], $allowed, true)) {
    http_response_code(403);
    echo '<h1>Forbidden</h1><p>You do not have permission to view this page.</p>';
    exit;
  }
}

function verify_csrf_token(): void {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        // In a real application, you would log this error
        // error_log('CSRF token validation failed.');
        header("Location: error.php");
        exit;
    }
}

function require_role_admin($roles = []) {
  require_login();
  $u = current_user();
  if (!in_array($u['role'], $roles)) {
    http_response_code(403);
    require 'header.php';
    echo "<section class='container' style='padding:40px;text-align:center'>
            <h1>403 – Forbidden</h1>
            <p class='muted'>You do not have permission to access this page.</p>
            <a href='index.php' class='btn' style='margin-top:20px'>Back to Home</a>
          </section>";
    require 'footer.php';
    exit;
  }
}

// --- CSRF Protection ---

/**
 * Echo a hidden input field with the CSRF token for use in forms.
 */
function csrf_input(): void {
    echo '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}
