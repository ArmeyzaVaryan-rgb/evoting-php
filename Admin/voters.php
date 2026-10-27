<?php 
require '../config.php'; 
require_login('admin');

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int) $_GET['id'];   // dipaksa angka agar aman dari SQL injection
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
$page_title = 'Kelola pemilih'; $page_sub = count($voters) . ' pemilih terdaftar.'; $active = 'voters.php';
require '../includes/admin_top.php';
?>
<div class="mb-4 flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 focus-within:border-brand-500 focus-within:ring-4 focus-within:ring-brand-100">
    <i data-lucide="search" class="h-4 w-4 text-slate-400"></i>
    <input id="cari" type="search" placeholder="Cari NIM atau nama" class="w-full bg-transparent text-sm focus:outline-none">
</div>

<div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
    <table class="w-full min-w-[680px] text-left text-sm">
        <thead class="border-b border-slate-200 bg-slate-50 text-slate-500">
            <tr>
                <th class="p-4 font-semibold">NIM</th>
                <th class="p-4 font-semibold">Nama</th>
                <th class="p-4 text-center font-semibold">Akun</th>
                <th class="p-4 text-center font-semibold">Suara</th>
                <th class="p-4 text-right font-semibold">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100" id="baris">
            <?php foreach($voters as $v): ?>
            <tr class="transition hover:bg-slate-50" data-s="<?= e(strtolower($v['nim'] . ' ' . $v['nama'])) ?>">
                <td class="p-4 font-mono font-medium text-slate-600"><?= e($v['nim']) ?></td>
                <td class="p-4 font-semibold text-ink"><?= e($v['nama']) ?></td>
                <td class="p-4 text-center">
                    <?php if($v['is_active']): ?>
                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">Aktif</span>
                    <?php else: ?>
                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-500">Nonaktif</span>
                    <?php endif; ?>
                </td>
                <td class="p-4 text-center">
                    <?php if($v['has_voted']): ?>
                        <span class="rounded-full bg-brand-50 px-2.5 py-1 text-xs font-bold text-brand-700">Sudah memilih</span>
                    <?php else: ?>
                        <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-700">Belum memilih</span>
                    <?php endif; ?>
                </td>
                <td class="space-x-2 whitespace-nowrap p-4 text-right">
                    <a href="?action=toggle&id=<?= (int)$v['id'] ?>" class="inline-block rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-100">
                        <?= $v['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>
                    </a>
                    <?php if($v['has_voted']): ?>
                    <a href="?action=reset&id=<?= (int)$v['id'] ?>" data-confirm="Reset hak pilih <?= e($v['nama']) ?>? Suara sebelumnya akan dihapus." class="inline-block rounded-lg bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-600 transition hover:bg-red-100">
                        Reset suara
                    </a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <tr id="kosong" hidden><td colspan="5" class="p-10 text-center text-slate-500">Tidak ada pemilih yang cocok dengan pencarian.</td></tr>
        </tbody>
    </table>
</div>
<script>
document.getElementById('cari').addEventListener('input', function () {
    var q = this.value.toLowerCase().trim(), n = 0;
    document.querySelectorAll('#baris tr[data-s]').forEach(function (tr) {
        var ok = tr.dataset.s.indexOf(q) > -1; tr.hidden = !ok; if (ok) n++;
    });
    document.getElementById('kosong').hidden = n > 0;
});
</script>
<?php require '../includes/admin_bottom.php'; ?>