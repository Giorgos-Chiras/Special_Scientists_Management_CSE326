<?php if (!empty($_SESSION['flash'])): ?>
    <div
        id="flash-data"
        data-type="<?= htmlspecialchars($_SESSION['flash']['type'] ?? 'success'); ?>"
        data-title="<?= htmlspecialchars($_SESSION['flash']['title'] ?? 'Done'); ?>"
        data-text="<?= htmlspecialchars($_SESSION['flash']['text'] ?? ''); ?>"
    ></div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/admin-alerts.js"></script>
</body>
</html>