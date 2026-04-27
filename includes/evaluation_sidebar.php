<?php
$currentEvaluationPage = basename($_SERVER['PHP_SELF']);
$currentModule = $_GET['module'] ?? '';
$role = $_SESSION['role'] ?? '';

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
        <p class="sidebar-text">Enrollment Module</p>
    </div>

    <nav class="sidebar-nav">

        <!-- Everyone sees LMS Sync -->
        <a href="lms_sync.php" class="<?= evaluationIsActive('lms_sync.php', $currentEvaluationPage); ?>">LMS Sync</a>

        <?php if (in_array($role, ['hr', 'admin'], true)): ?>
            <a href="full_sync.php" class="<?= evaluationIsActive('full_sync.php', $currentEvaluationPage); ?>">Full Sync</a>
            <a href="report.php" class="<?= evaluationIsActive('report.php', $currentEvaluationPage); ?>">Report</a>
        <?php endif; ?>

        <?php if ($role === 'hr'): ?>
            <div class="sidebar-divider"></div>

            <a href="../list.php" class="sidebar-link">
                Switch to Recruitment
            </a>
        <?php endif; ?>

        <a href="../../auth/logout.php" class="btn btn-danger sidebar-logout js-confirm-logout">Logout</a>
    </nav>
</aside>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="../../assets/js/chart_utils.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('click', function (event) {
        const logoutLink = event.target.closest('.js-confirm-logout');

        if (!logoutLink) {
            return;
        }

        event.preventDefault();

        Swal.fire({
            title: 'Log out?',
            text: 'You will be signed out.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, log out',
            cancelButtonText: 'Cancel'
        }).then(function (result) {
            if (result.isConfirmed) {
                window.location.href = logoutLink.getAttribute('href');
            }
        });
    });
});
</script>