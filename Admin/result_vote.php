<?php 
require '../config.php'; 
require_login('admin');

$kahim_res = $pdo->query("SELECT c.nama, c.nomor_urut, COUNT(v.id) as total FROM candidates c LEFT JOIN votes v ON v.candidate_id = c.id WHERE c.election_id = 1 GROUP BY c.id ORDER BY total DESC")->fetchAll();
$sen_res = $pdo->query("SELECT c.nama, c.nomor_urut, COUNT(v.id) as total FROM candidates c LEFT JOIN votes v ON v.candidate_id = c.id WHERE c.election_id = 2 GROUP BY c.id ORDER BY total DESC")->fetchAll();

$total_kahim = array_sum(array_column($kahim_res, 'total')) ?: 1;
$total_sen = array_sum(array_column($sen_res, 'total')) ?: 1;

$page_title = 'Hasil suara'; $page_sub = 'Perolehan suara sementara (quick count).'; $active = 'result_vote.php';
require '../includes/admin_top.php';

$panels = [
    ['Ketua Himpunan (Kahim)', 'crown', 'text-amber-600', 'from-amber-400 to-amber-500', $kahim_res, $total_kahim],
    ['Senator', 'graduation-cap', 'text-brand-600', 'from-brand-500 to-sky-400', $sen_res, $total_sen],
];
?>
<div class="grid gap-6 lg:grid-cols-2">
<?php foreach ($panels as [$label, $icon, $color, $grad, $rows, $sum]): ?>
    <section class="rounded-2xl border border-slate-200 bg-white p-6">
        <h2 class="mb-6 flex items-center gap-2 text-lg font-bold <?= $color ?>"><i data-lucide="<?= $icon ?>" class="h-5 w-5"></i><?= $label ?></h2>
        <div class="space-y-6">
            <?php foreach ($rows as $i => $k): $pct = round(($k['total'] / $sum) * 100); ?>
            <div>
                <div class="mb-2 flex items-baseline justify-between gap-3 text-sm">
                    <span class="font-semibold text-slate-700">
                        No. <?= e($k['nomor_urut']) ?> · <?= e($k['nama']) ?>
                        <?php if ($i === 0 && $k['total'] > 0): ?><span class="ml-1 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-bold text-emerald-700">Unggul</span><?php endif; ?>
                    </span>
                    <span class="shrink-0 font-bold text-ink"><?= (int)$k['total'] ?> suara <span class="font-medium text-slate-400">(<?= $pct ?>%)</span></span>
                </div>
                <div class="h-3 overflow-hidden rounded-full bg-slate-100">
                    <div class="bar-fill h-full rounded-full bg-gradient-to-r <?= $grad ?>" style="width: <?= $pct ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (!$rows): ?><p class="text-sm text-slate-500">Belum ada kandidat.</p><?php endif; ?>
        </div>
    </section>
<?php endforeach; ?>
</div>
<?php require '../includes/admin_bottom.php'; ?>