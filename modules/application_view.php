<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/crud/applications_crud.php';
require_once __DIR__ . '/../utils/status_utils.php';
require_once __DIR__ . '/../utils/time_utils.php';
require_once __DIR__ . '/../utils/user_utils.php';


$userId = (int) $_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? 'candidate';
$id = (int) ($_GET['id'] ?? 0);

if (!in_array($userRole, ['hr', 'evaluator', 'candidate'], true)) {
    header('Location: list.php');
    exit;
}

if ($id <= 0) {
    $_SESSION['flash'] = [
            'type' => 'error',
            'title' => 'Invalid application',
            'text' => 'No valid application was selected.'
    ];

    header('Location: list.php');
    exit;
}

if ($userRole === 'candidate') {
    $application = getApplicationByIdForCandidate($pdo, $id, $userId);
} elseif ($userRole === 'evaluator') {
    $application = getApplicationByIdForEvaluator($pdo, $id, $userId);
} else {
    $application = getApplicationById($pdo, $id);
}

if (!$application) {
    $_SESSION['flash'] = [
            'type' => 'error',
            'title' => 'Application not found',
            'text' => 'You do not have access to this application.'
    ];

    header('Location: list.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Application Details</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/list.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css?v=<?= filemtime(__DIR__ . '/../assets/css/dashboard.css'); ?>">
</head>
<body>

<div class="list-layout">
    <?php require_once __DIR__ . '/../includes/protected_sidebar.php'; ?>

    <main class="list-content">
        <div class="page-shell">
            <?php require_once __DIR__ . '/../includes/protected_topbar.php'; ?>

            <section class="page-card list-card">
                <div class="list-header">
                    <div>
                        <h1 class="page-title">
                            <?= htmlspecialchars($application['title'] ?? 'Application Details'); ?>
                        </h1>
                        <p class="page-subtitle">Full application details and submitted documents.</p>
                    </div>

                    <div class="list-actions">
                        <a href="list.php" class="btn btn-secondary">Back</a>
                    </div>
                </div>

                <div class="application-admin-head">
                    <span class="application-admin-id">
                        #<?= (int) $application['id']; ?>
                    </span>

                    <span class="status-pill <?= getStatusCssClass($application['status'] ?? 'draft'); ?>">
                        <?= htmlspecialchars(getStatusLabel($application['status'] ?? 'draft')); ?>
                    </span>
                </div>

                <div class="application-admin-meta">
                    <div>
                        <span>Candidate</span>
                        <strong><?= htmlspecialchars($application['candidate_name'] ?? 'Unknown candidate'); ?></strong>
                        <small><?= htmlspecialchars($application['candidate_email'] ?? 'No email'); ?></small>
                    </div>

                    <div>
                        <span>Role</span>
                        <strong><?= htmlspecialchars(getRoleLabel($application['candidate_role'] ?? 'candidate')); ?></strong>
                    </div>

                    <div>
                        <span>Course</span>
                        <strong><?= htmlspecialchars($application['course_name'] ?? 'No course'); ?></strong>
                        <small><?= htmlspecialchars($application['course_code'] ?? 'No code'); ?></small>
                    </div>

                    <div>
                        <span>Department</span>
                        <strong><?= htmlspecialchars($application['department_name'] ?? 'No department'); ?></strong>
                        <small><?= htmlspecialchars($application['faculty_name'] ?? 'No faculty'); ?></small>
                    </div>

                    <div>
                        <span>Period</span>
                        <strong><?= htmlspecialchars($application['period_title'] ?? 'No period'); ?></strong>
                        <small>
                            <?= htmlspecialchars(formatDate($application['start_date'] ?? null)); ?>
                            -
                            <?= htmlspecialchars(formatDate($application['end_date'] ?? null)); ?>
                        </small>
                    </div>

                    <div>
                        <span>Created</span>
                        <strong>
                            <?= htmlspecialchars(formatFullDateTime($application['created_at'] ?? null)); ?>
                        </strong>
                    </div>

                    <div>
                        <span>Updated</span>
                        <strong>
                            <?= htmlspecialchars(formatFullDateTime($application['updated_at'] ?? $application['created_at'] ?? null)); ?>
                        </strong>
                    </div>

                    <div>
                        <span>CV</span>
                        <?php if (!empty($application['cv_file_path'])): ?>
                            <a
                                    href="<?= htmlspecialchars($application['cv_file_path']); ?>"
                                    target="_blank"
                                    class="btn btn-secondary btn-sm-custom"
                            >
                                View CV
                            </a>
                        <?php else: ?>
                            <strong>Not uploaded</strong>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="placeholder-box" style="margin-top: 20px;">
                    <h3>Cover Letter</h3>
                    <p>
                        <?= nl2br(htmlspecialchars($application['cover_letter'] ?: 'No cover letter provided.')); ?>
                    </p>
                </div>

                <div class="placeholder-box" style="margin-top: 20px;">
                    <h3>Qualifications</h3>
                    <p>
                        <?= nl2br(htmlspecialchars($application['qualifications'] ?: 'No qualifications provided.')); ?>
                    </p>
                </div>
            </section>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/protected_footer.php'; ?>
</body>
</html>