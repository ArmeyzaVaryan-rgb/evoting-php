<?php require 'config.php';
$title = 'E-Voting Kampus'; $base = '';
$nav_mode = 'public'; $active = 'index.php'; $hero_header = true;
require 'includes/head.php';
$login = isset($_SESSION['user_id']);
require 'includes/site_header.php';
?>
<main id="konten" class="hero">
    <div class="container hero__grid">
        <div class="reveal">
            <h1>Pilih pemimpin himpunanmu dengan tenang dan aman.</h1>
            <p class="hero__lead">Pemilihan Ketua Himpunan dan Senator berlangsung secara daring. Masuk dengan akun dari panitia, pilih kandidatmu, dan suaramu langsung tercatat.</p>

            <?php if ($login): ?>
                <div class="hero__cta">
                    <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                        <a href="Admin/dashboard.php" class="btn btn--primary"><i data-lucide="layout-dashboard" class="icon" aria-hidden="true"></i>Buka panel admin</a>
                    <?php else: ?>
                        <a href="User/dashboard.php" class="btn btn--primary"><i data-lucide="vote" class="icon" aria-hidden="true"></i>Buka surat suara</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="hero__cta">
                    <a href="login.php" class="btn btn--primary"><i data-lucide="log-in" class="icon" aria-hidden="true"></i>Masuk (admin atau pemilih)</a>
                </div>
            <?php endif; ?>

            <ul class="hero__points">
                <li><i data-lucide="shield-check" class="icon" aria-hidden="true"></i>Satu akun hanya dapat memberikan suara satu kali.</li>
                <li><i data-lucide="scroll-text" class="icon" aria-hidden="true"></i>Aktivitas penting tercatat di audit log.</li>
                <li><i data-lucide="chart-pie" class="icon" aria-hidden="true"></i>Hasil dihitung langsung dari suara yang masuk.</li>
            </ul>
        </div>

        <div class="stage" aria-hidden="true">
            <span class="ring ring--1"></span><span class="ring ring--2"></span>
            <div class="ballot3d">
                <div class="ballot3d__sheet ballot3d__sheet--c"></div>
                <div class="ballot3d__sheet ballot3d__sheet--b"></div>
                <div class="ballot3d__sheet ballot3d__sheet--a">
                    <span class="ballot3d__title"></span><span class="ballot3d__sub"></span>
                    <div class="ballot3d__line"><i></i><b></b></div>
                    <div class="ballot3d__line ballot3d__line--on"><i><svg viewBox="0 0 24 24"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></i><b></b></div>
                    <div class="ballot3d__line"><i></i><b></b></div>
                </div>
                <span class="ballot3d__seal"><i data-lucide="badge-check" class="icon" aria-hidden="true"></i></span>
            </div>
        </div>
    </div>
</main>
<?php require 'includes/site_footer.php'; ?>
</body>
</html>
