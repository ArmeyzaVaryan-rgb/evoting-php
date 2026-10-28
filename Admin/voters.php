<?php
require '../config.php';
require_once '../includes/helpers.php';
require_login('admin');

$has_deleted_at = db_has_column($pdo, 'users', 'deleted_at');

function voters_safe_log(PDO $pdo, int $actor, string $action, string $description): void {
    try { log_activity($pdo, $actor, $action, $description); }
    catch (Throwable $e) { error_log('[e-voting] audit log pemilih: ' . $e->getMessage()); }
}
function voters_redirect(): void {
    header('Location: voters.php', true, 303);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $flash = null;

    if (!csrf_valid()) {
        $flash = ['danger', 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.'];
    } elseif ($action === 'add') {
        $nim = ev_substr(trim((string)($_POST['nim'] ?? '')), 80);
        $nama = ev_substr(trim((string)($_POST['nama'] ?? '')), 150);
        $password = (string)($_POST['password'] ?? '');

        if ($nim === '' || $nama === '' || $password === '') {
            $flash = ['danger', 'NIM, nama, dan password awal wajib diisi.'];
        } elseif (strlen($password) < 8) {
            $flash = ['danger', 'Password awal minimal 8 karakter.'];
        } else {
            try {
                $exists = $pdo->prepare('SELECT COUNT(*) FROM users WHERE nim = ?');
                $exists->execute([$nim]);
                if ((int)$exists->fetchColumn() > 0) {
                    $flash = ['danger', 'NIM sudah terdaftar. Gunakan NIM yang berbeda.'];
                } else {
                    $stmt = $pdo->prepare("INSERT INTO users (nim, nama, password, role, is_active, has_voted) VALUES (?, ?, ?, 'voter', 1, 0)");
                    $stmt->execute([$nim, $nama, password_hash($password, PASSWORD_DEFAULT)]);
                    $newId = (int)$pdo->lastInsertId();
                    voters_safe_log($pdo, (int)$_SESSION['user_id'], 'ADD_VOTER', "Menambahkan akun pemilih ID: $newId");
                    $flash = ['ok', 'Pemilih berhasil ditambahkan. Password disimpan dalam bentuk hash.'];
                }
            } catch (Throwable $e) {
                error_log('[e-voting] tambah pemilih: ' . $e->getMessage());
                $flash = ['danger', 'Pemilih belum dapat ditambahkan. Periksa NIM dan struktur database.'];
            }
        }
    } elseif ($action === 'edit' && $id) {
        $nim = ev_substr(trim((string)($_POST['nim'] ?? '')), 80);
        $nama = ev_substr(trim((string)($_POST['nama'] ?? '')), 150);
        $password = (string)($_POST['password'] ?? '');
        if ($nim === '' || $nama === '') {
            $flash = ['danger', 'NIM dan nama tidak boleh kosong.'];
        } elseif ($password !== '' && strlen($password) < 8) {
            $flash = ['danger', 'Password baru harus minimal 8 karakter. Kosongkan jika tidak ingin mengubah password.'];
        } else {
            try {
                $exists = $pdo->prepare("SELECT COUNT(*) FROM users WHERE nim = ? AND id <> ?");
                $exists->execute([$nim, $id]);
                $target = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'voter'" . ($has_deleted_at ? ' AND deleted_at IS NULL' : '') . ' LIMIT 1');
                $target->execute([$id]);
                if ((int)$exists->fetchColumn() > 0) {
                    $flash = ['danger', 'NIM tersebut sudah digunakan akun lain.'];
                } elseif (!$target->fetch()) {
                    $flash = ['danger', 'Pemilih tidak ditemukan atau sudah diarsipkan.'];
                } else {
                    if ($password !== '') {
                        $stmt = $pdo->prepare("UPDATE users SET nim = ?, nama = ?, password = ? WHERE id = ? AND role = 'voter'");
                        $stmt->execute([$nim, $nama, password_hash($password, PASSWORD_DEFAULT), $id]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE users SET nim = ?, nama = ? WHERE id = ? AND role = 'voter'");
                        $stmt->execute([$nim, $nama, $id]);
                    }
                    voters_safe_log($pdo, (int)$_SESSION['user_id'], 'EDIT_VOTER', "Memperbarui data akun pemilih ID: $id");
                    $flash = ['ok', 'Data pemilih berhasil diperbarui.'];
                }
            } catch (Throwable $e) {
                error_log('[e-voting] edit pemilih: ' . $e->getMessage());
                $flash = ['danger', 'Data pemilih belum dapat diperbarui. Periksa apakah NIM sudah digunakan.'];
            }
        }
    } elseif ($action === 'toggle' && $id) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET is_active = IF(is_active = 1, 0, 1) WHERE id = ? AND role = 'voter'" . ($has_deleted_at ? ' AND deleted_at IS NULL' : ''));
            $stmt->execute([$id]);
            if ($stmt->rowCount() === 1) {
                voters_safe_log($pdo, (int)$_SESSION['user_id'], 'TOGGLE_ACTIVE', "Mengubah status aktif akun pemilih ID: $id");
                $flash = ['ok', 'Status akun pemilih diperbarui.'];
            } else { $flash = ['danger', 'Pemilih tidak ditemukan atau sudah diarsipkan.']; }
        } catch (Throwable $e) {
            error_log('[e-voting] status pemilih: ' . $e->getMessage());
            $flash = ['danger', 'Status akun belum dapat diperbarui.'];
        }
    } elseif ($action === 'reset_vote' && $id) {
        // Hapus kedua catatan pilihan dalam satu transaksi, lalu buka kembali hak pilih.
        // Audit log tetap disimpan untuk mencatat bahwa panitia melakukan reset suara.
        try {
            $pdo->beginTransaction();
            $targetSql = "SELECT id, nama FROM users WHERE id = ? AND role = 'voter'";
            if ($has_deleted_at) { $targetSql .= ' AND deleted_at IS NULL'; }
            $targetSql .= ' LIMIT 1 FOR UPDATE';
            $targetStmt = $pdo->prepare($targetSql);
            $targetStmt->execute([$id]);
            $target = $targetStmt->fetch();

            if (!$target) {
                $pdo->rollBack();
                $flash = ['danger', 'Pemilih tidak ditemukan atau sudah diarsipkan.'];
            } else {
                $countStmt = $pdo->prepare('SELECT COUNT(*) FROM votes WHERE voter_id = ?');
                $countStmt->execute([$id]);
                $existingVotes = (int)$countStmt->fetchColumn();

                $deleteStmt = $pdo->prepare('DELETE FROM votes WHERE voter_id = ?');
                $deleteStmt->execute([$id]);
                $deletedVotes = $deleteStmt->rowCount();

                $resetStmt = $pdo->prepare("UPDATE users SET has_voted = 0 WHERE id = ? AND role = 'voter'");
                $resetStmt->execute([$id]);

                log_activity($pdo, (int)$_SESSION['user_id'], 'RESET_VOTE', "Menghapus/reset $deletedVotes catatan suara milik pemilih ID: $id; riwayat audit dipertahankan");
                $pdo->commit();

                if ($existingVotes > 0) {
                    $flash = ['ok', 'Suara untuk Ketua Himpunan dan Senator berhasil dihapus. Pemilih dapat memberikan suara kembali. Audit log tetap tersimpan.'];
                } else {
                    $flash = ['ok', 'Tidak ada catatan suara yang tersisa. Status hak pilih sudah diatur kembali agar konsisten.'];
                }
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            error_log('[e-voting] reset suara pemilih: ' . $e->getMessage());
            $flash = ['danger', 'Suara belum dapat dihapus. Tidak ada perubahan yang disimpan; periksa relasi tabel votes.'];
        }
    } elseif ($action === 'archive' && $id) {
        if (!$has_deleted_at) {
            $flash = ['danger', 'Kolom arsip belum tersedia. Jalankan database/migration_revisi4.sql terlebih dahulu.'];
        } else {
            try {
                // Soft delete: akses dinonaktifkan, tetapi suara dan audit log tidak dihapus.
                $stmt = $pdo->prepare("UPDATE users SET is_active = 0, deleted_at = NOW() WHERE id = ? AND role = 'voter' AND deleted_at IS NULL");
                $stmt->execute([$id]);
                if ($stmt->rowCount() === 1) {
                    voters_safe_log($pdo, (int)$_SESSION['user_id'], 'ARCHIVE_VOTER', "Mengarsipkan akun pemilih ID: $id; suara historis dipertahankan");
                    $flash = ['ok', 'Akun pemilih diarsipkan. Riwayat suara dan audit log tetap tersimpan.'];
                } else { $flash = ['danger', 'Pemilih tidak ditemukan atau sudah diarsipkan.']; }
            } catch (Throwable $e) {
                error_log('[e-voting] arsip pemilih: ' . $e->getMessage());
                $flash = ['danger', 'Pemilih belum dapat diarsipkan.'];
            }
        }
    } elseif ($action === 'restore' && $id) {
        if (!$has_deleted_at) {
            $flash = ['danger', 'Kolom arsip belum tersedia. Jalankan database/migration_revisi4.sql terlebih dahulu.'];
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE users SET deleted_at = NULL, is_active = 1 WHERE id = ? AND role = 'voter' AND deleted_at IS NOT NULL");
                $stmt->execute([$id]);
                if ($stmt->rowCount() === 1) {
                    voters_safe_log($pdo, (int)$_SESSION['user_id'], 'RESTORE_VOTER', "Memulihkan akun pemilih ID: $id");
                    $flash = ['ok', 'Akun pemilih berhasil dipulihkan dan diaktifkan.'];
                } else { $flash = ['danger', 'Pemilih tidak ditemukan atau tidak berada di arsip.']; }
            } catch (Throwable $e) {
                error_log('[e-voting] pulihkan pemilih: ' . $e->getMessage());
                $flash = ['danger', 'Pemilih belum dapat dipulihkan.'];
            }
        }
    } else {
        $flash = ['danger', 'Aksi tidak valid atau data pemilih tidak ditemukan.'];
    }

    $_SESSION['flash_admin'] = $flash ?? ['danger', 'Operasi tidak berhasil.'];
    voters_redirect();
}

