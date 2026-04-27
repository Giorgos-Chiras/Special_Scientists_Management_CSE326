<?php
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['hr', 'ee'], true)) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../utils/status_utils.php';

$pageTitle = 'LMS Sync';
$user_id  = (int) $_SESSION['user_id'];
$userRole = $_SESSION['role'];
$isHr     = $userRole === 'hr';

if ($isHr && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action       = $_POST['lms_action'] ?? '';
    $targetUserId = (int) ($_POST['target_user_id'] ?? 0);

    if ($targetUserId > 0 && in_array($action, ['enable', 'disable'], true)) {
        if ($action === 'enable') {
            $stmt = $pdo->prepare("SELECT user_id FROM lms_enrollments WHERE user_id = :user_id");
            $stmt->execute([':user_id' => $targetUserId]);

            if ($stmt->fetchColumn()) {
                $pdo->prepare("UPDATE lms_enrollments SET lms_access = 1, synced_at = NOW() WHERE user_id = :user_id")
                    ->execute([':user_id' => $targetUserId]);
            } else {
                $pdo->prepare("INSERT INTO lms_enrollments (user_id, lms_access, synced_at) VALUES (:user_id, 1, NOW())")
                    ->execute([':user_id' => $targetUserId]);
            }

            $_SESSION['flash'] = ['type' => 'success', 'title' => 'Access Enabled', 'text' => 'LMS access has been enabled.'];
        } elseif ($action === 'disable') {
            $pdo->prepare("UPDATE lms_enrollments SET lms_access = 0, synced_at = NOW() WHERE user_id = :user_id")
                ->execute([':user_id' => $targetUserId]);

            $_SESSION['flash'] = ['type' => 'success', 'title' => 'Access Disabled', 'text' => 'LMS access has been disabled.'];
        }
    }

    header('Location: lms_sync.php');
    exit;
}

if ($isHr) {
    $eeUsers = $pdo->query("
        SELECT u.id, u.username, u.email, c.name AS course_name, c.code AS course_code,
               COALESCE(le.lms_access, 0) AS lms_access, le.synced_at
        FROM users u
        LEFT JOIN lms_enrollments le ON le.user_id = u.id
        LEFT JOIN courses c ON le.course_id = c.id
        WHERE u.role = 'ee'
        ORDER BY u.username ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
}

if (!$isHr) {
    $stmt = $pdo->prepare("
        SELECT le.lms_access, le.synced_at, c.name AS course_name, c.code AS course_code
        FROM lms_enrollments le
        LEFT JOIN courses c ON le.course_id = c.id
        WHERE le.user_id = :user_id
    ");
    $stmt->execute([':user_id' => $user_id]);
    $myEnrollment = $stmt->fetch(PDO::FETCH_ASSOC);
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

            <?php if ($isHr): ?>
                <section class="page-card list-card">
                    <div class="list-header">
                        <div>
                            <h2 class="page-title">LMS Sync</h2>
                            <p class="page-subtitle">Manage LMS access for hired special scientists.</p>
                        </div>
                    </div>

                    <div class="applications-grid">
                        <?php if (empty($eeUsers)): ?>
                            <div class="empty-state">No EE users found.</div>
                        <?php else: ?>
                            <?php foreach ($eeUsers as $ee): ?>
                                <article class="application-admin-card">
                                    <div class="application-admin-head">
                                        <div>
                                            <span class="application-admin-id">#<?= (int) $ee['id']; ?></span>
                                            <h3><?= htmlspecialchars($ee['username']); ?></h3>
                                        </div>
                                        <span class="status-pill <?= $ee['lms_access'] ? 'status-approved' : 'status-rejected'; ?>">
                                            <?= $ee['lms_access'] ? 'Active' : 'No Access'; ?>
                                        </span>
                                    </div>

                                    <div class="application-admin-meta">
                                        <div>
                                            <span>Email</span>
                                            <strong><?= htmlspecialchars($ee['email']); ?></strong>
                                        </div>
                                        <?php if ($ee['course_name']): ?>
                                        <div>
                                            <span>Course</span>
                                            <strong><?= htmlspecialchars($ee['course_name']); ?></strong>
                                            <small><?= htmlspecialchars($ee['course_code']); ?></small>
                                        </div>
                                        <?php endif; ?>
                                        <div>
                                            <span>Last Synced</span>
                                            <strong><?= $ee['synced_at'] ? htmlspecialchars($ee['synced_at']) : 'Never'; ?></strong>
                                        </div>
                                    </div>

                                    <div class="application-admin-actions">
                                        <?php if (!$ee['lms_access']): ?>
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
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>

            <?php else: ?>
                <section class="page-card list-card">
                    <div class="list-header">
                        <div>
                            <h2 class="page-title">LMS Sync</h2>
                            <p class="page-subtitle">Your LMS access status.</p>
                        </div>
                    </div>

                    <div class="application-admin-card">
                        <div class="application-admin-head">
                            <div>
                                <h3>Your Courses</h3>
                            </div>
                            <?php if ($myEnrollment && $myEnrollment['lms_access']): ?>
                                <span class="status-pill status-approved">Active</span>
                            <?php else: ?>
                                <span class="status-pill status-rejected">No Access</span>
                            <?php endif; ?>
                        </div>

                        <div class="application-admin-meta">
                            <?php if ($myEnrollment): ?>
                                <div>
                                    <span>Course</span>
                                    <strong><?= htmlspecialchars($myEnrollment['course_name']); ?></strong>
                                    <small><?= htmlspecialchars($myEnrollment['course_code']); ?></small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../../includes/protected_footer.php'; ?>