<?php 
require '../config.php'; 
require_once '../includes/helpers.php';
require_login('admin');

$kahim_res = $pdo->query("SELECT c.nama, c.nomor_urut, COUNT(v.id) as total FROM candidates c LEFT JOIN votes v ON v.candidate_id = c.id WHERE c.election_id = 1 GROUP BY c.id ORDER BY total DESC")->fetchAll();
$sen_res = $pdo->query("SELECT c.nama, c.nomor_urut, COUNT(v.id) as total FROM candidates c LEFT JOIN votes v ON v.candidate_id = c.id WHERE c.election_id = 2 GROUP BY c.id ORDER BY total DESC")->fetchAll();

$total_kahim = array_sum(array_column($kahim_res, 'total')) ?: 1;
$total_sen = array_sum(array_column($sen_res, 'total')) ?: 1;

$page_title = 'Hasil suara'; $page_sub = 'Perolehan suara sementara (quick count).'; $active = 'result_vote.php';
$use_charts = true; $page_scripts = ['dashboard-charts'];
require '../includes/admin_top.php';

$panels = [
    ['Ketua Himpunan (Kahim)', 'crown', $kahim_res, $total_kahim],
    ['Senator', 'graduation-cap', $sen_res, $total_sen],
];
?>
<div class="grid grid--2" data-stagger>
<?php foreach ($panels as [$label, $icon, $rows, $sum]): ?>
    <section class="card card--accent">
        <h2 class="section-title"><i data-lucide="<?= $icon ?>" class="icon icon--lg" aria-hidden="true"></i><?= e($label) ?></h2>
        <div class="rank">
            <?php foreach ($rows as $i => $k):
                $pct = round(($k['total'] / $sum) * 100);
                $unggul = $i === 0 && $k['total'] > 0 && (!isset($rows[1]) || $k['total'] > $rows[1]['total']);
            ?>
            <div>
                <div class="rank__row-head">
                    <span class="rank__name">No. <?= e($k['nomor_urut']) ?> · <?= e($k['nama']) ?>
                        <?php if ($unggul): ?><span class="badge badge--gold" style="margin-left:6px"><i data-lucide="trophy" class="icon" aria-hidden="true"></i>Unggul</span><?php endif; ?>
                    </span>
                    <span class="rank__val"><?= (int)$k['total'] ?> suara <small>(<?= $pct ?>%)</small></span>
                </div>
                <div class="bar" role="img" aria-label="<?= e($k['nama']) ?>: <?= (int)$k['total'] ?> suara, <?= $pct ?> persen">
                    <div class="bar__fill <?= $unggul ? 'bar__fill--lead' : '' ?>" style="--w: <?= $pct ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (!$rows): ?><p class="muted">Belum ada kandidat.</p><?php endif; ?>
        </div>
    </section>
<?php endforeach; ?>
</div>

<h2 class="section-title mt-6" style="margin-bottom:0"><i data-lucide="chart-column" class="icon icon--lg" aria-hidden="true"></i>Analitik grafik</h2>
<?php $show_timeline = true; require '../includes/charts_panel.php'; ?>
<?php require '../includes/admin_bottom.php'; ?>
