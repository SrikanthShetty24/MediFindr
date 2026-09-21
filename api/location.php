<?php
// ─── MediFindr — User Location API ───────────────────────────────────────────
// Saves / loads user's manually pinned map location
// Endpoints:
//   POST ?action=save   { lat, lng, label }  → saves to users table
//   GET  ?action=load                        → returns saved location

require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

if (!isLoggedIn('user')) jsonError('Unauthorized', 401);

$db     = getDB();
$userId = (int)$_SESSION['user_id'];
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// ─── SAVE ─────────────────────────────────────────────────────────────────────
if ($action === 'save' && $method === 'POST') {
    $data  = json_decode(file_get_contents('php://input'), true);
    $label = isset($data['label']) ? sanitize($data['label']) : '';

    // null lat/lng = clear location
    if (!isset($data['lat']) || $data['lat'] === null || $data['lat'] === '') {
        $stmt = $db->prepare("UPDATE users SET saved_lat = NULL, saved_lng = NULL, saved_location = NULL WHERE id = ?");
        $stmt->bind_param('i', $userId);
        $stmt->execute() ? jsonSuccess('Location cleared.') : jsonError('Failed to clear location.');
        $stmt->close();
    }

    $lat = (float)$data['lat'];
    $lng = (float)$data['lng'];

    if ($lat < -90  || $lat > 90)  jsonError('Invalid latitude.');
    if ($lng < -180 || $lng > 180) jsonError('Invalid longitude.');

    $stmt = $db->prepare("UPDATE users SET saved_lat = ?, saved_lng = ?, saved_location = ? WHERE id = ?");
    $stmt->bind_param('ddsi', $lat, $lng, $label, $userId);

    if ($stmt->execute()) {
        jsonSuccess('Location saved.', ['lat' => $lat, 'lng' => $lng, 'label' => $label]);
    } else {
        jsonError('Failed to save location.');
    }
    $stmt->close();
}

// ─── LOAD ─────────────────────────────────────────────────────────────────────
if ($action === 'load' && $method === 'GET') {
    $stmt = $db->prepare("
        SELECT saved_lat, saved_lng, saved_location FROM users WHERE id = ? LIMIT 1
    ");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row && $row['saved_lat'] !== null) {
        jsonSuccess('', [
            'has_location' => true,
            'lat'          => (float)$row['saved_lat'],
            'lng'          => (float)$row['saved_lng'],
            'label'        => $row['saved_location'] ?? '',
        ]);
    } else {
        jsonSuccess('', ['has_location' => false]);
    }
}

jsonError('Invalid action.');
