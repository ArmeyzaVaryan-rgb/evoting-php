<?php 
require 'config.php'; 
if (isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nim = trim($_POST['nim'] ?? '');
    $password = $_POST['password'] ?? '';

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
$nav_mode = 'public'; $active = 'login.php';
require 'includes/head.php';
require 'includes/site_header.php';
?>
<main id="konten" class="auth">
    <section class="auth__aside" aria-label="Tentang pemilihan">
        <div class="reveal">
            <h2>Satu suara,<br>satu mahasiswa.</h2>
            <p>Pilih Ketua Himpunan dan Senator dari mana saja. Setiap akun hanya dapat memberikan suara satu kali.</p>
        </div>
        <ul class="auth__list">
            <li><i data-lucide="lock-keyhole" class="icon" aria-hidden="true"></i>Setiap suara memiliki bukti hash</li>
            <li><i data-lucide="user-check" class="icon" aria-hidden="true"></i>Hak pilih hanya untuk akun yang diaktifkan panitia</li>
            <li><i data-lucide="scroll-text" class="icon" aria-hidden="true"></i>Setiap aktivitas tercatat di audit log</li>
        </ul>
    </section>

    <section class="auth__main">
        <div class="auth__box reveal" style="--i:1">
            <h1>Masuk untuk memilih</h1>
            <p class="muted mt-4">Gunakan NIM dan password dari panitia.</p>

            <?php if ($error): ?>
            <div role="alert" class="alert alert--danger mt-5">
                <i data-lucide="circle-alert" class="icon" aria-hidden="true"></i><span class="alert__body"><?= e($error) ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" class="mt-5" data-loading>
                <div class="field">
                    <label for="nim" class="label">NIM / Username</label>
                    <div class="input-wrap">
                        <i data-lucide="user-round" class="icon" aria-hidden="true"></i>
                        <input id="nim" class="input input--icon" type="text" name="nim" required autofocus autocomplete="username" value="<?= e($_POST['nim'] ?? '') ?>" placeholder="Contoh: 2201">
                    </div>
                </div>
                <div class="field">
                    <label for="password" class="label">Password</label>
                    <div class="input-wrap">
                        <i data-lucide="key-round" class="icon" aria-hidden="true"></i>
                        <input id="password" class="input input--icon" type="password" name="password" required autocomplete="current-password" placeholder="Masukkan password" style="padding-right:104px">
                        <button type="button" class="input-wrap__btn" data-toggle-password="password" aria-pressed="false">Lihat</button>
                    </div>
                </div>
                <button type="submit" class="btn btn--primary btn--block mt-5">Masuk</button>
            </form>

            <div class="demo-box">
                <p><b>Akun percobaan</b></p>
                <p>Admin: <b>admin</b> &nbsp;|&nbsp; Pemilih: <b>2201</b> &nbsp;|&nbsp; Password: <b>password123</b></p>
            </div>
        </div>
    </section>
</main>
</body>
</html>
