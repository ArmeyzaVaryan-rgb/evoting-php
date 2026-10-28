<?php
// config.php
// Memulai session untuk sistem login
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Base URL otomatis agar proyek tetap bekerja baik di root web maupun subfolder
// (misalnya http://localhost/evoting-php/ pada XAMPP).
if (!defined('APP_BASE_URL')) {
    $appRootPath = realpath(__DIR__);
    $documentRootPath = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
    $relativeAppPath = '';

    if ($appRootPath !== false && $documentRootPath !== false) {
        $appRootNormalized = rtrim(str_replace('\\', '/', $appRootPath), '/');
        $documentRootNormalized = rtrim(str_replace('\\', '/', $documentRootPath), '/');

        if (strcasecmp($appRootNormalized, $documentRootNormalized) === 0) {
            $relativeAppPath = '';
        } else {
            $documentPrefix = $documentRootNormalized . '/';
            if (stripos($appRootNormalized, $documentPrefix) === 0) {
                $relativeAppPath = trim(substr($appRootNormalized, strlen($documentPrefix)), '/');
            }
        }
    }

    define('APP_BASE_URL', '/' . ($relativeAppPath !== '' ? $relativeAppPath . '/' : ''));
}

// Pengaturan Database
$host = 'localhost';
$db   = 'evoting';
$user = 'root';
$pass = ''; // Biasanya kosong jika menggunakan XAMPP bawaan

try {
    // Membuat koneksi menggunakan PDO
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    
    // Mengatur agar error ditampilkan (memudahkan debugging)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Mengatur agar hasil query berupa array asosiatif
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Jika koneksi gagal, hentikan program dan tampilkan pesan
    die("Koneksi database gagal: " . $e->getMessage());
}

/**
 * Fungsi untuk mencatat aktivitas ke dalam tabel audit_logs
 */
function log_activity($pdo, $user_id, $action, $description) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $action, $description, $ip]);
}

/**
 * Ambil konfigurasi satu akun admin yang secara eksplisit diizinkan.
 * Email harus Gmail dan daftar/hash disimpan di admin_accounts.php.
 */
function allowed_admin_account(string $email): ?array {
    static $accounts = null;
    if ($accounts === null) {
        $path = __DIR__ . '/admin_accounts.php';
        $loaded = is_file($path) ? require $path : [];
        $accounts = is_array($loaded) ? $loaded : [];
    }

    $email = strtolower(trim($email));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !str_ends_with($email, '@gmail.com')) {
        return null;
    }

    $account = $accounts[$email] ?? null;
    if (!is_array($account)) {
        return null;
    }

    $hash = (string)($account['password_hash'] ?? '');
    $nim = trim((string)($account['user_nim'] ?? ''));
    if ($hash === '' || str_contains($hash, 'PASTE_HASH') || $nim === '') {
        return null;
    }

    return $account;
}

/** Validasi session admin terhadap allowlist setiap kali halaman admin dibuka. */
function admin_session_is_allowed(): bool {
    if (($_SESSION['role'] ?? '') !== 'admin' || empty($_SESSION['email'])) {
        return false;
    }
    return allowed_admin_account((string)$_SESSION['email']) !== null;
}

/**
 * Fungsi untuk memproteksi halaman agar hanya bisa diakses user yang login.
 */
function require_login($role = null) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . APP_BASE_URL . 'login.php');
        exit;
    }

    // Putuskan session admin lama/invalid yang tidak memiliki Gmail dalam allowlist.
    if ($role === 'admin' && ($_SESSION['role'] ?? '') === 'admin' && !admin_session_is_allowed()) {
        $_SESSION = [];
        // Ganti ID session dan buang ID lama agar session admin sebelum revisi
        // tidak bisa dipakai lagi, sambil mempertahankan session anonim untuk CSRF login.
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        header('Location: ' . APP_BASE_URL . 'login.php', true, 303);
        exit;
    }

    if ($role && ($_SESSION['role'] ?? '') !== $role) {
        header('Location: ' . APP_BASE_URL . 'index.php');
        exit;
    }
}
?>