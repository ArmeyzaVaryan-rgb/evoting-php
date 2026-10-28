<?php
// Navbar bersama untuk halaman publik, peserta, dan admin.
// Variabel: $nav_mode ('public' | 'voter' | 'admin'), $active (nama file aktif), $base,
//           $header_status (opsional, teks status pemilihan untuk peserta)
$nav_mode = $nav_mode ?? 'public';
$active = $active ?? '';
$logged = isset($_SESSION['user_id']);
$nama = $_SESSION['nama'] ?? '';
$dark = ($nav_mode === 'public' && !empty($hero_header));

if ($nav_mode === 'admin') {
    $items = [
        'dashboard.php'   => ['Ringkasan', 'layout-dashboard'],
        'voters.php'      => ['Pemilih', 'users'],
        'candidate.php'   => ['Kandidat', 'user-round-check'],
        'result_vote.php' => ['Hasil suara', 'bar-chart-3'],
        'audit_log.php'   => ['Audit log', 'scroll-text'],
    ];
    $home = 'dashboard.php'; $logout = $base . 'logout.php'; $role_label = 'Panitia';
} elseif ($nav_mode === 'voter') {
    $items = ['dashboard.php' => ['Surat suara', 'vote']];
    $home = 'dashboard.php'; $logout = 'logout.php'; $role_label = 'Pemilih';
} else {
    $items = ['index.php' => ['Beranda', 'house']];
    if ($logged) {
        $items[($_SESSION['role'] ?? '') === 'admin' ? 'Admin/dashboard.php' : 'User/dashboard.php'] =
            [($_SESSION['role'] ?? '') === 'admin' ? 'Panel admin' : 'Surat suara', 'layout-dashboard'];
    }
    $home = 'index.php'; $logout = 'logout.php'; $role_label = ($_SESSION['role'] ?? '') === 'admin' ? 'Panitia' : 'Pemilih';
}
?>
<body class="<?= e($body_class ?? '') ?>">
<a class="skip-link" href="#konten">Lewati ke konten utama</a>
<header class="site-header<?= $dark ? ' site-header--dark' : '' ?>" data-site-header>
    <div class="container site-header__bar">
        <a class="brand" href="<?= $base . $home ?>" aria-label="E-Voting Kampus, ke beranda">
            <img src="<?= $base ?>assets/images/logo-mark.svg" alt="" width="38" height="38">
            <span class="brand__text">E-Voting<small>Kampus</small></span>
        </a>

        <nav class="site-nav" id="site-nav" aria-label="Navigasi utama" data-nav>
            <?php foreach ($items as $file => [$label, $icon]): $on = ($active === $file); ?>
                <a class="site-nav__link" href="<?= e($file) ?>" <?= $on ? 'aria-current="page"' : '' ?>>
                    <i data-lucide="<?= $icon ?>" class="icon" aria-hidden="true"></i><?= e($label) ?>
                </a>
            <?php endforeach; ?>
            <?php if ($logged): ?>
                <div class="site-nav__user"><b><?= e($nama) ?></b><?= e($role_label) ?><?php if (!empty($header_status)): ?> · <?= e($header_status) ?><?php endif; ?></div>
            <?php endif; ?>
        </nav>

        <div class="site-header__end">
            <?php if ($logged): ?>
                <?php if (!empty($header_status) && $nav_mode === 'voter'): ?>
                    <span class="badge <?= !empty($header_voted) ? 'badge--ok' : 'badge--gold' ?>"><i data-lucide="<?= !empty($header_voted) ? 'circle-check' : 'clock' ?>" class="icon" aria-hidden="true"></i><?= e($header_status) ?></span>
                <?php endif; ?>
                <div class="user-chip" title="<?= e($nama) ?>">
                    <span class="user-chip__avatar" aria-hidden="true"><?= e(initial_of($nama)) ?></span>
                    <span><span class="user-chip__name"><?= e($nama) ?></span><span class="user-chip__role"><?= e($role_label) ?></span></span>
                </div>
                <a class="btn btn--ghost btn--sm btn--logout" href="<?= e($logout) ?>"
                   data-confirm="Sesi Anda akan diakhiri dan Anda perlu masuk kembali." data-confirm-title="Keluar dari E-Voting?" data-confirm-ok="Ya, keluar">
                    <i data-lucide="log-out" class="icon" aria-hidden="true"></i><span>Keluar</span>
                </a>
            <?php elseif ($active !== 'login.php'): ?>
                <a class="btn btn--primary btn--sm" href="<?= $base ?>login.php"><i data-lucide="log-in" class="icon" aria-hidden="true"></i>Masuk</a>
            <?php endif; ?>
            <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav" aria-label="Buka menu" data-nav-toggle>
                <i data-lucide="menu" class="icon" aria-hidden="true"></i>
            </button>
        </div>
    </div>
</header>
