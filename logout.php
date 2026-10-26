<?php
// Panggil config agar session_start() berjalan
require 'config.php';

// Hapus semua data session (menghapus status login)
session_unset();
session_destroy();

// Alihkan (redirect) user kembali ke halaman login
header("Location: login.php");
exit;
?>