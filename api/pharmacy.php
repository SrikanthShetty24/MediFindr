<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
if (!isLoggedIn('pharmacy')) jsonError('Unauthorized', 401);

$db         = getDB();
$method     = $_SERVER['REQUEST_METHOD'];
$action     = $_GET['action'] ?? '';
$pharmacyId = (int)$_SESSION['user_id'];

// ─── DASHBOARD STATS ──────────────────────────────────────────────────────────
if ($action === 'stats') {
    $stmt = $db->prepare("SELECT COUNT(*) as c FROM stock WHERE pharmacy_id = ?");
    $stmt->bind_param('i', $pharmacyId); $stmt->execute();
    $total_items = $stmt->get_result()->fetch_assoc()['c']; $stmt->close();

    $stmt = $db->prepare("SELECT COALESCE(SUM(quantity),0) as total FROM stock WHERE pharmacy_id = ?");
    $stmt->bind_param('i', $pharmacyId); $stmt->execute();
    $total_qty = $stmt->get_result()->fetch_assoc()['total']; $stmt->close();

    $stmt = $db->prepare("SELECT COUNT(*) as c FROM stock WHERE pharmacy_id = ? AND quantity < 10");
    $stmt->bind_param('i', $pharmacyId); $stmt->execute();
    $low_stock = $stmt->get_result()->fetch_assoc()['c']; $stmt->close();

    $stmt = $db->prepare("SELECT COUNT(*) as c FROM stock WHERE pharmacy_id = ? AND expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(NOW(), INTERVAL 30 DAY) AND expiry_date >= CURDATE()");
    $stmt->bind_param('i', $pharmacyId); $stmt->execute();
    $expiring_soon = $stmt->get_result()->fetch_assoc()['c']; $stmt->close();

    $stmt = $db->prepare("SELECT COUNT(*) as c FROM medicines WHERE added_by_pharmacy_id = ? AND is_active = 1");
    $stmt->bind_param('i', $pharmacyId); $stmt->execute();
    $my_medicines = $stmt->get_result()->fetch_assoc()['c']; $stmt->close();

    jsonSuccess('', ['stats' => compact('total_items','total_qty','low_stock','expiring_soon','my_medicines')]);
}

