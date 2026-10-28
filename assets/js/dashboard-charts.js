/* dashboard-charts.js: seluruh grafik dashboard admin (Chart.js).
   Data berasal dari Admin/api_stats.php (agregat saja, tanpa catatan suara individual). */
(function () {
    'use strict';
    var EV = window.EVoting || (window.EVoting = {});
    var root = document.querySelector('[data-charts]');
    if (!root) { return; }

    var PALETTE = ['#134158', '#CC9933', '#1D5866', '#E3B551', '#3E7F8C', '#8A6310', '#7FA9B3', '#F0D9A0', '#0B2A3A', '#B8862B'];
    var NAVY = '#134158', MUTED = '#4A6573';

    var q = function (sel) { return root.querySelector(sel); };
    var els = {
        tabs: q('[data-election-tabs]'), updated: q('[data-updated]'), error: q('[data-chart-error]'), errorText: q('[data-chart-error-text]'),
        skeleton: q('[data-skeleton]'), dBox: q('[data-doughnut-box]'), bBox: q('[data-bar-box]'), empty: q('[data-empty]'),
        legend: q('[data-legend]'), detail: q('[data-detail]'), hint: q('[data-detail-hint]'),
        tlCard: q('[data-timeline-card]')
    };
    var refreshBtns = root.querySelectorAll('[data-refresh]');

    var state = { data: null, key: null, selected: null, hidden: {}, charts: {}, controller: null, first: true };
    var nf = new Intl.NumberFormat('id-ID');

    if (typeof window.Chart === 'undefined') {
        showError('Library grafik gagal dimuat. Periksa koneksi internet lalu muat ulang halaman.');
        if (els.skeleton) { els.skeleton.hidden = true; }
        return;
    }
    var Chart = window.Chart;
    Chart.defaults.font.family = '"Plus Jakarta Sans", system-ui, sans-serif';
    Chart.defaults.color = MUTED;
    if (EV.reducedMotion) { Chart.defaults.animation = false; } else { Chart.defaults.animation.duration = 800; }

    function showError(msg) { els.errorText.textContent = msg; els.error.hidden = false; }
    function trunc(s, n) { s = String(s); return s.length > n ? s.slice(0, n - 1) + '…' : s; }
    function label(c) { return 'No. ' + c.nomor + ' ' + c.nama; }

    /* Teks total di tengah donat, ikut diperbarui saat data berubah. */
    var centerText = {
        id: 'centerText',
        afterDraw: function (chart) {
            var t = chart.$center; var meta = chart.getDatasetMeta(0);
            if (!t || !meta.data.length) { return; }
            var ctx = chart.ctx, x = meta.data[0].x, y = meta.data[0].y;
            ctx.save(); ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
            ctx.fillStyle = NAVY; ctx.font = '800 30px ' + Chart.defaults.font.family; ctx.fillText(nf.format(t.total), x, y - 8);
            ctx.fillStyle = MUTED; ctx.font = '600 12px ' + Chart.defaults.font.family; ctx.fillText('total suara', x, y + 16);
            ctx.restore();
        }
    };

    function validate(d) {
        if (!d || d.ok !== true || !Array.isArray(d.elections) || !d.elections.length) { throw new Error('Format data tidak valid.'); }
        d.elections.forEach(function (el) {
            if (!el || !Array.isArray(el.candidates) || typeof el.key !== 'string') { throw new Error('Format data tidak valid.'); }
            el.candidates.forEach(function (c) { if (!isFinite(c.votes)) { throw new Error('Format data tidak valid.'); } });
        });
        return d;
    }

    function current() {
        var list = state.data.elections;
        for (var i = 0; i < list.length; i++) { if (list[i].key === state.key) { return list[i]; } }
        state.key = list[0].key; return list[0];
    }

    function hiddenSet() { return (state.hidden[state.key] = state.hidden[state.key] || {}); }

    function buildTabs() {
        els.tabs.textContent = '';
        state.data.elections.forEach(function (el) {
            var b = document.createElement('button');
            b.type = 'button'; b.className = 'seg__btn'; b.textContent = el.label;
            b.setAttribute('aria-pressed', el.key === state.key ? 'true' : 'false');
            b.addEventListener('click', function () { state.key = el.key; state.selected = null; buildTabs(); render(); });
            els.tabs.appendChild(b);
        });
    }

    function updateParticipation(p) {
        if (!p) { return; }
        document.querySelectorAll('[data-stat]').forEach(function (n) {
            var k = n.getAttribute('data-stat');
            if (p[k] != null && isFinite(p[k])) { n.textContent = p[k]; }
        });
        var bar = document.querySelector('[data-progress]');
        if (bar) {
            bar.setAttribute('aria-valuenow', p.percent);
            var fill = bar.querySelector('.progress__fill'); if (fill) { fill.style.setProperty('--w', p.percent + '%'); }
        }
    }

    function ensureCharts() {
        if (!state.charts.doughnut) {
            els.dBox.hidden = false;
            state.charts.doughnut = new Chart(root.querySelector('#chart-doughnut'), {
                type: 'doughnut',
                data: { labels: [], datasets: [{ data: [], backgroundColor: [], borderColor: '#fff', borderWidth: 3, hoverOffset: 8, offset: [] }] },
                options: {
                    responsive: true, maintainAspectRatio: false, cutout: '64%',
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: function (c) {
                            var cand = current().candidates[c.dataIndex];
                            return ' ' + nf.format(cand.votes) + ' suara (' + cand.percent + '%)';
                        } } }
                    },
                    onClick: function (e, items) { if (items.length) { select(items[0].index); } }
                },
                plugins: [centerText]
            });
        }
        if (!state.charts.bar) {
            els.bBox.hidden = false;
            state.charts.bar = new Chart(root.querySelector('#chart-bar'), {
                type: 'bar',
                data: { labels: [], datasets: [{ label: 'Suara', data: [], backgroundColor: [], borderRadius: 6, maxBarThickness: 28 }] },
                options: {
                    indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: {
                            title: function (items) { var c = current().candidates[items[0].dataIndex]; return label(c); },
                            label: function (c) { var cand = current().candidates[c.dataIndex]; return ' ' + nf.format(cand.votes) + ' suara (' + cand.percent + '%)'; }
                        } }
                    },
                    scales: {
                        x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#EDF2F5' }, title: { display: true, text: 'Jumlah suara' } },
                        y: { grid: { display: false }, ticks: { callback: function (v) { return trunc(this.getLabelForValue(v), 24); } } }
                    },
                    onClick: function (e, items) { if (items.length) { select(items[0].index); } }
                }
            });
        }
    }

    function applyVisibility() {
        var set = hiddenSet(), ch = state.charts.doughnut;
        current().candidates.forEach(function (c, i) {
            var want = !set[c.id];
            if (ch.getDataVisibility(i) !== want) { ch.toggleDataVisibility(i); }
        });
    }

    function renderLegend(el) {
        var set = hiddenSet();
        els.legend.textContent = '';
        el.candidates.forEach(function (c, i) {
            var li = document.createElement('li'); li.className = 'legend__item';
            var t = document.createElement('button'); t.type = 'button'; t.className = 'legend__toggle';
            t.setAttribute('aria-pressed', set[c.id] ? 'false' : 'true');
            t.setAttribute('aria-label', 'Tampilkan atau sembunyikan ' + label(c));
            var sw = document.createElement('span'); sw.className = 'legend__swatch'; sw.style.background = PALETTE[i % PALETTE.length];
            var nm = document.createElement('span'); nm.className = 'legend__name'; nm.textContent = label(c);
            var vl = document.createElement('span'); vl.className = 'legend__val'; vl.textContent = nf.format(c.votes) + ' · ' + c.percent + '%';
            t.appendChild(sw); t.appendChild(nm); t.appendChild(vl);
            t.addEventListener('click', function () {
                set[c.id] = !set[c.id];
                t.setAttribute('aria-pressed', set[c.id] ? 'false' : 'true');
                applyVisibility(); state.charts.doughnut.update();
            });
            var info = document.createElement('button'); info.type = 'button'; info.className = 'legend__info';
            info.setAttribute('aria-label', 'Lihat ringkasan ' + label(c));
            var ic = document.createElement('i'); ic.setAttribute('data-lucide', 'info'); ic.className = 'icon'; info.appendChild(ic);
            info.addEventListener('click', function () { select(i); });
            li.appendChild(t); li.appendChild(info); els.legend.appendChild(li);
        });
        EV.icons();
    }

    function select(i) {
        state.selected = i;
        var el = current(), c = el.candidates[i];
        if (!c) { return; }
        var rank = 1; el.candidates.forEach(function (o) { if (o.votes > c.votes) { rank++; } });
        var lead = Math.max.apply(null, el.candidates.map(function (o) { return o.votes; }));

        els.detail.textContent = '';
        var h = document.createElement('h4'); h.textContent = label(c); els.detail.appendChild(h);
        var dl = document.createElement('dl');
        [['Suara', nf.format(c.votes)], ['Persentase', c.percent + '%'], ['Peringkat', '#' + rank]].forEach(function (r) {
            var d = document.createElement('div'), dt = document.createElement('dt'), dd = document.createElement('dd');
            dt.textContent = r[0]; dd.textContent = r[1]; d.appendChild(dt); d.appendChild(dd); dl.appendChild(d);
        });
        els.detail.appendChild(dl);
        var note = document.createElement('p'); note.className = 'muted'; note.style.marginTop = '10px'; note.style.fontSize = '.82rem';
        note.textContent = c.votes === lead ? 'Perolehan suara tertinggi saat ini.' : 'Selisih ' + nf.format(lead - c.votes) + ' suara dari perolehan tertinggi.';
        els.detail.appendChild(note);
        els.detail.hidden = false; els.hint.hidden = true;

        var offs = el.candidates.map(function (_, k) { return k === i ? 12 : 0; });
        state.charts.doughnut.data.datasets[0].offset = offs;
        state.charts.doughnut.update();
    }

    function renderTimeline(el) {
        if (!els.tlCard) { return; }
        var tl = el.timeline;
        if (!Array.isArray(tl) || !tl.length) { els.tlCard.hidden = true; return; }
        els.tlCard.hidden = false;
        var labels = tl.map(function (t) { var p = String(t.date).split('-'); return p.length === 3 ? p[2] + '/' + p[1] : t.date; });
        var vals = tl.map(function (t) { return t.votes; });
        if (!state.charts.line) {
            state.charts.line = new Chart(root.querySelector('#chart-line'), {
                type: 'line',
                data: { labels: labels, datasets: [{ label: 'Suara masuk', data: vals, borderColor: '#134158', backgroundColor: 'rgba(204,153,51,.18)', fill: true, tension: .3, pointBackgroundColor: '#CC9933', pointRadius: 4 }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } } }
            });
        } else {
            state.charts.line.data.labels = labels; state.charts.line.data.datasets[0].data = vals; state.charts.line.update();
        }
    }

    function render() {
        var el = current(), total = el.total_votes;
        els.skeleton.hidden = true;
        if (!el.candidates.length || total === 0) {
            els.empty.hidden = false; els.dBox.hidden = true; els.bBox.hidden = true;
            els.legend.textContent = ''; els.detail.hidden = true; els.hint.hidden = true;
            if (els.tlCard) { els.tlCard.hidden = true; }
            var msg = els.empty.querySelector('p'); if (msg) { msg.firstChild.textContent = el.candidates.length ? 'Belum ada suara masuk.' : 'Belum ada kandidat.'; }
            return;
        }
        els.empty.hidden = true; els.hint.hidden = state.selected != null && !els.detail.hidden;
        ensureCharts();
        els.dBox.hidden = false; els.bBox.hidden = false;

        var d = state.charts.doughnut;
        d.$center = { total: total };
        d.data.labels = el.candidates.map(label);
        d.data.datasets[0].data = el.candidates.map(function (c) { return c.votes; });
        d.data.datasets[0].backgroundColor = el.candidates.map(function (_, i) { return PALETTE[i % PALETTE.length]; });
        d.data.datasets[0].offset = el.candidates.map(function () { return 0; });
        applyVisibility(); d.update();

        var b = state.charts.bar;
        els.bBox.style.height = Math.max(220, el.candidates.length * 46 + 56) + 'px';
        b.data.labels = el.candidates.map(label);
        b.data.datasets[0].data = el.candidates.map(function (c) { return c.votes; });
        b.data.datasets[0].backgroundColor = el.candidates.map(function (_, i) { return PALETTE[i % PALETTE.length]; });
        b.update();

        renderLegend(el);
        if (state.selected != null && el.candidates[state.selected]) { select(state.selected); } else { els.detail.hidden = true; els.hint.hidden = false; }
        renderTimeline(el);
    }

    function setLoading(on) {
        root.setAttribute('aria-busy', on ? 'true' : 'false');
        refreshBtns.forEach(function (b) { b.disabled = on; b.classList.toggle('is-loading', on); });
    }

    function load() {
        if (state.controller) { state.controller.abort(); }          // batalkan permintaan yang bertumpuk
        var controller = state.controller = new AbortController();
        setLoading(true); els.error.hidden = true;
        EV.fetchJSON(root.getAttribute('data-endpoint'), { signal: controller.signal, timeout: 12000 })
            .then(validate)
            .then(function (data) {
                if (controller !== state.controller) { return; }
                state.data = data;
                if (!state.key) { state.key = data.elections[0].key; }
                updateParticipation(data.participation);
                buildTabs(); render();
                els.updated.textContent = 'Diperbarui ' + new Date().toLocaleTimeString('id-ID');
            })
            .catch(function (err) {
                if (err.code === 'abort' && controller !== state.controller) { return; }
                els.skeleton.hidden = true;
                var msg = err.code === 'timeout' ? 'Permintaan terlalu lama. Coba lagi.'
                    : err.code === 'network' ? 'Tidak dapat terhubung ke server.'
                    : 'Data statistik tidak dapat dimuat.';
                showError(msg);
            })
            .finally(function () { if (controller === state.controller) { setLoading(false); } });
    }

    refreshBtns.forEach(function (b) { b.addEventListener('click', load); });
    load();
})();
