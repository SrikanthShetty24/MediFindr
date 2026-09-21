<?php
// ─── MediFindr — Search API (Updated with GPS Proximity Filter) ───────────────
// Feature 3: Accepts user lat/lng, filters pharmacies within 10-15 km radius
// Feature 4: Report endpoint (unchanged)
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
$db     = getDB();
$action = $_GET['action'] ?? 'search';
$method = $_SERVER['REQUEST_METHOD'];

// ─── HAVERSINE DISTANCE (km) ──────────────────────────────────────────────────
function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float {
    $R    = 6371; // Earth radius in km
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a    = sin($dLat/2) * sin($dLat/2)
          + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
          * sin($dLng/2) * sin($dLng/2);
    return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
}

// ─── SEARCH ───────────────────────────────────────────────────────────────────
if ($action === 'search') {
    if (!isLoggedIn('user')) jsonError('Unauthorized', 401);

    $query   = trim($_GET['q']    ?? '');
    $userLat = isset($_GET['lat']) && $_GET['lat'] !== '' ? (float)$_GET['lat'] : null;
    $userLng = isset($_GET['lng']) && $_GET['lng'] !== '' ? (float)$_GET['lng'] : null;
    $radius  = min(50, max(1, (float)($_GET['radius'] ?? 15))); // default 15 km, max 50
    $userId  = (int)$_SESSION['user_id'];

    if (strlen($query) < 2) jsonError('Search term too short.');

    $hasLocation = ($userLat !== null && $userLng !== null);

    // ── 1. Find medicines matching query ──────────────────────
    $stmt = $db->prepare("
        SELECT m.id, m.name, m.composition, m.dosage, m.manufacturer, m.category, m.is_flagged
        FROM medicines m
        WHERE m.is_active = 1 AND m.is_flagged = 0
          AND (m.name LIKE ? OR m.composition LIKE ?)
        ORDER BY
            CASE WHEN LOWER(m.name) = LOWER(?) THEN 0
                 WHEN m.name LIKE ?            THEN 1
                 ELSE 2 END
        LIMIT 20
    ");
    $like  = "%{$query}%";
    $start = "{$query}%";
    $stmt->bind_param('ssss', $like, $like, $query, $start);
    $stmt->execute();
    $medicines = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (empty($medicines)) {
        saveSearch($db, $userId, $query, 0);
        jsonSuccess('No medicines found.', [
            'mode'          => 'none',
            'results'       => [],
            'query'         => $query,
            'location_used' => $hasLocation,
            'radius_km'     => $hasLocation ? $radius : null,
        ]);
    }

    // ── 2. For each medicine get pharmacies with stock ────────
    $exactHit = [];
    $altHit   = [];

    foreach ($medicines as $med) {
        $isExact    = stripos($med['name'], $query) !== false;
        $pharmacies = getPharmacyStock($db, $med['id'], $userLat, $userLng, $radius, $hasLocation);
        $totalQty   = array_sum(array_column($pharmacies, 'quantity'));

        $entry = [
            'medicine'   => $med,
            'pharmacies' => $pharmacies,
            'total_qty'  => $totalQty,
            'available'  => $totalQty > 0,
        ];

        $isExact ? $exactHit[] = $entry : $altHit[] = $entry;
    }

    $exactAvailable = array_filter($exactHit, fn($e) => $e['available']);

    // CASE 1: exact match in stock
    if (!empty($exactAvailable)) {
        $total = count($exactHit);
        saveSearch($db, $userId, $query, $total);
        jsonSuccess('', [
            'mode'          => 'exact',
            'results'       => array_values($exactHit),
            'query'         => $query,
            'location_used' => $hasLocation,
            'radius_km'     => $hasLocation ? $radius : null,
        ]);
    }

    // CASE 2: exact match found but out of stock → show alternatives
    if (!empty($exactHit)) {
        $compositions = array_unique(array_column(array_column($exactHit, 'medicine'), 'composition'));
        $excludeIds   = array_column(array_column($exactHit, 'medicine'), 'id');
        $alternatives = [];
        foreach ($compositions as $comp) {
            $alts = getAlternativesByComposition($db, $comp, $excludeIds, $userLat, $userLng, $radius, $hasLocation);
            $alternatives = array_merge($alternatives, $alts);
        }
        $total = count($exactHit) + count($alternatives);
        saveSearch($db, $userId, $query, $total);
        jsonSuccess('', [
            'mode'          => 'alternatives',
            'results'       => array_values($exactHit),
            'alternatives'  => $alternatives,
            'query'         => $query,
            'location_used' => $hasLocation,
            'radius_km'     => $hasLocation ? $radius : null,
        ]);
    }

    // CASE 3: partial match
    $allResults = array_merge($exactHit, $altHit);
    saveSearch($db, $userId, $query, count($allResults));
    jsonSuccess('', [
        'mode'          => 'partial',
        'results'       => array_values($allResults),
        'query'         => $query,
        'location_used' => $hasLocation,
        'radius_km'     => $hasLocation ? $radius : null,
    ]);
}

// ─── SEARCH HISTORY ───────────────────────────────────────────────────────────
if ($action === 'history') {
    if (!isLoggedIn('user')) jsonError('Unauthorized', 401);
    $userId = (int)$_SESSION['user_id'];
    $stmt   = $db->prepare("
        SELECT search_term,
               MAX(searched_at)  AS last_searched,
               MAX(result_count) AS result_count
        FROM search_history
        WHERE user_id = ?
        GROUP BY search_term
        ORDER BY last_searched DESC LIMIT 10
    ");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    jsonSuccess('', ['history' => $history]);
}

// ─── REPORT MEDICINE ──────────────────────────────────────────────────────────
if ($action === 'report' && $method === 'POST') {
    if (!isLoggedIn('user')) jsonError('Unauthorized', 401);

    $data       = json_decode(file_get_contents('php://input'), true);
    $medicineId = (int)($data['medicine_id'] ?? 0);
    $reason     = sanitize($data['reason']   ?? '');
    $details    = sanitize($data['details']  ?? '');
    $userId     = (int)$_SESSION['user_id'];

    $allowed = ['wrong_composition','fake_medicine','incorrect_information','duplicate_medicine','other'];
    if (!$medicineId || !in_array($reason, $allowed)) jsonError('Medicine ID and valid reason required.');

    $stmt = $db->prepare("SELECT id FROM medicines WHERE id = ? AND is_active = 1 LIMIT 1");
    $stmt->bind_param('i', $medicineId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) { $stmt->close(); jsonError('Medicine not found.'); }
    $stmt->close();

    $stmt = $db->prepare("SELECT id FROM reported_medicines WHERE medicine_id = ? AND user_id = ? AND status = 'pending' LIMIT 1");
    $stmt->bind_param('ii', $medicineId, $userId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) { $stmt->close(); jsonError('You have already reported this medicine. It is under review.'); }
    $stmt->close();

    $stmt = $db->prepare("INSERT INTO reported_medicines (medicine_id, user_id, reason, details) VALUES (?,?,?,?)");
    $stmt->bind_param('iiss', $medicineId, $userId, $reason, $details);
    $stmt->execute() ? jsonSuccess('Report submitted for admin review.') : jsonError('Failed to submit report.');
    $stmt->close();
}

// ─── MEDICINE DETAIL ─────────────────────────────────────────────────────────
if ($action === 'detail') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) jsonError('Medicine ID required.');
    $stmt = $db->prepare("SELECT * FROM medicines WHERE id = ? AND is_active = 1 LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $med = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$med) jsonError('Medicine not found.');
    $pharmacies = getPharmacyStock($db, $id, null, null, 15, false);
    jsonSuccess('', ['medicine' => $med, 'pharmacies' => $pharmacies]);
}

// ─── HELPERS ─────────────────────────────────────────────────────────────────

/**
 * Get pharmacies that stock a medicine.
 * If hasLocation, calculates distance and filters by radius.
 * Returns pharmacies sorted by: in-radius first (by distance), then out-of-radius last.
 */
function getPharmacyStock(
    $db, int $medicineId,
    ?float $userLat, ?float $userLng,
    float $radius, bool $hasLocation
): array {
    $stmt = $db->prepare("
        SELECT p.id, p.pharmacy_name, p.address, p.city, p.state, p.phone,
               p.latitude, p.longitude,
               s.quantity, s.price, s.expiry_date, s.updated_at
        FROM stock s
        JOIN pharmacies p ON p.id = s.pharmacy_id
        WHERE s.medicine_id = ? AND p.status = 'approved' AND s.quantity > 0
        ORDER BY s.quantity DESC
    ");
    $stmt->bind_param('i', $medicineId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (!$hasLocation || $userLat === null || $userLng === null) {
        // No location — return all, no distance info
        foreach ($rows as &$r) {
            $r['distance_km']  = null;
            $r['within_range'] = true;
        }
        return $rows;
    }

    // Calculate distance for each pharmacy
    $inRange  = [];
    $outRange = [];
    foreach ($rows as $r) {
        if ($r['latitude'] !== null && $r['longitude'] !== null) {
            $dist              = haversineKm($userLat, $userLng, (float)$r['latitude'], (float)$r['longitude']);
            $r['distance_km']  = round($dist, 2);
            $r['within_range'] = $dist <= $radius;
            ($dist <= $radius) ? $inRange[] = $r : $outRange[] = $r;
        } else {
            // Pharmacy has no GPS — include but mark unknown
            $r['distance_km']  = null;
            $r['within_range'] = null;  // unknown
            $outRange[]        = $r;
        }
    }

    // Sort in-range by distance ascending
    usort($inRange, fn($a, $b) => $a['distance_km'] <=> $b['distance_km']);

    return array_merge($inRange, $outRange);
}

function getAlternativesByComposition(
    $db, string $composition, array $excludeIds,
    ?float $userLat, ?float $userLng, float $radius, bool $hasLocation
): array {
    $words = array_filter(
        explode(' ', strtolower(preg_replace('/[^a-z0-9 ]/i', ' ', $composition))),
        fn($w) => strlen($w) > 3
    );
    if (empty($words)) return [];

    $like = '%' . implode('%', array_slice(array_values($words), 0, 2)) . '%';

    if (empty($excludeIds)) $excludeIds = [0];
    $placeholders = implode(',', array_fill(0, count($excludeIds), '?'));
    $sql  = "SELECT m.id, m.name, m.composition, m.dosage, m.manufacturer, m.category
             FROM medicines m
             WHERE m.is_active = 1 AND m.is_flagged = 0
               AND m.composition LIKE ?
               AND m.id NOT IN ($placeholders)
             LIMIT 10";

    $stmt  = $db->prepare($sql);
    $types = 's' . str_repeat('i', count($excludeIds));
    $stmt->bind_param($types, $like, ...$excludeIds);
    $stmt->execute();
    $meds = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $results = [];
    foreach ($meds as $med) {
        $pharmacies = getPharmacyStock($db, $med['id'], $userLat, $userLng, $radius, $hasLocation);
        $totalQty   = array_sum(array_column($pharmacies, 'quantity'));
        if ($totalQty > 0) {
            $results[] = [
                'medicine'   => $med,
                'pharmacies' => $pharmacies,
                'total_qty'  => $totalQty,
                'available'  => true,
            ];
        }
    }
    return $results;
}

function saveSearch($db, int $userId, string $term, int $count): void {
    $stmt = $db->prepare("INSERT INTO search_history (user_id, search_term, result_count) VALUES (?,?,?)");
    $stmt->bind_param('isi', $userId, $term, $count);
    $stmt->execute();
    $stmt->close();
}
