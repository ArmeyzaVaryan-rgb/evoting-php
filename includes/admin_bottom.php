</main>
    </div>
</div>
<script src="https://unpkg.com/lucide@0.453.0"></script>
<script>
    lucide.createIcons();
    document.addEventListener('click', function (ev) {
        var el = ev.target.closest('[data-confirm]');
        if (el && !confirm(el.dataset.confirm)) ev.preventDefault();
    });
</script>
</body>
</html>