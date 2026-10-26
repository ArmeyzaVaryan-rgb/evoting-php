<?php 
require '../config.php'; 
require_login('admin');

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $action = $_GET['action'];
    if ($action == 'toggle') {
        $pdo->query("UPDATE users SET is_active = NOT is_active WHERE id = $id");
        log_activity($pdo, $_SESSION['user_id'], 'TOGGLE_ACTIVE', "Mengubah status aktif user ID: $id");
    } elseif ($action == 'reset') {
        $pdo->query("UPDATE users SET has_voted = 0 WHERE id = $id");
        $pdo->query("DELETE FROM votes WHERE voter_id = $id");
        log_activity($pdo, $_SESSION['user_id'], 'RESET_VOTE', "Reset hak pilih user ID: $id");
    }
    header("Location: voters.php");
    exit;
}

$voters = $pdo->query("SELECT * FROM users WHERE role='voter' ORDER BY nim ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Pemilih - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex h-screen overflow-hidden">
    <aside class="w-64 bg-white border-r flex flex-col">
        <div class="p-5 border-b text-xl font-bold text-indigo-600">🛡️ Admin Panel</div>
        <nav class="flex-1 p-3 space-y-1 text-sm">
            <a href="dashboard.php" class="block px-3 py-2.5 rounded hover:bg-gray-100">Overview</a>
            <a href="voters.php" class="block px-3 py-2.5 rounded bg-indigo-50 text-indigo-700 font-semibold">Kelola Pemilih</a>
            <a href="candidate.php" class="block px-3 py-2.5 rounded hover:bg-gray-100">Kandidat</a>
            <a href="result_vote.php" class="block px-3 py-2.5 rounded hover:bg-gray-100">Hasil Voting</a>
            <a href="audit_log.php" class="block px-3 py-2.5 rounded hover:bg-gray-100">Audit Log</a>
        </nav>
        <div class="p-4 border-t"><a href="logout.php" class="text-red-600 text-sm font-medium hover:underline">Logout</a></div>
    </aside>

    <main class="flex-1 p-8 overflow-y-auto">
        <h1 class="text-2xl font-bold mb-6 text-gray-800">Kelola Pemilih</h1>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 border-b border-gray-200 text-gray-600">
                    <tr>
                        <th class="p-4 font-semibold">NIM</th>
                        <th class="p-4 font-semibold">Nama</th>
                        <th class="p-4 font-semibold text-center">Status Aktif</th>
                        <th class="p-4 font-semibold text-center">Status Memilih</th>
                        <th class="p-4 font-semibold text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach($voters as $v): ?>
                    <tr class="hover:bg-gray-50 transition">
                        <td class="p-4 font-mono font-medium text-gray-700"><?= $v['nim'] ?></td>
                        <td class="p-4 font-medium text-gray-800"><?= $v['nama'] ?></td>
                        <td class="p-4 text-center">
                            <?php if($v['is_active']): ?>
                                <span class="text-green-700 bg-green-100 px-2.5 py-1 rounded-full text-xs font-bold">Aktif</span>
                            <?php else: ?>
                                <span class="text-gray-600 bg-gray-100 px-2.5 py-1 rounded-full text-xs font-bold">Belum</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-4 text-center">
                            <?php if($v['has_voted']): ?>
                                <span class="text-blue-700 bg-blue-100 px-2.5 py-1 rounded-full text-xs font-bold">✔ Sudah</span>
                            <?php else: ?>
                                <span class="text-amber-700 bg-amber-100 px-2.5 py-1 rounded-full text-xs font-bold">✖ Belum</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-4 text-right space-x-2">
                            <a href="?action=toggle&id=<?= $v['id'] ?>" class="text-xs bg-gray-100 border border-gray-200 px-3 py-1.5 rounded hover:bg-gray-200 font-medium text-gray-700 transition">
                                <?= $v['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>
                            </a>
                            <?php if($v['has_voted']): ?>
                            <a href="?action=reset&id=<?= $v['id'] ?>" onclick="return confirm('Yakin reset hak pilih <?= $v['nama'] ?>? Suara sebelumnya akan dihapus.')" class="text-xs bg-red-100 text-red-700 border border-red-200 px-3 py-1.5 rounded hover:bg-red-200 font-medium transition">
                                Reset Suara
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>