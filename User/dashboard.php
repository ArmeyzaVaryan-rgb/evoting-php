<?php 
require '../config.php'; 
require_once '../includes/helpers.php';
require_login('voter');

class AlreadyVotedException extends RuntimeException {}

$user_id = (int)$_SESSION['user_id'];
$userColumns = ['has_voted'];
if (db_has_column($pdo, 'users', 'is_active')) { $userColumns[] = 'is_active'; }
if (db_has_column($pdo, 'users', 'deleted_at')) { $userColumns[] = 'deleted_at'; }
$stmt = $pdo->prepare("SELECT " . implode(', ', $userColumns) . " FROM users WHERE id = ? AND role = 'voter' LIMIT 1");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
if (!$user || (array_key_exists('is_active', $user) && (int)$user['is_active'] !== 1) || !empty($user['deleted_at'])) {
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) { session_regenerate_id(true); }
    header('Location: ../login.php', true, 303);
    exit;
}

$candidateActiveFilter = db_has_column($pdo, 'candidates', 'is_active') ? ' AND is_active = 1' : '';
$kahim = $pdo->query('SELECT * FROM candidates WHERE election_id = 1' . $candidateActiveFilter . ' ORDER BY nomor_urut ASC')->fetchAll();
$senator = $pdo->query('SELECT * FROM candidates WHERE election_id = 2' . $candidateActiveFilter . ' ORDER BY nomor_urut ASC')->fetchAll();

$wants_json = (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch') || strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
$error_msg = '';
$show_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_vote'])) {
    $status = 200; $code = 'error';

    if (!csrf_valid()) {
        $error_msg = "Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi."; $status = 403; $code = 'csrf';
    } elseif ($user['has_voted']) {
        $error_msg = "Anda sudah memilih!"; $status = 409; $code = 'already_voted';
    } else {
        $kahim_id = $_POST['kahim_id'] ?? null;
        $senator_id = $_POST['senator_id'] ?? null;

        if (!$kahim_id || !$senator_id) {
            $error_msg = "Pilih Kahim dan Senator terlebih dahulu!"; $status = 422; $code = 'incomplete';
        } else {
            // Pastikan kandidat benar-benar milik pemilihan yang bersangkutan.
            $cek = $pdo->prepare('SELECT COUNT(*) FROM candidates WHERE id = ? AND election_id = ?' . $candidateActiveFilter);
            $cek->execute([$kahim_id, 1]);  $ok_k = (int)$cek->fetchColumn() === 1;
            $cek->execute([$senator_id, 2]); $ok_s = (int)$cek->fetchColumn() === 1;

            if (!$ok_k || !$ok_s) {
                $error_msg = "Kandidat yang dipilih tidak valid."; $status = 422; $code = 'invalid_candidate';
            } else {
                try {
                    $pdo->beginTransaction();

                    // Kunci hak pilih secara atomik agar tidak bisa dipakai dua kali (mis. dua tab bersamaan).
                    $lock = $pdo->prepare("UPDATE users SET has_voted = 1 WHERE id = ? AND has_voted = 0");
                    $lock->execute([$user_id]);
                    if ($lock->rowCount() !== 1) { throw new AlreadyVotedException(); }

                    $hash = hash('sha256', $user_id . time() . 'rahasia');

                    $stmt = $pdo->prepare("INSERT INTO votes (election_id, voter_id, candidate_id, vote_hash) VALUES (1, ?, ?, ?)");
                    $stmt->execute([$user_id, $kahim_id, $hash]);

                    $stmt = $pdo->prepare("INSERT INTO votes (election_id, voter_id, candidate_id, vote_hash) VALUES (2, ?, ?, ?)");
                    $stmt->execute([$user_id, $senator_id, hash('sha256', $hash . 'sen')]);

                    log_activity($pdo, $user_id, 'VOTE_CAST', 'Memilih Kahim & Senator');

                    $pdo->commit();
                    $user['has_voted'] = 1;

                    if ($wants_json) { json_out(['ok' => true, 'message' => 'Suara berhasil dicatat.']); }
                    $_SESSION['vote_flash'] = 'ok';
                    header("Location: dashboard.php");
                    exit;
                } catch (AlreadyVotedException $e) {
                    if ($pdo->inTransaction()) { $pdo->rollBack(); }
                    $error_msg = "Anda sudah memilih!"; $status = 409; $code = 'already_voted';
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) { $pdo->rollBack(); }
                    error_log('[e-voting] gagal menyimpan suara: ' . $e->getMessage());
                    $error_msg = "Suara belum dapat disimpan. Silakan coba lagi atau hubungi panitia."; $status = 500; $code = 'server';
                }
            }
        }
    }
    if ($wants_json) { json_out(['ok' => false, 'code' => $code, 'message' => $error_msg], $status); }
}

