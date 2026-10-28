/* audit-log.js: filter, pencarian, reset, dan pagination audit log tanpa reload penuh.
   Server tetap menjadi satu-satunya sumber data (filter diproses di PHP dengan prepared statement). */
(function () {
    'use strict';
    var EV = window.EVoting || (window.EVoting = {});
    var form = document.querySelector('[data-audit-filter]');
    var box = document.querySelector('[data-audit-results]');
    if (!form || !box) { return; }

    var from = form.elements.from, to = form.elements.to, qInput = form.elements.q;
    var controller = null, debounce = null;

    function params() {
        var p = new URLSearchParams();
        Array.prototype.forEach.call(form.elements, function (f) {
            if (f.name && f.value !== '' && f.type !== 'submit') { p.set(f.name, f.value); }
        });
        return p;
    }

    function syncDateBounds() {
        to.min = from.value || '';
        if (from.value && to.value && to.value < from.value) {
            to.setCustomValidity('Tanggal akhir tidak boleh lebih awal dari tanggal awal.');
        } else { to.setCustomValidity(''); }
    }

    function setForm(search) {
        var p = new URLSearchParams(search);
        ['q', 'from', 'to', 'action', 'actor'].forEach(function (n) { if (form.elements[n]) { form.elements[n].value = p.get(n) || ''; } });
        syncDateBounds();
    }

    function showError(msg) {
        var old = box.querySelector('[data-load-error]'); if (old) { old.remove(); }
        var a = document.createElement('div');
        a.className = 'alert alert--danger mb-4'; a.setAttribute('role', 'alert'); a.setAttribute('data-load-error', '');
        a.textContent = msg + ' ';
        var retry = document.createElement('button'); retry.type = 'button'; retry.className = 'btn-link'; retry.textContent = 'Muat ulang halaman';
        retry.addEventListener('click', function () { window.location.reload(); });
        a.appendChild(retry);
        box.insertBefore(a, box.firstChild);
    }

    function load(search, push) {
        if (controller) { controller.abort(); }
        controller = new AbortController();
        var ctl = controller, timedOut = false;
        var timer = setTimeout(function () { timedOut = true; ctl.abort(); }, 15000);
        box.classList.add('is-loading'); box.setAttribute('aria-busy', 'true');

        var url = 'audit_log.php' + (search ? '?' + search : '');
        var partial = 'audit_log.php?' + (search ? search + '&' : '') + 'partial=1';

        fetch(partial, { credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' }, signal: ctl.signal })
            .then(function (res) {
                if (!res.ok) { throw new Error('status'); }
                return res.text();
            })
            .then(function (html) {
                if (html.indexOf('data-audit-inner') === -1) { throw new Error('invalid'); }
                box.innerHTML = html;
                EV.icons();
                if (push) { history.pushState({ s: search }, '', url); }
            })
            .catch(function (err) {
                if (err.name === 'AbortError' && !timedOut) { return; }
                showError(timedOut ? 'Permintaan terlalu lama.' : 'Data audit log tidak dapat dimuat.');
            })
            .finally(function () {
                clearTimeout(timer);
                if (ctl === controller) { box.classList.remove('is-loading'); box.removeAttribute('aria-busy'); }
            });
    }

    from.addEventListener('change', syncDateBounds);
    to.addEventListener('change', syncDateBounds);

    form.addEventListener('submit', function (ev) {
        ev.preventDefault();
        syncDateBounds();
        if (!form.reportValidity()) { return; }
        load(params().toString(), true);
    });

    /* Pencarian teks: terapkan otomatis setelah jeda mengetik. */
    qInput.addEventListener('input', function () {
        clearTimeout(debounce);
        debounce = setTimeout(function () { if (form.checkValidity()) { load(params().toString(), true); } }, 450);
    });

    /* Reset (tombol dan chip "Bersihkan semua"). */
    document.addEventListener('click', function (ev) {
        var reset = ev.target.closest('[data-reset]');
        if (reset) { ev.preventDefault(); form.reset(); setForm(''); Array.prototype.forEach.call(form.querySelectorAll('input:not([type=submit]), select'), function (f) { f.value = ''; }); syncDateBounds(); load('', true); return; }
        var link = ev.target.closest('a[data-ajax]');
        if (link && box.contains(link)) {
            if (link.getAttribute('aria-disabled') === 'true') { ev.preventDefault(); return; }
            ev.preventDefault();
            var search = (link.getAttribute('href') || '').replace(/^\?/, '');
            setForm(search); load(search, true);
            box.scrollIntoView({ behavior: EV.reducedMotion ? 'auto' : 'smooth', block: 'start' });
        }
    });

    window.addEventListener('popstate', function () {
        var s = window.location.search.replace(/^\?/, '');
        setForm(s); load(s, false);
    });
    syncDateBounds();
})();
