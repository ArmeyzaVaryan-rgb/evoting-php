<?php 
require '../config.php'; 
require_once '../includes/helpers.php';
require_login('admin');

// Aksi mengubah data hanya lewat POST + token CSRF (sebelumnya lewat link GET).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if (!csrf_valid()) {
        $_SESSION['flash_admin'] = ['danger', 'Sesi formulir kedaluwarsa. Silakan coba lagi.'];
    } elseif ($id > 0 && $action === 'toggle') {
        $s = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ? AND role = 'voter'");
        $s->execute([$id]);
        log_activity($pdo, $_SESSION['user_id'], 'TOGGLE_ACTIVE', "Mengubah status aktif user ID: $id");
        $_SESSION['flash_admin'] = ['ok', 'Status akun pemilih diperbarui.'];
    } elseif ($id > 0 && $action === 'reset') {
        $pdo->beginTransaction();
        try {
            $s = $pdo->prepare("UPDATE users SET has_voted = 0 WHERE id = ? AND role = 'voter'"); $s->execute([$id]);
            $s = $pdo->prepare("DELETE FROM votes WHERE voter_id = ?"); $s->execute([$id]);
            log_activity($pdo, $_SESSION['user_id'], 'RESET_VOTE', "Reset hak pilih user ID: $id");
            $pdo->commit();
            $_SESSION['flash_admin'] = ['ok', 'Hak pilih berhasil direset.'];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            error_log('[e-voting] reset suara: ' . $e->getMessage());
            $_SESSION['flash_admin'] = ['danger', 'Hak pilih belum dapat direset. Silakan coba lagi.'];
        }
    }
    header("Location: voters.php");
    exit;
}

$flash = $_SESSION['flash_admin'] ?? null; unset($_SESSION['flash_admin']);

$voters = $pdo->query("SELECT * FROM users WHERE role='voter' ORDER BY nim ASC")->fetchAll();
$page_title = 'Kelola pemilih'; $page_sub = count($voters) . ' pemilih terdaftar.'; $active = 'voters.php';
require '../includes/admin_top.php';
?>
<?php if ($flash): ?>
<div class="alert alert--<?= $flash[0] === 'ok' ? 'ok' : 'danger' ?> mb-4" role="status"><i data-lucide="<?= $flash[0] === 'ok' ? 'circle-check' : 'circle-alert' ?>" class="icon" aria-hidden="true"></i><span class="alert__body"><?= e($flash[1]) ?></span></div>
<?php endif; ?>

<div class="card mb-4 reveal" style="--i:1">
    <label class="label" for="cari">Cari pemilih</label>
    <div class="input-wrap"><i data-lucide="search" class="icon" aria-hidden="true"></i>
        <input id="cari" class="input input--icon" type="search" placeholder="Cari NIM atau nama" data-table-search="#baris" data-empty="#kosong" data-counter="#voter-count"></div>
    <p class="hint" id="voter-count" aria-live="polite"><?= count($voters) ?> dari <?= count($voters) ?> pemilih ditampilkan</p>
</div>

<div class="table-wrap">
    <table class="table table--stack">
        <thead>
            <tr><th>NIM</th><th>Nama</th><th>Akun</th><th>Suara</th><th class="text-right">Aksi</th></tr>
        </thead>
        <tbody id="baris">
            <?php foreach($voters as $v): ?>
            <tr data-s="<?= e(strtolower($v['nim'] . ' ' . $v['nama'])) ?>">
                <td data-label="NIM" class="mono"><?= e($v['nim']) ?></td>
                <td data-label="Nama"><b><?= e($v['nama']) ?></b></td>
                <td data-label="Akun">
                    <?php if($v['is_active']): ?><span class="badge badge--ok"><i data-lucide="circle-check" class="icon" aria-hidden="true"></i>Aktif</span>
                    <?php else: ?><span class="badge badge--muted"><i data-lucide="circle-slash" class="icon" aria-hidden="true"></i>Nonaktif</span><?php endif; ?>
                </td>
                <td data-label="Suara">
                    <?php if($v['has_voted']): ?><span class="badge badge--navy"><i data-lucide="vote" class="icon" aria-hidden="true"></i>Sudah memilih</span>
                    <?php else: ?><span class="badge badge--gold"><i data-lucide="clock" class="icon" aria-hidden="true"></i>Belum memilih</span><?php endif; ?>
                </td>
                <td class="table-actions-cell">
                    <div class="table-actions">
                        <form method="post" class="inline-form">
                            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$v['id'] ?>"><input type="hidden" name="action" value="toggle">
                            <button type="submit" class="btn btn--ghost btn--sm"><?= $v['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                        </form>
                        <?php if($v['has_voted']): ?>
                        <form method="post" class="inline-form">
                            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$v['id'] ?>"><input type="hidden" name="action" value="reset">
                            <button type="submit" class="btn btn--danger btn--sm"
                                    data-confirm="Reset hak pilih <?= e($v['nama']) ?>? Suara sebelumnya akan dihapus." data-confirm-tone="danger" data-confirm-title="Reset hak pilih?" data-confirm-ok="Ya, reset">Reset suara</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <tr id="kosong" class="table-empty-row" hidden><td colspan="5" class="table-empty">Tidak ada pemilih yang cocok dengan pencarian.</td></tr>
            <?php if (!$voters): ?><tr class="table-empty-row"><td colspan="5" class="table-empty">Belum ada pemilih terdaftar.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php require '../includes/admin_bottom.php'; ?>
