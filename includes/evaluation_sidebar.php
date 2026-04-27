<?php
$currentEvaluationPage = basename($_SERVER['PHP_SELF']);
$currentModule = $_GET['module'] ?? '';

function evaluationIsActive($file, $currentEvaluationPage, $currentModule = '') {
    if ($file === 'dashboard.php') {
        return $currentEvaluationPage === 'dashboard.php' && $currentModule === 'evaluation'
            ? 'sidebar-link active'
            : 'sidebar-link';
    }

    return $file === $currentEvaluationPage ? 'sidebar-link active' : 'sidebar-link';
}
?>

<aside class="sidebar">
    <div class="sidebar-top">
        <div class="sidebar-brand">
            <span>EE</span> <strong>Evaluation</strong>
        </div>
    </div>

    <nav class="sidebar-nav">
        <a href="lms_sync.php" class="<?= evaluationIsActive('lms_sync.php', $currentEvaluationPage); ?>">LMS Sync</a>
        <a href="full_sync.php" class="<?= evaluationIsActive('full_sync.php', $currentEvaluationPage); ?>">Full Sync</a>
        <a href="report.php" class="<?= evaluationIsActive('report.php', $currentEvaluationPage); ?>">Report</a>


        <?php if (($_SESSION['role'] ?? '') === 'hr'): ?>
            <div class="sidebar-divider"></div>

            <a href="../list.php" class="sidebar-link">
                Switch to Recruitment
            </a>
        <?php endif; ?>

        <a href="../../auth/logout.php" class="btn btn-danger sidebar-logout js-confirm-logout">Logout</a>
    </nav>
</aside>