if (!empty($_SESSION['vote_flash'])) { $show_success = true; unset($_SESSION['vote_flash']); }

/* Samakan resolusi foto kandidat yang disimpan sebagai path relatif.
   Path dari folder proyek (mis. assets/images/x.jpg) perlu naik satu tingkat dari /User/. */
function voter_candidate_photo(string $photo): string {
    $photo = trim($photo);
    if ($photo === '') { return ''; }
    if (preg_match('~^(?:https?:)?//~i', $photo) || str_starts_with($photo, '/')) { return $photo; }
    if (str_starts_with($photo, 'assets/') || str_starts_with($photo, 'uploads/')) { return '../' . $photo; }
    return $photo;
}

/* Data untuk panel detail. Misi hanya tampil bila sudah disetujui panitia. */
function candidate_payload(array $k, string $label): array {
    $status = $k['misi_status'] ?? 'none';
    $misi = null;
    if ($status === 'approved' && trim((string)($k['misi'] ?? '')) !== '') {
        $misi = array_values(array_filter(array_map('trim', preg_split('/\R/u', (string)$k['misi']))));
    }
    $programStatus = (string)($k['program_kerja_status'] ?? 'none');
    $program = null;
    if ($programStatus === 'approved' && trim((string)($k['program_kerja'] ?? '')) !== '') {
        $program = array_values(array_filter(array_map('trim', preg_split('/\R/u', (string)$k['program_kerja']))));
    }
    return [
        'id' => (int)$k['id'], 'kategori' => $label, 'nomor' => (string)$k['nomor_urut'],
        'nama' => (string)$k['nama'], 'foto' => voter_candidate_photo((string)($k['foto_url'] ?? '')), 'visi' => (string)$k['visi'],
        'misi' => $misi, 'misi_status' => $status,
        'program_kerja' => $program, 'program_kerja_status' => $programStatus,
    ];
}

$groups = [
    ['kahim_id', 'Ketua Himpunan', 'Pilih Ketua Himpunan', 'crown', $kahim],
    ['senator_id', 'Senator', 'Pilih Senator', 'graduation-cap', $senator],
];
$payload = [];
foreach ($groups as [$field, $short, $label, $icon, $rows]) { foreach ($rows as $k) { $payload['c' . (int)$k['id']] = candidate_payload($k, $short); } }

