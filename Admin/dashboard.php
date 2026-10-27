<?php 
require '../config.php'; 
require_login('admin');

$total_voters = $pdo->query("SELECT COUNT(*) FROM users WHERE role='voter'")->fetchColumn();
$active_voters = $pdo->query("SELECT COUNT(*) FROM users WHERE role='voter' AND is_active=1")->fetchColumn();
$voted = $pdo->query("SELECT COUNT(*) FROM users WHERE role='voter' AND has_voted=1")->fetchColumn();
$not_voted = $active_voters - $voted;
$progress = $active_voters > 0 ? round(($voted / $active_voters) * 100) : 0;

$page_title = 'Ringkasan pemilihan'; $page_sub = 'Pantau partisipasi pemilih secara langsung.'; $active = 'dashboard.php';
require '../includes/admin_top.php';
?>
<section class="rounded-3xl bg-ink p-8 text-white lg:p-10">
    <p class="text-sm text-slate-300">Partisipasi pemilih aktif</p>
    <div class="mt-2 flex items-end gap-4">
        <span class="text-7xl font-extrabold tracking-tighter lg:text-8xl"><?= $progress ?><span class="text-4xl text-brand-100">%</span></span>
        <p class="mb-3 text-sm text-slate-300"><?= $voted ?> dari <?= $active_voters ?> pemilih aktif<br>sudah memberikan suara.</p>
    </div>
    <div class="mt-6 h-3 overflow-hidden rounded-full bg-white/10" role="progressbar" aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100">
        <div class="bar-fill h-full rounded-full bg-gradient-to-r from-brand-500 to-sky-400" style="width: <?= $progress ?>%"></div>
    </div>
</section>

<dl class="mt-6 grid grid-cols-2 divide-x divide-y divide-slate-200 overflow-hidden rounded-2xl border border-slate-200 bg-white lg:grid-cols-4 lg:divide-y-0">
    <div class="p-6"><dt class="text-sm text-slate-500">Total pemilih</dt><dd class="mt-1 text-3xl font-extrabold text-ink"><?= $total_voters ?></dd></div>
    <div class="p-6"><dt class="text-sm text-slate-500">Pemilih aktif</dt><dd class="mt-1 text-3xl font-extrabold text-emerald-600"><?= $active_voters ?></dd></div>
    <div class="p-6"><dt class="text-sm text-slate-500">Sudah memilih</dt><dd class="mt-1 text-3xl font-extrabold text-brand-600"><?= $voted ?></dd></div>
    <div class="p-6"><dt class="text-sm text-slate-500">Belum memilih</dt><dd class="mt-1 text-3xl font-extrabold text-amber-600"><?= $not_voted ?></dd></div>
</dl>
<?php require '../includes/admin_bottom.php'; ?>