<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
if (!isLoggedIn('user')) jsonError('Unauthorized', 401);

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$userId = (int)$_SESSION['user_id'];

// ─── UPDATE PROFILE ───────────────────────────────────────────────────────────
if ($action === 'profile' && $method === 'PUT') {
    $data  = json_decode(file_get_contents('php://input'), true);
    $name  = sanitize($data['full_name'] ?? '');
    $phone = sanitize($data['phone'] ?? '');

    if (!$name) jsonError('Name is required.');

    $stmt = $db->prepare("UPDATE users SET full_name = ?, phone = ? WHERE id = ?");
    $stmt->bind_param('ssi', $name, $phone, $userId);

    if ($stmt->execute()) {
        $_SESSION['user_name'] = $name;
        jsonSuccess('Profile updated successfully.');
    } else {
        jsonError('Failed to update profile.');
    }
    $stmt->close();
}

// ─── CHANGE PASSWORD ──────────────────────────────────────────────────────────
if ($action === 'password' && $method === 'PUT') {
    $data    = json_decode(file_get_contents('php://input'), true);
    $current = $data['current_password'] ?? '';
    $new     = $data['new_password'] ?? '';

    if (!$current || !$new) jsonError('All fields are required.');
    if (!validatePassword($new)) jsonError('New password must be at least 8 characters with letters and numbers.');

    // Verify current password
    $stmt = $db->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row || !password_verify($current, $row['password'])) {
        jsonError('Current password is incorrect.');
    }

    $hash = password_hash($new, PASSWORD_BCRYPT);
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param('si', $hash, $userId);

    if ($stmt->execute()) {
        jsonSuccess('Password updated successfully.');
    } else {
        jsonError('Failed to update password.');
    }
    $stmt->close();
}
