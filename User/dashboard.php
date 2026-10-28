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

$stats = [
    ['Total pemilih', $total_voters, 'users', ''],
    ['Pemilih aktif', $active_voters, 'user-check', 'ok'],
    ['Sudah memilih', $voted, 'vote', ''],
    ['Belum memilih', $not_voted, 'hourglass', 'gold'],
];
?>
<section class="hero-card reveal lg:p-10" style="--d:.05s" aria-label="Partisipasi pemilih">
    <p class="text-sm font-semibold" style="color:var(--gold-300)">Partisipasi pemilih aktif</p>
    <div class="mt-2 flex flex-wrap items-end gap-4">
        <span class="text-7xl font-extrabold tracking-tighter lg:text-8xl"><?= $progress ?><span class="text-4xl" style="color:var(--gold-300)">%</span></span>
        <p class="mb-3 text-sm" style="color:#C5D6DE"><?= $voted ?> dari <?= $active_voters ?> pemilih aktif<br>sudah memberikan suara.</p>
    </div>
    <div class="progress mt-6" role="progressbar" aria-label="Partisipasi pemilih" aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100">
        <div class="bar-fill bar-gold" style="width: <?= $progress ?>%"></div>
    </div>
</section>

<dl class="mt-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
    <?php foreach ($stats as $i => [$label, $value, $icon, $tone]): ?>
    <div class="card card-hover stat reveal" style="--d:<?= number_format(0.12 + $i * 0.06, 2) ?>s">
        <span class="stat-icon <?= $tone ?>"><i data-lucide="<?= $icon ?>" class="h-5 w-5"></i></span>
        <dt class="muted mt-4 text-sm font-semibold"><?= $label ?></dt>
        <dd class="stat-value mt-1 text-ink"><?= (int)$value ?></dd>
    </div>
    <?php endforeach; ?>
</dl>
<?php require '../includes/admin_bottom.php'; ?>
