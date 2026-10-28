<?php
// Variabel: $page_title, $page_sub (opsional), $active (nama file),
//           $use_charts, $page_scripts (opsional, diset sebelum include)
$base = '../';
$title = $page_title . ' · Admin E-Voting';
$nav_mode = 'admin';
require __DIR__ . '/head.php';
require __DIR__ . '/site_header.php';
?>
<main id="konten" class="page">
    <div class="container">
        <div class="page-head reveal">
            <div>
                <h1><?= e($page_title) ?></h1>
                <?php if (!empty($page_sub)): ?><p class="page-head__sub"><?= e($page_sub) ?></p><?php endif; ?>
            </div>
            <?php if (!empty($page_actions)): ?><div class="page-head__actions"><?= $page_actions /* HTML tepercaya dari halaman */ ?></div><?php endif; ?>
        </div>
