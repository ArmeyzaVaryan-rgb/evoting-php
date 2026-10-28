<?php
// Helper bersama. Semua fungsi diberi guard agar tidak bentrok dengan config.php.
if (!function_exists('e')) {
    function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('csrf_token')) {
    function csrf_token(): string {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }
        return $_SESSION['csrf_token'];
    }
}
if (!function_exists('csrf_field')) {
    function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
}
if (!function_exists('csrf_valid')) {
    function csrf_valid(?string $token = null): bool {
        $token = $token ?? ($_POST['csrf'] ?? '');
        return is_string($token) && $token !== '' && hash_equals(csrf_token(), $token);
    }
}
if (!function_exists('json_out')) {
    function json_out(array $data, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
if (!function_exists('ev_substr')) {   // aman walau ekstensi mbstring tidak aktif
    function ev_substr(string $s, int $len): string {
        if (function_exists('mb_substr')) { return mb_substr($s, 0, $len, 'UTF-8'); }
        return preg_match('/^.{0,' . $len . '}/us', $s, $m) ? $m[0] : substr($s, 0, $len);
    }
}
if (!function_exists('initial_of')) {
    function initial_of($name): string {
        $name = trim((string)$name);
        if ($name === '') { return '?'; }
        $c = ev_substr($name, 1);
        return function_exists('mb_strtoupper') ? mb_strtoupper($c, 'UTF-8') : strtoupper($c);
    }
}

if (!function_exists('db_has_column')) {
    /** Pemeriksaan kolom yang di-cache agar fitur dapat mendeteksi migrasi yang belum dijalankan. */
    function db_has_column(PDO $pdo, string $table, string $column): bool {
        static $columnCache = [];
        $allowedTables = ['users', 'candidates', 'votes', 'audit_logs'];
        if (!in_array($table, $allowedTables, true)) { return false; }
        if (!array_key_exists($table, $columnCache)) {
            $columnCache[$table] = [];
            try {
                $stmt = $pdo->query('SHOW COLUMNS FROM `' . $table . '`');
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    if (isset($row['Field'])) { $columnCache[$table][$row['Field']] = true; }
                }
            } catch (Throwable $e) {
                error_log('[e-voting] gagal memeriksa struktur tabel ' . $table . ': ' . $e->getMessage());
                return false;
            }
        }
        return isset($columnCache[$table][$column]);
    }
}
