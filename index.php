<?php require 'config.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>E-Voting Kampus</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 flex items-center justify-center h-screen">
    <div class="text-center bg-white p-10 rounded-2xl shadow-sm border max-w-md w-full">
        <h1 class="text-4xl font-bold text-indigo-600 mb-2">🗳️ E-Voting</h1>
        <p class="text-gray-500 mb-8">Sistem Pemilihan Kahim & Senator</p>
        
        <?php if(isset($_SESSION['user_id'])): ?>
            <!-- Jika sudah login -->
            <div class="bg-green-50 border border-green-200 text-green-800 p-4 rounded-lg mb-6 text-sm">
                Anda sudah login sebagai <b><?= $_SESSION['nama'] ?></b> (<?= $_SESSION['role'] ?>)
            </div>
            <?php if($_SESSION['role'] == 'admin'): ?>
                <a href="Admin/dashboard.php" class="block w-full bg-indigo-600 text-white px-8 py-3 rounded-lg font-bold hover:bg-indigo-700 mb-3">Ke Panel Admin</a>
            <?php else: ?>
                <a href="User/dashboard.php" class="block w-full bg-indigo-600 text-white px-8 py-3 rounded-lg font-bold hover:bg-indigo-700 mb-3">Ke Surat Suara</a>
            <?php endif; ?>
            <a href="logout.php" class="block w-full bg-red-100 text-red-600 px-8 py-3 rounded-lg font-bold hover:bg-red-200">Logout</a>

        <?php else: ?>
            <!-- Jika belum login, HANYA ADA SATU TOMBOL -->
            <a href="login.php" class="block w-full bg-indigo-600 text-white px-8 py-3 rounded-lg font-bold hover:bg-indigo-700">Masuk Sistem (Admin / Pemilih)</a>
        <?php endif; ?>
    </div>
</body>
</html>