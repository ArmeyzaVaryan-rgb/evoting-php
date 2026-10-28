/* main.js: inisialisasi bersama, utilitas UI, dan fungsi umum.
   Dimuat di semua halaman. Modul lain memakai namespace window.EVoting. */
(function () {
    'use strict';

    var EV = (window.EVoting = window.EVoting || {});
    EV.reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    EV.esc = function (s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    };

    EV.icons = function () {
        if (window.lucide && typeof window.lucide.createIcons === 'function') { window.lucide.createIcons(); }
    };

    /* Konfigurasi dasar SweetAlert2 agar seluruh dialog konsisten dengan tema. */
    EV.swal = function (opts) {
        if (!window.Swal) { return Promise.resolve({ isConfirmed: false, unavailable: true }); }
        var base = {
            buttonsStyling: false,
            heightAuto: false,
            reverseButtons: true,
            customClass: {
                popup: 'swal-evoting',
                confirmButton: 'btn btn--primary',
                cancelButton: 'btn btn--ghost',
                denyButton: 'btn btn--danger'
            }
        };
        return window.Swal.fire(Object.assign(base, opts));
    };

    /* fetch JSON dengan timeout, pembatalan, dan validasi format. */
    EV.fetchJSON = function (url, options) {
        options = options || {};
        var controller = new AbortController();
        var timeout = options.timeout || 15000;
        var timedOut = false;
        var timer = setTimeout(function () { timedOut = true; controller.abort(); }, timeout);
        if (options.signal) { options.signal.addEventListener('abort', function () { controller.abort(); }); }
        var init = Object.assign({ credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'fetch' } }, options.init || {});
        init.signal = controller.signal;
        return fetch(url, init).then(function (res) {
            return res.text().then(function (text) {
                var data = null;
                try { data = JSON.parse(text); } catch (err) { /* ditangani di bawah */ }
                if (data === null || typeof data !== 'object') {
                    var bad = new Error('Respons server tidak valid.'); bad.code = 'invalid'; bad.status = res.status; throw bad;
                }
                data.__status = res.status;
                return data;
            });
        }).catch(function (err) {
            if (err && err.code === 'invalid') { throw err; }
            var e = new Error(timedOut ? 'Permintaan terlalu lama.' : (err && err.name === 'AbortError' ? 'Dibatalkan.' : 'Tidak dapat terhubung ke server.'));
            e.code = timedOut ? 'timeout' : (err && err.name === 'AbortError' ? 'abort' : 'network');
            throw e;
        }).finally(function () { clearTimeout(timer); });
    };

    /* Dialog konfirmasi untuk elemen dengan atribut data-confirm. */
    function ask(el) {
        var d = el.dataset;
        if (!window.Swal) { return Promise.resolve(window.confirm(d.confirm)); }
        var danger = d.confirmTone === 'danger';
        return EV.swal({
            icon: danger ? 'warning' : 'question',
            iconColor: '#CC9933',
            title: d.confirmTitle || 'Konfirmasi',
            text: d.confirm,
            showCancelButton: true,
            confirmButtonText: d.confirmOk || 'Ya, lanjutkan',
            cancelButtonText: 'Batal',
            focusCancel: danger,
            customClass: {
                popup: 'swal-evoting',
                confirmButton: danger ? 'btn btn--danger' : 'btn btn--primary',
                cancelButton: 'btn btn--ghost'
            }
        }).then(function (r) { return !!r.isConfirmed; });
    }
    EV.ask = ask;

    document.addEventListener('click', function (ev) {
        var el = ev.target.closest('a[data-confirm], button[data-confirm]');
        if (!el) { return; }
        ev.preventDefault();
        ask(el).then(function (ok) {
            if (!ok) { return; }
            if (el.tagName === 'A') { window.location.href = el.href; }
            else if (el.form) { el.form.requestSubmit ? el.form.requestSubmit(el) : el.form.submit(); }
        });
    });

    /* Form dengan data-loading: cegah kirim ganda dan tampilkan loading state. */
    document.addEventListener('submit', function (ev) {
        var form = ev.target;
        if (!form.matches || !form.matches('form[data-loading]')) { return; }
        if (form.dataset.submitting === '1') { ev.preventDefault(); return; }
        form.dataset.submitting = '1';
        var btn = form.querySelector('[type="submit"]');
        if (btn) { btn.classList.add('is-loading'); btn.setAttribute('aria-busy', 'true'); setTimeout(function () { btn.disabled = true; }, 0); }
    });
    window.addEventListener('pageshow', function () {
        document.querySelectorAll('form[data-loading]').forEach(function (f) {
            f.dataset.submitting = '0';
            var b = f.querySelector('[type="submit"]');
            if (b) { b.classList.remove('is-loading'); b.removeAttribute('aria-busy'); b.disabled = false; }
        });
    });

    /* Tampilkan/sembunyikan password. */
    document.addEventListener('click', function (ev) {
        var btn = ev.target.closest('[data-toggle-password]');
        if (!btn) { return; }
        var input = document.getElementById(btn.getAttribute('data-toggle-password'));
        if (!input) { return; }
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.textContent = show ? 'Sembunyikan' : 'Lihat';
        btn.setAttribute('aria-pressed', show ? 'true' : 'false');
    });

    /* Pencarian tabel sisi klien (dataset kecil): input[data-table-search="#idTbody"]. */
    function initTableSearch() {
        document.querySelectorAll('[data-table-search]').forEach(function (input) {
            var body = document.querySelector(input.getAttribute('data-table-search'));
            if (!body) { return; }
            var empty = document.querySelector(input.getAttribute('data-empty') || '');
            var counter = document.querySelector(input.getAttribute('data-counter') || '');
            var rows = Array.prototype.slice.call(body.querySelectorAll('tr[data-s]'));
            input.addEventListener('input', function () {
                var q = input.value.toLowerCase().trim(), n = 0;
                rows.forEach(function (tr) {
                    var ok = tr.dataset.s.indexOf(q) > -1;
                    tr.hidden = !ok; if (ok) { n++; }
                });
                if (empty) { empty.hidden = n > 0; }
                if (counter) { counter.textContent = n + ' dari ' + rows.length + ' pemilih ditampilkan'; }
            });
        });
    }

    /* Foto yang gagal dimuat: sembunyikan gambar, inisial pada latar tetap terlihat. */
    function initImageFallback() {
        document.querySelectorAll('img[data-fallback]').forEach(function (img) {
            var broken = function () { img.classList.add('is-broken'); };
            img.addEventListener('error', broken);
            if (img.complete && img.naturalWidth === 0) { broken(); }
        });
    }

    function init() {
        EV.icons();
        initTableSearch();
        initImageFallback();
    }
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }
})();