$flash = $_SESSION['flash_admin'] ?? null;
unset($_SESSION['flash_admin']);
$status = (string)($_GET['status'] ?? 'all');
$allowedStatuses = ['all', 'voted', 'not_voted'];
if (!in_array($status, $allowedStatuses, true)) { $status = 'all'; }
$q = ev_substr(trim((string)($_GET['q'] ?? '')), 100);

$where = ["u.role = 'voter'"];
$params = [];
if ($has_deleted_at) { $where[] = 'u.deleted_at IS NULL'; }
if ($status === 'voted') { $where[] = 'EXISTS (SELECT 1 FROM votes v WHERE v.voter_id = u.id)'; }
if ($status === 'not_voted') { $where[] = 'NOT EXISTS (SELECT 1 FROM votes v WHERE v.voter_id = u.id)'; }
if ($q !== '') {
    $where[] = '(u.nim LIKE ? OR u.nama LIKE ?)';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}
$sqlWhere = 'WHERE ' . implode(' AND ', $where);
$stmt = $pdo->prepare("SELECT u.*, EXISTS (SELECT 1 FROM votes v WHERE v.voter_id = u.id) AS voted_record FROM users u $sqlWhere ORDER BY u.nim ASC");
$stmt->execute($params);
$voters = $stmt->fetchAll();

