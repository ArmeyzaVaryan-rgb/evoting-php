<?php 
require '../config.php'; 
require_login('admin');
$kahim = $pdo->query("SELECT * FROM candidates WHERE election_id = 1")->fetchAll();
$senator = $pdo->query("SELECT * FROM candidates WHERE election_id = 2")->fetchAll();

$page_title = 'Data kandidat'; $page_sub = 'Kandidat yang tampil di surat suara.'; $active = 'candidate.php';
require '../includes/admin_top.php';

$groups = [
    ['Ketua Himpunan (Kahim)', 'crown', 'text-amber-600', $kahim],
    ['Senator', 'graduation-cap', 'text-brand-600', $senator],
];
?>
<div class="grid gap-10 lg:grid-cols-2">
<?php foreach ($groups as [$label, $icon, $color, $rows]): ?>
    <section>
        <h2 class="mb-4 flex items-center gap-2 text-lg font-bold <?= $color ?>"><i data-lucide="<?= $icon ?>" class="h-5 w-5"></i><?= $label ?></h2>
        <div class="space-y-4">
            <?php foreach ($rows as $k): ?>
            <article class="flex gap-5 rounded-2xl border border-slate-200 bg-white p-5">
                <div class="h-24 w-24 shrink-0 overflow-hidden rounded-xl bg-slate-200">
                    <img src="<?= e($k['foto_url']) ?>" alt="Foto <?= e($k['nama']) ?>" class="h-full w-full object-cover" onerror="this.style.visibility='hidden'">
                </div>
                <div class="min-w-0">
                    <span class="rounded-md bg-brand-50 px-2 py-1 text-xs font-bold text-brand-700">No. <?= e($k['nomor_urut']) ?></span>
                    <h3 class="mt-2 text-lg font-bold text-ink"><?= e($k['nama']) ?></h3>
                    <p class="mt-1 text-sm leading-relaxed text-slate-600"><span class="font-semibold">Visi:</span> <?= e($k['visi']) ?></p>
                </div>
            </article>
            <?php endforeach; ?>
            <?php if (!$rows): ?><p class="rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">Belum ada kandidat di kategori ini.</p><?php endif; ?>
        </div>
    </section>
<?php endforeach; ?>
</div>
<?php require '../includes/admin_bottom.php'; ?>