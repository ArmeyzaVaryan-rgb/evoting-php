/* navigation.js: menu mobile, penanda aktif, Escape, klik di luar. */
(function () {
    'use strict';
    var toggle = document.querySelector('[data-nav-toggle]');
    var nav = document.querySelector('[data-nav]');
    if (!toggle || !nav) { return; }

    var mq = window.matchMedia('(max-width: 900px)');

    function setOpen(open, returnFocus) {
        nav.classList.toggle('is-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute('aria-label', open ? 'Tutup menu' : 'Buka menu');
        if (!open && returnFocus) { toggle.focus(); }
    }
    function isOpen() { return nav.classList.contains('is-open'); }

    toggle.addEventListener('click', function () { setOpen(!isOpen()); });

    nav.addEventListener('click', function (ev) {
        if (ev.target.closest('a')) { setOpen(false); }
    });

    document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape' && isOpen()) { setOpen(false, true); }
    });

    document.addEventListener('click', function (ev) {
        if (isOpen() && !nav.contains(ev.target) && !toggle.contains(ev.target)) { setOpen(false); }
    });

    var onChange = function () { if (!mq.matches) { setOpen(false); } };
    if (mq.addEventListener) { mq.addEventListener('change', onChange); } else if (mq.addListener) { mq.addListener(onChange); }
})();
