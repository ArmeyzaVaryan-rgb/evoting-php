<?php 
require '../config.php'; 
require_once '../includes/helpers.php';
require_login('admin');

/* Kolom misi / misi_status bersifat opsional: lihat database/migration_candidates_misi.sql */
$has_misi = (bool)$pdo->query("SHOW COLUMNS FROM candidates LIKE 'misi'")->fetch()
         && (bool)$pdo->query("SHOW COLUMNS FROM candidates LIKE 'misi_status'")->fetch();

if ($has_misi && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_misi') {
    $id = (int)($_POST['id'] ?? 0);
    $status = $_POST['misi_status'] ?? 'draft';
    $lines = array_values(array_filter(array_map('trim', preg_split('/\R/u', (string)($_POST['misi'] ?? '')))));
    $misi = ev_substr(implode("\n", $lines), 5000);

    $ex = $pdo->prepare("SELECT COUNT(*) FROM candidates WHERE id = ?"); $ex->execute([$id]);
    if (!csrf_valid()) {
        $_SESSION['flash_admin'] = ['danger', 'Sesi formulir kedaluwarsa. Silakan coba lagi.'];
    } elseif ($id <= 0 || (int)$ex->fetchColumn() !== 1) {
        $_SESSION['flash_admin'] = ['danger', 'Kandidat tidak ditemukan.'];
    } elseif (!in_array($status, ['draft', 'approved'], true)) {
        $_SESSION['flash_admin'] = ['danger', 'Status visi-misi tidak valid.'];
    } elseif ($status === 'approved' && $misi === '') {
        $_SESSION['flash_admin'] = ['danger', 'Misi kosong tidak dapat disetujui. Isi misi terlebih dahulu.'];
    } else {
        if ($misi === '') { $status = 'none'; }
        $u = $pdo->prepare("UPDATE candidates SET misi = ?, misi_status = ? WHERE id = ?");
        $u->execute([$misi === '' ? null : $misi, $status, $id]);
        log_activity($pdo, $_SESSION['user_id'], 'EDIT_CANDIDATE', "Memperbarui misi kandidat ID: $id (status: $status)");
        $_SESSION['flash_admin'] = ['ok', $status === 'approved' ? 'Misi disetujui dan kini tampil di surat suara.' : 'Misi disimpan sebagai draf. Belum tampil ke pemilih.'];
    }
    header("Location: candidate.php#cand-" . $id);
    exit;
}
$flash = $_SESSION['flash_admin'] ?? null; unset($_SESSION['flash_admin']);

$kahim = $pdo->query("SELECT * FROM candidates WHERE election_id = 1 ORDER BY nomor_urut ASC")->fetchAll();
$senator = $pdo->query("SELECT * FROM candidates WHERE election_id = 2 ORDER BY nomor_urut ASC")->fetchAll();

$page_title = 'Data kandidat'; $page_sub = 'Kandidat yang tampil di surat suara.'; $active = 'candidate.php';
$page_scripts = $has_misi ? ['candidate-admin'] : [];
require '../includes/admin_top.php';

$groups = [
    ['Ketua Himpunan (Kahim)', 'crown', $kahim],
    ['Senator', 'graduation-cap', $senator],
];
$status_badge = ['none' => ['badge--muted', 'Misi belum ada'], 'draft' => ['badge--gold', 'Draf, belum tampil'], 'approved' => ['badge--ok', 'Disetujui, tampil']];
?>
<?php if ($flash): ?>
<div class="alert alert--<?= $flash[0] === 'ok' ? 'ok' : 'danger' ?> mb-4" role="status"><i data-lucide="<?= $flash[0] === 'ok' ? 'circle-check' : 'circle-alert' ?>" class="icon" aria-hidden="true"></i><span class="alert__body"><?= e($flash[1]) ?></span></div>
<?php endif; ?>
<?php if (!$has_misi): ?>
<div class="alert alert--gold mb-5" role="note"><i data-lucide="database" class="icon" aria-hidden="true"></i>
    <span class="alert__body"><b>Kolom misi belum tersedia di database.</b> Jalankan <code>database/migration_candidates_misi.sql</code> agar visi-misi dapat dikelola dan ditampilkan di panel detail kandidat.</span></div>
<?php endif; ?>

