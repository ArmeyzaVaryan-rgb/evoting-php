<?php 
require '../config.php'; 
require_login('admin');

$total_voters = $pdo->query("SELECT COUNT(*) FROM users WHERE role='voter'")->fetchColumn();
$active_voters = $pdo->query("SELECT COUNT(*) FROM users WHERE role='voter' AND is_active=1")->fetchColumn();
$voted = $pdo->query("SELECT COUNT(*) FROM users WHERE role='voter' AND has_voted=1")->fetchColumn();
$not_voted = $active_voters - $voted;
$progress = $active_voters > 0 ? round(($voted / $active_voters) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - E-Voting</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden">
    
    <aside class="w-64 bg-white border-r flex flex-col">
        <div class="p-5 border-b text-xl font-bold text-indigo-600">🛡️ Admin Panel</div>
        <nav class="flex-1 p-3 space-y-1 text-sm">
            <a href="dashboard.php" class="block px-3 py-2.5 rounded bg-indigo-50 text-indigo-700 font-semibold">Overview</a>
            <a href="voters.php" class="block px-3 py-2.5 rounded hover:bg-gray-100">Kelola Pemilih</a>
            <a href="candidate.php" class="block px-3 py-2.5 rounded hover:bg-gray-100">Kandidat</a>
            <a href="result_vote.php" class="block px-3 py-2.5 rounded hover:bg-gray-100">Hasil Voting</a>
            <a href="audit_log.php" class="block px-3 py-2.5 rounded hover:bg-gray-100">Audit Log</a>
        </nav>
        <div class="p-4 border-t"><a href="logout.php" class="text-red-600 text-sm font-medium hover:underline">Logout</a></div>
    </aside>

    <main class="flex-1 p-8 overflow-y-auto">
        <h1 class="text-2xl font-bold mb-6 text-gray-800">Overview Pemilihan</h1>
        
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                <p class="text-sm text-gray-500 font-medium">Total Pemilih</p>
                <p class="text-3xl font-bold mt-1 text-gray-800"><?= $total_voters ?></p>
            </div>
            <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                <p class="text-sm text-gray-500 font-medium">Pemilih Aktif</p>
                <p class="text-3xl font-bold text-green-600 mt-1"><?= $active_voters ?></p>
            </div>
            <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                <p class="text-sm text-gray-500 font-medium">Sudah Memilih</p>
                <p class="text-3xl font-bold text-blue-600 mt-1"><?= $voted ?></p>
            </div>
            <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                <p class="text-sm text-gray-500 font-medium">Belum Memilih</p>
                <p class="text-3xl font-bold text-amber-600 mt-1"><?= $not_voted ?></p>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <h2 class="font-bold text-lg mb-4 text-gray-800">Progress Partisipasi</h2>
            <div class="w-full bg-gray-200 rounded-full h-6 mb-2 overflow-hidden">
                <div class="bg-indigo-600 h-6 rounded-full transition-all duration-1000 ease-out flex items-center justify-end pr-2 text-xs text-white font-bold" style="width: <?= $progress ?>%">
                    <?= $progress ?>%
                </div>
            </div>
            <p class="text-sm text-gray-500 text-right"><?= $voted ?> dari <?= $active_voters ?> pemilih aktif telah memberikan suara.</p>
        </div>
    </main>
</body>
</html>