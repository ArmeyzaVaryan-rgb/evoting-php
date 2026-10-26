<?php
require '../config.php';

// Catat log aktivitas sebelum session dihancurkan
if (isset($_SESSION['user_id'])) {
    log_activity($pdo, $_SESSION['user_id'], 'LOGOUT', 'Admin logout');
}

// Hapus session dan alihkan ke halaman login
session_unset();
session_destroy();
header("Location: ../login.php");
exit;