<div class="grid grid--2" style="align-items:start">
<?php foreach ($groups as [$label, $icon, $rows]): ?>
    <section>
        <h2 class="section-title"><i data-lucide="<?= $icon ?>" class="icon icon--lg" aria-hidden="true"></i><?= e($label) ?></h2>
        <div class="stack" data-stagger>
            <?php foreach ($rows as $k): $st = $has_misi ? ($k['misi_status'] ?? 'none') : 'none'; ?>
            <article class="card cand-admin" id="cand-<?= (int)$k['id'] ?>">
                <div class="cand-admin__top">
                    <div class="avatar"><span aria-hidden="true"><?= e(initial_of($k['nama'])) ?></span><img src="<?= e($k['foto_url']) ?>" alt="Foto <?= e($k['nama']) ?>" loading="lazy" data-fallback></div>
                    <div style="min-width:0">
                        <span class="number-tag">No. <?= e($k['nomor_urut']) ?></span>
                        <h3 class="candidate__name"><?= e($k['nama']) ?></h3>
                        <p class="cand-admin__visi"><b>Visi:</b> <?= e($k['visi']) ?></p>
                        <?php if ($has_misi): ?><p class="cand-admin__misi"><span class="badge <?= $status_badge[$st][0] ?? 'badge--muted' ?>"><?= e($status_badge[$st][1] ?? '') ?></span></p><?php endif; ?>
                    </div>
                </div>
                <?php if ($has_misi): ?>
                <details class="editor">
                    <summary><i data-lucide="chevron-right" class="icon" aria-hidden="true"></i>Kelola misi</summary>
                    <form method="post" class="editor__form">
                        <?= csrf_field() ?><input type="hidden" name="action" value="save_misi"><input type="hidden" name="id" value="<?= (int)$k['id'] ?>">
                        <div class="alert alert--info"><i data-lucide="info" class="icon" aria-hidden="true"></i><span class="alert__body">Misi hanya tampil ke pemilih setelah berstatus <b>Disetujui</b>. Draf contoh dari sistem bukan pernyataan resmi kandidat dan wajib ditinjau.</span></div>
                        <div class="field">
                            <label class="label" for="draft-<?= (int)$k['id'] ?>">Isi dengan draf contoh (opsional)</label>
                            <select id="draft-<?= (int)$k['id'] ?>" class="select" data-draft-select data-target="misi-<?= (int)$k['id'] ?>" data-status="status-<?= (int)$k['id'] ?>">
                                <option value="">Pilih pendekatan kepemimpinan…</option>
                                <option value="komunikasi">Komunikasi dan transparansi</option>
                                <option value="terukur">Program kerja terukur</option>
                                <option value="potensi">Pengembangan potensi anggota</option>
                                <option value="kolaborasi">Kolaborasi dan inklusivitas</option>
                            </select>
                        </div>
                        <div class="field">
                            <label class="label" for="misi-<?= (int)$k['id'] ?>">Misi (satu poin per baris)</label>
                            <textarea id="misi-<?= (int)$k['id'] ?>" class="textarea" name="misi" maxlength="5000"><?= e($k['misi'] ?? '') ?></textarea>
                        </div>
                        <div class="field">
                            <label class="label" for="status-<?= (int)$k['id'] ?>">Status</label>
                            <select id="status-<?= (int)$k['id'] ?>" class="select" name="misi_status">
                                <option value="draft" <?= $st !== 'approved' ? 'selected' : '' ?>>Draf (belum tampil ke pemilih)</option>
                                <option value="approved" <?= $st === 'approved' ? 'selected' : '' ?>>Disetujui (tampil ke pemilih)</option>
                            </select>
                        </div>
                        <div><button type="submit" class="btn btn--secondary btn--sm"><i data-lucide="save" class="icon" aria-hidden="true"></i>Simpan misi</button></div>
                    </form>
                </details>
                <?php endif; ?>
            </article>
            <?php endforeach; ?>
            <?php if (!$rows): ?><p class="empty"><i data-lucide="users" class="icon" aria-hidden="true"></i>Belum ada kandidat di kategori ini.</p><?php endif; ?>
        </div>
    </section>
<?php endforeach; ?>
</div>
<?php require '../includes/admin_bottom.php'; ?>
