<?php
require_once __DIR__ . '/config.php';

// Catat logout sebelum data session dihapus. Kegagalan audit tidak boleh
// membuat pengguna tetap masuk atau menggagalkan proses logout.
$userId = $_SESSION['user_id'] ?? null;
$role = (string)($_SESSION['role'] ?? 'user');
if ($userId !== null) {
    try {
        log_activity($pdo, $userId, 'LOGOUT', ucfirst($role) . ' logout');
    } catch (Throwable $e) {
        error_log('[e-voting] gagal mencatat aktivitas logout: ' . $e->getMessage());
    }
}

// Kosongkan data session dan invalidasi cookie session di browser.
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    $cookieOptions = [
        'expires'  => time() - 42000,
        'path'     => $params['path'] !== '' ? $params['path'] : '/',
        'domain'   => $params['domain'],
        'secure'   => (bool)$params['secure'],
        'httponly' => (bool)$params['httponly'],
    ];
    if (isset($params['samesite'])) {
        $cookieOptions['samesite'] = $params['samesite'];
    }
    setcookie(session_name(), '', $cookieOptions);
}
session_destroy();

// Gunakan base URL aplikasi, bukan path yang diasumsikan seperti /evoting/.
header('Location: ' . APP_BASE_URL . 'login.php', true, 303);
exit;
