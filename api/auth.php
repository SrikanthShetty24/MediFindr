<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$db = getDB();

// ─── LOGIN ────────────────────────────────────────────────────────────────────
if ($action === 'login' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $role = $data['role'] ?? 'user';

    if (!$email || !$password) {
        jsonError('Email and password are required.');
    }

    if ($role === 'admin') {
        $stmt = $db->prepare("SELECT id, full_name, email, password FROM admin WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_name']  = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['role']       = 'admin';
            jsonSuccess('Login successful.', ['redirect' => '../admin/dashboard.php']);
        } else {
            jsonError('Invalid credentials.');
        }

    } elseif ($role === 'pharmacy') {
        $stmt = $db->prepare("SELECT id, pharmacy_name, email, password, status FROM pharmacies WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user || !password_verify($password, $user['password'])) {
            jsonError('Invalid credentials.');
        }
        if ($user['status'] === 'pending') {
            jsonError('Your account is pending admin approval.');
        }
        if ($user['status'] === 'rejected') {
            jsonError('Your registration was rejected. Contact support.');
        }
        if ($user['status'] === 'inactive') {
            jsonError('Your account has been deactivated. Contact support.');
        }

        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_name']  = $user['pharmacy_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['role']       = 'pharmacy';
        jsonSuccess('Login successful.', ['redirect' => '../pharmacy/dashboard.php']);

    } else {
        // User
        $stmt = $db->prepare("SELECT id, full_name, email, password, is_active FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (!$user || !password_verify($password, $user['password'])) {
            jsonError('Invalid credentials.');
        }
        if (!$user['is_active']) {
            jsonError('Your account has been deactivated.');
        }

        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_name']  = $user['full_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['role']       = 'user';
        jsonSuccess('Login successful.', ['redirect' => '../user/dashboard.php']);
    }
}

// ─── REGISTER ─────────────────────────────────────────────────────────────────
/**
 * MediFindr — Auth API Register Patch (Feature 4)
 * ══════════════════════════════════════════════════════════════
 * This is the UPDATED register block for api/auth.php.
 * Replace the existing REGISTER section in api/auth.php with
 * the code below. It adds terms_accepted validation + DB save.
 * ══════════════════════════════════════════════════════════════
 *
 * WHAT TO DO:
 * Open api/auth.php and find the section that starts with:
 *   if ($action === 'register' && $method === 'POST') {
 * Replace that entire block with the content below.
 */

