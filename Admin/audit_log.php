<?php 
require '../config.php'; 
require_once '../includes/helpers.php';
require_login('admin');

$per_page = 25;

function valid_date($s) {
    $d = DateTime::createFromFormat('Y-m-d', (string)$s);
    return ($d && $d->format('Y-m-d') === $s) ? $s : '';
}

/* Pilihan filter diambil dari data aktual (bukan daftar karangan). */
$actions = $pdo->query("SELECT DISTINCT action FROM audit_logs ORDER BY action ASC")->fetchAll(PDO::FETCH_COLUMN);
$actors  = $pdo->query("SELECT DISTINCT u.id, u.nama FROM audit_logs a JOIN users u ON a.user_id = u.id ORDER BY u.nama ASC")->fetchAll();
$actor_ids = array_map('intval', array_column($actors, 'id'));

$q      = ev_substr(trim((string)($_GET['q'] ?? '')), 100);
$from   = valid_date($_GET['from'] ?? '');
$to     = valid_date($_GET['to'] ?? '');
$action = in_array(($_GET['action'] ?? ''), $actions, true) ? $_GET['action'] : '';
$actor  = in_array((int)($_GET['actor'] ?? 0), $actor_ids, true) ? (int)$_GET['actor'] : 0;
$page   = max(1, (int)($_GET['page'] ?? 1));

$date_error = '';
if ($from && $to && $to < $from) { $date_error = 'Tanggal akhir tidak boleh lebih awal dari tanggal awal. Filter tanggal akhir diabaikan.'; $to = ''; }

$where = []; $params = [];
if ($q !== '') {
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $where[] = "(a.description LIKE ? OR u.nama LIKE ? OR a.ip_address LIKE ? OR a.action LIKE ?)";
    array_push($params, $like, $like, $like, $like);
}
if ($from)   { $where[] = "a.created_at >= ?"; $params[] = $from . ' 00:00:00'; }
if ($to)     { $where[] = "a.created_at < ?";  $params[] = date('Y-m-d', strtotime($to . ' +1 day')) . ' 00:00:00'; }
if ($action !== '') { $where[] = "a.action = ?"; $params[] = $action; }
if ($actor)  { $where[] = "a.user_id = ?"; $params[] = $actor; }
$sql_where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$cnt = $pdo->prepare("SELECT COUNT(*) FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id $sql_where");
$cnt->execute($params);
$total = (int)$cnt->fetchColumn();
$pages = max(1, (int)ceil($total / $per_page));
$page = min($page, $pages);
$offset = ($page - 1) * $per_page;

$stmt = $pdo->prepare("SELECT a.*, u.nama AS user_nama FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id $sql_where ORDER BY a.id DESC LIMIT " . (int)$per_page . " OFFSET " . (int)$offset);
$stmt->execute($params);
$logs = $stmt->fetchAll();

$filters = ['q' => $q, 'from' => $from, 'to' => $to, 'action' => $action, 'actor' => $actor ?: ''];
function audit_qs(array $filters, array $extra = [], array $drop = []): string {
    $p = array_merge($filters, $extra);
    foreach ($drop as $k) { unset($p[$k]); }
    return http_build_query(array_filter($p, function ($v) { return $v !== '' && $v !== null && $v !== 0; }));
}

$badge = [
    'LOGIN' => 'badge--ok', 'LOGOUT' => 'badge--muted', 'VOTE_CAST' => 'badge--navy',
    'RESET_VOTE' => 'badge--danger', 'TOGGLE_ACTIVE' => 'badge--gold', 'EDIT_CANDIDATE' => 'badge--gold',
];

