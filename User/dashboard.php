<?php 
require '../config.php'; 
require_login('voter');

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT has_voted FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$kahim = $pdo->query("SELECT * FROM candidates WHERE election_id = 1")->fetchAll();
$senator = $pdo->query("SELECT * FROM candidates WHERE election_id = 2")->fetchAll();

$success_msg = ''; $error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_vote'])) {
    if ($user['has_voted']) {
        $error_msg = "Anda sudah memilih!";
    } else {
        $kahim_id = $_POST['kahim_id'] ?? null;
        $senator_id = $_POST['senator_id'] ?? null;

        if (!$kahim_id || !$senator_id) {
            $error_msg = "Pilih Kahim dan Senator terlebih dahulu!";
        } else {
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
                $success_msg = "Suara berhasil dikirim. Bukti hash: <span class='font-mono text-xs'>".substr($hash, 0, 16)."...</span>";
                $user['has_voted'] = 1;
            } catch (Exception $e) {
                $pdo->rollBack();
                $error_msg = "Gagal: " . $e->getMessage();
            }
        }
    }
}
$title = 'Surat suara · E-Voting Kampus'; $base = '../';
require '../includes/head.php';

$groups = [
    ['kahim_id', 'Pilih Ketua Himpunan', 'crown', 'text-amber-600', 'kahim', $kahim],
    ['senator_id', 'Pilih Senator', 'graduation-cap', 'text-brand-600', 'senator', $senator],
];
?>
<body class="bg-slate-50 pb-28 font-sans text-slate-800" x-data="{ kahim: '', senator: '', open: false }" @keydown.escape.window="open = false">

    <nav class="sticky top-0 z-10 border-b bg-white/90 backdrop-blur">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3">
            <div class="flex items-center gap-2.5 font-bold text-ink">
                <span class="grid h-8 w-8 place-items-center rounded-lg bg-brand-600 text-white"><i data-lucide="vote" class="h-4 w-4"></i></span>Surat suara
            </div>
            <div class="flex items-center gap-4 text-sm">
                <span class="hidden text-slate-500 sm:inline">Halo, <b class="text-ink"><?= e($_SESSION['nama']) ?></b></span>
                <a href="logout.php" class="font-semibold text-red-600 hover:underline">Keluar</a>
            </div>
        </div>
    </nav>

    <div class="mx-auto mt-8 max-w-5xl px-4">
        <?php if($success_msg): ?>
            <div role="status" class="mb-6 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800"><i data-lucide="circle-check" class="mt-0.5 h-4 w-4 shrink-0"></i><span><?= $success_msg ?></span></div>
        <?php endif; ?>
        <?php if($error_msg): ?>
            <div role="alert" class="mb-6 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"><i data-lucide="circle-alert" class="mt-0.5 h-4 w-4 shrink-0"></i><span><?= e($error_msg) ?></span></div>
        <?php endif; ?>

        <?php if($user['has_voted']): ?>
            <div class="mx-auto mt-10 max-w-md rounded-3xl border border-slate-200 bg-white p-10 text-center">
                <span class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-emerald-50 text-emerald-600"><i data-lucide="check" class="h-8 w-8"></i></span>
                <h2 class="mt-5 text-2xl font-extrabold text-ink">Terima kasih sudah memilih</h2>
                <p class="mt-2 text-slate-500">Hak pilihmu sudah digunakan. Suaramu bersifat rahasia dan tidak bisa diubah.</p>
            </div>
        <?php else: ?>
            <div class="mb-8">
                <h1 class="text-3xl font-extrabold tracking-tight text-ink">Pilih satu kandidat di tiap kategori</h1>
                <p class="mt-1 text-slate-500">Ketuk kartu kandidat untuk memilih. Kamu bisa mengganti pilihan sebelum mengirim.</p>
            </div>

            <form method="POST">
                <?php foreach ($groups as [$field, $label, $icon, $color, $model, $rows]): ?>
                <h2 class="mb-4 flex items-center gap-2 text-xl font-bold <?= $color ?>"><i data-lucide="<?= $icon ?>" class="h-5 w-5"></i><?= $label ?></h2>
                <div class="mb-10 grid gap-4 md:grid-cols-2">
                    <?php foreach($rows as $k): ?>
                    <label class="relative block cursor-pointer">
                        <input type="radio" name="<?= $field ?>" value="<?= (int)$k['id'] ?>" data-nama="<?= e($k['nama']) ?>" @change="<?= $model ?> = $el.dataset.nama" class="peer sr-only" required>
                        <div class="flex gap-4 rounded-2xl border-2 border-slate-200 bg-white p-4 transition hover:border-brand-500/50 peer-checked:border-brand-600 peer-checked:bg-brand-50 peer-checked:shadow-lg peer-checked:shadow-brand-600/10 peer-focus-visible:ring-4 peer-focus-visible:ring-brand-100 [&_.tick]:opacity-0 peer-checked:[&_.tick]:opacity-100">
                            <div class="h-24 w-24 shrink-0 overflow-hidden rounded-xl bg-slate-200">
                                <img src="<?= e($k['foto_url']) ?>" alt="Foto <?= e($k['nama']) ?>" class="h-full w-full object-cover" onerror="this.style.visibility='hidden'">
                            </div>
                            <div class="min-w-0 flex-1">
                                <span class="rounded-md bg-brand-50 px-2 py-1 text-xs font-bold text-brand-700">No. <?= e($k['nomor_urut']) ?></span>
                                <h3 class="mt-1.5 text-lg font-bold leading-tight text-ink"><?= e($k['nama']) ?></h3>
                                <p class="mt-1 line-clamp-3 text-sm text-slate-500"><span class="font-semibold">Visi:</span> <?= e($k['visi']) ?></p>
                            </div>
                            <span class="tick grid h-6 w-6 shrink-0 place-items-center rounded-full bg-brand-600 text-white transition"><i data-lucide="check" class="h-3.5 w-3.5"></i></span>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>

                <!-- Bilah kirim -->
                <div class="fixed inset-x-0 bottom-0 z-20 border-t bg-white/95 p-4 backdrop-blur">
                    <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-4">
                        <p class="text-sm text-slate-500"><b class="text-ink" x-text="(kahim ? 1 : 0) + (senator ? 1 : 0)"></b> dari 2 kategori dipilih</p>
                        <button type="button" @click="open = true" :disabled="!kahim || !senator"
                                class="rounded-xl bg-brand-600 px-7 py-3 text-sm font-bold text-white shadow-lg shadow-brand-600/20 transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:shadow-none">
                            Tinjau dan kirim suara
                        </button>
                    </div>
                </div>

                <!-- Modal konfirmasi -->
                <div x-show="open" x-cloak class="fixed inset-0 z-30 grid place-items-center p-4" role="dialog" aria-modal="true" aria-labelledby="judul-konfirmasi">
                    <div class="absolute inset-0 bg-ink/60" @click="open = false" x-transition.opacity></div>
                    <div class="relative w-full max-w-md rounded-3xl bg-white p-7 shadow-2xl" x-show="open" x-transition>
                        <h3 id="judul-konfirmasi" class="text-xl font-extrabold text-ink">Kirim suara ini?</h3>
                        <p class="mt-1 text-sm text-slate-500">Suara yang sudah dikirim tidak dapat diubah.</p>
                        <dl class="mt-5 divide-y divide-slate-100 rounded-xl border border-slate-200 text-sm">
                            <div class="p-4"><dt class="text-slate-500">Ketua Himpunan</dt><dd class="mt-0.5 font-bold text-ink" x-text="kahim"></dd></div>
                            <div class="p-4"><dt class="text-slate-500">Senator</dt><dd class="mt-0.5 font-bold text-ink" x-text="senator"></dd></div>
                        </dl>
                        <div class="mt-6 flex gap-3">
                            <button type="button" @click="open = false" class="flex-1 rounded-xl border border-slate-200 py-3 text-sm font-bold text-slate-600 hover:bg-slate-50">Ubah pilihan</button>
                            <button type="submit" name="submit_vote" value="1" class="flex-1 rounded-xl bg-brand-600 py-3 text-sm font-bold text-white hover:bg-brand-700">Kirim suara</button>
                        </div>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
<script src="https://unpkg.com/lucide@0.453.0"></script>
<script>lucide.createIcons();</script>
</body>
</html>