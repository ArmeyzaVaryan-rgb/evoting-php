<?php
// Endpoint JSON statistik pemilihan (hanya admin). Hanya data agregat: tidak ada catatan suara individual.
require '../config.php';
require_once '../includes/helpers.php';

if (($_SESSION['role'] ?? '') !== 'admin' || empty($_SESSION['user_id']) || !admin_session_is_allowed()) {
    json_out(['ok' => false, 'message' => 'Tidak diizinkan.'], 403);
}

try {
    $total_voters  = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='voter'")->fetchColumn();
    $active_voters = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='voter' AND is_active=1")->fetchColumn();
    $voted         = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='voter' AND has_voted=1")->fetchColumn();
    $not_voted     = max(0, $active_voters - $voted);
    $percent       = $active_voters > 0 ? (int)round(($voted / $active_voters) * 100) : 0;

    $elections = [['id' => 1, 'key' => 'kahim', 'label' => 'Ketua Himpunan'], ['id' => 2, 'key' => 'senator', 'label' => 'Senator']];
    $stmt = $pdo->prepare("SELECT c.id, c.nama, c.nomor_urut, COUNT(v.id) AS total
                           FROM candidates c LEFT JOIN votes v ON v.candidate_id = c.id
                           WHERE c.election_id = ? GROUP BY c.id ORDER BY total DESC, c.nomor_urut ASC");

    // Linimasa hanya tersedia jika tabel votes memiliki kolom waktu yang sah.
    $has_time = (bool)$pdo->query("SHOW COLUMNS FROM votes LIKE 'created_at'")->fetch();
    $tl = $has_time ? $pdo->prepare("SELECT DATE(created_at) AS d, COUNT(*) AS n FROM votes WHERE election_id = ? AND created_at IS NOT NULL GROUP BY DATE(created_at) ORDER BY d ASC") : null;

    $out = [];
    foreach ($elections as $el) {
        $stmt->execute([$el['id']]);
        $rows = $stmt->fetchAll();
        $sum = 0; foreach ($rows as $r) { $sum += (int)$r['total']; }
        $cands = [];
        foreach ($rows as $r) {
            $n = (int)$r['total'];
            $cands[] = ['id' => (int)$r['id'], 'nama' => (string)$r['nama'], 'nomor' => (string)$r['nomor_urut'],
                        'votes' => $n, 'percent' => $sum > 0 ? round($n / $sum * 100, 1) : 0];
        }
        $timeline = null;
        if ($tl) {
            $tl->execute([$el['id']]);
            $timeline = array_map(function ($r) { return ['date' => $r['d'], 'votes' => (int)$r['n']]; }, $tl->fetchAll());
        }
        $out[] = ['key' => $el['key'], 'label' => $el['label'], 'total_votes' => $sum, 'candidates' => $cands, 'timeline' => $timeline];
    }

    json_out([
        'ok' => true,
        'generated_at' => date('c'),
        'participation' => ['total_voters' => $total_voters, 'active_voters' => $active_voters, 'voted' => $voted, 'not_voted' => $not_voted, 'percent' => $percent],
        'elections' => $out,
    ]);
} catch (Throwable $e) {
    error_log('[e-voting] api_stats: ' . $e->getMessage());
    json_out(['ok' => false, 'message' => 'Data statistik tidak dapat dimuat.'], 500);
}
