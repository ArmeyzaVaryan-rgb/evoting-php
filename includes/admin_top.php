<?php
// Variabel: $page_title, $page_sub (opsional), $active (nama file)
$base = '../';
$title = $page_title . ' · Admin E-Voting';
require __DIR__ . '/head.php';
$nav = [
    'dashboard.php'   => ['Ringkasan', 'layout-dashboard'],
    'voters.php'      => ['Kelola pemilih', 'users'],
    'candidate.php'   => ['Kandidat', 'user-round-check'],
    'result_vote.php' => ['Hasil suara', 'bar-chart-3'],
    'audit_log.php'   => ['Audit log', 'scroll-text'],
];
?>
<body class="bg-slate-50 text-slate-800 font-sans" x-data="{ nav: false }" @keydown.escape.window="nav = false">
<div class="min-h-screen lg:flex">
    <div x-show="nav" x-cloak @click="nav = false" class="fixed inset-0 z-30 bg-ink/60 lg:hidden"></div>

    <aside class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-ink text-slate-300 transition-transform lg:sticky lg:top-0 lg:h-screen lg:translate-x-0"
           :class="nav ? 'translate-x-0' : '-translate-x-full'">
        <div class="flex items-center gap-3 px-6 py-6">
            <span class="grid h-9 w-9 place-items-center rounded-lg bg-brand-500 text-white"><i data-lucide="vote" class="h-5 w-5"></i></span>
            <div class="leading-tight">
                <p class="font-bold text-white">E-Voting</p>
                <p class="text-xs text-slate-400">Panel panitia</p>
            </div>
        </div>
        <nav class="flex-1 space-y-1 px-3 text-sm font-medium">
            <?php foreach ($nav as $file => [$label, $icon]): $on = ($active === $file); ?>
            <a href="<?= $file ?>" <?= $on ? 'aria-current="page"' : '' ?>
               class="flex items-center gap-3 rounded-lg px-3 py-2.5 transition <?= $on ? 'bg-white/10 text-white' : 'hover:bg-white/5 hover:text-white' ?>">
                <i data-lucide="<?= $icon ?>" class="h-4 w-4 <?= $on ? 'text-brand-100' : '' ?>"></i><?= $label ?>
            </a>
            <?php endforeach; ?>
        </nav>
        <div class="border-t border-white/10 p-4">
            <p class="mb-2 truncate px-3 text-xs text-slate-400"><?= e($_SESSION['nama'] ?? 'Admin') ?></p>
            <a href="logout.php" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-red-300 transition hover:bg-red-500/10">
                <i data-lucide="log-out" class="h-4 w-4"></i>Keluar
            </a>
        </div>
    </aside>

    <div class="min-w-0 flex-1">
        <header class="flex items-center gap-3 border-b bg-white px-4 py-3 lg:hidden">
            <button @click="nav = true" class="rounded-lg p-2 hover:bg-slate-100" aria-label="Buka menu"><i data-lucide="menu" class="h-5 w-5"></i></button>
            <span class="font-bold text-ink"><?= e($page_title) ?></span>
        </header>
        <main class="mx-auto max-w-6xl p-5 lg:p-10">
            <div class="mb-8">
                <h1 class="text-2xl font-extrabold tracking-tight text-ink lg:text-3xl"><?= e($page_title) ?></h1>
                <?php if (!empty($page_sub)): ?><p class="mt-1 text-sm text-slate-500"><?= e($page_sub) ?></p><?php endif; ?>
            </div>