$activeSuffix = $has_deleted_at ? ' AND deleted_at IS NULL' : '';
$totalVoters = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='voter'" . $activeSuffix)->fetchColumn();
$votedCount = (int)$pdo->query("SELECT COUNT(*) FROM users u WHERE u.role='voter'" . ($has_deleted_at ? ' AND u.deleted_at IS NULL' : '') . ' AND EXISTS (SELECT 1 FROM votes v WHERE v.voter_id = u.id)')->fetchColumn();
$notVotedCount = max(0, $totalVoters - $votedCount);

$page_title = 'Kelola pemilih';
$page_sub = number_format(count($voters)) . ' pemilih pada filter saat ini.';
$active = 'voters.php';
require '../includes/admin_top.php';
?>
<?php if ($flash): ?>
<div class="alert alert--<?= $flash[0] === 'ok' ? 'ok' : 'danger' ?> mb-4" role="status"><i data-lucide="<?= $flash[0] === 'ok' ? 'circle-check' : 'circle-alert' ?>" class="icon" aria-hidden="true"></i><span class="alert__body"><?= e($flash[1]) ?></span></div>
<?php endif; ?>
<?php if (!$has_deleted_at): ?>
<div class="alert alert--gold mb-4" role="note"><i data-lucide="database" class="icon" aria-hidden="true"></i><span class="alert__body">Fitur arsip aman belum aktif. Jalankan <code>database/migration_revisi4.sql</code> agar akun dapat diarsipkan tanpa menghapus suara atau audit log.</span></div>
<?php endif; ?>

