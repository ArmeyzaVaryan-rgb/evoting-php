/* voting.js: pemilihan kandidat, konfirmasi, loading state, dan notifikasi keberhasilan.
   Notifikasi sukses HANYA tampil setelah server mengembalikan {ok:true}. */
(function () {
    'use strict';
    var EV = window.EVoting || (window.EVoting = {});

    var SUCCESS_HTML =
        '<div class="success-check" aria-hidden="true"><svg viewBox="0 0 104 104">' +
        '<circle class="success-check__circle" cx="52" cy="52" r="46"></circle>' +
        '<path class="success-check__tick" d="M32 54l14 14 27-30"></path></svg></div>' +
        '<h2 class="swal2-title" style="display:block;margin-top:.8rem">Terima kasih, suara Anda telah tercatat.</h2>' +
        '<p>Pilihan Anda telah berhasil diproses. Terima kasih telah berpartisipasi dalam pemilihan.</p>';

    function showSuccess() {
        return EV.swal({
            html: SUCCESS_HTML,
            showConfirmButton: true,
            confirmButtonText: 'Selesai',
            allowOutsideClick: false,
            allowEscapeKey: false,
            focusConfirm: true
        }).then(function () { window.location.reload(); });
    }
    EV.showVoteSuccess = showSuccess;

    function showError(message, opts) {
        opts = opts || {};
        return EV.swal({
            icon: 'error',
            iconColor: '#B42318',
            title: 'Suara belum terkirim',
            text: message,
            confirmButtonText: opts.reload ? 'Muat ulang halaman' : 'Mengerti',
            showCancelButton: !!opts.reload,
            cancelButtonText: 'Tutup'
        }).then(function (r) { if (opts.reload && r.isConfirmed) { window.location.reload(); } });
    }

    function init() {
        /* Alur flash (cadangan tanpa JavaScript): tampilkan sukses setelah redirect dari server. */
        if (document.querySelector('[data-vote-success]')) { showSuccess(); }

        var form = document.querySelector('[data-vote-form]');
        if (!form) { return; }

        var openBtn = document.querySelector('[data-vote-open]');
        var counter = document.querySelector('[data-selected-count]');
        var dots = document.querySelectorAll('.vote-bar__dots i');
        var groups = Array.prototype.slice.call(form.querySelectorAll('[data-group]'));
        var busy = false;

        function picked(group) {
            var r = group.querySelector('input[type="radio"]:checked');
            return r ? { id: r.value, name: r.dataset.nama, number: r.dataset.nomor } : null;
        }

        function sync() {
            var count = 0;
            groups.forEach(function (g, i) {
                var chosen = picked(g);
                if (chosen) { count++; }
                g.querySelectorAll('[data-candidate]').forEach(function (card) {
                    var on = card.querySelector('input').checked;
                    card.classList.toggle('is-selected', on);
                    var st = card.querySelector('[data-state]');
                    if (st) { st.textContent = on ? 'Dipilih' : 'Ketuk kartu untuk memilih'; }
                });
                var badge = g.querySelector('[data-group-count]');
                if (badge) {
                    badge.textContent = chosen ? 'No. ' + chosen.number + ' dipilih' : 'Belum dipilih';
                    badge.className = 'badge vote-group__count ' + (chosen ? 'badge--gold' : 'badge--muted');
                }
                if (dots[i]) { dots[i].classList.toggle('is-on', !!chosen); }
            });
            if (counter) { counter.textContent = count; }
            if (openBtn) { openBtn.disabled = busy || count !== groups.length; }
        }
        form.addEventListener('change', sync);
        sync();

        function summaryHTML() {
            var html = '<dl class="swal-summary">';
            groups.forEach(function (g) {
                var c = picked(g);
                html += '<div><dt>' + EV.esc(g.dataset.label) + '</dt><dd>No. ' + EV.esc(c.number) + ' · ' + EV.esc(c.name) + '</dd></div>';
            });
            return html + '</dl>';
        }

        function send() {
            busy = true; sync();
            openBtn.classList.add('is-loading');
            EV.swal({
                title: 'Mengirim suara…',
                text: 'Mohon tunggu, jangan tutup halaman ini.',
                allowOutsideClick: false, allowEscapeKey: false, showConfirmButton: false,
                didOpen: function () { window.Swal.showLoading(); }
            });

            var data = new FormData(form);
            data.append('submit_vote', '1');

            EV.fetchJSON(form.getAttribute('action') || window.location.href, {
                timeout: 25000,
                init: { method: 'POST', body: data }
            }).then(function (res) {
                if (res.ok === true) { return showSuccess(); }
                var message = typeof res.message === 'string' && res.message ? res.message : 'Terjadi kesalahan saat memproses suara.';
                return showError(message, { reload: res.code === 'already_voted' || res.code === 'csrf' });
            }).catch(function (err) {
                var uncertain = err.code === 'timeout' || err.code === 'network' || err.code === 'invalid';
                var msg = uncertain
                    ? 'Kami belum menerima konfirmasi dari server. Muat ulang halaman untuk memeriksa apakah suara Anda sudah tercatat sebelum mencoba lagi.'
                    : err.message;
                return showError(msg, { reload: uncertain });
            }).finally(function () {
                busy = false;
                openBtn.classList.remove('is-loading');
                sync();
            });
        }

        openBtn.addEventListener('click', function () {
            if (busy || openBtn.disabled) { return; }
            EV.swal({
                icon: 'question',
                iconColor: '#CC9933',
                title: 'Kirim suara ini?',
                html: '<p>Suara yang sudah dikirim tidak dapat diubah.</p>' + summaryHTML(),
                showCancelButton: true,
                confirmButtonText: 'Ya, kirim suara',
                cancelButtonText: 'Ubah pilihan'
            }).then(function (r) { if (r.isConfirmed) { send(); } });
        });

        /* Cegah submit langsung (mis. tombol Enter) tanpa konfirmasi. */
        form.addEventListener('submit', function (ev) { ev.preventDefault(); });
    }

    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }
})();
