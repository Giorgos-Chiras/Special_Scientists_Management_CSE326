<?php
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['hr', 'admin', 'ee'], true)) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../utils/time_utils.php';

$pageTitle = 'LMS Sync';

$user_id  = (int) $_SESSION['user_id'];
$userRole = $_SESSION['role'];

$isManager = in_array($userRole, ['hr', 'admin'], true);

if ($isManager && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action       = $_POST['lms_action'] ?? '';
    $targetUserId = (int) ($_POST['target_user_id'] ?? 0);
    $courseId     = (int) ($_POST['course_id'] ?? 0);

    if ($targetUserId > 0) {
        if ($action === 'check' && $courseId > 0) {
            $stmt = $pdo->prepare("
                UPDATE lms_enrollments
                SET synced_at = NOW()
                WHERE user_id = :user_id AND course_id = :course_id
            ");
            $stmt->execute([
                ':user_id' => $targetUserId,
                ':course_id' => $courseId
            ]);

            $_SESSION['flash'] = [
                'type' => 'success',
                'title' => 'LMS Checked',
                'text' => 'LMS access was checked successfully.'
            ];
        }

        if ($action === 'assign' && $courseId > 0) {
            $stmt = $pdo->prepare("
                INSERT INTO lms_enrollments (user_id, course_id, lms_access, synced_at)
                VALUES (:user_id, :course_id, 1, NOW())
                ON DUPLICATE KEY UPDATE 
                    lms_access = 1,
                    synced_at = NOW()
            ");
            $stmt->execute([
                ':user_id' => $targetUserId,
                ':course_id' => $courseId
            ]);

            $_SESSION['flash'] = [
                'type' => 'success',
                'title' => 'Course Assigned',
                'text' => 'The course was assigned successfully.'
            ];
        }

        if ($action === 'enable' && $courseId > 0) {
            $stmt = $pdo->prepare("
                UPDATE lms_enrollments
                SET lms_access = 1, synced_at = NOW()
                WHERE user_id = :user_id AND course_id = :course_id
            ");
            $stmt->execute([
                ':user_id' => $targetUserId,
                ':course_id' => $courseId
            ]);

            $_SESSION['flash'] = [
                'type' => 'success',
                'title' => 'Access Enabled',
                'text' => 'LMS access was enabled successfully.'
            ];
        }

        if ($action === 'disable' && $courseId > 0) {
            $stmt = $pdo->prepare("
                UPDATE lms_enrollments
                SET lms_access = 0, synced_at = NOW()
                WHERE user_id = :user_id AND course_id = :course_id
            ");
            $stmt->execute([
                ':user_id' => $targetUserId,
                ':course_id' => $courseId
            ]);

            $_SESSION['flash'] = [
                'type' => 'success',
                'title' => 'Access Disabled',
                'text' => 'LMS access was disabled successfully.'
            ];
        }

        if ($action === 'remove' && $courseId > 0) {
            $stmt = $pdo->prepare("
                DELETE FROM lms_enrollments
                WHERE user_id = :user_id AND course_id = :course_id
            ");
            $stmt->execute([
                ':user_id' => $targetUserId,
                ':course_id' => $courseId
            ]);

            $_SESSION['flash'] = [
                'type' => 'success',
                'title' => 'Course Removed',
                'text' => 'The course access was removed successfully.'
            ];
        }
    }

    header('Location: lms_sync.php');
    exit;
}

$courses = $pdo->query("
    SELECT id, code, name
    FROM courses
    ORDER BY code ASC
")->fetchAll(PDO::FETCH_ASSOC);

if ($isManager) {
    $eeUsers = $pdo->query("
        SELECT 
            u.id,
            u.username,
            u.email,
            COUNT(le.id) AS total_courses,
            SUM(CASE WHEN le.lms_access = 1 THEN 1 ELSE 0 END) AS active_courses,
            MAX(le.synced_at) AS last_synced
        FROM users u
        LEFT JOIN lms_enrollments le ON le.user_id = u.id
        WHERE u.role = 'ee'
        GROUP BY u.id, u.username, u.email
        ORDER BY u.username ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $rows = $pdo->query("
        SELECT 
            le.user_id,
            le.course_id,
            le.lms_access,
            le.synced_at,
            c.name AS course_name,
            c.code AS course_code
        FROM lms_enrollments le
        INNER JOIN courses c ON c.id = le.course_id
        ORDER BY c.code ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $enrollmentsByUser = [];

    foreach ($rows as $row) {
        $enrollmentsByUser[(int) $row['user_id']][] = $row;
    }
} else {
    $stmt = $pdo->prepare("
        SELECT 
            le.lms_access,
            le.synced_at,
            c.name AS course_name,
            c.code AS course_code
        FROM lms_enrollments le
        INNER JOIN courses c ON c.id = le.course_id
        WHERE le.user_id = :user_id
        ORDER BY c.code ASC
    ");
    $stmt->execute([':user_id' => $user_id]);
    $myEnrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<?php if ($flash): ?>
    <div
        id="flash-data"
        data-type="<?= htmlspecialchars($flash['type']); ?>"
        data-title="<?= htmlspecialchars($flash['title']); ?>"
        data-text="<?= htmlspecialchars($flash['text']); ?>">
    </div>
<?php endif; ?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../../includes/evaluation_sidebar.php'; ?>

    <main class="dashboard-content">
        <div class="page-shell">
            <?php require_once __DIR__ . '/../../includes/protected_topbar.php'; ?>

            <?php if ($isManager): ?>
                <section class="page-card list-card">
                    <div class="list-header">
                        <div>
                            <h2 class="page-title">LMS Sync</h2>
                            <p class="page-subtitle">Manage simulated LMS access for EE users.</p>
                        </div>
                    </div>

                    <div class="applications-grid">
                        <?php if (empty($eeUsers)): ?>
                            <div class="empty-state">No EE users found.</div>
                        <?php else: ?>
                            <?php foreach ($eeUsers as $ee): ?>
                                <?php
                                $userEnrollments = $enrollmentsByUser[(int) $ee['id']] ?? [];
                                $hasActiveAccess = (int) $ee['active_courses'] > 0;
                                ?>

                                <article class="application-admin-card">
                                    <div class="application-admin-head">
                                        <div>
                                            <span class="application-admin-id">#<?= (int) $ee['id']; ?></span>
                                            <h3><?= htmlspecialchars($ee['username']); ?></h3>
                                        </div>

                                        <span class="status-pill <?= $hasActiveAccess ? 'status-approved' : 'status-rejected'; ?>">
                                            <?= $hasActiveAccess ? 'Active' : 'No Access'; ?>
                                        </span>
                                    </div>

                                    <div class="application-admin-meta">
                                        <div>
                                            <span>Email</span>
                                            <strong><?= htmlspecialchars($ee['email']); ?></strong>
                                        </div>

                                        <div>
                                            <span>Total Courses</span>
                                            <strong><?= (int) $ee['total_courses']; ?></strong>
                                        </div>

                                        <div>
                                            <span>Active Courses</span>
                                            <strong><?= (int) $ee['active_courses']; ?></strong>
                                        </div>

                                        <div>
                                            <span>Last Sync</span>
                                            <strong><?= $ee['last_synced'] ? htmlspecialchars(formatFullDateTime($ee['last_synced'])) : 'Never'; ?></strong>
                                        </div>
                                    </div>

                                    <div class="placeholder-box" style="margin-top: 18px;">
                                        <h3>Assigned LMS Courses</h3>

                                        <?php if (empty($userEnrollments)): ?>
                                            <div class="empty-state">No LMS course access assigned.</div>
                                        <?php else: ?>
                                            <?php foreach ($userEnrollments as $enrollment): ?>
                                                <div class="application-admin-meta" style="margin-top: 14px;">
                                                    <div>
                                                        <span>Course</span>
                                                        <strong><?= htmlspecialchars($enrollment['course_code']); ?></strong>
                                                        <small><?= htmlspecialchars($enrollment['course_name']); ?></small>
                                                    </div>

                                                    <div>
                                                        <span>Status</span>
                                                        <strong><?= $enrollment['lms_access'] ? 'Active' : 'Inactive'; ?></strong>
                                                    </div>

                                                    <div>
                                                        <span>Last Sync</span>
                                                        <strong><?= $enrollment['synced_at'] ? htmlspecialchars(formatFullDateTime($enrollment['synced_at'])) : 'Never'; ?></strong>
                                                    </div>
                                                </div>

                                                <div class="application-admin-actions" style="margin-top: 12px;">
                                                    <form method="POST" class="inline-form">
                                                        <input type="hidden" name="target_user_id" value="<?= (int) $ee['id']; ?>">
                                                        <input type="hidden" name="course_id" value="<?= (int) $enrollment['course_id']; ?>">
                                                        <input type="hidden" name="lms_action" value="check">
                                                        <button
                                                            type="button"
                                                            class="btn btn-secondary btn-sm-custom js-confirm-action"
                                                            data-title="Check LMS access?"
                                                            data-text="Sync and check the current LMS access status for this course."
                                                            data-confirm-text="Yes, check">
                                                            Check
                                                        </button>
                                                    </form>

                                                    <form method="POST" class="inline-form">
                                                        <input type="hidden" name="target_user_id" value="<?= (int) $ee['id']; ?>">
                                                        <input type="hidden" name="course_id" value="<?= (int) $enrollment['course_id']; ?>">
                                                        <input type="hidden" name="lms_action" value="<?= $enrollment['lms_access'] ? 'disable' : 'enable'; ?>">
                                                        <button
                                                            type="button"
                                                            class="btn <?= $enrollment['lms_access'] ? 'btn-danger' : 'btn-primary'; ?> btn-sm-custom js-confirm-action"
                                                            data-title="<?= $enrollment['lms_access'] ? 'Disable LMS access?' : 'Enable LMS access?'; ?>"
                                                            data-text="<?= $enrollment['lms_access'] ? 'This will deactivate access for this course.' : 'This will activate access for this course.'; ?>"
                                                            data-confirm-text="<?= $enrollment['lms_access'] ? 'Yes, disable' : 'Yes, enable'; ?>">
                                                            <?= $enrollment['lms_access'] ? 'Disable' : 'Enable'; ?>
                                                        </button>
                                                    </form>

                                                    <form method="POST" class="inline-form">
                                                        <input type="hidden" name="target_user_id" value="<?= (int) $ee['id']; ?>">
                                                        <input type="hidden" name="course_id" value="<?= (int) $enrollment['course_id']; ?>">
                                                        <input type="hidden" name="lms_action" value="remove">
                                                        <button
                                                            type="button"
                                                            class="btn btn-danger btn-sm-custom js-confirm-action"
                                                            data-title="Remove course access?"
                                                            data-text="This will remove this LMS course access."
                                                            data-confirm-text="Yes, remove">
                                                            Remove
                                                        </button>
                                                    </form>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>

                                    <div class="placeholder-box" style="margin-top: 18px;">
                                        <h3>Assign New Course Access</h3>

                                        <form method="POST" class="admin-form" style="margin-top: 14px;">
                                            <input type="hidden" name="target_user_id" value="<?= (int) $ee['id']; ?>">
                                            <input type="hidden" name="lms_action" value="assign">

                                            <div class="form-group full-width">
                                                <label for="course_<?= (int) $ee['id']; ?>">Course</label>
                                                <select id="course_<?= (int) $ee['id']; ?>" name="course_id" class="admin-select" required>
                                                    <option value="">Select course</option>
                                                    <?php foreach ($courses as $course): ?>
                                                        <option value="<?= (int) $course['id']; ?>">
                                                            <?= htmlspecialchars($course['code'] . ' - ' . $course['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="form-actions">
                                                <button
                                                    type="button"
                                                    class="btn btn-primary js-confirm-action"
                                                    data-title="Assign course access?"
                                                    data-text="This will assign and activate LMS access for the selected course."
                                                    data-confirm-text="Yes, assign">
                                                    Assign
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>

            <?php else: ?>
                <?php
                $hasAccess = false;

                foreach ($myEnrollments as $enrollment) {
                    if ((int) $enrollment['lms_access'] === 1) {
                        $hasAccess = true;
                        break;
                    }
                }
                ?>

                <section class="page-card list-card">
            

                    <div class="application-admin-card">
                        <div class="application-admin-head">
                            <div>
                                <h3>Your LMS Access</h3>
                            </div>

                            <span class="status-pill <?= $hasAccess ? 'status-approved' : 'status-rejected'; ?>">
                                <?= $hasAccess ? 'Active' : 'No Access'; ?>
                            </span>
                        </div>

                        <div class="application-admin-meta">
                        

                            <?php if (empty($myEnrollments)): ?>
                                <div class="empty-state">You do not currently have LMS course access.</div>
                            <?php else: ?>
                                <?php foreach ($myEnrollments as $enrollment): ?>
                                    <div>
                                        <span>Course</span>
                                        <strong><?= htmlspecialchars($enrollment['course_code']); ?></strong>
                                        <small><?= htmlspecialchars($enrollment['course_name']); ?></small>
                                    </div>

                                    <div>
                                        <span>Status</span>
                                        <strong><?= $enrollment['lms_access'] ? 'Active' : 'Inactive'; ?></strong>
                                    </div>

                                    <div>
                                        <span>Last Sync</span>
                                        <strong><?= $enrollment['synced_at'] ? htmlspecialchars(formatFullDateTime($enrollment['synced_at'])) : 'Never'; ?></strong>
                                    </div>
                                    <br>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const flashDataElement = document.getElementById('flash-data');

    if (flashDataElement && flashDataElement.dataset.title) {
        Swal.fire({
            icon: flashDataElement.dataset.type || 'success',
            title: flashDataElement.dataset.title || 'Done',
            html: flashDataElement.dataset.text || '',
            confirmButtonColor: '#2563eb'
        });
    }

    document.addEventListener('click', function (event) {
        const button = event.target.closest('.js-confirm-action');

        if (!button) {
            return;
        }

        event.preventDefault();

        const form = button.closest('form');

        if (!form) {
            return;
        }

        Swal.fire({
            title: button.dataset.title || 'Are you sure?',
            text: button.dataset.text || 'This action will update LMS access.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: button.classList.contains('btn-danger') ? '#dc2626' : '#2563eb',
            cancelButtonColor: '#64748b',
            confirmButtonText: button.dataset.confirmText || 'Yes',
            cancelButtonText: 'Cancel'
        }).then(function (result) {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/protected_footer.php'; ?>

</body>
</html>