$has_voted = !empty($user['has_voted']);
$title = 'Surat suara · E-Voting Kampus'; $base = '../';
$nav_mode = 'voter'; $active = 'dashboard.php';
$header_status = $has_voted ? 'Hak pilih digunakan' : 'Belum memilih'; $header_voted = $has_voted;
$body_class = $has_voted ? '' : 'has-vote-bar';
$page_scripts = $has_voted ? ['voting'] : ['voting', 'candidate-details'];
require '../includes/head.php';
require '../includes/site_header.php';
?>
<main id="konten" class="page">
<div class="container">
    <div class="page-head reveal">
        <div>
            <h1><?= $has_voted ? 'Terima kasih sudah berpartisipasi' : 'Pilih satu kandidat di tiap kategori' ?></h1>
            <p class="page-head__sub"><?= $has_voted ? 'Suara Anda sudah tercatat di sistem.' : 'Ketuk kartu kandidat untuk memilih. Anda bisa mengganti pilihan sebelum mengirim.' ?></p>
        </div>
    </div>

    <?php if ($error_msg): ?>
        <div role="alert" class="alert alert--danger mb-5"><i data-lucide="circle-alert" class="icon" aria-hidden="true"></i><span class="alert__body"><?= e($error_msg) ?></span></div>
    <?php endif; ?>
    <?php if ($show_success): ?><div data-vote-success hidden></div><?php endif; ?>

    <section class="card status-panel <?= $has_voted ? 'status-panel--done' : '' ?> reveal" style="--i:1" aria-label="Status hak pilih">
        <div class="status-panel__main">
            <span class="status-panel__icon"><i data-lucide="<?= $has_voted ? 'badge-check' : 'ticket' ?>" class="icon icon--lg" aria-hidden="true"></i></span>
            <div>
                <h2 style="font-size:1.1rem"><?= $has_voted ? 'Hak pilih sudah digunakan' : 'Hak pilih Anda aktif' ?></h2>
                <p class="muted"><?= e($_SESSION['nama']) ?><?= !empty($_SESSION['nim']) ? ' · NIM ' . e($_SESSION['nim']) : '' ?></p>
            </div>
        </div>
        <span class="badge <?= $has_voted ? 'badge--ok' : 'badge--gold' ?>"><i data-lucide="<?= $has_voted ? 'lock' : 'clock' ?>" class="icon" aria-hidden="true"></i><?= $has_voted ? 'Tidak dapat digunakan kembali' : 'Belum digunakan' ?></span>
    </section>

<?php if ($has_voted): ?>
    <div class="done-card reveal" style="--i:2">
        <div class="success-check success-check--static" aria-hidden="true"><svg viewBox="0 0 104 104"><circle class="success-check__circle" cx="52" cy="52" r="46"/><path class="success-check__tick" d="M32 54l14 14 27-30"/></svg></div>
        <h2 class="mt-4">Terima kasih, suara Anda telah tercatat.</h2>
        <p>Hak pilih Anda sudah digunakan dan tidak dapat digunakan kembali.</p>
    </div>
