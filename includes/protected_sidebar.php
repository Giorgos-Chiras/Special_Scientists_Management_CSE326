<?php
$currentProtectedPage = basename($_SERVER['PHP_SELF']);

function protectedIsActive($file, $currentProtectedPage) {
    return $file === $currentProtectedPage ? 'sidebar-link active' : 'sidebar-link';
}
?>
<aside class="sidebar">
    <div class="sidebar-top">
        <div class="sidebar-brand">
            <span>EE</span> <strong>Recruitment</strong>
        </div>
    </div>

    <nav class="sidebar-nav">
        <a href="list.php" class="<?= protectedIsActive('list.php', $currentProtectedPage); ?>">Applications</a>
        <a href="dashboard.php" class="<?= protectedIsActive('dashboard.php', $currentProtectedPage); ?>">Account Settings</a>

        <?php if (($_SESSION['role'] ?? '') === 'hr'): ?>
            <div class="sidebar-divider"></div>

            <a href="../modules/evaluation/lms_sync.php" class="sidebar-link">
                Switch to Enrollment
            </a>
        <?php endif; ?>

        <a href="../auth/logout.php" class="btn btn-danger sidebar-logout js-confirm-logout">Logout</a>
</aside>