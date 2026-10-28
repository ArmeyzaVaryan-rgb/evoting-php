<?php 
require 'config.php'; 
if (isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nim = trim($_POST['nim']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE nim = ?");
    $stmt->execute([$nim]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if (!$user['is_active'] && $user['role'] === 'voter') {
            $error = "Akun Anda belum diaktifkan oleh panitia.";
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['nim'] = $user['nim'];
            
            log_activity($pdo, $user['id'], 'LOGIN', 'User berhasil login');

            if ($user['role'] === 'admin') {
                header("Location: Admin/dashboard.php");
            } else {
                header("Location: User/dashboard.php");
            }
            exit;
        }
    } else {
        $error = "NIM atau password salah.";
    }
}
$title = 'Masuk · E-Voting Kampus'; $base = '';
require 'includes/head.php';
?>
<body class="min-h-screen bg-white font-sans lg:grid lg:grid-cols-5">
<a href="#konten" class="skip-link">Lewati ke konten</a>

    <aside class="surface-deep on-dark relative hidden overflow-hidden p-12 lg:col-span-2 lg:flex lg:flex-col lg:justify-between">
        <div class="ring-deco" aria-hidden="true"></div>
        <div class="ring-deco alt" aria-hidden="true"></div>
        <div class="relative flex items-center gap-3">
            <span class="brand-mark"><i data-lucide="vote" class="h-5 w-5"></i></span>
            <span class="text-lg font-extrabold">E-Voting Kampus</span>
        </div>
        <div class="relative">
            <p class="reveal text-4xl font-extrabold leading-tight tracking-tight"><span class="gradient-text">Satu suara,</span><br>satu mahasiswa.</p>
            <p class="reveal mt-4 max-w-sm" style="--d:.1s;color:#C5D6DE">Pilih Ketua Himpunan dan Senator dari mana saja. Suaramu tercatat aman dan bersifat rahasia.</p>
        </div>
        <ul class="trust-list relative">
            <li><span class="ico"><i data-lucide="lock" class="h-4 w-4"></i></span>Suara dienkripsi dengan bukti hash</li>
            <li><span class="ico"><i data-lucide="eye-off" class="h-4 w-4"></i></span>Pilihanmu tidak bisa dilihat siapa pun</li>
            <li><span class="ico"><i data-lucide="scroll-text" class="h-4 w-4"></i></span>Setiap aktivitas tercatat di audit log</li>
        </ul>
    </aside>

    <main id="konten" class="flex min-h-screen items-center justify-center p-6 lg:col-span-3"
          x-data="{ show: false, loading: false }" @pageshow.window="if ($event.persisted) loading = false">
        <div class="reveal w-full max-w-sm">
            <div class="mb-8 flex items-center gap-3 lg:hidden">
                <span class="brand-mark"><i data-lucide="vote" class="h-5 w-5"></i></span>
                <span class="text-lg font-extrabold text-ink">E-Voting Kampus</span>
            </div>
            <h1 class="text-3xl font-extrabold tracking-tight text-ink">Masuk untuk memilih</h1>
            <div class="divider-gold mt-3"></div>
            <p class="muted mt-3 text-sm">Gunakan NIM dan password dari panitia.</p>

            <?php if ($error): ?>
            <div role="alert" class="alert alert-err mt-6">
                <i data-lucide="circle-alert" class="h-4 w-4"></i><span><?= e($error) ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" class="mt-6 space-y-5" @submit="loading = true">
                <div>
                    <label for="nim" class="label">NIM / Username</label>
                    <input id="nim" type="text" name="nim" required autofocus autocomplete="username" value="<?= e($_POST['nim'] ?? '') ?>"
                           class="input" placeholder="Contoh: 2201">
                </div>
                <div>
                    <label for="password" class="label">Password</label>
                    <div class="relative">
                        <input id="password" :type="show ? 'text' : 'password'" name="password" required autocomplete="current-password"
                               class="input pr-28" placeholder="Masukkan password">
                        <button type="button" @click="show = !show" :aria-pressed="show"
                                class="absolute inset-y-0 right-0 rounded-r-xl px-4 text-xs font-bold text-ink-600 hover:text-ink"
                                x-text="show ? 'Sembunyikan' : 'Lihat'">Lihat</button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block" :class="{ 'is-loading': loading }" :aria-busy="loading">
                    <span x-text="loading ? 'Memproses…' : 'Masuk'">Masuk</span>
                </button>
            </form>

            <div class="note mt-8">
                <p class="mb-1 font-bold">Akun percobaan</p>
                <p>Admin: <b>admin</b> &nbsp;|&nbsp; Pemilih: <b>2201</b></p>
                <p>Password: <b>password123</b></p>
            </div>
        </div>
    </main>

</body>
</html>
