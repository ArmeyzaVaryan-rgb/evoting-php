/* candidate-details.js: panel detail kandidat (visi dan misi) sebagai dialog aksesibel.
   Membuka/menutup panel tidak pernah mengirim suara. */
(function () {
    'use strict';
    var EV = window.EVoting || (window.EVoting = {});

    function init() {
        var modal = document.getElementById('candidate-modal');
        var dataEl = document.getElementById('candidate-data');
        if (!modal || !dataEl) { return; }

        var data = {};
        try { data = JSON.parse(dataEl.textContent); } catch (err) { data = {}; }

        var dialog = modal.querySelector('.modal__dialog');
        var photo = document.getElementById('cm-photo');
        var number = document.getElementById('cm-number');
        var title = document.getElementById('cm-title');
        var cat = document.getElementById('cm-cat');
        var body = document.getElementById('cm-body');
        var selectBtn = document.getElementById('cm-select');
        var opener = null, currentId = null, closeTimer = null;

        function el(tag, cls, text) {
            var n = document.createElement(tag);
            if (cls) { n.className = cls; }
            if (text != null) { n.textContent = text; }
            return n;
        }
        function block(iconName, heading) {
            var wrap = el('section', 'detail-block');
            var h = el('h4');
            var i = document.createElement('i'); i.setAttribute('data-lucide', iconName); i.className = 'icon'; i.setAttribute('aria-hidden', 'true');
            h.appendChild(i); h.appendChild(document.createTextNode(heading));
            wrap.appendChild(h);
            return wrap;
        }

        function fill(c) {
            photo.textContent = '';
            var ph = el('span', null, (c.nama || '?').charAt(0).toUpperCase()); ph.setAttribute('aria-hidden', 'true');
            photo.appendChild(ph);
            if (c.foto) {
                var img = document.createElement('img');
                img.alt = 'Foto ' + c.nama; img.src = c.foto;
                img.addEventListener('error', function () { img.classList.add('is-broken'); });
                photo.appendChild(img);
            }
            number.textContent = 'No. ' + c.nomor;
            title.textContent = c.nama;
            cat.textContent = 'Calon ' + c.kategori;

            body.textContent = '';
            var visi = block('target', 'Visi');
            visi.appendChild(el('p', null, c.visi || 'Visi belum tersedia.'));
            body.appendChild(visi);

            var misi = block('list-checks', 'Misi');
            if (c.misi && c.misi.length) {
                var ol = el('ol', 'mission-list');
                c.misi.forEach(function (m) { ol.appendChild(el('li', null, m)); });
                misi.appendChild(ol);
            } else {
                var note = el('p', 'alert alert--info');
                note.textContent = 'Misi resmi kandidat ini belum dipublikasikan oleh panitia.';
                misi.appendChild(note);
            }
            body.appendChild(misi);
            body.scrollTop = 0;
            EV.icons();
        }

        function updateSelectBtn() {
            var radio = document.getElementById('cand-' + currentId);
            var on = !!(radio && radio.checked);
            selectBtn.querySelector('span').textContent = on ? 'Kandidat ini sudah dipilih' : 'Pilih kandidat ini';
            selectBtn.setAttribute('aria-pressed', on ? 'true' : 'false');
        }

        function focusables() {
            return Array.prototype.slice.call(modal.querySelectorAll('button, [href], input, [tabindex]:not([tabindex="-1"])'))
                .filter(function (n) { return !n.disabled && n.offsetParent !== null; });
        }

        function open(id, trigger) {
            var c = data['c' + id];
            if (!c) { return; }
            clearTimeout(closeTimer);
            opener = trigger; currentId = id;
            fill(c); updateSelectBtn();
            modal.hidden = false;
            void modal.offsetWidth;                 // paksa reflow agar transisi berjalan
            modal.classList.add('is-open');
            document.body.classList.add('is-locked');
            dialog.focus();
        }

        function close() {
            if (modal.hidden) { return; }
            modal.classList.remove('is-open');
            document.body.classList.remove('is-locked');
            closeTimer = setTimeout(function () { modal.hidden = true; }, EV.reducedMotion ? 0 : 280);
            if (opener && typeof opener.focus === 'function') { opener.focus({ preventScroll: true }); }
            opener = null; currentId = null;
        }

        document.addEventListener('click', function (ev) {
            var btn = ev.target.closest('[data-detail-id]');
            if (btn) { ev.preventDefault(); open(btn.getAttribute('data-detail-id'), btn); return; }
            if (ev.target.closest('[data-modal-close]') && !modal.hidden) { close(); }
        });

        /* Tombol pilih memakai kandidat yang benar-benar sedang dibuka (berdasarkan id). */
        selectBtn.addEventListener('click', function () {
            var radio = document.getElementById('cand-' + currentId);
            if (radio && !radio.checked) { radio.click(); }
            close();
        });

        document.addEventListener('keydown', function (ev) {
            if (modal.hidden) { return; }
            if (ev.key === 'Escape') { ev.preventDefault(); close(); return; }
            if (ev.key !== 'Tab') { return; }
            var f = focusables();
            if (!f.length) { ev.preventDefault(); return; }
            var first = f[0], last = f[f.length - 1];
            if (ev.shiftKey && (document.activeElement === first || document.activeElement === dialog)) { ev.preventDefault(); last.focus(); }
            else if (!ev.shiftKey && document.activeElement === last) { ev.preventDefault(); first.focus(); }
        });
    }

    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', init); } else { init(); }
})();
