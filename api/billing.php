<?php
// ─── MediFindr — Billing API ───────────────────────────────────────────────
// Handles: create bill, list bills, get bill detail, cancel bill
// All queries filter by pharmacy_id from session — no cross-pharmacy access
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
if (!isLoggedIn('pharmacy')) jsonError('Unauthorized', 401);

$db         = getDB();
$method     = $_SERVER['REQUEST_METHOD'];
$action     = $_GET['action'] ?? '';
$pharmacyId = (int)$_SESSION['user_id'];

// ─── GENERATE UNIQUE BILL NUMBER ─────────────────────────────────────────────
function generateBillNumber($db, $pharmacyId) {
    $prefix = 'BILL-' . date('Y') . str_pad($pharmacyId, 2, '0', STR_PAD_LEFT);
    $stmt   = $db->prepare("SELECT COUNT(*) as c FROM bills WHERE pharmacy_id = ?");
    $stmt->bind_param('i', $pharmacyId);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['c'] + 1;
    $stmt->close();
    return $prefix . str_pad($count, 4, '0', STR_PAD_LEFT);
}

// ─── CREATE BILL ─────────────────────────────────────────────────────────────
if ($action === 'create' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $patientName   = sanitize($data['patient_name']   ?? 'Walk-in');
    $patientPhone  = sanitize($data['patient_phone']  ?? '');
    $userId        = isset($data['user_id']) ? (int)$data['user_id'] : null;
    $discountPct   = max(0, min(100, (float)($data['discount_pct']  ?? 0)));
    $taxPct        = max(0, (float)($data['tax_pct'] ?? 0));
    $paymentMethod = in_array($data['payment_method'] ?? '', ['cash','card','upi','other'])
                     ? $data['payment_method'] : 'cash';
    $notes         = sanitize($data['notes'] ?? '');
    $items         = $data['items'] ?? [];

    if (empty($items))      jsonError('Bill must have at least one item.');
    if (!$patientName)      jsonError('Patient name is required.');

    // Validate each item has medicine_id, quantity > 0
    foreach ($items as $item) {
        if (empty($item['medicine_id']) || empty($item['quantity']) || (int)$item['quantity'] < 1) {
            jsonError('Each item needs a valid medicine and quantity ≥ 1.');
        }
    }

    $db->begin_transaction();
    try {
        $subtotal = 0;

        // Verify stock availability and get prices
        $verifiedItems = [];
        foreach ($items as $item) {
            $medId = (int)$item['medicine_id'];
            $qty   = (int)$item['quantity'];

            // Get current stock for THIS pharmacy
            $stmt = $db->prepare("
                SELECT s.quantity, s.price, m.name
                FROM stock s
                JOIN medicines m ON m.id = s.medicine_id
                WHERE s.medicine_id = ? AND s.pharmacy_id = ? AND s.quantity >= ? AND m.is_active = 1
                LIMIT 1
            ");
            $stmt->bind_param('iii', $medId, $pharmacyId, $qty);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$row) {
                $db->rollback();
                // Get medicine name for error message
                $ns = $db->prepare("SELECT name FROM medicines WHERE id = ? LIMIT 1");
                $ns->bind_param('i', $medId); $ns->execute();
                $nm = $ns->get_result()->fetch_assoc()['name'] ?? "Medicine #$medId";
                $ns->close();
                jsonError("Insufficient stock for: $nm. Check quantity and try again.");
            }

            $unitPrice = isset($item['unit_price']) && $item['unit_price'] > 0
                         ? (float)$item['unit_price']
                         : (float)($row['price'] ?? 0);
            $lineTotal  = $unitPrice * $qty;
            $subtotal  += $lineTotal;

            $verifiedItems[] = [
                'medicine_id'   => $medId,
                'medicine_name' => $row['name'],
                'quantity'      => $qty,
                'unit_price'    => $unitPrice,
                'line_total'    => $lineTotal,
            ];
        }

        // Calculate totals
        $discountAmt = round($subtotal * $discountPct / 100, 2);
        $afterDisc   = $subtotal - $discountAmt;
        $taxAmt      = round($afterDisc * $taxPct / 100, 2);
        $total       = $afterDisc + $taxAmt;
        $billNumber  = generateBillNumber($db, $pharmacyId);

        // Insert bill
        $stmt = $db->prepare("
            INSERT INTO bills
                (bill_number, pharmacy_id, user_id, patient_name, patient_phone,
                 subtotal, discount_pct, discount_amt, tax_pct, tax_amt,
                 total_amount, payment_method, notes, status)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,'paid')
        ");
        $stmt->bind_param(
            'siissddddddss',
            $billNumber, $pharmacyId, $userId, $patientName, $patientPhone,
            $subtotal, $discountPct, $discountAmt, $taxPct, $taxAmt,
            $total, $paymentMethod, $notes
        );
        $stmt->execute();
        $billId = $stmt->insert_id;
        $stmt->close();

        // Insert items AND deduct stock
        foreach ($verifiedItems as $vi) {
            // Insert line item
            $stmt = $db->prepare("
                INSERT INTO bill_items (bill_id, medicine_id, medicine_name, quantity, unit_price, line_total)
                VALUES (?,?,?,?,?,?)
            ");
            $stmt->bind_param('iisidd', $billId, $vi['medicine_id'], $vi['medicine_name'], $vi['quantity'], $vi['unit_price'], $vi['line_total']);
            $stmt->execute();
            $stmt->close();

            // Deduct stock
            $stmt = $db->prepare("
                UPDATE stock SET quantity = quantity - ?
                WHERE medicine_id = ? AND pharmacy_id = ? AND quantity >= ?
            ");
            $stmt->bind_param('iiii', $vi['quantity'], $vi['medicine_id'], $pharmacyId, $vi['quantity']);
            $stmt->execute();
            if ($stmt->affected_rows === 0) {
                $db->rollback();
                jsonError('Stock changed during billing. Please try again.');
            }
            $stmt->close();
        }

        $db->commit();

        jsonSuccess('Bill created successfully.', [
            'bill_id'     => $billId,
            'bill_number' => $billNumber,
            'total'       => $total,
        ]);

    } catch (Exception $e) {
        $db->rollback();
        jsonError('Billing failed: ' . $e->getMessage());
    }
}

// ─── LIST BILLS ──────────────────────────────────────────────────────────────
if ($action === 'list' && $method === 'GET') {
    $page    = max(1, (int)($_GET['page'] ?? 1));
    $perPage = 15;
    $offset  = ($page - 1) * $perPage;
    $search  = trim($_GET['search'] ?? '');
    $from    = sanitize($_GET['from'] ?? '');
    $to      = sanitize($_GET['to']   ?? '');

    $where  = "b.pharmacy_id = ?";
    $types  = 'i';
    $params = [$pharmacyId];

    if ($search) {
        $like    = "%{$search}%";
        $where  .= " AND (b.bill_number LIKE ? OR b.patient_name LIKE ? OR b.patient_phone LIKE ?)";
        $types  .= 'sss';
        $params  = array_merge($params, [$like, $like, $like]);
    }
    if ($from) { $where .= " AND DATE(b.created_at) >= ?"; $types .= 's'; $params[] = $from; }
    if ($to)   { $where .= " AND DATE(b.created_at) <= ?"; $types .= 's'; $params[] = $to;   }

    // Count
    $c = $db->prepare("SELECT COUNT(*) as c FROM bills b WHERE $where");
    $c->bind_param($types, ...$params);
    $c->execute();
    $total = $c->get_result()->fetch_assoc()['c'];
    $c->close();

    // Fetch
    $allParams = array_merge($params, [$perPage, $offset]);
    $allTypes  = $types . 'ii';
    $stmt = $db->prepare("
        SELECT b.id, b.bill_number, b.patient_name, b.patient_phone,
               b.subtotal, b.discount_pct, b.discount_amt, b.tax_pct, b.tax_amt,
               b.total_amount, b.payment_method, b.status, b.notes, b.created_at,
               (SELECT COUNT(*) FROM bill_items bi WHERE bi.bill_id = b.id) as item_count
        FROM bills b
        WHERE $where
        ORDER BY b.created_at DESC LIMIT ? OFFSET ?
    ");
    $stmt->bind_param($allTypes, ...$allParams);
    $stmt->execute();
    $bills = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Summary stats
    $sStmt = $db->prepare("
        SELECT
            COUNT(*)                                       as total_bills,
            COALESCE(SUM(total_amount),0)                 as total_revenue,
            COALESCE(SUM(CASE WHEN DATE(created_at) = CURDATE() THEN total_amount END), 0) as today_revenue,
            COALESCE(SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END), 0)            as today_bills
        FROM bills WHERE pharmacy_id = ? AND status = 'paid'
    ");
    $sStmt->bind_param('i', $pharmacyId);
    $sStmt->execute();
    $stats = $sStmt->get_result()->fetch_assoc();
    $sStmt->close();

    jsonSuccess('', [
        'bills'  => $bills,
        'total'  => $total,
        'pages'  => ceil($total / $perPage),
        'stats'  => $stats,
    ]);
}

// ─── BILL DETAIL ─────────────────────────────────────────────────────────────
if ($action === 'detail' && $method === 'GET') {
    $billId = (int)($_GET['id'] ?? 0);
    if (!$billId) jsonError('Bill ID required.');

    $stmt = $db->prepare("SELECT * FROM bills WHERE id = ? AND pharmacy_id = ? LIMIT 1");
    $stmt->bind_param('ii', $billId, $pharmacyId);
    $stmt->execute();
    $bill = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$bill) jsonError('Bill not found or not authorized.');

    $stmt = $db->prepare("SELECT * FROM bill_items WHERE bill_id = ?");
    $stmt->bind_param('i', $billId);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Pharmacy info for printing
    $stmt = $db->prepare("SELECT pharmacy_name, address, city, state, phone, email FROM pharmacies WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $pharmacyId);
    $stmt->execute();
    $pharmacy = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    jsonSuccess('', ['bill' => $bill, 'items' => $items, 'pharmacy' => $pharmacy]);
}

// ─── CANCEL BILL (restore stock) ─────────────────────────────────────────────
if ($action === 'cancel' && $method === 'POST') {
    $data   = json_decode(file_get_contents('php://input'), true);
    $billId = (int)($data['id'] ?? 0);
    if (!$billId) jsonError('Bill ID required.');

    // Verify ownership and status
    $stmt = $db->prepare("SELECT status FROM bills WHERE id = ? AND pharmacy_id = ? LIMIT 1");
    $stmt->bind_param('ii', $billId, $pharmacyId);
    $stmt->execute();
    $bill = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$bill)                   jsonError('Bill not found.');
    if ($bill['status'] !== 'paid') jsonError('Only paid bills can be cancelled.');

    // Get items to restore
    $stmt = $db->prepare("SELECT medicine_id, quantity FROM bill_items WHERE bill_id = ?");
    $stmt->bind_param('i', $billId);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $db->begin_transaction();
    try {
        // Cancel bill
        $stmt = $db->prepare("UPDATE bills SET status = 'cancelled' WHERE id = ?");
        $stmt->bind_param('i', $billId);
        $stmt->execute();
        $stmt->close();

        // Restore stock
        foreach ($items as $item) {
            $stmt = $db->prepare("
                UPDATE stock SET quantity = quantity + ?
                WHERE medicine_id = ? AND pharmacy_id = ?
            ");
            $stmt->bind_param('iii', $item['quantity'], $item['medicine_id'], $pharmacyId);
            $stmt->execute();
            $stmt->close();
        }

        $db->commit();
        jsonSuccess('Bill cancelled and stock restored.');
    } catch (Exception $e) {
        $db->rollback();
        jsonError('Cancellation failed: ' . $e->getMessage());
    }
}

// ─── MEDICINE SEARCH FOR BILLING (autocomplete) ──────────────────────────────
if ($action === 'search_medicine' && $method === 'GET') {
    $q = trim($_GET['q'] ?? '');
    if (strlen($q) < 2) jsonSuccess('', ['medicines' => []]);

    $like = "%{$q}%";
    $stmt = $db->prepare("
        SELECT m.id, m.name, m.composition, m.dosage,
               s.quantity, s.price
        FROM stock s
        JOIN medicines m ON m.id = s.medicine_id
        WHERE s.pharmacy_id = ? AND s.quantity > 0 AND m.is_active = 1
          AND (m.name LIKE ? OR m.composition LIKE ?)
        ORDER BY m.name LIMIT 20
    ");
    $stmt->bind_param('iss', $pharmacyId, $like, $like);
    $stmt->execute();
    $medicines = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    jsonSuccess('', ['medicines' => $medicines]);
}

