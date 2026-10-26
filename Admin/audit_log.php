<?php 
require '../config.php'; 
require_login('admin');

$logs = $pdo->query("SELECT a.*, u.nama as user_nama FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.id DESC LIMIT 100")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Audit Log - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden">
    <aside class="w-64 bg-white border-r flex flex-col">
        <div class="p-5 border-b text-xl font-bold text-indigo-600">🛡️ Admin Panel</div>
        <nav class="flex-1 p-3 space-y-1 text-sm">
            <a href="dashboard.php" class="block px-3 py-2.5 rounded hover:bg-gray-100">Overview</a>
            <a href="voters.php" class="block px-3 py-2.5 rounded hover:bg-gray-100">Kelola Pemilih</a>
            <a href="candidate.php" class="block px-3 py-2.5 rounded hover:bg-gray-100">Kandidat</a>
            <a href="result_vote.php" class="block px-3 py-2.5 rounded hover:bg-gray-100">Hasil Voting</a>
            <a href="audit_log.php" class="block px-3 py-2.5 rounded bg-indigo-50 text-indigo-700 font-semibold">Audit Log</a>
        </nav>
        <div class="p-4 border-t"><a href="logout.php" class="text-red-600 text-sm font-medium hover:underline">Logout</a></div>
    </aside>
    <main class="flex-1 p-8 overflow-y-auto">
        <h1 class="text-2xl font-bold mb-6 text-gray-800">Audit Log (100 Aktivitas Terakhir)</h1>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 border-b border-gray-200 text-gray-600">
                    <tr>
                        <th class="p-4 font-semibold">Waktu</th>
                        <th class="p-4 font-semibold">Pelaku</th>
                        <th class="p-4 font-semibold">Aksi</th>
                        <th class="p-4 font-semibold">Deskripsi</th>
                        <th class="p-4 font-semibold">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 font-mono text-xs">
                    <?php foreach($logs as $l): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="p-4 whitespace-nowrap text-gray-600"><?= $l['created_at'] ?></td>
                        <td class="p-4 font-sans font-medium text-gray-800"><?= $l['user_nama'] ?? 'System' ?></td>
                        <td class="p-4 font-bold text-indigo-600"><?= $l['action'] ?></td>
                        <td class="p-4 font-sans text-gray-600"><?= $l['description'] ?></td>
                        <td class="p-4 text-gray-500"><?= $l['ip_address'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>