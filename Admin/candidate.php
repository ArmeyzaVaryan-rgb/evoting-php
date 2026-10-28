<?php
require '../config.php';
require_once '../includes/helpers.php';
require_login('admin');

$has_misi = db_has_column($pdo, 'candidates', 'misi');
$has_misi_status = db_has_column($pdo, 'candidates', 'misi_status');
$has_program_kerja = db_has_column($pdo, 'candidates', 'program_kerja');
$has_program_kerja_status = db_has_column($pdo, 'candidates', 'program_kerja_status');
$has_candidate_active = db_has_column($pdo, 'candidates', 'is_active');

function candidate_safe_log(PDO $pdo, int $actor, string $action, string $description): void {
    try { log_activity($pdo, $actor, $action, $description); }
    catch (Throwable $e) { error_log('[e-voting] audit log kandidat: ' . $e->getMessage()); }
}
function candidate_text(string $value, int $limit = 5000): string {
    $lines = array_values(array_filter(array_map('trim', preg_split('/\R/u', trim($value)) ?: []), static function ($line) { return $line !== ''; }));
    return ev_substr(implode("\n", $lines), $limit);
}
function candidate_photo_is_safe(string $url): bool {
    if ($url === '') { return true; }
    if (strlen($url) > 500 || preg_match('/^(?:javascript|data|vbscript):/i', $url) || str_starts_with($url, '//')) { return false; }
    $parts = parse_url($url);
    if ($parts === false) { return false; }
    if (isset($parts['scheme'])) { return in_array(strtolower($parts['scheme']), ['http', 'https'], true); }
    return strpos($url, '..') === false && !preg_match('/[\r\n]/', $url);
}
function candidate_redirect(int $id = 0): void {
    header('Location: candidate.php' . ($id > 0 ? '#cand-' . $id : ''), true, 303);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $flash = null;

    if (!csrf_valid()) {
        $flash = ['danger', 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.'];
    } elseif (in_array($action, ['add', 'edit'], true)) {
        $electionId = filter_var($_POST['election_id'] ?? null, FILTER_VALIDATE_INT);
        $nomor = filter_var($_POST['nomor_urut'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $nama = ev_substr(trim((string)($_POST['nama'] ?? '')), 150);
        $foto = trim((string)($_POST['foto_url'] ?? ''));
        $visi = ev_substr(trim((string)($_POST['visi'] ?? '')), 5000);
        $misi = candidate_text((string)($_POST['misi'] ?? ''));
        $program = candidate_text((string)($_POST['program_kerja'] ?? ''));
        $misiStatus = (string)($_POST['misi_status'] ?? 'draft');
        $programStatus = (string)($_POST['program_kerja_status'] ?? 'draft');

        if (!in_array($electionId, [1, 2], true) || !$nomor || $nama === '' || $visi === '') {
            $flash = ['danger', 'Kategori, nomor urut, nama, dan visi wajib diisi dengan benar.'];
        } elseif (!candidate_photo_is_safe($foto)) {
            $flash = ['danger', 'Alamat foto tidak valid. Gunakan URL HTTP/HTTPS atau path relatif yang aman.'];
        } elseif (($has_misi_status && !in_array($misiStatus, ['none', 'draft', 'approved'], true)) || ($has_program_kerja_status && !in_array($programStatus, ['none', 'draft', 'approved'], true))) {
            $flash = ['danger', 'Status publikasi misi atau program kerja tidak valid.'];
        } elseif (($has_misi_status && $misiStatus === 'approved' && $misi === '') || ($has_program_kerja_status && $programStatus === 'approved' && $program === '')) {
            $flash = ['danger', 'Misi atau program kerja yang disetujui tidak boleh kosong.'];
        } elseif ((!$has_program_kerja || !$has_program_kerja_status) && $program !== '') {
            $flash = ['danger', 'Kolom Program Kerja belum tersedia. Jalankan database/migration_revisi4.sql terlebih dahulu.'];
        } else {
            try {
                $duplicate = $pdo->prepare('SELECT COUNT(*) FROM candidates WHERE election_id = ? AND nomor_urut = ?' . ($action === 'edit' && $id ? ' AND id <> ?' : ''));
                $dupParams = [$electionId, $nomor];
                if ($action === 'edit' && $id) { $dupParams[] = $id; }
                $duplicate->execute($dupParams);
                $target = null;
                if ($action === 'edit') {
                    if (!$id) { throw new RuntimeException('ID kandidat tidak valid.'); }
                    $find = $pdo->prepare('SELECT id FROM candidates WHERE id = ? LIMIT 1');
                    $find->execute([$id]);
                    $target = $find->fetch();
                }

                if ((int)$duplicate->fetchColumn() > 0) {
                    $flash = ['danger', 'Nomor urut sudah dipakai pada kategori pemilihan tersebut.'];
                } elseif ($action === 'edit' && !$target) {
                    $flash = ['danger', 'Kandidat tidak ditemukan.'];
                } else {
                    $values = [
                        'election_id' => (int)$electionId,
                        'nomor_urut' => (int)$nomor,
                        'nama' => $nama,
                        'foto_url' => $foto,
                        'visi' => $visi,
                    ];
                    if ($has_misi) { $values['misi'] = $misi !== '' ? $misi : null; }
                    if ($has_misi_status) { $values['misi_status'] = $misi === '' ? 'none' : $misiStatus; }
                    if ($has_program_kerja) { $values['program_kerja'] = $program !== '' ? $program : null; }
                    if ($has_program_kerja_status) { $values['program_kerja_status'] = $program === '' ? 'none' : $programStatus; }

                    if ($action === 'add') {
                        if ($has_candidate_active) { $values['is_active'] = 1; }
                        $columns = array_keys($values);
                        $sql = 'INSERT INTO candidates (`' . implode('`,`', $columns) . '`) VALUES (' . implode(',', array_fill(0, count($columns), '?')) . ')';
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute(array_values($values));
                        $savedId = (int)$pdo->lastInsertId();
                        candidate_safe_log($pdo, (int)$_SESSION['user_id'], 'ADD_CANDIDATE', "Menambahkan kandidat ID: $savedId (kategori $electionId)");
                        $flash = ['ok', 'Kandidat berhasil ditambahkan ke database.'];
                        $id = $savedId;
                    } else {
                        $sets = [];
                        foreach (array_keys($values) as $column) { $sets[] = '`' . $column . '` = ?'; }
                        $stmt = $pdo->prepare('UPDATE candidates SET ' . implode(', ', $sets) . ' WHERE id = ?');
                        $params = array_values($values);
                        $params[] = $id;
                        $stmt->execute($params);
                        candidate_safe_log($pdo, (int)$_SESSION['user_id'], 'EDIT_CANDIDATE', "Memperbarui kandidat ID: $id");
                        $flash = ['ok', 'Data kandidat, misi, dan program kerja berhasil diperbarui.'];
                    }
                }
            } catch (Throwable $e) {
                error_log('[e-voting] simpan kandidat: ' . $e->getMessage());
                $flash = ['danger', 'Data kandidat belum dapat disimpan. Periksa nomor urut, struktur database, dan kolom wajib.'];
            }
        }
    } elseif ($action === 'delete' && $id) {
        // Hapus permanen jika belum pernah menerima suara. Jika sudah ada suara,
        // kandidat hanya dinonaktifkan agar hasil historis tetap merujuk ke kandidat yang benar.
        try {
            $pdo->beginTransaction();
            $find = $pdo->prepare('SELECT id, nama' . ($has_candidate_active ? ', is_active' : '') . ' FROM candidates WHERE id = ? LIMIT 1 FOR UPDATE');
            $find->execute([$id]);
            $candidate = $find->fetch();

            if (!$candidate) {
                $pdo->rollBack();
                $flash = ['danger', 'Kandidat tidak ditemukan.'];
            } else {
                $voteCountStmt = $pdo->prepare('SELECT COUNT(*) FROM votes WHERE candidate_id = ?');
                $voteCountStmt->execute([$id]);
                $voteCount = (int)$voteCountStmt->fetchColumn();

                if ($voteCount > 0) {
                    if (!$has_candidate_active) {
                        throw new RuntimeException('Kolom is_active belum tersedia untuk mempertahankan kandidat yang sudah memperoleh suara.');
                    }
                    $disable = $pdo->prepare('UPDATE candidates SET is_active = 0 WHERE id = ?');
                    $disable->execute([$id]);
                    candidate_safe_log($pdo, (int)$_SESSION['user_id'], 'DELETE_CANDIDATE_PRESERVED_VOTES', "Permintaan hapus kandidat ID: $id; dinonaktifkan karena memiliki $voteCount suara historis");
                    $pdo->commit();
                    $flash = ['ok', 'Kandidat sudah menerima suara, sehingga dinonaktifkan dan tidak muncul pada pemilihan baru. Data suara historis tetap utuh dan kandidat tidak dihapus permanen.'];
                } else {
                    $delete = $pdo->prepare('DELETE FROM candidates WHERE id = ?');
                    $delete->execute([$id]);
                    candidate_safe_log($pdo, (int)$_SESSION['user_id'], 'DELETE_CANDIDATE', "Menghapus permanen kandidat ID: $id karena belum memiliki suara");
                    $pdo->commit();
                    $flash = ['ok', 'Kandidat berhasil dihapus permanen karena belum memiliki suara.'];
                }
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            error_log('[e-voting] hapus kandidat: ' . $e->getMessage());
            $flash = ['danger', 'Kandidat belum dapat dihapus. Periksa relasi database dan riwayat suara.'];
        }
    } else {
        $flash = ['danger', 'Aksi tidak valid atau data kandidat tidak ditemukan.'];
    }

    $_SESSION['flash_admin'] = $flash ?? ['danger', 'Operasi tidak berhasil.'];
    candidate_redirect(($action === 'edit' || $action === 'delete') && $id ? (int)$id : 0);
}

$flash = $_SESSION['flash_admin'] ?? null;
unset($_SESSION['flash_admin']);
$kahim = $pdo->query('SELECT * FROM candidates WHERE election_id = 1 ORDER BY nomor_urut ASC, id ASC')->fetchAll();
$senator = $pdo->query('SELECT * FROM candidates WHERE election_id = 2 ORDER BY nomor_urut ASC, id ASC')->fetchAll();

$page_title = 'Data kandidat';
$page_sub = 'Tambah, perbarui, publikasikan, dan hapus kandidat.';
$active = 'candidate.php';
$page_scripts = ($has_misi && $has_misi_status) ? ['candidate-admin'] : [];
require '../includes/admin_top.php';

$groups = [
    ['Ketua Himpunan (Kahim)', 'crown', $kahim, 1],
    ['Senator', 'graduation-cap', $senator, 2],
];
$status_badge = [
    'none' => ['badge--muted', 'Belum diisi'],
    'draft' => ['badge--gold', 'Draf, belum tampil'],
    'approved' => ['badge--ok', 'Disetujui, tampil'],
];
?>
<?php if ($flash): ?>
<div class="alert alert--<?= $flash[0] === 'ok' ? 'ok' : 'danger' ?> mb-4" role="status"><i data-lucide="<?= $flash[0] === 'ok' ? 'circle-check' : 'circle-alert' ?>" class="icon" aria-hidden="true"></i><span class="alert__body"><?= e($flash[1]) ?></span></div>
<?php endif; ?>
<?php if (!$has_misi || !$has_misi_status || !$has_program_kerja || !$has_program_kerja_status || !$has_candidate_active): ?>
<div class="alert alert--gold mb-5" role="note"><i data-lucide="database" class="icon" aria-hidden="true"></i><span class="alert__body"><b>Struktur database Revisi 4 belum lengkap.</b> Jalankan <code>database/migration_revisi4.sql</code> pada database <code>evoting</code>. Migrasi memeriksa kolom yang sudah ada sebelum menambah kolom baru, serta tidak menghapus data lama.</span></div>
<?php endif; ?>

<details class="card mb-5 candidate-add-disclosure">
    <summary class="candidate-add-disclosure__summary"><span class="candidate-add-disclosure__title"><i data-lucide="user-round-plus" class="icon icon--lg" aria-hidden="true"></i>Tambah kandidat</span><span class="candidate-add-disclosure__hint">Klik untuk membuka formulir di halaman ini</span><i data-lucide="chevron-down" class="icon candidate-add-disclosure__chevron" aria-hidden="true"></i></summary>
    <div class="candidate-add-card">
    <p class="muted mb-4">Data kandidat disimpan ke SQL. Misi dan program kerja baru terlihat oleh pemilih jika statusnya disetujui.</p>
    <form method="post" class="candidate-form" autocomplete="off">
        <?= csrf_field() ?><input type="hidden" name="action" value="add">
        <div class="field"><label for="new-election" class="label">Kategori pemilihan</label><select class="select" id="new-election" name="election_id" required><option value="1">Ketua Himpunan (Kahim)</option><option value="2">Senator</option></select></div>
        <div class="field"><label for="new-nomor" class="label">Nomor urut</label><input class="input" id="new-nomor" name="nomor_urut" type="number" min="1" required></div>
        <div class="field"><label for="new-nama" class="label">Nama kandidat</label><input class="input" id="new-nama" name="nama" maxlength="150" required></div>
        <div class="field"><label for="new-foto" class="label">URL/path foto (opsional)</label><input class="input" id="new-foto" name="foto_url" maxlength="500" placeholder="assets/images/kandidat.jpg"></div>
        <div class="field candidate-form__wide"><label for="new-visi" class="label">Visi</label><textarea class="textarea" id="new-visi" name="visi" maxlength="5000" required></textarea></div>
        <div class="field"><label for="new-misi" class="label">Misi (satu poin per baris)</label><textarea class="textarea" id="new-misi" name="misi" maxlength="5000" placeholder="Satu poin misi per baris"></textarea></div>
        <div class="field"><label for="new-misi-status" class="label">Status misi</label><select class="select" id="new-misi-status" name="misi_status"><option value="draft" selected>Draf (belum tampil)</option><option value="approved">Disetujui (tampil)</option><option value="none">Belum diisi</option></select></div>
        <div class="field"><label for="new-program" class="label">Program kerja (satu poin per baris)</label><textarea class="textarea" id="new-program" name="program_kerja" maxlength="5000" placeholder="Satu program kerja per baris"></textarea></div>
        <div class="field"><label for="new-program-status" class="label">Status program kerja</label><select class="select" id="new-program-status" name="program_kerja_status"><option value="draft" selected>Draf (belum tampil)</option><option value="approved">Disetujui (tampil)</option><option value="none">Belum diisi</option></select></div>
        <div class="candidate-form__submit"><button type="submit" class="btn btn--primary"><i data-lucide="plus" class="icon" aria-hidden="true"></i>Simpan kandidat</button></div>
    </form>
    </div>
</details>

<div class="grid grid--2 candidate-admin-grid" style="align-items:start">
<?php foreach ($groups as [$label, $icon, $rows, $categoryId]): ?>
    <section>
        <h2 class="section-title"><i data-lucide="<?= $icon ?>" class="icon icon--lg" aria-hidden="true"></i><?= e($label) ?></h2>
        <div class="stack" data-stagger>
            <?php foreach ($rows as $k):
                $id = (int)$k['id'];
                $misiStatus = $has_misi_status ? (string)($k['misi_status'] ?? 'none') : 'none';
                $programStatus = $has_program_kerja_status ? (string)($k['program_kerja_status'] ?? 'none') : 'none';
                $isActive = !$has_candidate_active || !array_key_exists('is_active', $k) || (int)$k['is_active'] === 1;
            ?>
            <article class="card cand-admin <?= $isActive ? '' : 'cand-admin--archived' ?>" id="cand-<?= $id ?>">
                <div class="cand-admin__top">
                    <div class="avatar"><span aria-hidden="true"><?= e(initial_of($k['nama'])) ?></span><img src="<?= e($k['foto_url'] ?? '') ?>" alt="Foto <?= e($k['nama']) ?>" loading="lazy" data-fallback></div>
                    <div class="cand-admin__summary">
                        <span class="number-tag">No. <?= e($k['nomor_urut']) ?> · <?= $categoryId === 1 ? 'Kahim' : 'Senator' ?></span>
                        <h3 class="candidate__name"><?= e($k['nama']) ?></h3>
                        <p class="cand-admin__visi"><b>Visi:</b> <?= e($k['visi'] ?? '') ?></p>
                        <div class="cand-admin__badges">
                            <span class="badge <?= $isActive ? 'badge--ok' : 'badge--muted' ?>"><?= $isActive ? 'Aktif' : 'Nonaktif' ?></span>
                            <span class="badge <?= $status_badge[$misiStatus][0] ?? 'badge--muted' ?>">Misi: <?= e($status_badge[$misiStatus][1] ?? 'Belum diisi') ?></span>
                            <span class="badge <?= $status_badge[$programStatus][0] ?? 'badge--muted' ?>">Program: <?= e($status_badge[$programStatus][1] ?? 'Belum diisi') ?></span>
                        </div>
                    </div>
                </div>
                <details class="editor">
                    <summary><i data-lucide="pencil-line" class="icon" aria-hidden="true"></i>Kelola data, misi, dan program kerja</summary>
                    <form method="post" class="editor__form candidate-edit-form">
                        <?= csrf_field() ?><input type="hidden" name="action" value="edit"><input type="hidden" name="id" value="<?= $id ?>">
                        <div class="field"><label class="label" for="e-election-<?= $id ?>">Kategori pemilihan</label><select class="select" id="e-election-<?= $id ?>" name="election_id"><option value="1" <?= (int)$k['election_id'] === 1 ? 'selected' : '' ?>>Ketua Himpunan</option><option value="2" <?= (int)$k['election_id'] === 2 ? 'selected' : '' ?>>Senator</option></select></div>
                        <div class="field"><label class="label" for="e-nomor-<?= $id ?>">Nomor urut</label><input class="input" id="e-nomor-<?= $id ?>" name="nomor_urut" type="number" min="1" value="<?= e($k['nomor_urut']) ?>" required></div>
                        <div class="field"><label class="label" for="e-nama-<?= $id ?>">Nama kandidat</label><input class="input" id="e-nama-<?= $id ?>" name="nama" value="<?= e($k['nama']) ?>" maxlength="150" required></div>
                        <div class="field"><label class="label" for="e-foto-<?= $id ?>">URL/path foto</label><input class="input" id="e-foto-<?= $id ?>" name="foto_url" value="<?= e($k['foto_url'] ?? '') ?>" maxlength="500"></div>
                        <div class="field candidate-form__wide"><label class="label" for="e-visi-<?= $id ?>">Visi</label><textarea class="textarea" id="e-visi-<?= $id ?>" name="visi" maxlength="5000" required><?= e($k['visi'] ?? '') ?></textarea></div>
                        <?php if ($has_misi && $has_misi_status): ?>
                        <div class="field"><label class="label" for="misi-<?= $id ?>">Misi (satu poin per baris)</label>
                            <select class="select mb-4" data-draft-select data-target="misi-<?= $id ?>" data-status="status-<?= $id ?>"><option value="">Isi dengan draf contoh (opsional)…</option><option value="komunikasi">Komunikasi dan transparansi</option><option value="terukur">Program kerja terukur</option><option value="potensi">Pengembangan potensi anggota</option><option value="kolaborasi">Kolaborasi dan inklusivitas</option></select>
                            <textarea class="textarea" id="misi-<?= $id ?>" name="misi" maxlength="5000"><?= e($k['misi'] ?? '') ?></textarea>
                        </div>
                        <div class="field"><label class="label" for="status-<?= $id ?>">Status misi</label><select class="select" id="status-<?= $id ?>" name="misi_status"><option value="draft" <?= $misiStatus === 'draft' ? 'selected' : '' ?>>Draf (belum tampil)</option><option value="approved" <?= $misiStatus === 'approved' ? 'selected' : '' ?>>Disetujui (tampil)</option><option value="none" <?= $misiStatus === 'none' ? 'selected' : '' ?>>Belum diisi</option></select></div>
                        <?php endif; ?>
                        <?php if ($has_program_kerja && $has_program_kerja_status): ?>
                        <div class="field"><label class="label" for="program-<?= $id ?>">Program kerja (satu poin per baris)</label><textarea class="textarea" id="program-<?= $id ?>" name="program_kerja" maxlength="5000"><?= e($k['program_kerja'] ?? '') ?></textarea></div>
                        <div class="field"><label class="label" for="program-status-<?= $id ?>">Status program kerja</label><select class="select" id="program-status-<?= $id ?>" name="program_kerja_status"><option value="draft" <?= $programStatus === 'draft' ? 'selected' : '' ?>>Draf (belum tampil)</option><option value="approved" <?= $programStatus === 'approved' ? 'selected' : '' ?>>Disetujui (tampil)</option><option value="none" <?= $programStatus === 'none' ? 'selected' : '' ?>>Belum diisi</option></select></div>
                        <?php else: ?><input type="hidden" name="program_kerja" value=""><?php endif; ?>
                        <div class="candidate-form__submit"><button type="submit" class="btn btn--secondary btn--sm"><i data-lucide="save" class="icon" aria-hidden="true"></i>Simpan perubahan</button></div>
                    </form>
                </details>
                <div class="cand-admin__actions">
                    <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $id ?>"><button type="submit" class="btn btn--danger btn--sm" data-confirm="Hapus kandidat <?= e($k['nama']) ?>? Jika kandidat belum memiliki suara, datanya dihapus permanen. Jika sudah memiliki suara, kandidat hanya dinonaktifkan agar hasil dan riwayat suara tetap utuh." data-confirm-tone="danger" data-confirm-title="Hapus kandidat?" data-confirm-ok="Lanjutkan">Hapus kandidat</button></form>
                </div>
            </article>
            <?php endforeach; ?>
            <?php if (!$rows): ?><p class="empty"><i data-lucide="users" class="icon" aria-hidden="true"></i>Belum ada kandidat di kategori ini.</p><?php endif; ?>
        </div>
    </section>
<?php endforeach; ?>
</div>
<?php require '../includes/admin_bottom.php'; ?>
