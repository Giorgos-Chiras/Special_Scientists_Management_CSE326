<?php
require_once __DIR__ . '/db.php';

$stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");

$settings = [];
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>

<footer class="app-footer">
    <div class="footer-content">
        <span><?= htmlspecialchars($settings['system_name'] ?? 'System'); ?></span>
        <span><?= htmlspecialchars($settings['footer_text'] ?? ''); ?></span>
        <span><?= htmlspecialchars($settings['contact_email'] ?? ''); ?></span>
    </div>
</footer>

<?php if (!empty($_SESSION['flash'])): ?>
    <div
            id="flash-data"
            data-type="<?= htmlspecialchars($_SESSION['flash']['type'] ?? 'success'); ?>"
            data-title="<?= htmlspecialchars($_SESSION['flash']['title'] ?? 'Done'); ?>"
            data-text="<?= htmlspecialchars($_SESSION['flash']['text'] ?? ''); ?>"
    ></div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/admin-alerts.js?v=<?= time(); ?>"></script>
</body>
</html>