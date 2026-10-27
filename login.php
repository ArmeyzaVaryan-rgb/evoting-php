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
<body class="min-h-screen bg-white font-sans text-slate-800 lg:grid lg:grid-cols-5">

    <section class="relative hidden overflow-hidden bg-ink p-12 text-white lg:col-span-2 lg:flex lg:flex-col lg:justify-between">
        <div class="flex items-center gap-3">
            <span class="grid h-10 w-10 place-items-center rounded-xl bg-brand-500"><i data-lucide="vote" class="h-5 w-5"></i></span>
            <span class="text-lg font-bold">E-Voting Kampus</span>
        </div>
        <div>
            <p class="text-4xl font-extrabold leading-tight tracking-tight">Satu suara,<br>satu mahasiswa.</p>
            <p class="mt-4 max-w-sm text-slate-300">Pilih Ketua Himpunan dan Senator dari mana saja. Suaramu tercatat aman dan bersifat rahasia.</p>
        </div>
        <ul class="space-y-3 text-sm text-slate-300">
            <li class="flex items-center gap-3"><i data-lucide="lock" class="h-4 w-4 text-brand-100"></i>Suara dienkripsi dengan bukti hash</li>
            <li class="flex items-center gap-3"><i data-lucide="eye-off" class="h-4 w-4 text-brand-100"></i>Pilihanmu tidak bisa dilihat siapa pun</li>
            <li class="flex items-center gap-3"><i data-lucide="scroll-text" class="h-4 w-4 text-brand-100"></i>Setiap aktivitas tercatat di audit log</li>
        </ul>
        <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full border-[40px] border-white/5"></div>
    </section>

    <section class="flex min-h-screen items-center justify-center p-6 lg:col-span-3" x-data="{ show: false }">
        <div class="w-full max-w-sm">
            <div class="mb-8 flex items-center gap-3 lg:hidden">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-ink text-white"><i data-lucide="vote" class="h-5 w-5"></i></span>
                <span class="text-lg font-bold text-ink">E-Voting Kampus</span>
            </div>
            <h1 class="text-3xl font-extrabold tracking-tight text-ink">Masuk untuk memilih</h1>
            <p class="mt-2 text-sm text-slate-500">Gunakan NIM dan password dari panitia.</p>

            <?php if ($error): ?>
            <div role="alert" class="mt-6 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                <i data-lucide="circle-alert" class="mt-0.5 h-4 w-4 shrink-0"></i><span><?= e($error) ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" class="mt-6 space-y-5">
                <div>
                    <label for="nim" class="mb-1.5 block text-sm font-semibold text-slate-700">NIM / Username</label>
                    <input id="nim" type="text" name="nim" required autofocus autocomplete="username" value="<?= e($_POST['nim'] ?? '') ?>"
                           class="w-full rounded-xl border border-slate-300 px-4 py-3 text-sm transition focus:border-brand-500 focus:outline-none focus:ring-4 focus:ring-brand-100" placeholder="Contoh: 2201">
                </div>
                <div>
                    <label for="password" class="mb-1.5 block text-sm font-semibold text-slate-700">Password</label>
                    <div class="relative">
                        <input id="password" :type="show ? 'text' : 'password'" name="password" required autocomplete="current-password"
                               class="w-full rounded-xl border border-slate-300 px-4 py-3 pr-12 text-sm transition focus:border-brand-500 focus:outline-none focus:ring-4 focus:ring-brand-100" placeholder="Masukkan password">
                        <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 px-4 text-xs font-semibold text-slate-500 hover:text-brand-600" x-text="show ? 'Sembunyikan' : 'Lihat'"></button>
                    </div>
                </div>
                <button type="submit" class="w-full rounded-xl bg-brand-600 py-3 text-sm font-bold text-white shadow-lg shadow-brand-600/20 transition hover:bg-brand-700 active:scale-[.99]">Masuk</button>
            </form>

            <div class="mt-8 rounded-xl bg-slate-50 p-4 text-xs text-slate-500">
                <p class="mb-1 font-semibold text-slate-600">Akun percobaan</p>
                <p>Admin: <b>admin</b> &nbsp;|&nbsp; Pemilih: <b>2201</b></p>
                <p>Password: <b>password123</b></p>
            </div>
        </div>
    </section>

<script src="https://unpkg.com/lucide@0.453.0"></script>
<script>lucide.createIcons();</script>
</body>
</html>