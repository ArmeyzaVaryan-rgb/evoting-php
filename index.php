<?php require 'config.php';
$title = 'E-Voting Kampus'; $base = '';
require 'includes/head.php';
$login = isset($_SESSION['user_id']);
?>
<body class="font-sans">
<a href="#konten" class="skip-link">Lewati ke konten</a>
<div class="hero surface-deep on-dark">
    <div class="ring-deco" aria-hidden="true"></div>
    <div class="ring-deco alt" aria-hidden="true"></div>

    <header class="relative z-10 mx-auto flex w-full max-w-6xl items-center gap-3 px-6 py-6">
        <span class="brand-mark"><i data-lucide="vote" class="h-5 w-5"></i></span>
        <span class="text-lg font-extrabold">E-Voting Kampus</span>
    </header>

    <main id="konten" class="relative z-10 mx-auto grid w-full max-w-6xl flex-1 items-center gap-12 px-6 pb-12 lg:grid-cols-2">
        <section>
            <span class="eyebrow reveal"><i data-lucide="shield-check" class="h-3.5 w-3.5"></i>Pemilihan Ketua Himpunan dan Senator</span>
            <h1 class="reveal mt-6 text-5xl font-extrabold leading-[1.08] tracking-tight lg:text-6xl" style="--d:.08s">
                <span class="gradient-text">Satu suara,</span><br>satu mahasiswa.
            </h1>
            <p class="reveal mt-5 max-w-md text-lg" style="--d:.16s;color:#C5D6DE">Pilih dari mana saja. Suaramu tercatat aman dan bersifat rahasia.</p>
            <ul class="trust-list reveal mt-8" style="--d:.24s">
                <li><span class="ico"><i data-lucide="lock" class="h-4 w-4"></i></span>Suara dienkripsi dengan bukti hash</li>
                <li><span class="ico"><i data-lucide="eye-off" class="h-4 w-4"></i></span>Pilihanmu tidak bisa dilihat siapa pun</li>
                <li><span class="ico"><i data-lucide="scroll-text" class="h-4 w-4"></i></span>Setiap aktivitas tercatat di audit log</li>
            </ul>
        </section>

        <section class="reveal card mx-auto w-full max-w-md p-8 sm:p-10" style="--d:.2s;box-shadow:var(--shadow-lg)" aria-label="Akses pemilihan">
            <?php if ($login): ?>
                <p class="text-sm font-semibold muted">Selamat datang kembali</p>
                <p class="mt-1 text-2xl font-extrabold text-ink"><?= e($_SESSION['nama']) ?></p>
                <p class="mt-2"><span class="badge badge-ok"><i data-lucide="circle-check" class="h-3.5 w-3.5"></i>Masuk sebagai <?= e($_SESSION['role']) ?></span></p>
                <div class="mt-7 space-y-3">
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                        <a href="Admin/dashboard.php" class="btn btn-primary btn-block"><i data-lucide="layout-dashboard" class="h-4 w-4"></i>Buka panel admin</a>
                    <?php else: ?>
                        <a href="User/dashboard.php" class="btn btn-primary btn-block"><i data-lucide="vote" class="h-4 w-4"></i>Buka surat suara</a>
                    <?php endif; ?>
                    <a href="logout.php" class="btn btn-ghost btn-block">Keluar</a>
                </div>
            <?php else: ?>
                <span class="brand-mark"><i data-lucide="log-in" class="h-5 w-5"></i></span>
                <h2 class="mt-5 text-2xl font-extrabold text-ink">Mulai memilih</h2>
                <div class="divider-gold mt-3"></div>
                <p class="muted mt-3 text-sm">Masuk dengan NIM dan password yang diberikan panitia.</p>
                <a href="login.php" class="btn btn-primary btn-block mt-7">Masuk (admin atau pemilih)<i data-lucide="arrow-right" class="h-4 w-4"></i></a>
            <?php endif; ?>
        </section>
    </main>

    <footer class="site-footer relative z-10">&copy; <?= date('Y') ?> E-Voting Kampus</footer>
</div>
</body>
</html>
