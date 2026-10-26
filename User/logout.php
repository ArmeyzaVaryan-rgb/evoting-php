<?php
require '../config.php';

// Cek apakah session user_id ada, jika ada baru dicatat ke log
if (isset($_SESSION['user_id'])) {
    log_activity($pdo, $_SESSION['user_id'], 'LOGOUT', 'User logout');
}

// Hapus semua data session dan hancurkan
session_unset();
session_destroy();

// Lempar kembali ke halaman login
header("Location: ../login.php");
exit;
?>