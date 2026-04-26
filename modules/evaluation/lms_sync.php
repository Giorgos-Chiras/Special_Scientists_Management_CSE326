<?php
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['hr', 'ee'], true)) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../utils/status_utils.php';

$pageTitle = 'LMS Sync';
$user_id = $_SESSION['user_id'];
$userRole = $_SESSION['role'];
$isHr = $userRole === 'hr';


function moodleEnableAccess(string $email, string $courseCode): bool {
        return true;
    }

function moodleDisableAccess(string $email, string $courseCode): bool {
        return true;
    }


if ($isHr && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $courseId = (int) ($_POST['course_id'] ?? 0);
    $userId = (int) ($_POST['user_id'] ?? 0);

    if ($courseId > 0 && $userId > 0) {
        $stmt = $pdo->prepare("SELECT email FROM users WHERE id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        $userEmail = $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT code FROM courses WHERE id = :course_id");
        $stmt->execute([':course_id' => $courseId]);
        $courseCode = $stmt->fetchColumn();

        if ($action === 'enable') {
            if (moodleEnableAccess($userEmail, $courseCode)) {
                $updateStmt = $pdo->prepare("UPDATE lms_enrollments SET moodle_access = 1, synced_at = NOW() WHERE user_id = :user_id AND course_id = :course_id");
                $updateStmt->execute([':user_id' => $userId, ':course_id' => $courseId]);
            }
        } elseif ($action === 'disable') {
            if (moodleDisableAccess($userEmail, $courseCode)) {
                $updateStmt = $pdo->prepare("UPDATE lms_enrollments SET moodle_access = 0, synced_at = NOW() WHERE user_id = :user_id AND course_id = :course_id");
                $updateStmt->execute([':user_id' => $userId, ':course_id' => $courseId]);
            }
        }
    }
}

