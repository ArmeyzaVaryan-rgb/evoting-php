<?php 
require '../config.php'; 
require_login('admin');
$kahim = $pdo->query("SELECT * FROM candidates WHERE election_id = 1")->fetchAll();
$senator = $pdo->query("SELECT * FROM candidates WHERE election_id = 2")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kandidat - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden">
    <aside class="w-64 bg-white border-r flex flex-col">
        <div class="p-5 border-b text-xl font-bold text-indigo-600">🛡️ Admin Panel</div>
        <nav class="flex-1 p-3 space-y-1 text-sm">
            <a href="dashboard.php" class="block px-3 py-2.5 rounded hover:bg-gray-100">Overview</a>
            <a href="voters.php" class="block px-3 py-2.5 rounded hover:bg-gray-100">Kelola Pemilih</a>
            <a href="candidate.php" class="block px-3 py-2.5 rounded bg-indigo-50 text-indigo-700 font-semibold">Kandidat</a>
            <a href="result_vote.php" class="block px-3 py-2.5 rounded hover:bg-gray-100">Hasil Voting</a>
            <a href="audit_log.php" class="block px-3 py-2.5 rounded hover:bg-gray-100">Audit Log</a>
        </nav>
        <div class="p-4 border-t"><a href="logout.php" class="text-red-600 text-sm font-medium hover:underline">Logout</a></div>
    </aside>
    <main class="flex-1 p-8 overflow-y-auto">
        <h1 class="text-2xl font-bold mb-6 text-gray-800">Data Kandidat</h1>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <div>
                <h2 class="font-bold text-lg text-amber-600 mb-4 flex items-center gap-2">👑 Kandidat Kahim</h2>
                <div class="space-y-4">
                    <?php foreach($kahim as $k): ?>
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex gap-5">
                        <img src="<?= $k['foto_url'] ?>" class="w-24 h-24 rounded-lg object-cover">
                        <div>
                            <span class="text-xs font-bold text-indigo-600 bg-indigo-50 px-2 py-1 rounded">No. <?= $k['nomor_urut'] ?></span>
                            <p class="font-bold text-lg mt-2 text-gray-800"><?= $k['nama'] ?></p>
                            <p class="text-sm text-gray-600 mt-1"><b>Visi:</b> <?= $k['visi'] ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div>
                <h2 class="font-bold text-lg text-indigo-600 mb-4 flex items-center gap-2">🎓 Kandidat Senator</h2>
                <div class="space-y-4">
                    <?php foreach($senator as $s): ?>
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex gap-5">
                        <img src="<?= $s['foto_url'] ?>" class="w-24 h-24 rounded-lg object-cover">
                        <div>
                            <span class="text-xs font-bold text-indigo-600 bg-indigo-50 px-2 py-1 rounded">No. <?= $s['nomor_urut'] ?></span>
                            <p class="font-bold text-lg mt-2 text-gray-800"><?= $s['nama'] ?></p>
                            <p class="text-sm text-gray-600 mt-1"><b>Visi:</b> <?= $s['visi'] ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>
</body>
</html>