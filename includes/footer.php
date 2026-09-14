    <!-- Bootstrap 5.3 JS Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom Global JS -->
    <script src="/rental-mobil/assets/js/main.js"></script>
    <?php if (isset($extra_js)): ?>
        <script src="/rental-mobil/assets/js/<?= htmlspecialchars($extra_js) ?>"></script>
    <?php endif; ?>
</body>
</html>
