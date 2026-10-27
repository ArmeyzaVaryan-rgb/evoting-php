<?php require 'config.php';
$title = 'E-Voting Kampus'; $base = '';
require 'includes/head.php';
$login = isset($_SESSION['user_id']);
?>
<body class="flex min-h-screen items-center justify-center bg-ink p-6 font-sans">
    <div class="w-full max-w-md rounded-3xl bg-white p-10 shadow-2xl">
        <span class="grid h-12 w-12 place-items-center rounded-2xl bg-brand-600 text-white"><i data-lucide="vote" class="h-6 w-6"></i></span>
        <h1 class="mt-6 text-3xl font-extrabold tracking-tight text-ink">E-Voting Kampus</h1>
        <p class="mt-2 text-slate-500">Pemilihan Ketua Himpunan dan Senator.</p>

        <?php if ($login): ?>
            <div class="mt-8 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                <i data-lucide="circle-check" class="h-4 w-4 shrink-0"></i>
                <span>Masuk sebagai <b><?= e($_SESSION['nama']) ?></b> (<?= e($_SESSION['role']) ?>)</span>
            </div>
            <div class="mt-5 space-y-3">
                <?php if ($_SESSION['role'] == 'admin'): ?>
                    <a href="Admin/dashboard.php" class="block rounded-xl bg-brand-600 py-3 text-center text-sm font-bold text-white transition hover:bg-brand-700">Buka panel admin</a>
                <?php else: ?>
                    <a href="User/dashboard.php" class="block rounded-xl bg-brand-600 py-3 text-center text-sm font-bold text-white transition hover:bg-brand-700">Buka surat suara</a>
                <?php endif; ?>
                <a href="logout.php" class="block rounded-xl bg-slate-100 py-3 text-center text-sm font-bold text-slate-600 transition hover:bg-red-50 hover:text-red-600">Keluar</a>
            </div>
        <?php else: ?>
            <a href="login.php" class="mt-8 block rounded-xl bg-brand-600 py-3 text-center text-sm font-bold text-white shadow-lg shadow-brand-600/20 transition hover:bg-brand-700">Masuk (admin atau pemilih)</a>
        <?php endif; ?>
    </div>
<script src="https://unpkg.com/lucide@0.453.0"></script>
<script>lucide.createIcons();</script>
</body>
</html>