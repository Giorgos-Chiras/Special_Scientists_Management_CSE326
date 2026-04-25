<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../utils/status_utils.php';
require_once __DIR__ . '/../utils/time_utils.php';


$userId = (int) $_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? 'candidate';
$id = (int) ($_GET['id'] ?? 0);

if (!in_array($userRole, ['hr', 'evaluator'], true)) {
    header('Location: list.php');
    exit;
}

$sql = "
    SELECT 
        a.id,
        a.title,
        a.status,
        a.cover_letter,
        a.qualifications,
        a.cv_file_path,
        a.cv_original_name,
        a.created_at,
        a.updated_at,
        u.username AS candidate_name,
        u.email AS candidate_email,
        u.role AS candidate_role,
        c.name AS course_name,
        c.code AS course_code,
        d.name AS department_name,
        f.name AS faculty_name,
        rp.title AS period_title,
        rp.start_date,
        rp.end_date
    FROM applications a
    INNER JOIN users u ON a.user_id = u.id
    INNER JOIN courses c ON a.course_id = c.id
    INNER JOIN departments d ON c.department_id = d.id
    INNER JOIN faculties f ON d.faculty_id = f.id
    INNER JOIN recruitment_periods rp ON a.period_id = rp.id
";

$params = [':id' => $id];

if ($userRole === 'evaluator') {
    $sql .= "
        INNER JOIN application_evaluators ae ON ae.application_id = a.id
        WHERE a.id = :id AND ae.evaluator_id = :user_id
        LIMIT 1
    ";
    $params[':user_id'] = $userId;
} else {
    $sql .= "
        WHERE a.id = :id
        LIMIT 1
    ";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$application = $stmt->fetch(PDO::FETCH_ASSOC);

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
                        <h1 class="page-title"><?= htmlspecialchars($application['title']); ?></h1>
                        <p class="page-subtitle">Full application details and submitted documents.</p>
                    </div>

                    <div class="list-actions">
                        <a href="list.php" class="btn btn-secondary">Back</a>
                    </div>
                </div>

                <div class="application-admin-head">
                    <span class="application-admin-id">#<?= (int) $application['id']; ?></span>

                    <span class="status-pill <?= getStatusCssClass($application['status']); ?>">
                        <?= htmlspecialchars(getStatusLabel($application['status'])); ?>
                    </span>
                </div>

                <div class="application-admin-meta">
                    <div>
                        <span>Candidate</span>
                        <strong><?= htmlspecialchars($application['candidate_name']); ?></strong>
                        <small><?= htmlspecialchars($application['candidate_email']); ?></small>
                    </div>

                    <div>
                        <span>Role</span>
                        <strong><?= htmlspecialchars($application['candidate_role']); ?></strong>
                    </div>

                    <div>
                        <span>Course</span>
                        <strong><?= htmlspecialchars($application['course_name']); ?></strong>
                        <small><?= htmlspecialchars($application['course_code'] ?? 'No code'); ?></small>
                    </div>

                    <div>
                        <span>Department</span>
                        <strong><?= htmlspecialchars($application['department_name']); ?></strong>
                        <small><?= htmlspecialchars($application['faculty_name']); ?></small>
                    </div>

                    <div>
                        <span>Period</span>
                        <strong><?= htmlspecialchars($application['period_title']); ?></strong>
                        <small><?= htmlspecialchars(formatDate( $application['start_date'])); ?> - <?= htmlspecialchars(formatDate($application['end_date'])); ?></small>
                    </div>

                    <div>
                        <span>Created</span>
                        <strong><?= htmlspecialchars(formatFullDateTime($application['created_at'])); ?></strong>
                    </div>

                    <div>
                        <span>Updated</span>
                        <strong><?= htmlspecialchars(formatFullDateTime( $application['updated_at'] ?? $application['created_at'])); ?></strong>
                    </div>

                    <div>
                        <span>CV</span>
                        <?php if (!empty($application['cv_file_path'])): ?>
                            <a href="<?= htmlspecialchars($application['cv_file_path']); ?>" target="_blank" class="btn btn-secondary btn-sm-custom">
                                View CV
                            </a>
                        <?php else: ?>
                            <strong>Not uploaded</strong>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="placeholder-box" style="margin-top: 20px;">
                    <h3>Cover Letter</h3>
                    <p><?= nl2br(htmlspecialchars($application['cover_letter'] ?: 'No cover letter provided.')); ?></p>
                </div>

                <div class="placeholder-box" style="margin-top: 20px;">
                    <h3>Qualifications</h3>
                    <p><?= nl2br(htmlspecialchars($application['qualifications'] ?: 'No qualifications provided.')); ?></p>
                </div>
            </section>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/protected_footer.php'; ?>