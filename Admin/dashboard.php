<?php 
require '../config.php'; 
require_login('voter');

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT has_voted FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$kahim = $pdo->query("SELECT * FROM candidates WHERE election_id = 1")->fetchAll();
$senator = $pdo->query("SELECT * FROM candidates WHERE election_id = 2")->fetchAll();

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_vote'])) {
    if ($user['has_voted']) {
        $error_msg = "Anda sudah memilih!";
    } else {
        $kahim_id = $_POST['kahim_id'] ?? null;
        $senator_id = $_POST['senator_id'] ?? null;

        if (!$kahim_id || !$senator_id) {
            $error_msg = "Pilih Kahim dan Senator terlebih dahulu!";
        } else {
            $saved = false;
            try {
                $pdo->beginTransaction();
                $hash = hash('sha256', $user_id . time() . 'rahasia');

                $stmt = $pdo->prepare("INSERT INTO votes (election_id, voter_id, candidate_id, vote_hash) VALUES (1, ?, ?, ?)");
                $stmt->execute([$user_id, $kahim_id, $hash]);

                $stmt = $pdo->prepare("INSERT INTO votes (election_id, voter_id, candidate_id, vote_hash) VALUES (2, ?, ?, ?)");
                $stmt->execute([$user_id, $senator_id, hash('sha256', $hash . 'sen')]);

                $stmt = $pdo->prepare("UPDATE users SET has_voted = 1 WHERE id = ?");
                $stmt->execute([$user_id]);

                log_activity($pdo, $user_id, 'VOTE_CAST', 'Memilih Kahim & Senator');
                
                $pdo->commit();
                $saved = true;
            } catch (Exception $e) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                error_log('[evoting] gagal menyimpan suara: ' . $e->getMessage()); // detail hanya di log server
                $error_msg = "Suara belum dapat disimpan. Silakan coba lagi atau hubungi panitia.";
            }

            // Hanya setelah commit berhasil: simpan penanda sekali-tampil lalu redirect (PRG),
            // sehingga refresh halaman tidak mengirim ulang form dan modal sukses muncul tepat sekali.
            if ($saved) {
                $_SESSION['vote_receipt'] = substr($hash, 0, 16);
                header("Location: dashboard.php");
                exit;
            }
        }
    }
}

$vote_receipt = '';
if (!empty($_SESSION['vote_receipt'])) {
    $vote_receipt = $_SESSION['vote_receipt'];
    unset($_SESSION['vote_receipt']);
}

$title = 'Surat suara · E-Voting Kampus'; $base = '../';
require '../includes/head.php';

$groups = [
    ['kahim_id', 'Ketua Himpunan', 'crown', 'kahim', $kahim],
    ['senator_id', 'Senator', 'graduation-cap', 'senator', $senator],
];
$nama = $_SESSION['nama'] ?? '';
?>
<body class="app-body pb-32 font-sans" x-data="{ kahim: '', senator: '', open: false, sending: false }"
      @keydown.escape.window="if (!sending) open = false" @pageshow.window="if ($event.persisted) sending = false">