<section class="card mb-4 voter-add-card">
    <h2 class="section-title"><i data-lucide="user-plus" class="icon icon--lg" aria-hidden="true"></i>Tambah pemilih</h2>
    <p class="muted mb-4">Buat akun peserta baru. Password akan di-hash sebelum disimpan.</p>
    <form method="post" class="voter-form" autocomplete="off">
        <?= csrf_field() ?><input type="hidden" name="action" value="add">
        <div class="field"><label class="label" for="add-nim">NIM</label><input class="input" id="add-nim" name="nim" maxlength="80" required></div>
        <div class="field"><label class="label" for="add-nama">Nama lengkap</label><input class="input" id="add-nama" name="nama" maxlength="150" required></div>
        <div class="field"><label class="label" for="add-password">Password awal</label><input class="input" id="add-password" name="password" type="password" minlength="8" autocomplete="new-password" required><small class="hint">Minimal 8 karakter.</small></div>
        <div class="voter-form__submit"><button class="btn btn--primary" type="submit"><i data-lucide="plus" class="icon" aria-hidden="true"></i>Tambah pemilih</button></div>
    </form>
</section>

<section class="card mb-4 voter-filter-card">
    <form method="get" class="voter-filters">
        <div class="field"><label for="filter-status" class="label">Status pemilihan</label>
            <select class="select" name="status" id="filter-status">
                <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Semua pemilih (<?= $totalVoters ?>)</option>
                <option value="voted" <?= $status === 'voted' ? 'selected' : '' ?>>Sudah memilih (<?= $votedCount ?>)</option>
                <option value="not_voted" <?= $status === 'not_voted' ? 'selected' : '' ?>>Belum memilih (<?= $notVotedCount ?>)</option>
            </select>
        </div>
        <div class="field"><label for="filter-q" class="label">Cari NIM atau nama</label><input class="input" id="filter-q" name="q" type="search" value="<?= e($q) ?>" placeholder="Ketik NIM atau nama"></div>
        <div class="voter-filters__submit"><button class="btn btn--secondary" type="submit"><i data-lucide="search" class="icon" aria-hidden="true"></i>Terapkan filter</button><a href="voters.php" class="btn btn--ghost">Reset</a></div>
    </form>
    <p class="hint mt-4" aria-live="polite"><?= count($voters) ?> dari <?= $totalVoters ?> pemilih pada filter saat ini. Status “Sudah memilih” dihitung dari catatan suara di database.</p>
</section>