function render_results() {
    global $logs, $total, $pages, $page, $per_page, $offset, $filters, $actors, $date_error, $badge;
    $chips = [];
    if ($filters['q'] !== '')      { $chips[] = ['Kata kunci: ' . $filters['q'], 'q']; }
    if ($filters['from'] !== '')   { $chips[] = ['Dari ' . date('d/m/Y', strtotime($filters['from'])), 'from']; }
    if ($filters['to'] !== '')     { $chips[] = ['Sampai ' . date('d/m/Y', strtotime($filters['to'])), 'to']; }
    if ($filters['action'] !== '') { $chips[] = ['Aksi: ' . $filters['action'], 'action']; }
    if ($filters['actor'] !== '')  {
        $nm = ''; foreach ($actors as $a) { if ((int)$a['id'] === (int)$filters['actor']) { $nm = $a['nama']; } }
        $chips[] = ['Pelaku: ' . $nm, 'actor'];
    }
    ?>
<div id="audit-results-inner" data-audit-inner>
    <?php if ($date_error): ?><div class="alert alert--gold mb-4" role="alert"><i data-lucide="calendar-x" class="icon" aria-hidden="true"></i><span class="alert__body"><?= e($date_error) ?></span></div><?php endif; ?>
    <?php if ($chips): ?>
    <div class="chips" aria-label="Filter aktif">
        <?php foreach ($chips as [$text, $key]): ?>
            <a class="chip" href="?<?= e(audit_qs($filters, [], [$key])) ?>" data-ajax aria-label="Hapus filter <?= e($text) ?>"><?= e($text) ?><i data-lucide="x" class="icon" aria-hidden="true"></i></a>
        <?php endforeach; ?>
        <a class="chip" href="audit_log.php" data-ajax data-reset>Bersihkan semua</a>
    </div>
    <?php endif; ?>

    <div class="results__meta" aria-live="polite">
        <span><?php if ($total): ?>Menampilkan <b><?= $offset + 1 ?>–<?= $offset + count($logs) ?></b> dari <b><?= $total ?></b> catatan<?= $chips ? ' sesuai filter' : '' ?>.<?php else: ?>0 catatan ditemukan.<?php endif; ?></span>
    </div>

    <div class="table-wrap">
        <table class="table table--stack">
            <thead><tr><th>Waktu</th><th>Pelaku</th><th>Aksi</th><th>Deskripsi</th><th>IP</th></tr></thead>
            <tbody>
            <?php foreach ($logs as $l): $ts = strtotime($l['created_at']); ?>
                <tr>
                    <td data-label="Waktu"><span class="table__time"><?= e(date('d/m/Y', $ts)) ?></span><span class="table__sub"><?= e(date('H:i:s', $ts)) ?></span></td>
                    <td data-label="Pelaku"><b><?= e($l['user_nama'] ?? 'System') ?></b></td>
                    <td data-label="Aksi"><span class="badge badge--code <?= $badge[$l['action']] ?? 'badge--muted' ?>"><?= e($l['action']) ?></span></td>
                    <td data-label="Deskripsi"><?= e($l['description']) ?></td>
                    <td data-label="IP" class="mono"><?= e($l['ip_address']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$logs): ?>
                <tr class="table-empty-row"><td colspan="5" class="table-empty"><i data-lucide="search-x" class="icon icon--lg" aria-hidden="true" style="margin:0 auto 8px"></i><?= $chips ? 'Tidak ada catatan yang cocok dengan filter.' : 'Belum ada aktivitas tercatat.' ?></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pages > 1): ?>
    <nav class="pager" aria-label="Halaman audit log">
        <span>Halaman <?= $page ?> dari <?= $pages ?></span>
        <div class="pager__list">
            <a class="pager__link" data-ajax href="?<?= e(audit_qs($filters, ['page' => $page - 1])) ?>" <?= $page <= 1 ? 'aria-disabled="true" tabindex="-1"' : '' ?> aria-label="Halaman sebelumnya">‹</a>
            <?php
            $show = array_unique(array_filter([1, $page - 1, $page, $page + 1, $pages], function ($n) use ($pages) { return $n >= 1 && $n <= $pages; }));
            sort($show); $prev = 0;
            foreach ($show as $n):
                if ($n - $prev > 1): ?><span class="pager__link" aria-hidden="true" style="border:0;background:none">…</span><?php endif; ?>
                <a class="pager__link" data-ajax href="?<?= e(audit_qs($filters, ['page' => $n])) ?>" <?= $n === $page ? 'aria-current="page"' : '' ?>><?= $n ?></a>
            <?php $prev = $n; endforeach; ?>
            <a class="pager__link" data-ajax href="?<?= e(audit_qs($filters, ['page' => $page + 1])) ?>" <?= $page >= $pages ? 'aria-disabled="true" tabindex="-1"' : '' ?> aria-label="Halaman berikutnya">›</a>
        </div>
    </nav>
    <?php endif; ?>
</div>
<?php
}

/* Permintaan AJAX: kirim hanya bagian hasil. */
if (($_GET['partial'] ?? '') === '1') {
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-store');
    render_results();
    exit;
}

$page_title = 'Audit log'; $page_sub = 'Pantau seluruh aktivitas di sistem. Catatan tidak dapat diubah dari halaman ini.'; $active = 'audit_log.php';
$page_scripts = ['audit-log'];
require '../includes/admin_top.php';
?>
<form method="get" action="audit_log.php" class="card filters reveal" style="--i:1" data-audit-filter aria-label="Filter audit log">
    <div class="filters__grid">
        <div class="filters__col-4">
            <label class="label" for="f-q">Cari</label>
            <div class="input-wrap"><i data-lucide="search" class="icon" aria-hidden="true"></i>
                <input id="f-q" class="input input--icon" type="search" name="q" value="<?= e($q) ?>" maxlength="100" placeholder="Pelaku, deskripsi, atau IP"></div>
        </div>
        <div class="filters__col-2">
            <label class="label" for="f-from">Dari tanggal</label>
            <input id="f-from" class="input" type="date" name="from" value="<?= e($from) ?>">
        </div>
        <div class="filters__col-2">
            <label class="label" for="f-to">Sampai tanggal</label>
            <input id="f-to" class="input" type="date" name="to" value="<?= e($to) ?>" <?= $from ? 'min="' . e($from) . '"' : '' ?>>
        </div>
        <div class="filters__col-2">
            <label class="label" for="f-action">Jenis aktivitas</label>
            <select id="f-action" class="select" name="action">
                <option value="">Semua</option>
                <?php foreach ($actions as $a): ?><option value="<?= e($a) ?>" <?= $a === $action ? 'selected' : '' ?>><?= e($a) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="filters__col-2">
            <label class="label" for="f-actor">Pelaku</label>
            <select id="f-actor" class="select" name="actor">
                <option value="">Semua</option>
                <?php foreach ($actors as $a): ?><option value="<?= (int)$a['id'] ?>" <?= (int)$a['id'] === $actor ? 'selected' : '' ?>><?= e($a['nama']) ?></option><?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="filters__actions">
        <button type="submit" class="btn btn--secondary btn--sm"><i data-lucide="filter" class="icon" aria-hidden="true"></i>Terapkan filter</button>
        <a href="audit_log.php" class="btn btn--ghost btn--sm" data-reset><i data-lucide="rotate-ccw" class="icon" aria-hidden="true"></i>Reset</a>
    </div>
</form>

<div class="results mt-5" id="audit-results" data-audit-results>
    <?php render_results(); ?>
</div>
<?php require '../includes/admin_bottom.php'; ?>