// ─── REGISTER ────────────────────────────────────────────────────────────────
if ($action === 'register' && $method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $role = $data['role'] ?? 'user';

    // ── Terms acceptance check (Feature 4) ───────────────────
    $termsAccepted = !empty($data['terms_accepted']) && $data['terms_accepted'] === true;
    if (!$termsAccepted) {
        jsonError('You must accept the Terms & Conditions to register.');
    }

    if ($role === 'user') {
        $name     = sanitize($data['full_name'] ?? '');
        $email    = trim($data['email']         ?? '');
        $phone    = sanitize($data['phone']     ?? '');
        $password = $data['password']           ?? '';

        if (!$name || !$email || !$password) jsonError('All fields are required.');
        if (!validateEmail($email))           jsonError('Invalid email address.');
        if (!validatePassword($password))     jsonError('Password must be at least 8 characters with letters and numbers.');

        // Duplicate check
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) jsonError('Email already registered.');
        $stmt->close();

        $hash       = password_hash($password, PASSWORD_BCRYPT);
        $acceptedAt = date('Y-m-d H:i:s');

        // Insert with terms_accepted columns
        $stmt = $db->prepare("
            INSERT INTO users
                (full_name, email, phone, password, terms_accepted, terms_accepted_at)
            VALUES (?, ?, ?, ?, 1, ?)
        ");
        $stmt->bind_param('sssss', $name, $email, $phone, $hash, $acceptedAt);

        if ($stmt->execute()) {
            jsonSuccess('Account created! You can now log in.');
        } else {
            jsonError('Registration failed. Please try again.');
        }
        $stmt->close();

    } elseif ($role === 'pharmacy') {
        $pname    = sanitize($data['pharmacy_name']   ?? '');
        $owner    = sanitize($data['owner_name']      ?? '');
        $email    = trim($data['email']               ?? '');
        $phone    = sanitize($data['phone']           ?? '');
        $address  = sanitize($data['address']         ?? '');
        $city     = sanitize($data['city']            ?? '');
        $state    = sanitize($data['state']           ?? '');
        $pincode  = sanitize($data['pincode']         ?? '');
        $license  = sanitize($data['license_number']  ?? '');
        $password = $data['password']                 ?? '';

        if (!$pname || !$owner || !$email || !$phone || !$address || !$city || !$license || !$password) {
            jsonError('All required fields must be filled.');
        }
        if (!validateEmail($email))       jsonError('Invalid email address.');
        if (!validatePassword($password)) jsonError('Password must be at least 8 characters with letters and numbers.');

        $stmt = $db->prepare("SELECT id FROM pharmacies WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) jsonError('Email already registered.');
        $stmt->close();

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $db->prepare("
            INSERT INTO pharmacies
                (pharmacy_name, owner_name, email, phone, password,
                 address, city, state, pincode, license_number)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            'ssssssssss',
            $pname, $owner, $email, $phone, $hash,
            $address, $city, $state, $pincode, $license
        );

        if ($stmt->execute()) {
            jsonSuccess('Registration submitted! Await admin approval before logging in.');
        } else {
            jsonError('Registration failed. Please try again.');
        }
        $stmt->close();
    }
}

// ─── LOGOUT ───────────────────────────────────────────────────────────────────
if ($action === 'logout') {
    logout();
    $role = $_GET['role'] ?? 'user';
    $routes = ['admin' => '../admin/login.php', 'pharmacy' => '../pharmacy/login.php', 'user' => '../user/login.php'];
    header('Location: ' . ($routes[$role] ?? '../index.php'));
    exit;
}

// ─── FORGOT PASSWORD ──────────────────────────────────────────────────────────
if ($action === 'forgot' && $method === 'POST') {
    require_once __DIR__ . '/../includes/mailer.php';

    $data  = json_decode(file_get_contents('php://input'), true);
    $email = trim($data['email'] ?? '');
    $role  = $data['role'] ?? 'user';

    if (!validateEmail($email)) jsonError('Invalid email address.');

    $token      = bin2hex(random_bytes(32));
    $expires    = date('Y-m-d H:i:s', strtotime('+1 hour'));
    $resetLink  = BASE_URL . "/auth/reset.php?token={$token}&role={$role}";
    $found      = false;
    $recipient  = ['name' => '', 'email' => $email];

    // ── Look up the account ────────────────────────────────────
    if ($role === 'user') {
        $stmt = $db->prepare("SELECT id, full_name FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row) {
            $found = true;
            $recipient['name'] = $row['full_name'];
            $stmt = $db->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
            $stmt->bind_param('sss', $token, $expires, $email);
            $stmt->execute();
            $stmt->close();
        }

    } elseif ($role === 'pharmacy') {
        $stmt = $db->prepare("SELECT id, pharmacy_name FROM pharmacies WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row) {
            $found = true;
            $recipient['name'] = $row['pharmacy_name'];
            $stmt = $db->prepare("UPDATE pharmacies SET reset_token = ?, reset_expires = ? WHERE email = ?");
            $stmt->bind_param('sss', $token, $expires, $email);
            $stmt->execute();
            $stmt->close();
        }
    }

    // ── Send email only if account exists ──────────────────────
    if ($found) {
        $htmlBody = buildResetEmail($recipient['name'], $resetLink, $role, 60);
        $subject  = 'Reset Your MediFindr Password';
        $result   = sendMail($email, $recipient['name'], $subject, $htmlBody);

        if (!$result['success']) {
            // Log silently — don't reveal SMTP errors to the user
            error_log("MediFindr: Password reset mail failed for {$email} — " . $result['error']);
        }
    }

    // Always return the same message (prevent email enumeration)
    jsonSuccess('If this email is registered, a reset link has been sent. Please check your inbox (and spam folder).');
}

// ─── RESET PASSWORD ───────────────────────────────────────────────────────────
if ($action === 'reset' && $method === 'POST') {
    $data     = json_decode(file_get_contents('php://input'), true);
    $token    = trim($data['token'] ?? '');
    $password = $data['password'] ?? '';
    $role     = $data['role'] ?? 'user';

    if (!$token || !$password) jsonError('Token and new password required.');
    if (!validatePassword($password)) jsonError('Password must be at least 8 characters with letters and numbers.');

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $now  = date('Y-m-d H:i:s');

    if ($role === 'user') {
        $stmt = $db->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE reset_token = ? AND reset_expires > ?");
        $stmt->bind_param('sss', $hash, $token, $now);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
    } elseif ($role === 'pharmacy') {
        $stmt = $db->prepare("UPDATE pharmacies SET password = ?, reset_token = NULL, reset_expires = NULL WHERE reset_token = ? AND reset_expires > ?");
        $stmt->bind_param('sss', $hash, $token, $now);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
    } else {
        $affected = 0;
    }

    if ($affected > 0) {
        jsonSuccess('Password reset successfully! Redirecting to login…');
    } else {
        jsonError('This reset link is invalid or has expired. Please request a new one.');
    }
}