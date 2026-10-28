<?php 
require '../config.php'; 
require_once '../includes/helpers.php';
require_login('admin');

$total_voters = $pdo->query("SELECT COUNT(*) FROM users WHERE role='voter'")->fetchColumn();
$active_voters = $pdo->query("SELECT COUNT(*) FROM users WHERE role='voter' AND is_active=1")->fetchColumn();
$voted = $pdo->query("SELECT COUNT(*) FROM users WHERE role='voter' AND has_voted=1")->fetchColumn();
$not_voted = $active_voters - $voted;
$progress = $active_voters > 0 ? round(($voted / $active_voters) * 100) : 0;

$page_title = 'Ringkasan pemilihan'; $page_sub = 'Pantau partisipasi pemilih secara langsung.'; $active = 'dashboard.php';
$use_charts = true; $page_scripts = ['dashboard-charts'];
require '../includes/admin_top.php';
?>
<section class="hero-stat reveal" style="--i:1" data-participation>
    <p class="hero-stat__label">Partisipasi pemilih aktif</p>
    <div class="hero-stat__value">
        <span class="hero-stat__num"><span data-stat="percent"><?= (int)$progress ?></span><small>%</small></span>
        <p class="hero-stat__text"><b data-stat="voted"><?= (int)$voted ?></b> dari <b data-stat="active_voters"><?= (int)$active_voters ?></b> pemilih aktif<br>sudah memberikan suara.</p>
    </div>
    <div class="progress" role="progressbar" aria-label="Partisipasi pemilih aktif" aria-valuenow="<?= (int)$progress ?>" aria-valuemin="0" aria-valuemax="100" data-progress>
        <div class="progress__fill" style="--w: <?= (int)$progress ?>%"></div>
    </div>
</section>

<dl class="stats" data-stagger>
    <div class="stat stat--navy"><dt class="stat__label"><i data-lucide="users" class="icon" aria-hidden="true"></i>Total pemilih</dt><dd class="stat__value" data-stat="total_voters"><?= (int)$total_voters ?></dd></div>
    <div class="stat stat--ok"><dt class="stat__label"><i data-lucide="user-check" class="icon" aria-hidden="true"></i>Pemilih aktif <button type="button" class="tip" data-tip="Pemilih yang akunnya sudah diaktifkan panitia dan berhak memilih." aria-label="Penjelasan pemilih aktif">?</button></dt><dd class="stat__value" data-stat="active_voters"><?= (int)$active_voters ?></dd></div>
    <div class="stat stat--gold"><dt class="stat__label"><i data-lucide="vote" class="icon" aria-hidden="true"></i>Sudah memilih</dt><dd class="stat__value" data-stat="voted"><?= (int)$voted ?></dd></div>
    <div class="stat"><dt class="stat__label"><i data-lucide="hourglass" class="icon" aria-hidden="true"></i>Belum memilih <button type="button" class="tip" data-tip="Pemilih aktif dikurangi pemilih yang sudah memberikan suara." aria-label="Penjelasan belum memilih">?</button></dt><dd class="stat__value" data-stat="not_voted"><?= (int)$not_voted ?></dd></div>
</dl>

<?php $show_timeline = false; require '../includes/charts_panel.php'; ?>
<?php require '../includes/admin_bottom.php'; ?>