<div class="table-wrap">
    <table class="table table--stack">
        <thead><tr><th>NIM</th><th>Nama</th><th>Akun</th><th>Suara</th><th class="text-right">Aksi</th></tr></thead>
        <tbody>
        <?php foreach ($voters as $v): $didVote = !empty($v['voted_record']) || !empty($v['has_voted']); ?>
            <tr>
                <td data-label="NIM" class="mono"><?= e($v['nim']) ?></td>
                <td data-label="Nama"><b><?= e($v['nama']) ?></b></td>
                <td data-label="Akun">
                    <?php if (!empty($v['is_active'])): ?><span class="badge badge--ok">Aktif</span>
                    <?php else: ?><span class="badge badge--muted">Nonaktif</span><?php endif; ?>
                </td>
                <td data-label="Suara">
                    <?php if ($didVote): ?><span class="badge badge--navy"><i data-lucide="vote" class="icon" aria-hidden="true"></i>Sudah memilih</span>
                    <?php else: ?><span class="badge badge--gold"><i data-lucide="clock" class="icon" aria-hidden="true"></i>Belum memilih</span><?php endif; ?>
                </td>
                <td class="table-actions-cell"><div class="table-actions">
                    <?php if ($didVote): ?>
                    <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$v['id'] ?>"><input type="hidden" name="action" value="reset_vote"><button type="submit" class="btn btn--danger btn--sm" data-confirm="Semua suara <?= e($v['nama']) ?> untuk Ketua Himpunan dan Senator akan dihapus. Pemilih dapat memilih kembali. Audit log tidak dihapus." data-confirm-tone="danger" data-confirm-title="Hapus suara pemilih?" data-confirm-ok="Ya, hapus suara">Hapus suara</button></form>
                    <?php endif; ?>
                    <details class="voter-edit"><summary class="btn btn--ghost btn--sm">Edit</summary><form method="post" class="voter-edit__form">
                        <?= csrf_field() ?><input type="hidden" name="action" value="edit"><input type="hidden" name="id" value="<?= (int)$v['id'] ?>">
                        <div class="field"><label class="label" for="edit-nim-<?= (int)$v['id'] ?>">NIM</label><input class="input" id="edit-nim-<?= (int)$v['id'] ?>" name="nim" value="<?= e($v['nim']) ?>" maxlength="80" required></div>
                        <div class="field"><label class="label" for="edit-nama-<?= (int)$v['id'] ?>">Nama</label><input class="input" id="edit-nama-<?= (int)$v['id'] ?>" name="nama" value="<?= e($v['nama']) ?>" maxlength="150" required></div>
                        <div class="field"><label class="label" for="edit-password-<?= (int)$v['id'] ?>">Password baru (opsional)</label><input class="input" id="edit-password-<?= (int)$v['id'] ?>" name="password" type="password" minlength="8" autocomplete="new-password" placeholder="Kosongkan jika tetap"></div>
                        <button class="btn btn--primary btn--sm" type="submit">Simpan perubahan</button>
                    </form></details>
                    <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$v['id'] ?>"><input type="hidden" name="action" value="toggle"><button type="submit" class="btn btn--ghost btn--sm"><?= !empty($v['is_active']) ? 'Nonaktifkan' : 'Aktifkan' ?></button></form>
                    <?php if ($has_deleted_at): ?><form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$v['id'] ?>"><input type="hidden" name="action" value="archive"><button type="submit" class="btn btn--danger btn--sm" data-confirm="Akun <?= e($v['nama']) ?> akan dinonaktifkan dan dipindahkan ke arsip. Riwayat suara yang masih ada tidak dihapus." data-confirm-tone="danger" data-confirm-title="Hapus pemilih?" data-confirm-ok="Ya, hapus">Hapus pemilih</button></form><?php endif; ?>
                </div></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$voters): ?><tr class="table-empty-row"><td colspan="5" class="table-empty">Tidak ada pemilih yang cocok dengan filter ini.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php require '../includes/admin_bottom.php'; ?>
