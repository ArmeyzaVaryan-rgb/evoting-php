<?php 
require '../config.php'; 
require_login('voter');

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT has_voted FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$kahim = $pdo->query("SELECT * FROM candidates WHERE election_id = 1")->fetchAll();
$senator = $pdo->query("SELECT * FROM candidates WHERE election_id = 2")->fetchAll();

$success_msg = ''; $error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_vote'])) {
    if ($user['has_voted']) {
        $error_msg = "Anda sudah memilih!";
    } else {
        $kahim_id = $_POST['kahim_id'] ?? null;
        $senator_id = $_POST['senator_id'] ?? null;

        if (!$kahim_id || !$senator_id) {
            $error_msg = "Pilih Kahim dan Senator terlebih dahulu!";
        } else {
            try {
                $pdo->beginTransaction();
                $hash = hash('sha256', $user_id . time() . 'rahasia');

                $stmt = $pdo->prepare("INSERT INTO votes (election_id, voter_id, candidate_id, vote_hash) VALUES (1, ?, ?, ?)");
                $stmt->execute([$user_id, $kahim_id, $hash]);

                $stmt = $pdo->prepare("INSERT INTO votes (election_id, voter_id, candidate_id, vote_hash) VALUES (2, ?, ?, ?)");
                $stmt->execute([$user_id, $senator_id, hash('sha256', $hash . 'sen')]);

                $stmt = $pdo->prepare("UPDATE users SET has_voted = 1 WHERE id = ?");
                $stmt->execute([$user_id]);

                log_activity($pdo, $user_id, 'VOTE_CAST', 'Memilih Kahim & Senator');
                
                $pdo->commit();
                $success_msg = "✅ Suara berhasil dikirim! Bukti Hash: <span class='font-mono text-xs'>".substr($hash, 0, 16)."...</span>";
                $user['has_voted'] = 1;
            } catch (Exception $e) {
                $pdo->rollBack();
                $error_msg = "Gagal: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Surat Suara - E-Voting</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .peer:checked ~ .card { border-color: #4f46e5; background-color: #eef2ff; box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2); }
    </style>
</head>
<body class="bg-gray-50 pb-24">

    <nav class="bg-white shadow-sm border-b px-6 py-4 flex justify-between items-center sticky top-0 z-10">
        <div class="font-bold text-lg text-indigo-700">🗳️ Surat Suara</div>
        <div class="flex items-center gap-4 text-sm">
            <span>Halo, <b><?= $_SESSION['nama'] ?></b></span>
            <a href="logout.php" class="text-red-600 hover:underline font-medium">Keluar</a>
        </div>
    </nav>

    <div class="max-w-5xl mx-auto px-4 mt-6">
        <?php if($success_msg): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 p-4 rounded-lg mb-6"><?= $success_msg ?></div>
        <?php endif; ?>
        <?php if($error_msg): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 p-4 rounded-lg mb-6"><?= $error_msg ?></div>
        <?php endif; ?>

        <?php if($user['has_voted']): ?>
            <div class="bg-white p-10 rounded-xl shadow-sm text-center border mt-10">
                <div class="text-5xl mb-4">✅</div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Terima Kasih!</h2>
                <p class="text-gray-500">Anda sudah menggunakan hak pilih Anda. Suara Anda bersifat rahasia.</p>
            </div>
        <?php else: ?>
            <form method="POST">
                <!-- Kandidat Kahim -->
                <h2 class="text-xl font-bold mb-4 text-amber-600 mt-4 flex items-center gap-2">👑 Pilih Ketua Himpunan (Kahim)</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-10">
                    <?php foreach($kahim as $k): ?>
                    <label class="cursor-pointer relative">
                        <input type="radio" name="kahim_id" value="<?= $k['id'] ?>" class="hidden peer" required>
                        <div class="card bg-white border-2 border-gray-200 rounded-xl p-4 flex gap-4 transition-all duration-200 hover:border-indigo-300">
                            <img src="<?= $k['foto_url'] ?>" class="w-24 h-24 object-cover rounded-lg shadow-sm">
                            <div>
                                <span class="text-xs font-bold text-indigo-600 bg-indigo-50 px-2 py-1 rounded">No. <?= $k['nomor_urut'] ?></span>
                                <h3 class="font-bold text-lg leading-tight mt-1"><?= $k['nama'] ?></h3>
                                <p class="text-xs text-gray-500 mt-1 line-clamp-2"><b>Visi:</b> <?= $k['visi'] ?></p>
                            </div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>

                <!-- Kandidat Senator -->
                <h2 class="text-xl font-bold mb-4 text-indigo-600 flex items-center gap-2">🎓 Pilih Senator</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-10">
                    <?php foreach($senator as $s): ?>
                    <label class="cursor-pointer relative">
                        <input type="radio" name="senator_id" value="<?= $s['id'] ?>" class="hidden peer" required>
                        <div class="card bg-white border-2 border-gray-200 rounded-xl p-4 flex gap-4 transition-all duration-200 hover:border-indigo-300">
                            <img src="<?= $s['foto_url'] ?>" class="w-24 h-24 object-cover rounded-lg shadow-sm">
                            <div>
                                <span class="text-xs font-bold text-indigo-600 bg-indigo-50 px-2 py-1 rounded">No. <?= $s['nomor_urut'] ?></span>
                                <h3 class="font-bold text-lg leading-tight mt-1"><?= $s['nama'] ?></h3>
                                <p class="text-xs text-gray-500 mt-1 line-clamp-2"><b>Visi:</b> <?= $s['visi'] ?></p>
                            </div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>

                <!-- Tombol Submit -->
                <div class="fixed bottom-0 left-0 w-full bg-white border-t p-4 shadow-lg z-20">
                    <div class="max-w-5xl mx-auto flex justify-between items-center px-4">
                        <p class="text-sm text-gray-500">Pastikan pilihan Anda sudah benar sebelum mengirim.</p>
                        <button type="submit" name="submit_vote" onclick="return confirm('Kirim suara Anda? Suara tidak dapat diubah.')" class="bg-indigo-600 text-white px-8 py-3 rounded-lg font-bold hover:bg-indigo-700 shadow-md transition transform hover:scale-105">
                            Kirim Suara ✔
                        </button>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>