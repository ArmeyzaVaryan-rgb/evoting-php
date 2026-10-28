<?php
// Panel grafik interaktif (dirender oleh assets/js/dashboard-charts.js dari Admin/api_stats.php).
// Variabel: $show_timeline (bool)
$show_timeline = $show_timeline ?? false;
?>
<section data-charts data-endpoint="api_stats.php" aria-label="Grafik hasil pemilihan">
    <div class="dash-toolbar">
        <div class="row">
            <div class="seg" role="group" aria-label="Pilih pemilihan" data-election-tabs></div>
            <span class="dash-toolbar__meta" data-updated aria-live="polite"></span>
        </div>
        <button type="button" class="btn btn--ghost btn--sm" data-refresh><i data-lucide="refresh-cw" class="icon" aria-hidden="true"></i>Perbarui data</button>
    </div>

    <div class="alert alert--danger mb-4" data-chart-error role="alert" hidden>
        <i data-lucide="circle-alert" class="icon" aria-hidden="true"></i>
        <span class="alert__body" data-chart-error-text>Data tidak dapat dimuat.</span>
        <button type="button" class="btn btn--sm btn--danger" data-refresh>Coba lagi</button>
    </div>

    <div class="chart-grid" data-chart-grid>
        <article class="card">
            <div class="card__head"><h2 class="card__title"><i data-lucide="chart-pie" class="icon" aria-hidden="true"></i>Distribusi suara</h2>
                <button type="button" class="tip" data-tip="Persentase dihitung dari total suara yang masuk pada pemilihan ini." aria-label="Penjelasan persentase">?</button></div>
            <div class="skeleton" style="height:300px" data-skeleton></div>
            <div class="chart-box" data-doughnut-box hidden><canvas id="chart-doughnut" role="img" aria-label="Diagram donat distribusi suara per kandidat"></canvas></div>
            <div class="chart-empty" data-empty hidden><div><i data-lucide="inbox" class="icon" aria-hidden="true"></i><p><b>Belum ada suara masuk.</b></p><p>Grafik akan muncul setelah pemilih mulai memberikan suara.</p></div></div>
            <ul class="legend" data-legend></ul>
        </article>

        <article class="card card--accent">
            <div class="card__head"><h2 class="card__title"><i data-lucide="user-round-search" class="icon" aria-hidden="true"></i>Ringkasan kandidat</h2></div>
            <p class="muted" data-detail-hint>Klik segmen grafik atau tombol detail pada legenda untuk melihat ringkasan kandidat.</p>
            <div class="detail-panel" data-detail hidden></div>
            <div class="mt-5">
                <h3 class="mb-4" style="font-size:.95rem">Perbandingan perolehan suara</h3>
                <div class="chart-box chart-box--bar" data-bar-box hidden><canvas id="chart-bar" role="img" aria-label="Diagram batang perolehan suara per kandidat"></canvas></div>
            </div>
        </article>
    </div>

<?php if ($show_timeline): ?>
    <article class="card mt-5" data-timeline-card hidden>
        <div class="card__head"><h2 class="card__title"><i data-lucide="trending-up" class="icon" aria-hidden="true"></i>Perkembangan suara per hari</h2></div>
        <div class="chart-box" style="height:240px"><canvas id="chart-line" role="img" aria-label="Grafik jumlah suara masuk per hari"></canvas></div>
    </article>
<?php endif; ?>
</section>
