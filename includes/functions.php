<?php
require_once __DIR__ . '/config.php';
session_start();

// ─── Auth Helpers ─────────────────────────────────────────────────────────────
function isLoggedIn($role = null) {
    if (!isset($_SESSION['user_id'], $_SESSION['role'])) return false;
    if ($role && $_SESSION['role'] !== $role) return false;
    return true;
}

function requireLogin($role, $redirect = null) {
    if (!isLoggedIn($role)) {
        $default = ['admin' => '../admin/login.php', 'pharmacy' => '../pharmacy/login.php', 'user' => '../user/login.php'];
        $r = $redirect ?? ($default[$role] ?? '/index.php');
        header('Location: ' . $r);
        exit;
    }
}

function getCurrentUser() {
    return [
        'id'   => $_SESSION['user_id']   ?? null,
        'name' => $_SESSION['user_name'] ?? '',
        'role' => $_SESSION['role']      ?? '',
        'email'=> $_SESSION['user_email']?? ''
    ];
}

function logout() {
    session_destroy();
}

// ─── Input Sanitization ───────────────────────────────────────────────────────
function sanitize($value) {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function sanitizeInt($val) {
    return (int) $val;
}

// ─── Response Helpers ─────────────────────────────────────────────────────────
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function jsonSuccess($message, $data = []) {
    jsonResponse(array_merge(['success' => true, 'message' => $message], $data));
}

function jsonError($message, $code = 400) {
    jsonResponse(['success' => false, 'message' => $message], $code);
}

// ─── Flash Messages ───────────────────────────────────────────────────────────
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

// ─── Pagination ───────────────────────────────────────────────────────────────
function paginate($total, $page, $perPage = 15) {
    $pages = ceil($total / $perPage);
    $offset = ($page - 1) * $perPage;
    return ['pages' => $pages, 'offset' => $offset, 'current' => $page, 'perPage' => $perPage];
}

// ─── Time Ago ─────────────────────────────────────────────────────────────────
function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'just now';
}

// ─── CSRF Token ───────────────────────────────────────────────────────────────
function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// ─── Password Validation ──────────────────────────────────────────────────────
function validatePassword($pass) {
    // Min 8 chars, at least one letter and one number
    return strlen($pass) >= 8 && preg_match('/[A-Za-z]/', $pass) && preg_match('/[0-9]/', $pass);
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}
