<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
if (!isLoggedIn('admin')) jsonError('Unauthorized', 401);

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ─── DASHBOARD STATS ──────────────────────────────────────────────────────────
if ($action === 'stats') {
    $stats = [];

    $r = $db->query("SELECT COUNT(*) as c FROM medicines WHERE is_active=1");
    $stats['total_medicines'] = $r->fetch_assoc()['c'];

    $r = $db->query("SELECT COUNT(*) as c FROM pharmacies WHERE status='approved'");
    $stats['total_pharmacies'] = $r->fetch_assoc()['c'];

    $r = $db->query("SELECT COUNT(*) as c FROM pharmacies WHERE status='pending'");
    $stats['pending_pharmacies'] = $r->fetch_assoc()['c'];

    $r = $db->query("SELECT COUNT(*) as c FROM users WHERE is_active=1");
    $stats['total_users'] = $r->fetch_assoc()['c'];

    $r = $db->query("SELECT COUNT(*) as c FROM reported_medicines WHERE status='pending'");
    $stats['pending_reports'] = $r->fetch_assoc()['c'];

    // Most searched
    $r = $db->query("SELECT search_term, COUNT(*) as count FROM search_history GROUP BY search_term ORDER BY count DESC LIMIT 5");
    $stats['most_searched'] = $r->fetch_all(MYSQLI_ASSOC);

    // Low stock
    $r = $db->query("SELECT m.name, SUM(s.quantity) as total_qty FROM stock s JOIN medicines m ON m.id=s.medicine_id GROUP BY m.id,m.name HAVING total_qty < 20 ORDER BY total_qty ASC LIMIT 5");
    $stats['low_stock'] = $r->fetch_all(MYSQLI_ASSOC);

    // Search trend
    $r = $db->query("SELECT DATE_FORMAT(searched_at,'%Y-%m') as month, COUNT(*) as count FROM search_history WHERE searched_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY month ORDER BY month ASC");
    $stats['search_trend'] = $r->fetch_all(MYSQLI_ASSOC);

    // Duplicate count
    $r = $db->query("
        SELECT COUNT(*) as c FROM (
            SELECT LOWER(REGEXP_REPLACE(REGEXP_REPLACE(name,'[^a-zA-Z0-9]',''),' ','')) as norm_name
            FROM medicines WHERE is_active=1
            GROUP BY norm_name HAVING COUNT(*) > 1
        ) sub
    ");
    $stats['duplicate_groups'] = $r->fetch_assoc()['c'];

    jsonSuccess('', ['stats' => $stats]);
}

// ─── MEDICINE INSPECTION PANEL (pharmacy-wise) ────────────────────────────────
if ($action === 'inspection') {
    $pharmacyFilter = (int)($_GET['pharmacy_id'] ?? 0);
    $page    = max(1, (int)($_GET['page'] ?? 1));
    $search  = trim($_GET['search'] ?? '');
    $perPage = 15;
    $offset  = ($page - 1) * $perPage;

    $where  = "m.is_active = 1";
    $params = [];
    $types  = '';

    if ($pharmacyFilter) {
        $where   .= " AND m.added_by_pharmacy_id = ?";
        $params[] = $pharmacyFilter;
        $types   .= 'i';
    }
    if ($search) {
        $like     = "%{$search}%";
        $where   .= " AND (m.name LIKE ? OR m.composition LIKE ? OR m.manufacturer LIKE ?)";
        $params[] = $like; $params[] = $like; $params[] = $like;
        $types   .= 'sss';
    }

    // Count
    $countSql = "SELECT COUNT(*) as c FROM medicines m WHERE $where";
    if ($types) {
        $c = $db->prepare($countSql);
        $c->bind_param($types, ...$params);
        $c->execute();
        $total = $c->get_result()->fetch_assoc()['c'];
        $c->close();
    } else {
        $total = $db->query($countSql)->fetch_assoc()['c'];
    }

    // Fetch
    $sql = "
        SELECT m.id, m.name, m.composition, m.dosage, m.manufacturer, m.category,
               m.is_flagged, m.flag_reason, m.created_at,
               p.pharmacy_name, p.id as pharmacy_id,
               (SELECT SUM(s.quantity) FROM stock s WHERE s.medicine_id = m.id) as total_stock,
               (SELECT COUNT(*) FROM reported_medicines r WHERE r.medicine_id = m.id AND r.status='pending') as report_count
        FROM medicines m
        LEFT JOIN pharmacies p ON p.id = m.added_by_pharmacy_id
        WHERE $where
        ORDER BY m.created_at DESC LIMIT ? OFFSET ?
    ";

    $allParams = array_merge($params, [$perPage, $offset]);
    $allTypes  = $types . 'ii';
    $stmt      = $db->prepare($sql);
    $stmt->bind_param($allTypes, ...$allParams);
    $stmt->execute();
    $medicines = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Pharmacy list for filter dropdown
    $pharmacies = $db->query("SELECT id, pharmacy_name FROM pharmacies WHERE status='approved' ORDER BY pharmacy_name")->fetch_all(MYSQLI_ASSOC);

    jsonSuccess('', [
        'medicines'  => $medicines,
        'total'      => $total,
        'pages'      => ceil($total / $perPage),
        'pharmacies' => $pharmacies
    ]);
}

// ─── FLAG / REMOVE MEDICINE ───────────────────────────────────────────────────
if ($action === 'flag_medicine' && $method === 'POST') {
    $data   = json_decode(file_get_contents('php://input'), true);
    $id     = (int)($data['id']     ?? 0);
    $reason = sanitize($data['reason'] ?? '');

    if (!$id) jsonError('Medicine ID required.');

    $stmt = $db->prepare("UPDATE medicines SET is_flagged=1, flag_reason=? WHERE id=?");
    $stmt->bind_param('si', $reason, $id);
    $stmt->execute() ? jsonSuccess('Medicine flagged.') : jsonError('Failed to flag.');
    $stmt->close();
}

if ($action === 'remove_medicine' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id   = (int)($data['id'] ?? 0);
    if (!$id) jsonError('Medicine ID required.');

    // Soft delete — preserves referential integrity
    $stmt = $db->prepare("UPDATE medicines SET is_active=0 WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute() ? jsonSuccess('Medicine removed.') : jsonError('Failed to remove.');
    $stmt->close();
}

// ─── DUPLICATE DETECTION ──────────────────────────────────────────────────────
if ($action === 'duplicates') {
    // Normalize: lowercase, strip non-alphanumeric → group names that look the same
    $r = $db->query("
        SELECT
            LOWER(REGEXP_REPLACE(name, '[^a-zA-Z0-9]', '')) as norm_name,
            GROUP_CONCAT(id ORDER BY id) as ids,
            GROUP_CONCAT(name ORDER BY id SEPARATOR '|||') as names,
            COUNT(*) as cnt
        FROM medicines
        WHERE is_active = 1
        GROUP BY norm_name
        HAVING cnt > 1
        ORDER BY cnt DESC
    ");

    $groups = [];
    while ($row = $r->fetch_assoc()) {
        $ids   = array_map('intval', explode(',', $row['ids']));
        $names = explode('|||', $row['names']);

        // Fetch full details for each medicine in group
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $db->prepare("
            SELECT m.id, m.name, m.composition, m.dosage, m.manufacturer,
                   p.pharmacy_name,
                   (SELECT SUM(s.quantity) FROM stock s WHERE s.medicine_id = m.id) as total_stock
            FROM medicines m
            LEFT JOIN pharmacies p ON p.id = m.added_by_pharmacy_id
            WHERE m.id IN ($placeholders)
        ");
        $types = str_repeat('i', count($ids));
        $stmt->bind_param($types, ...$ids);
        $stmt->execute();
        $members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $groups[] = ['norm' => $row['norm_name'], 'count' => (int)$row['cnt'], 'members' => $members];
    }

    jsonSuccess('', ['groups' => $groups]);
}

// ─── MERGE MEDICINES ──────────────────────────────────────────────────────────
if ($action === 'merge' && $method === 'POST') {
    $data      = json_decode(file_get_contents('php://input'), true);
    $masterId  = (int)($data['master_id']    ?? 0);
    $mergeIds  = array_map('intval', $data['merge_ids'] ?? []);
    $adminId   = (int)$_SESSION['user_id'];

    if (!$masterId || empty($mergeIds)) jsonError('Master ID and at least one duplicate ID required.');
    if (in_array($masterId, $mergeIds))  jsonError('Master cannot be in the duplicate list.');

    // Validate master exists
    $stmt = $db->prepare("SELECT id FROM medicines WHERE id=? AND is_active=1 LIMIT 1");
    $stmt->bind_param('i', $masterId); $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) { $stmt->close(); jsonError('Master medicine not found.'); }
    $stmt->close();

    $db->begin_transaction();
    try {
        foreach ($mergeIds as $dupId) {
            if ($dupId === $masterId) continue;

            // Get all stock entries of the duplicate
            $stmt = $db->prepare("SELECT pharmacy_id, quantity, price, expiry_date FROM stock WHERE medicine_id = ?");
            $stmt->bind_param('i', $dupId); $stmt->execute();
            $dupStocks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            foreach ($dupStocks as $ds) {
                $pid = $ds['pharmacy_id'];
                $qty = $ds['quantity'];
                $pr  = $ds['price'];
                $exp = $ds['expiry_date'];

                // Check if master already has stock for this pharmacy
                $chk = $db->prepare("SELECT id, quantity FROM stock WHERE medicine_id=? AND pharmacy_id=? LIMIT 1");
                $chk->bind_param('ii', $masterId, $pid); $chk->execute();
                $existing = $chk->get_result()->fetch_assoc(); $chk->close();

                if ($existing) {
                    // Add quantities together
                    $newQty = $existing['quantity'] + $qty;
                    $upd = $db->prepare("UPDATE stock SET quantity=?, updated_at=NOW() WHERE id=?");
                    $upd->bind_param('ii', $newQty, $existing['id']); $upd->execute(); $upd->close();
                } else {
                    // Insert new stock row for master
                    $ins = $db->prepare("INSERT INTO stock (pharmacy_id, medicine_id, quantity, price, expiry_date) VALUES (?,?,?,?,?)");
                    $ins->bind_param('iiids', $pid, $masterId, $qty, $pr, $exp); $ins->execute(); $ins->close();
                }
            }

            // Delete old stock for duplicate
            $del = $db->prepare("DELETE FROM stock WHERE medicine_id=?");
            $del->bind_param('i', $dupId); $del->execute(); $del->close();

            // Soft-delete duplicate medicine
            $upd = $db->prepare("UPDATE medicines SET is_active=0 WHERE id=?");
            $upd->bind_param('i', $dupId); $upd->execute(); $upd->close();

            // Log the merge
            $log = $db->prepare("INSERT INTO medicine_merge_log (master_id, duplicate_id, merged_by) VALUES (?,?,?)");
            $log->bind_param('iii', $masterId, $dupId, $adminId); $log->execute(); $log->close();

            // Redirect any reports on duplicate to master
            $rpt = $db->prepare("UPDATE reported_medicines SET medicine_id=? WHERE medicine_id=?");
            $rpt->bind_param('ii', $masterId, $dupId); $rpt->execute(); $rpt->close();
        }

        $db->commit();
        jsonSuccess('Medicines merged successfully. Stock quantities transferred to master.');

    } catch (Exception $e) {
        $db->rollback();
        jsonError('Merge failed: ' . $e->getMessage());
    }
}

// ─── REPORTS MANAGEMENT ───────────────────────────────────────────────────────
if ($action === 'reports') {
    $status = $_GET['status'] ?? 'pending';
    $page   = max(1, (int)($_GET['page'] ?? 1));
    $perPage = 15;
    $offset  = ($page - 1) * $perPage;

    $c = $db->prepare("SELECT COUNT(*) as c FROM reported_medicines WHERE status=?");
    $c->bind_param('s', $status); $c->execute();
    $total = $c->get_result()->fetch_assoc()['c']; $c->close();

    $stmt = $db->prepare("
        SELECT r.id, r.reason, r.details, r.status, r.admin_note, r.created_at,
               r.updated_at,
               m.id as medicine_id, m.name as medicine_name,
               m.composition, m.manufacturer, m.is_active,
               u.full_name as reported_by, u.email as reporter_email
        FROM reported_medicines r
        JOIN medicines m ON m.id = r.medicine_id
        JOIN users u     ON u.id = r.user_id
        WHERE r.status = ?
        ORDER BY r.created_at DESC LIMIT ? OFFSET ?
    ");
    $stmt->bind_param('sii', $status, $perPage, $offset);
    $stmt->execute();
    $reports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    jsonSuccess('', ['reports' => $reports, 'total' => $total, 'pages' => ceil($total / $perPage)]);
}

if ($action === 'resolve_report' && $method === 'POST') {
    $data      = json_decode(file_get_contents('php://input'), true);
    $reportId  = (int)($data['id']         ?? 0);
    $newStatus = sanitize($data['status']  ?? 'resolved');
    $note      = sanitize($data['note']    ?? '');

    if (!$reportId || !in_array($newStatus, ['resolved','dismissed'])) jsonError('Invalid parameters.');

    $stmt = $db->prepare("UPDATE reported_medicines SET status=?, admin_note=?, updated_at=NOW() WHERE id=?");
    $stmt->bind_param('ssi', $newStatus, $note, $reportId);
    $stmt->execute() ? jsonSuccess('Report updated.') : jsonError('Failed.');
    $stmt->close();
}

// ─── PHARMACY MANAGEMENT ──────────────────────────────────────────────────────
if ($action === 'pharmacies') {
    $status  = $_GET['status'] ?? '';
    $page    = max(1, (int)($_GET['page'] ?? 1));
    $perPage = 10;
    $offset  = ($page - 1) * $perPage;

    if ($status) {
        $c = $db->prepare("SELECT COUNT(*) as c FROM pharmacies WHERE status=?");
        $c->bind_param('s', $status); $c->execute();
        $total = $c->get_result()->fetch_assoc()['c']; $c->close();
        $stmt = $db->prepare("SELECT id, pharmacy_name, owner_name, email, phone, city, state, license_number, status, created_at FROM pharmacies WHERE status=? ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->bind_param('sii', $status, $perPage, $offset);
    } else {
        $total = $db->query("SELECT COUNT(*) as c FROM pharmacies")->fetch_assoc()['c'];
        $stmt  = $db->prepare("SELECT id, pharmacy_name, owner_name, email, phone, city, state, license_number, status, created_at FROM pharmacies ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->bind_param('ii', $perPage, $offset);
    }
    $stmt->execute();
    $pharmacies = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    jsonSuccess('', ['pharmacies' => $pharmacies, 'total' => $total, 'pages' => ceil($total / $perPage)]);
}

if ($action === 'pharmacy_action' && $method === 'POST') {
    $data    = json_decode(file_get_contents('php://input'), true);
    $id      = (int)($data['id']     ?? 0);
    $act     = $data['action'] ?? '';
    $allowed = ['approved','rejected','inactive','pending'];
    if (!$id || !in_array($act, $allowed)) jsonError('Invalid action.');
    $stmt = $db->prepare("UPDATE pharmacies SET status=? WHERE id=?");
    $stmt->bind_param('si', $act, $id);
    $stmt->execute() ? jsonSuccess('Status updated.') : jsonError('Failed.');
    $stmt->close();
}

if ($action === 'pharmacy_detail' && isset($_GET['id'])) {
    $id   = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT * FROM pharmacies WHERE id=? LIMIT 1");
    $stmt->bind_param('i', $id); $stmt->execute();
    $pharmacy = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if (!$pharmacy) jsonError('Not found.');
    unset($pharmacy['password'], $pharmacy['reset_token'], $pharmacy['reset_expires']);
    jsonSuccess('', ['pharmacy' => $pharmacy]);
}

// ─── USERS LIST ───────────────────────────────────────────────────────────────
if ($action === 'users') {
    $stmt = $db->prepare("SELECT id, full_name, email, phone, is_active, created_at FROM users ORDER BY created_at DESC");
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    jsonSuccess('', ['users' => $users]);
}
