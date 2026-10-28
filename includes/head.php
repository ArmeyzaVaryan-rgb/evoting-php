<?php
// Variabel masuk:
//   $title        judul tab
//   $base         '' untuk root, '../' untuk subfolder
//   $use_charts   true jika halaman memakai Chart.js
//   $page_scripts daftar nama file di assets/js/ yang dibutuhkan halaman (tanpa .js)
require_once __DIR__ . '/helpers.php';
$base = $base ?? '';
$use_charts = $use_charts ?? false;
$page_scripts = $page_scripts ?? [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#134158">
    <title><?= e($title ?? 'E-Voting') ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= $base ?>assets/images/logo-mark.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <?php $stylePath = __DIR__ . '/../assets/css/style.css'; $styleVersion = is_file($stylePath) ? (string)filemtime($stylePath) . '-' . (string)filesize($stylePath) : '1'; ?>
    <link rel="stylesheet" href="<?= $base ?>assets/css/style.css?v=<?= e($styleVersion) ?>">

    <!-- Library (dimuat sekali, terpusat) -->
    <script defer src="https://unpkg.com/lucide@0.453.0"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js"></script>
<?php if ($use_charts): ?>
    <script defer src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js"></script>
<?php endif; ?>

    <!-- Script aplikasi -->
    <script defer src="<?= $base ?>assets/js/main.js"></script>
    <script defer src="<?= $base ?>assets/js/navigation.js"></script>
    <script defer src="<?= $base ?>assets/js/ui-motion.js"></script>
<?php foreach ($page_scripts as $js): ?>
    <script defer src="<?= $base ?>assets/js/<?= e($js) ?>.js"></script>
<?php endforeach; ?>
</head>
