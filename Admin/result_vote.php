<?php 
require '../config.php'; 
require_login('admin');

$kahim_res = $pdo->query("SELECT c.nama, c.nomor_urut, COUNT(v.id) as total FROM candidates c LEFT JOIN votes v ON v.candidate_id = c.id WHERE c.election_id = 1 GROUP BY c.id ORDER BY total DESC")->fetchAll();
$sen_res = $pdo->query("SELECT c.nama, c.nomor_urut, COUNT(v.id) as total FROM candidates c LEFT JOIN votes v ON v.candidate_id = c.id WHERE c.election_id = 2 GROUP BY c.id ORDER BY total DESC")->fetchAll();

$total_kahim = array_sum(array_column($kahim_res, 'total')) ?: 1;
$total_sen = array_sum(array_column($sen_res, 'total')) ?: 1;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Hasil Voting - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden">
    <aside class="w-64 bg-white border-r flex flex-col">
        <div class="p-5 border-b text-xl font-bold text-indigo-600">🛡️ Admin Panel</div>
        <nav class="flex-1 p-3 space-y-1 text-sm">
            <a href="dashboard.php" class="block px-3 py-2.5 rounded hover:bg-gray-100">Overview</a>
            <a href="voters.php" class="block px-3 py-2.5 rounded hover:bg-gray-100">Kelola Pemilih</a>
            <a href="candidate.php" class="block px-3 py-2.5 rounded hover:bg-gray-100">Kandidat</a>
            <a href="result_vote.php" class="block px-3 py-2.5 rounded bg-indigo-50 text-indigo-700 font-semibold">Hasil Voting</a>
            <a href="audit_log.php" class="block px-3 py-2.5 rounded hover:bg-gray-100">Audit Log</a>
        </nav>
        <div class="p-4 border-t"><a href="logout.php" class="text-red-600 text-sm font-medium hover:underline">Logout</a></div>
    </aside>
    <main class="flex-1 p-8 overflow-y-auto">
        <h1 class="text-2xl font-bold mb-6 text-gray-800">Hasil Quick Count</h1>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h2 class="text-lg font-bold text-amber-600 mb-6 flex items-center gap-2">👑 Perolehan Kahim</h2>
                <div class="space-y-6">
                    <?php foreach($kahim_res as $k): $pct = round(($k['total'] / $total_kahim) * 100); ?>
                    <div>
                        <div class="flex justify-between text-sm mb-2 font-medium">
                            <span class="text-gray-700">No. <?= $k['nomor_urut'] ?> - <?= $k['nama'] ?></span>
                            <span class="text-gray-900 font-bold"><?= $k['total'] ?> Suara (<?= $pct ?>%)</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden">
                            <div class="bg-gradient-to-r from-amber-400 to-amber-500 h-4 rounded-full transition-all duration-1000" style="width: <?= $pct ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h2 class="text-lg font-bold text-indigo-600 mb-6 flex items-center gap-2">🎓 Perolehan Senator</h2>
                <div class="space-y-6">
                    <?php foreach($sen_res as $s): $pct = round(($s['total'] / $total_sen) * 100); ?>
                    <div>
                        <div class="flex justify-between text-sm mb-2 font-medium">
                            <span class="text-gray-700">No. <?= $s['nomor_urut'] ?> - <?= $s['nama'] ?></span>
                            <span class="text-gray-900 font-bold"><?= $s['total'] ?> Suara (<?= $pct ?>%)</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden">
                            <div class="bg-gradient-to-r from-indigo-400 to-indigo-600 h-4 rounded-full transition-all duration-1000" style="width: <?= $pct ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>
</body>
</html>