// ─── ADD MEDICINE + STOCK (PHARMACY-OWNED WORKFLOW) ──────────────────────────
if ($action === 'add_medicine' && $method === 'POST') {
    $data         = json_decode(file_get_contents('php://input'), true);
    $name         = sanitize($data['name']         ?? '');
    $composition  = sanitize($data['composition']  ?? '');
    $dosage       = sanitize($data['dosage']       ?? '');
    $manufacturer = sanitize($data['manufacturer'] ?? '');
    $category     = sanitize($data['category']     ?? 'General');
    $description  = sanitize($data['description']  ?? '');
    $quantity     = (int)($data['quantity']         ?? 0);
    $price        = isset($data['price']) && $data['price'] !== '' ? (float)$data['price'] : null;
    $expiry       = sanitize($data['expiry_date']   ?? '');

    if (!$name || !$composition || !$dosage || !$manufacturer)
        jsonError('Name, composition, dosage and manufacturer are required.');
    if ($quantity <= 0)
        jsonError('Quantity must be at least 1.');

    // Normalize for duplicate detection
    $normalName = strtolower(preg_replace('/[^a-z0-9]/i', '', $name));

    // Check exact match (same normalized name + same composition)
    $stmt = $db->prepare("
        SELECT id, name FROM medicines
        WHERE LOWER(REPLACE(REPLACE(REPLACE(name,' ',''),'-',''),'_','')) = ?
          AND LOWER(composition) = LOWER(?) AND is_active = 1 LIMIT 1
    ");
    $stmt->bind_param('ss', $normalName, $composition);
    $stmt->execute();
    $existingMed = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $db->begin_transaction();
    try {
        if ($existingMed) {
            $medicineId = $existingMed['id'];
            $reused     = true;
        } else {
            $stmt = $db->prepare("
                INSERT INTO medicines (name, composition, dosage, manufacturer, category, description, added_by_pharmacy_id, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, 1)
            ");
            $stmt->bind_param('ssssssi', $name, $composition, $dosage, $manufacturer, $category, $description, $pharmacyId);
            $stmt->execute();
            $medicineId = $stmt->insert_id;
            $stmt->close();
            $reused = false;
        }

        $expiryVal = $expiry ?: null;
        $stmt = $db->prepare("
            INSERT INTO stock (pharmacy_id, medicine_id, quantity, price, expiry_date)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                quantity = quantity + VALUES(quantity),
                price = VALUES(price), expiry_date = VALUES(expiry_date), updated_at = NOW()
        ");
        $stmt->bind_param('iiids', $pharmacyId, $medicineId, $quantity, $price, $expiryVal);
        $stmt->execute();
        $stmt->close();

        $db->commit();
        $msg = $reused
            ? 'Stock added to existing medicine "' . htmlspecialchars($existingMed['name']) . '".'
            : 'Medicine added and stock linked successfully.';
        jsonSuccess($msg, ['medicine_id' => $medicineId, 'reused' => $reused]);

    } catch (Exception $e) {
        $db->rollback();
        jsonError('Failed: ' . $e->getMessage());
    }
}

// ─── STOCK LIST ───────────────────────────────────────────────────────────────
if ($action === 'stock' && $method === 'GET') {
    $search  = trim($_GET['search'] ?? '');
    $page    = max(1, (int)($_GET['page'] ?? 1));
    $perPage = 15;
    $offset  = ($page - 1) * $perPage;

    if ($search) {
        $like = "%{$search}%";
        $c = $db->prepare("SELECT COUNT(*) as c FROM stock s JOIN medicines m ON m.id=s.medicine_id WHERE s.pharmacy_id=? AND (m.name LIKE ? OR m.composition LIKE ?)");
        $c->bind_param('iss', $pharmacyId, $like, $like); $c->execute();
        $total = $c->get_result()->fetch_assoc()['c']; $c->close();

        $stmt = $db->prepare("
            SELECT s.id, s.quantity, s.price, s.expiry_date, s.updated_at,
                   m.id as medicine_id, m.name, m.composition, m.dosage, m.manufacturer, m.category,
                   IF(m.added_by_pharmacy_id = ?, 1, 0) as is_my_medicine
            FROM stock s JOIN medicines m ON m.id = s.medicine_id
            WHERE s.pharmacy_id = ? AND (m.name LIKE ? OR m.composition LIKE ?)
            ORDER BY m.name LIMIT ? OFFSET ?
        ");
        $stmt->bind_param('iissii', $pharmacyId, $pharmacyId, $like, $like, $perPage, $offset);
    } else {
        $c = $db->prepare("SELECT COUNT(*) as c FROM stock WHERE pharmacy_id = ?");
        $c->bind_param('i', $pharmacyId); $c->execute();
        $total = $c->get_result()->fetch_assoc()['c']; $c->close();

        $stmt = $db->prepare("
            SELECT s.id, s.quantity, s.price, s.expiry_date, s.updated_at,
                   m.id as medicine_id, m.name, m.composition, m.dosage, m.manufacturer, m.category,
                   IF(m.added_by_pharmacy_id = ?, 1, 0) as is_my_medicine
            FROM stock s JOIN medicines m ON m.id = s.medicine_id
            WHERE s.pharmacy_id = ?
            ORDER BY m.name LIMIT ? OFFSET ?
        ");
        $stmt->bind_param('iiii', $pharmacyId, $pharmacyId, $perPage, $offset);
    }

    $stmt->execute();
    $stock = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    jsonSuccess('', ['stock' => $stock, 'total' => $total, 'pages' => ceil($total / $perPage)]);
}

// ─── UPDATE STOCK ─────────────────────────────────────────────────────────────
if ($action === 'stock' && $method === 'PUT') {
    $data     = json_decode(file_get_contents('php://input'), true);
    $stockId  = (int)($data['id']       ?? 0);
    $quantity = (int)($data['quantity'] ?? 0);
    $price    = isset($data['price']) && $data['price'] !== '' ? (float)$data['price'] : null;
    $expiry   = sanitize($data['expiry_date'] ?? '');

    if (!$stockId || $quantity < 0) jsonError('Valid stock ID and quantity required.');

    $expiryVal = $expiry ?: null;
    $stmt = $db->prepare("UPDATE stock SET quantity=?, price=?, expiry_date=?, updated_at=NOW() WHERE id=? AND pharmacy_id=?");
    $stmt->bind_param('idsii', $quantity, $price, $expiryVal, $stockId, $pharmacyId);
    ($stmt->execute() && $stmt->affected_rows > 0)
        ? jsonSuccess('Stock updated.')
        : jsonError('Update failed or not authorized.');
    $stmt->close();
}

// ─── DELETE STOCK ─────────────────────────────────────────────────────────────
if ($action === 'stock' && $method === 'DELETE') {
    $stockId = (int)($_GET['id'] ?? 0);
    if (!$stockId) jsonError('Stock ID required.');

    $stmt = $db->prepare("DELETE FROM stock WHERE id=? AND pharmacy_id=?");
    $stmt->bind_param('ii', $stockId, $pharmacyId);
    ($stmt->execute() && $stmt->affected_rows > 0)
        ? jsonSuccess('Stock removed.')
        : jsonError('Failed or not authorized.');
    $stmt->close();
}

// ─── AUTOCOMPLETE SEARCH (for adding stock to existing medicine) ──────────────
if ($action === 'medicines_list') {
    $q = trim($_GET['q'] ?? '');
    if (strlen($q) < 2) jsonSuccess('', ['medicines' => []]);
    $like = "%{$q}%";
    $stmt = $db->prepare("SELECT id, name, composition, dosage, manufacturer FROM medicines WHERE is_active=1 AND (name LIKE ? OR composition LIKE ?) ORDER BY name LIMIT 20");
    $stmt->bind_param('ss', $like, $like); $stmt->execute();
    $medicines = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    jsonSuccess('', ['medicines' => $medicines]);
}

// ─── PHARMACY PROFILE ─────────────────────────────────────────────────────────
if ($action === 'profile') {
    if ($method === 'GET') {
        $stmt = $db->prepare("SELECT id, pharmacy_name, owner_name, email, phone, address, city, state, pincode, latitude, longitude, license_number, status, created_at FROM pharmacies WHERE id=? LIMIT 1");
        $stmt->bind_param('i', $pharmacyId); $stmt->execute();
        $profile = $stmt->get_result()->fetch_assoc(); $stmt->close();
        jsonSuccess('', ['profile' => $profile]);
    }
    if ($method === 'PUT') {
        $data    = json_decode(file_get_contents('php://input'), true);
        $phone   = sanitize($data['phone']   ?? '');
        $address = sanitize($data['address'] ?? '');
        $city    = sanitize($data['city']    ?? '');
        $state   = sanitize($data['state']   ?? '');
        $pincode = sanitize($data['pincode'] ?? '');
        $lat     = isset($data['latitude'])  ? (float)$data['latitude']  : null;
        $lng     = isset($data['longitude']) ? (float)$data['longitude'] : null;

        $stmt = $db->prepare("UPDATE pharmacies SET phone=?,address=?,city=?,state=?,pincode=?,latitude=?,longitude=? WHERE id=?");
        $stmt->bind_param('sssssddi', $phone, $address, $city, $state, $pincode, $lat, $lng, $pharmacyId);
        $stmt->execute() ? jsonSuccess('Profile updated.') : jsonError('Failed to update.');
        $stmt->close();
    }
}
