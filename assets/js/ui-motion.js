/* ui-motion.js: interaksi visual ringan yang terpisah dari logika voting. */
(function () {
    'use strict';
    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* Stagger ringan: anak dari [data-stagger] mendapat --i berurutan. */
    document.querySelectorAll('[data-stagger]').forEach(function (parent) {
        Array.prototype.forEach.call(parent.children, function (child, i) {
            child.classList.add('reveal');
            child.style.setProperty('--i', Math.min(i, 8));
        });
    });

    /* Hitung naik untuk angka statistik: <span data-count="120">. */
    function countUp(el) {
        var target = parseInt(el.getAttribute('data-count'), 10);
        if (!isFinite(target)) { return; }
        if (reduced || target < 2) { el.textContent = target; return; }
        var start = null, dur = 900;
        function step(ts) {
            if (start === null) { start = ts; }
            var p = Math.min((ts - start) / dur, 1);
            var eased = 1 - Math.pow(1 - p, 3);
            el.textContent = Math.round(target * eased);
            if (p < 1) { requestAnimationFrame(step); } else { el.textContent = target; }
        }
        el.textContent = '0';
        requestAnimationFrame(step);
    }
    window.EVoting = window.EVoting || {};
    window.EVoting.countUp = countUp;
    document.querySelectorAll('[data-count]').forEach(countUp);
})();