<a href="#konten" class="skip-link">Lewati ke konten</a>

    <header class="topnav on-dark">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3.5">
            <div class="flex items-center gap-3 font-extrabold">
                <span class="brand-mark" style="width:2.1rem;height:2.1rem;border-radius:10px"><i data-lucide="vote" class="h-4 w-4"></i></span>Surat suara
            </div>
            <div class="flex items-center gap-4 text-sm">
                <span class="hidden sm:inline" style="color:#C5D6DE">Halo, <b class="text-white"><?= e($nama) ?></b></span>
                <a href="logout.php" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 font-bold text-white transition hover:bg-white/10" style="border:1px solid rgba(255,255,255,.25)">
                    <i data-lucide="log-out" class="h-4 w-4"></i>Keluar
                </a>
            </div>
        </div>
    </header>

    <main id="konten" class="mx-auto mt-8 max-w-5xl px-4">

        <!-- Identitas & status hak pilih -->
        <section class="card reveal mb-6 flex flex-wrap items-center gap-4 p-5" aria-label="Identitas pemilih">
            <span class="avatar-sm" aria-hidden="true"><?= e(initial($nama)) ?></span>
            <div class="min-w-0 flex-1">
                <p class="muted text-xs font-semibold">Pemilih terdaftar</p>
                <p class="truncate text-lg font-extrabold text-ink"><?= e($nama) ?></p>
                <p class="muted text-xs">NIM <span class="mono font-semibold"><?= e($_SESSION['nim'] ?? '-') ?></span></p>
            </div>
            <?php if ($user['has_voted']): ?>
                <span class="badge badge-ok"><i data-lucide="check-circle-2" class="h-3.5 w-3.5"></i>Hak pilih sudah digunakan</span>
            <?php else: ?>
                <span class="badge badge-gold"><i data-lucide="clock" class="h-3.5 w-3.5"></i>Belum memilih</span>
            <?php endif; ?>
        </section>

        <?php if ($error_msg): ?>
            <div role="alert" class="alert alert-err mb-6"><i data-lucide="circle-alert" class="h-4 w-4"></i><span><?= e($error_msg) ?></span></div>
        <?php endif; ?>

        <?php if ($vote_receipt): ?>
            <!-- Dirender server hanya setelah transaksi voting berhasil; dibaca oleh assets/js/app.js -->
            <div id="flash-data" hidden data-type="vote-success"
                 data-title="Terima kasih, suara Anda telah tercatat."
                 data-text="Pilihan Anda telah berhasil diproses. Terima kasih telah berpartisipasi dalam pemilihan."
                 data-receipt="<?= e($vote_receipt) ?>"></div>
        <?php endif; ?>

        <?php if ($user['has_voted']): ?>
            <section class="card reveal mx-auto mt-10 max-w-md p-10 text-center" style="--d:.1s">
                <svg class="check mx-auto" viewBox="0 0 96 96" width="80" height="80" aria-hidden="true">
                    <circle cx="48" cy="48" r="44" fill="#E7F5EE"/>
                    <circle class="check-ring" cx="48" cy="48" r="44" fill="none" stroke="#1E8E5A" stroke-width="4"/>
                    <path class="check-mark" d="M28 50l14 14 27-30" fill="none" stroke="#1E8E5A" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <h1 class="mt-5 text-2xl font-extrabold text-ink">Terima kasih sudah memilih</h1>
                <div class="divider-gold mx-auto mt-3"></div>
                <p class="muted mt-4">Hak pilihmu sudah digunakan. Suaramu bersifat rahasia dan tidak bisa diubah.</p>
            </section>
        <?php else: ?>
            <div class="reveal mb-6" style="--d:.05s">
                <h1 class="text-3xl font-extrabold tracking-tight text-ink">Pilih satu kandidat di tiap kategori</h1>
                <div class="divider-gold mt-3"></div>
                <p class="muted mt-3">Ketuk kartu kandidat untuk memilih. Kamu bisa mengganti pilihan sebelum mengirim.</p>
            </div>

            <ol class="steps card reveal mb-10 p-5" style="--d:.1s">
                <li class="step"><b>1</b>Pilih satu kandidat di tiap kategori</li>
                <li class="step"><b>2</b>Tinjau pilihanmu</li>
                <li class="step"><b>3</b>Kirim suara (tidak dapat diubah)</li>
            </ol>

            <form method="POST" @submit="sending = true">
                <input type="hidden" name="submit_vote" value="1">

                <?php foreach ($groups as [$field, $label, $icon, $model, $rows]): ?>
                <h2 class="section-title text-ink">
                    <span class="section-icon"><i data-lucide="<?= $icon ?>" class="h-4 w-4"></i></span>Pilih <?= e($label) ?>
                    <span class="badge badge-ok" x-show="<?= $model ?>" x-cloak><i data-lucide="check" class="h-3.5 w-3.5"></i>Sudah dipilih</span>
                </h2>
                <div class="mb-10 grid gap-4 md:grid-cols-2">
                    <?php foreach($rows as $i => $k): ?>
                    <label class="cand reveal" style="--d:<?= number_format(0.12 + $i * 0.06, 2) ?>s">
                        <input type="radio" name="<?= $field ?>" value="<?= (int)$k['id'] ?>" data-nama="<?= e($k['nama']) ?>"
                               @change="<?= $model ?> = $el.dataset.nama" class="cand-input" required>
                        <div class="cand-card">
                            <div class="avatar">
                                <span aria-hidden="true"><?= e(initial($k['nama'])) ?></span>
                                <?php if (!empty($k['foto_url'])): ?>
                                <img src="<?= e($k['foto_url']) ?>" alt="Foto <?= e($k['nama']) ?>" onerror="this.style.visibility='hidden'">
                                <?php endif; ?>
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="num">No. <?= e($k['nomor_urut']) ?></span>
                                <h3 class="mt-2 text-lg font-extrabold leading-tight text-ink"><?= e($k['nama']) ?></h3>
                                <p class="muted mt-1 line-clamp-3 text-sm"><span class="font-bold">Visi:</span> <?= e($k['visi']) ?></p>
                            </div>
                            <span class="cand-tick" aria-hidden="true"><i data-lucide="check" class="h-4 w-4"></i></span>
                            <span class="cand-flag">Dipilih</span>
                        </div>
                    </label>
                    <?php endforeach; ?>
                    <?php if (!$rows): ?><p class="empty md:col-span-2">Belum ada kandidat di kategori ini.</p><?php endif; ?>
                </div>
                <?php endforeach; ?>

                <!-- Bilah kirim -->
                <div class="votebar">
                    <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4">
                        <p class="muted text-sm"><b class="text-ink" x-text="(kahim ? 1 : 0) + (senator ? 1 : 0)">0</b> dari 2 kategori dipilih</p>
                        <button type="button" @click="open = true" :disabled="!kahim || !senator" class="btn btn-primary">
                            Tinjau dan kirim suara<i data-lucide="arrow-right" class="h-4 w-4"></i>
                        </button>
                    </div>
                </div>

                <!-- Konfirmasi: form baru dikirim setelah pemilih menekan "Kirim suara" -->
                <div x-show="open" x-cloak class="modal" role="dialog" aria-modal="true" aria-labelledby="judul-konfirmasi"
                     x-effect="if (open) $nextTick(() => $refs.ubah && $refs.ubah.focus())">
                    <div class="modal-backdrop" @click="if (!sending) open = false" x-transition.opacity></div>
                    <div class="modal-panel" x-show="open" x-transition>
                        <h3 id="judul-konfirmasi" class="text-xl font-extrabold text-ink">Kirim suara ini?</h3>
                        <p class="muted mt-1 text-sm">Suara yang sudah dikirim tidak dapat diubah.</p>
                        <dl class="summary mt-5">
                            <div><dt class="muted">Ketua Himpunan</dt><dd class="mt-0.5 font-extrabold text-ink" x-text="kahim"></dd></div>
                            <div><dt class="muted">Senator</dt><dd class="mt-0.5 font-extrabold text-ink" x-text="senator"></dd></div>
                        </dl>
                        <div class="mt-6 flex gap-3">
                            <button type="button" x-ref="ubah" @click="open = false" :disabled="sending" class="btn btn-ghost flex-1">Ubah pilihan</button>
                            <button type="submit" :disabled="sending" :class="{ 'is-loading': sending }" :aria-busy="sending" class="btn btn-primary flex-1">
                                <span x-text="sending ? 'Mengirim…' : 'Kirim suara'">Kirim suara</span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </main>

    <footer class="site-footer">&copy; <?= date('Y') ?> E-Voting Kampus</footer>
</body>
</html>