<?php else: ?>
    <ol class="steps card mt-5 reveal" style="--i:2" aria-label="Langkah pemilihan">
        <li class="step"><span class="step__icon"><i data-lucide="mouse-pointer-click" class="icon" aria-hidden="true"></i></span><span><b>Pilih kandidat</b>Satu untuk Ketua Himpunan dan satu untuk Senator.</span></li>
        <li class="step"><span class="step__icon"><i data-lucide="list-checks" class="icon" aria-hidden="true"></i></span><span><b>Tinjau pilihan</b>Periksa kembali sebelum mengirim.</span></li>
        <li class="step"><span class="step__icon"><i data-lucide="send" class="icon" aria-hidden="true"></i></span><span><b>Kirim suara</b>Suara yang sudah dikirim tidak dapat diubah.</span></li>
    </ol>

    <form method="POST" id="vote-form" class="mt-6" data-vote-form>
        <?= csrf_field() ?>
        <?php foreach ($groups as [$field, $short, $label, $icon, $rows]): ?>
        <section class="vote-group" data-group data-label="<?= e($short) ?>" aria-labelledby="g-<?= e($field) ?>">
            <div class="vote-group__head">
                <span class="vote-group__icon"><i data-lucide="<?= $icon ?>" class="icon icon--lg" aria-hidden="true"></i></span>
                <h2 id="g-<?= e($field) ?>"><?= e($label) ?></h2>
                <span class="badge badge--muted vote-group__count" data-group-count>Belum dipilih</span>
            </div>
            <div class="grid grid--2" data-stagger>
                <?php foreach ($rows as $k): $id = (int)$k['id']; ?>
                <div class="candidate candidate--ballot" data-candidate>
                    <input class="candidate__input" type="radio" name="<?= e($field) ?>" id="cand-<?= $id ?>" value="<?= $id ?>" data-nama="<?= e($k['nama']) ?>" data-nomor="<?= e($k['nomor_urut']) ?>" required>
                    <label class="candidate__body" for="cand-<?= $id ?>">
                        <span class="avatar"><span aria-hidden="true"><?= e(initial_of($k['nama'])) ?></span><?php $candidatePhoto = voter_candidate_photo((string)($k['foto_url'] ?? '')); if ($candidatePhoto !== ''): ?><img src="<?= e($candidatePhoto) ?>" alt="Foto <?= e($k['nama']) ?>" loading="lazy" data-fallback><?php endif; ?></span>
                        <span class="candidate__main">
                            <span class="number-tag">No. <?= e($k['nomor_urut']) ?></span>
                            <span class="candidate__name"><?= e($k['nama']) ?></span>
                            <span class="candidate__visi"><b>Visi:</b> <?= e($k['visi']) ?></span>
                        </span>
                        <span class="candidate__check" aria-hidden="true"><i data-lucide="check" class="icon"></i></span>
                    </label>
                    <div class="candidate__foot">
                        <span class="candidate__state" data-state>Ketuk kartu untuk memilih</span>
                        <button type="button" class="btn btn--ghost btn--sm" data-detail-id="<?= $id ?>" aria-haspopup="dialog">
                            <i data-lucide="file-text" class="icon" aria-hidden="true"></i>Lihat Lebih Detail
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (!$rows): ?><p class="empty" style="grid-column:1/-1"><i data-lucide="users" class="icon" aria-hidden="true"></i>Belum ada kandidat di kategori ini.</p><?php endif; ?>
            </div>
        </section>
        <?php endforeach; ?>
    </form>
<?php endif; ?>
</div>
</main>

<?php if (!$has_voted): ?>
<div class="vote-bar" role="region" aria-label="Ringkasan pilihan">
    <div class="container vote-bar__inner">
        <div class="vote-bar__meter">
            <span class="vote-bar__dots" aria-hidden="true"><?php foreach ($groups as $g): ?><i></i><?php endforeach; ?></span>
            <span class="vote-bar__text" aria-live="polite"><b data-selected-count>0</b> dari <?= count($groups) ?> kategori dipilih</span>
        </div>
        <button type="button" class="btn btn--primary" data-vote-open disabled><i data-lucide="send" class="icon" aria-hidden="true"></i>Tinjau dan kirim suara</button>
    </div>
</div>

<div class="modal" id="candidate-modal" role="dialog" aria-modal="true" aria-labelledby="cm-title" hidden>
    <div class="modal__backdrop" data-modal-close></div>
    <div class="modal__dialog" tabindex="-1">
        <div class="modal__head">
            <div class="avatar avatar--lg" id="cm-photo"></div>
            <div>
                <span class="number-tag" id="cm-number"></span>
                <h2 id="cm-title" style="margin-top:6px"></h2>
                <p class="muted" id="cm-cat"></p>
            </div>
            <button type="button" class="modal__close" data-modal-close aria-label="Tutup detail kandidat"><i data-lucide="x" class="icon" aria-hidden="true"></i></button>
        </div>
        <div class="modal__body" id="cm-body"></div>
        <div class="modal__foot">
            <button type="button" class="btn btn--ghost" data-modal-close>Kembali</button>
            <button type="button" class="btn btn--primary" id="cm-select"><i data-lucide="check" class="icon" aria-hidden="true"></i><span>Pilih kandidat ini</span></button>
        </div>
    </div>
</div>
<script type="application/json" id="candidate-data"><?= json_encode($payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>
<?php endif; ?>
<?php require '../includes/site_footer.php'; ?>
</body>
</html>