if ($isHr) {
    $eeUsers = $pdo->query("
    SELECT
        u.id,
        u.username,
        u.email,
        c.id AS course_id,
        c.name AS course_name,
        c.code AS course_code,
        d.name AS department_name,
        f.name AS faculty_name,
        COALESCE(le.moodle_access, 0) AS moodle_access,
        le.synced_at
    FROM users u
    INNER JOIN applications a ON a.user_id = u.id AND a.status = 'approved'
    INNER JOIN courses c ON a.course_id = c.id
    INNER JOIN departments d ON c.department_id = d.id
    INNER JOIN faculties f ON d.faculty_id = f.id
    LEFT JOIN lms_enrollments le ON le.user_id = u.id
    WHERE u.role = 'ee'
    ORDER BY u.username ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $courses =$pdo ->query("
    SELECT c.id, c.name AS course_name, c.code AS course_code, 
        d.name AS department_name
    FROM courses c
    INNER JOIN departments d ON c.department_id = d.id
    ORDER BY d.name ASC, c.name ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
}

 if (!$isHr) {
    $stmt = $pdo->prepare("
        SELECT
            le.moodle_access, le.synced_at,
            c.name AS course_name, c.code AS course_code,
        FROM lms_enrollments le
        INNER JOIN courses c ON le.course_id = c.id
        WHERE le.user_id = :user_id
        LIMIT 1
    ");
    $stmt->execute([':user_id' => $user_id]);
    $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
 }
?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>LMS Sync</title>
        <link rel="stylesheet" href="../../assets/css/style.css">
        <link rel="stylesheet" href="../../assets/css/admin.css">
        <link rel="stylesheet" href="../../assets/css/dashboard.css">
    </head>
<body>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../../includes/evaluation_sidebar.php'; ?>

    <main class="dashboard-content">
        <div class="page-shell">
            <?php require_once __DIR__ . '/../../includes/protected_topbar.php'; ?>
            <section class="page-card">
                <?php if ($isHr): ?>
                    <div class="list-header">       
                        </div>
                            <h2 class="page-title">LMS Sync Management</h2>
                            <p class="page-subtitle">Manage LMS access for EEs based on approved applications.</p>
                        <div>
                    </div>
                <div class="application-grid">
                    <?php if (empty($eeUsers)): ?>
                        <div class="No approved applications found."></div>>
                    <?php else: ?>
                            <?php foreach ($eeUsers as $ee): ?>
                                <article class="application-admin-card">
                                    <div class="application-admin-head">
                                        <div>
                                            <span class="application-admin-id">#<?= (int) $ee['id']; ?></span>
                                            <h3><?= htmlspecialchars($ee['username']); ?></h3>
                                        </div>
                                        <span class="status-pill <?= $ee['moodle_access'] ? 'status-approved' : 'status-rejected'; ?>">
                                            <?= $ee['moodle_access'] ? 'Active' : 'No Access'; ?>
                                        </span>
                                    </div>
 
                                    <div class="application-admin-meta">
                                        <div>
                                            <span>Email</span>
                                            <strong><?= htmlspecialchars($ee['email']); ?></strong>
                                        </div>
                                        <div>
                                            <span>Course</span>
                                            <strong><?= htmlspecialchars($ee['course_name']); ?></strong>
                                            <small><?= htmlspecialchars($ee['course_code']); ?></small>
                                        </div>
                                        <div>
                                            <span>Department</span>
                                            <strong><?= htmlspecialchars($ee['department_name']); ?></strong>
                                            <small><?= htmlspecialchars($ee['faculty_name']); ?></small>
                                        </div>
                                        <div>
                                            <span>Last Synced</span>
                                            <strong><?= $ee['synced_at'] ? htmlspecialchars($ee['synced_at']) : 'Never'; ?></strong>
                                        </div>
                                    </div>
 
                                    <div class="application-admin-actions">
                                        <?php if (!$ee['moodle_access']): ?>
                                            <form method="POST" action="lms_sync.php">
                                                <input type="hidden" name="target_user_id" value="<?= (int) $ee['id']; ?>">
                                                <input type="hidden" name="lms_action" value="enable">
                                                <button type="submit" class="btn btn-primary btn-sm-custom">Enable</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" action="lms_sync.php">
                                                <input type="hidden" name="target_user_id" value="<?= (int) $ee['id']; ?>">
                                                <input type="hidden" name="lms_action" value="disable">
                                                <button type="submit" class="btn btn-danger btn-sm-custom">Disable</button>
                                            </form>
                                        <?php endif; ?>
 
                                        <form method="POST" action="lms_sync.php" class="status-change-row">
                                            <input type="hidden" name="target_user_id" value="<?= (int) $ee['id']; ?>">
                                            <input type="hidden" name="lms_action" value="switch">
                                            <select name="new_course_id" class="admin-select">
                                                <option value="">Switch course…</option>
                                                <?php foreach ($allCourses as $course): ?>
                                                    <option value="<?= (int) $course['id']; ?>">
                                                        <?= htmlspecialchars($course['course_name'] . ' (' . $course['course_code'] . ')'); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn btn-secondary btn-sm-custom">Switch</button>
                                        </form>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
 
                <?php else: ?>
 
                    <div class="placeholder-header">
                        <h1 class="page-title">LMS Sync</h1>
                        <p class="page-subtitle">Synchronize evaluation data from the LMS platform.</p>
                    </div>
 
                    <div class="placeholder-box">
                        <h3>Your LMS Access</h3>
                        <?php if ($myEnrollment): ?>
                            <p>Course: <strong><?= htmlspecialchars($myEnrollment['course_name']); ?></strong></p>
                            <?php if ($myEnrollment['moodle_access']): ?>
                                <span class="status-pill status-approved">Active</span>
                                <a href="#" class="btn btn-primary" target="_blank">Go to Moodle</a>
                            <?php else: ?>
                                <span class="status-pill status-rejected">No Access</span>
                                <p>You do not currently have active LMS access. Please contact HR.</p>
                            <?php endif; ?>
                        <?php else: ?>
                            <p>No enrollment record found. Please contact HR.</p>
                        <?php endif; ?>
                    </div>
 
                <?php endif; ?>
 
            </section>
        </div>
    </main>
</div>
 
<?php require_once __DIR__ . '/../../includes/protected_footer.php'; ?>