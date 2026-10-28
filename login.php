<?php
require __DIR__ . '/config.php';
require_once __DIR__ . '/includes/helpers.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . APP_BASE_URL . 'index.php', true, 303);
    exit;
}

$error = '';
$identity = trim((string)($_POST['identity'] ?? ''));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string)($_POST['password'] ?? '');

    if (!csrf_valid()) {
        $error = 'Formulir sudah kedaluwarsa. Muat ulang halaman lalu coba lagi.';
    } elseif (filter_var($identity, FILTER_VALIDATE_EMAIL)) {
        // Administrator harus menggunakan alamat Gmail yang secara eksplisit diizinkan.
        $email = strtolower($identity);
        $account = allowed_admin_account($email);
        $configuredHash = is_array($account) ? (string)($account['password_hash'] ?? '') : '';
        $adminNim = is_array($account) ? trim((string)($account['user_nim'] ?? '')) : '';

        if (!is_array($account)) {
            $error = 'Email admin tidak diizinkan atau konfigurasi akun belum lengkap.';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE nim = ? AND role = 'admin' LIMIT 1");
            $stmt->execute([$adminNim]);
            $adminUser = $stmt->fetch();

            $active = $adminUser && (!array_key_exists('is_active', $adminUser) || (int)$adminUser['is_active'] === 1);
            if ($active && password_verify($password, $configuredHash)) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $adminUser['id'];
                $_SESSION['nama'] = trim((string)($account['name'] ?? '')) !== '' ? trim((string)$account['name']) : $adminUser['nama'];
                $_SESSION['role'] = 'admin';
                $_SESSION['nim'] = $adminUser['nim'];
                $_SESSION['email'] = $email;

                try {
                    log_activity($pdo, $adminUser['id'], 'LOGIN', 'Administrator login: ' . $email);
                } catch (Throwable $e) {
                    error_log('[e-voting] gagal mencatat login admin: ' . $e->getMessage());
                }

                header('Location: ' . APP_BASE_URL . 'Admin/dashboard.php', true, 303);
                exit;
            }
            $error = 'Email admin atau password salah, atau akun admin tidak aktif.';
        }
    } else {
        // Jalur peserta tetap menggunakan NIM dan password dari database.
        $stmt = $pdo->prepare('SELECT * FROM users WHERE nim = ? LIMIT 1');
        $stmt->execute([$identity]);
        $user = $stmt->fetch();

        if ($user && ($user['role'] ?? '') === 'admin') {
            $error = 'Administrator harus masuk menggunakan Gmail yang sudah didaftarkan panitia.';
        } elseif ($user && ($user['role'] ?? '') === 'voter' && password_verify($password, (string)$user['password'])) {
            if (!empty($user['deleted_at'])) {
                $error = 'Akun Anda sudah diarsipkan. Hubungi panitia jika perlu dipulihkan.';
            } elseif (!(int)$user['is_active']) {
                $error = 'Akun Anda belum diaktifkan oleh panitia.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['role'] = 'voter';
                $_SESSION['nim'] = $user['nim'];

                try {
                    log_activity($pdo, $user['id'], 'LOGIN', 'Pemilih berhasil login');
                } catch (Throwable $e) {
                    error_log('[e-voting] gagal mencatat login pemilih: ' . $e->getMessage());
                }

                header('Location: ' . APP_BASE_URL . 'User/dashboard.php', true, 303);
                exit;
            }
        } else {
            $error = 'NIM atau password salah.';
        }
    }
}

$title = 'Masuk · E-Voting Kampus';
$base = '';
$nav_mode = 'public';
$active = 'login.php';
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/site_header.php';
?>
<main id="konten" class="auth">
    <section class="auth__aside" aria-label="Tentang pemilihan">
        <div class="auth__intro reveal">
            <h2>Satu <span class="auth__gold">suara</span>,<br>satu <span class="auth__gold">mahasiswa</span>.</h2>
            <p>Pilih Ketua Himpunan dan Senator dari mana saja. Setiap akun hanya dapat memberikan suara satu kali.</p>
        </div>
        <ul class="auth__list">
            <li><i data-lucide="lock-keyhole" class="icon" aria-hidden="true"></i>Akses admin hanya untuk Gmail yang terdaftar</li>
            <li><i data-lucide="user-check" class="icon" aria-hidden="true"></i>Hak pilih peserta diaktifkan oleh panitia</li>
            <li><i data-lucide="scroll-text" class="icon" aria-hidden="true"></i>Aktivitas penting tercatat di audit log</li>
        </ul>
    </section>

    <section class="auth__main">
        <div class="auth__box reveal" style="--i:1">
            <h1>Masuk ke E-Voting</h1>
            <p class="muted mt-4">Administrator menggunakan Gmail yang diizinkan panitia. Peserta menggunakan NIM dan password dari panitia.</p>

            <?php if ($error): ?>
            <div role="alert" class="alert alert--danger mt-5">
                <i data-lucide="circle-alert" class="icon" aria-hidden="true"></i><span class="alert__body"><?= e($error) ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" class="mt-5" data-loading>
                <?= csrf_field() ?>
                <div class="field">
                    <label for="identity" class="label">Email admin / NIM pemilih</label>
                    <div class="input-wrap">
                        <i data-lucide="user-round" class="icon" aria-hidden="true"></i>
                        <input id="identity" class="input input--icon" type="text" name="identity" required autofocus autocomplete="username" value="<?= e($identity) ?>" placeholder="admin@gmail.com atau NIM" autocapitalize="none" spellcheck="false">
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
            <div class="alert alert--info mt-5">
                <i data-lucide="shield-check" class="icon" aria-hidden="true"></i>
                <span class="alert__body">Admin hanya dapat masuk jika alamat Gmail sudah dimasukkan ke daftar akun admin yang diizinkan.</span>
            </div>
        </div>
    </section>
</main>
</body>
</html>
