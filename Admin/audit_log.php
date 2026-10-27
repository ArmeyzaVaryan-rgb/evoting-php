<?php 
require '../config.php'; 
require_login('admin');

$logs = $pdo->query("SELECT a.*, u.nama as user_nama FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.id DESC LIMIT 100")->fetchAll();

$page_title = 'Audit log'; $page_sub = '100 aktivitas terakhir di sistem.'; $active = 'audit_log.php';
require '../includes/admin_top.php';

$warna = [
    'LOGIN' => 'bg-emerald-50 text-emerald-700', 'LOGOUT' => 'bg-slate-100 text-slate-600',
    'VOTE_CAST' => 'bg-brand-50 text-brand-700', 'RESET_VOTE' => 'bg-red-50 text-red-600',
    'TOGGLE_ACTIVE' => 'bg-amber-50 text-amber-700',
];
?>
<div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
    <table class="w-full min-w-[760px] text-left text-sm">
        <thead class="border-b border-slate-200 bg-slate-50 text-slate-500">
            <tr>
                <th class="p-4 font-semibold">Waktu</th>
                <th class="p-4 font-semibold">Pelaku</th>
                <th class="p-4 font-semibold">Aksi</th>
                <th class="p-4 font-semibold">Deskripsi</th>
                <th class="p-4 font-semibold">IP</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php foreach($logs as $l): ?>
            <tr class="transition hover:bg-slate-50">
                <td class="whitespace-nowrap p-4 text-xs text-slate-500"><?= e($l['created_at']) ?></td>
                <td class="p-4 font-semibold text-ink"><?= e($l['user_nama'] ?? 'System') ?></td>
                <td class="p-4"><span class="rounded-md px-2 py-1 font-mono text-xs font-bold <?= $warna[$l['action']] ?? 'bg-slate-100 text-slate-600' ?>"><?= e($l['action']) ?></span></td>
                <td class="p-4 text-slate-600"><?= e($l['description']) ?></td>
                <td class="p-4 font-mono text-xs text-slate-400"><?= e($l['ip_address']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$logs): ?><tr><td colspan="5" class="p-10 text-center text-slate-500">Belum ada aktivitas tercatat.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php require '../includes/admin_bottom.php'; ?>