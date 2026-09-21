# MediFindr — Medicine Availability & Alternative Finder

A complete PHP/MySQL web application for searching medicine availability across pharmacies, with smart alternative suggestions.

---

## 🚀 Setup Instructions

### Requirements
- PHP 7.4+ (8.0+ recommended)
- MySQL 5.7+ or 8.0+
- Apache with mod_rewrite enabled
- Web server (XAMPP / WAMP / LAMP / Laragon)

---

### Step 1: Clone / Copy Files
Place the `medifindr/` folder inside your web root:
- XAMPP: `C:/xampp/htdocs/medifindr/`
- LAMP:  `/var/www/html/medifindr/`

---

### Step 2: Create Database
1. Open **phpMyAdmin** or MySQL CLI
2. Run the contents of `database.sql`

```bash
mysql -u root -p < database.sql
```

This will:
- Create `medifindr` database
- Create all 6 tables with proper indexes & foreign keys
- Seed sample medicines, pharmacies, and stock

---

### Step 3: Configure Database Connection
Edit `includes/config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // your MySQL username
define('DB_PASS', '');           // your MySQL password
define('DB_NAME', 'medifindr');
define('BASE_URL', 'http://localhost/medifindr');
```

---

### Step 4: Access the Application

| URL | Description |
|-----|-------------|
| `http://localhost/medifindr/` | Landing page |
| `http://localhost/medifindr/auth/login.php` | User/Pharmacy login |
| `http://localhost/medifindr/auth/register.php` | Register |
| `http://localhost/medifindr/admin/login.php` | Admin login |
| `http://localhost/medifindr/pharmacy/login.php` | Pharmacy login |

---

## 🔑 Demo Credentials

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@medifindr.com | Admin@123 |
| Pharmacy | apollo.koramangala@medifindr.com | Admin@123 |
| Pharmacy | medplus.indiranagar@medifindr.com | Admin@123 |
| User | Register a new account | — |

> **Note:** The seeded admin password hash uses `password_hash('Admin@123', PASSWORD_BCRYPT)`. If it doesn't work, update it via phpMyAdmin using PHP's `password_hash()`.

---

## 📁 Folder Structure

```
medifindr/
├── index.php                  ← Landing page
├── database.sql               ← Database schema & seed data
├── .htaccess                  ← Apache configuration
│
├── includes/
│   ├── config.php             ← DB connection & constants
│   └── functions.php          ← Auth, helpers, sanitization
│
├── api/
│   ├── auth.php               ← Login, register, forgot/reset password
│   ├── admin.php              ← Admin CRUD APIs
│   ├── pharmacy.php           ← Pharmacy stock APIs
│   ├── search.php             ← Medicine search + history
│   └── user.php               ← User profile APIs
│
├── assets/
│   ├── css/main.css           ← Full design system (CSS variables, dark mode)
│   └── js/main.js             ← Global utilities (Toast, Modal, API, etc.)
│
├── admin/
│   ├── login.php              ← Admin login
│   ├── sidebar.php            ← Admin sidebar partial
│   ├── dashboard.php          ← Stats, charts, quick actions
│   ├── medicines.php          ← Add/Edit/Delete medicines
│   ├── pharmacies.php         ← Approve/Reject/Manage pharmacies
│   └── users.php              ← View all registered users
│
├── pharmacy/
│   ├── login.php              ← Pharmacy login
│   ├── sidebar.php            ← Pharmacy sidebar partial
│   ├── dashboard.php          ← Stats, low stock, expiry alerts
│   ├── stock.php              ← Add/Update/Delete stock (own only)
│   └── profile.php            ← Update address, coordinates
│
├── auth/
│   ├── login.php              ← Unified login (user/pharmacy/admin tabs)
│   ├── register.php           ← User & pharmacy registration
│   ├── forgot.php             ← Forgot password
│   └── reset.php              ← Reset password via token
│
└── user/
    ├── login.php              ← Redirects to auth/login.php
    ├── sidebar.php            ← User sidebar partial
    ├── dashboard.php          ← Welcome + recent searches + quick links
    ├── search.php             ← Main medicine search with results
    ├── history.php            ← Last 10 searches (clickable)
    └── profile.php            ← Update name, phone, password
```

---

## ✨ Features

### User Module
- Register / Login / Forgot Password / Reset Password
- **Smart medicine search:**
  - Exact name match → shows all pharmacies with stock
  - Medicine found but out of stock → shows same-composition alternatives
  - Partial search → shows closest matches
- View pharmacy locations on embedded Google Maps
- Get directions to pharmacy
- Search history (last 10, deduplicated, clickable)
- Update profile & change password

### Pharmacy Module
- Register (requires admin approval) / Login
- Dashboard with stats: total items, total units, low stock, expiring soon
- Add/Update/Delete own stock only
- Medicine autocomplete search when adding stock
- Upsert stock (adds to existing quantity if medicine already listed)
- Update pharmacy location (lat/lng for map)
- Expiry and low-stock alerts

### Admin Module
- Secure admin login
- Dashboard: totals, most-searched medicines, search trend chart, low availability alerts
- Full medicine CRUD (duplicate prevention)
- Pharmacy management: approve/reject/activate/deactivate
- View all registered users

---

## 🔒 Security
- Passwords hashed with `password_hash()` (BCrypt)
- All DB queries use MySQLi prepared statements
- Input sanitized with `htmlspecialchars()`
- Session-based authentication per role
- Direct access to `includes/` blocked via `.htaccess`
- Role isolation: pharmacies cannot access other pharmacies' data

---

## 🎨 UI Features
- Medical green theme with CSS variables
- Light / Dark mode toggle (stored in localStorage)
- Fully responsive (mobile + desktop)
- Sidebar with smooth mobile overlay
- Toast notifications
- Confirm dialogs for destructive actions
- Loading spinners on all async actions
- Autocomplete medicine search in pharmacy stock form
- Google Maps embed for pharmacy locations
