<?php
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['hr', 'admin'], true)) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../utils/time_utils.php';

$pageTitle = 'Enrollment Report';

$summary = $pdo->query("
    SELECT
        COUNT(DISTINCT u.id) AS total_ee,
        COUNT(DISTINCT CASE WHEN active.active_count > 0 THEN u.id END) AS ee_with_access,
        COUNT(DISTINCT CASE WHEN active.active_count IS NULL OR active.active_count = 0 THEN u.id END) AS ee_without_access
    FROM users u
    LEFT JOIN (
        SELECT user_id, COUNT(*) AS active_count
        FROM lms_enrollments
        WHERE lms_access = 1
        GROUP BY user_id
    ) active ON active.user_id = u.id
    WHERE u.role = 'ee'
")->fetch(PDO::FETCH_ASSOC);

$coursesWithoutTeacher = $pdo->query("
    SELECT c.code, c.name
    FROM courses c
    LEFT JOIN lms_enrollments le ON le.course_id = c.id AND le.lms_access = 1
    LEFT JOIN users u ON u.id = le.user_id AND u.role = 'ee'
    GROUP BY c.id, c.code, c.name
    HAVING COUNT(u.id) = 0
    ORDER BY c.code ASC
")->fetchAll(PDO::FETCH_ASSOC);

$eeAccessRows = $pdo->query("
    SELECT
        u.username,
        u.email,
        COUNT(le.id) AS total_courses,
        SUM(CASE WHEN le.lms_access = 1 THEN 1 ELSE 0 END) AS active_courses,
        SUM(CASE WHEN le.lms_access = 0 THEN 1 ELSE 0 END) AS disabled_courses,
        MAX(le.synced_at) AS last_sync
    FROM users u
    LEFT JOIN lms_enrollments le ON le.user_id = u.id
    WHERE u.role = 'ee'
    GROUP BY u.id, u.username, u.email
    ORDER BY u.username ASC
")->fetchAll(PDO::FETCH_ASSOC);

$courseRows = $pdo->query("
    SELECT
        c.code,
        c.name,
        COUNT(le.id) AS assigned_teachers
    FROM courses c
    INNER JOIN lms_enrollments le ON le.course_id = c.id
    GROUP BY c.id, c.code, c.name
    HAVING COUNT(le.id) > 0
    ORDER BY c.code ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle); ?></title>
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

            <section class="page-card list-card">
                <div class="list-header">
                    <div>
                        <h1 class="page-title">Enrollment Report</h1>
                        <p class="page-subtitle">LMS access statistics and Moodle enrollment overview.</p>
                    </div>
                </div>

                <div class="report-summary-grid">
                    <div class="report-summary-card">
                        <span>Total EEs</span>
                        <strong><?= (int) ($summary['total_ee'] ?? 0); ?></strong>
                    </div>

                    <div class="report-summary-card">
                        <span>With Moodle Access</span>
                        <strong><?= (int) ($summary['ee_with_access'] ?? 0); ?></strong>
                    </div>

                    <div class="report-summary-card">
                        <span>Without Moodle Access</span>
                        <strong><?= (int) ($summary['ee_without_access'] ?? 0); ?></strong>
                    </div>

                    <div class="report-summary-card">
                        <span>Courses Without Teacher</span>
                        <strong><?= count($coursesWithoutTeacher); ?></strong>
                    </div>
                </div>
            </section>

            <section class="page-card list-card">
                <div class="list-header">
                    <div>
                        <h2 class="page-title">EE Access Overview</h2>
                        <p class="page-subtitle">Moodle access status per EE.</p>
                    </div>
                </div>

                <div class="table-card">
                    <div class="table-wrapper">
                        <table>
                            <thead>
                            <tr>
                                <th>EE</th>
                                <th>Email</th>
                                <th>Courses</th>
                                <th>Active</th>
                                <th>Disabled</th>
                                <th>Status</th>
                                <th>Last Sync</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($eeAccessRows)): ?>
                                <tr>
                                    <td colspan="7" class="empty-state">No EE users found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($eeAccessRows as $row): ?>
                                    <?php $hasAccess = (int) $row['active_courses'] > 0; ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['username']); ?></td>
                                        <td><?= htmlspecialchars($row['email']); ?></td>
                                        <td><?= (int) $row['total_courses']; ?></td>
                                        <td><?= (int) $row['active_courses']; ?></td>
                                        <td><?= (int) $row['disabled_courses']; ?></td>
                                        <td>
                                            <span class="status-pill <?= $hasAccess ? 'status-approved' : 'status-rejected'; ?>">
                                                <?= $hasAccess ? 'Has Access' : 'No Access'; ?>
                                            </span>
                                        </td>
                                        <td><?= $row['last_sync'] ? htmlspecialchars(formatFullDateTime($row['last_sync'])) : 'Never'; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section class="page-card list-card">
                <div class="list-header">
                    <div>
                        <h2 class="page-title">Courses With Assigned Teachers</h2>
                        <p class="page-subtitle">Only courses that currently have assigned EEs.</p>
                    </div>
                </div>

                <div class="table-card">
                    <div class="table-wrapper">
                        <table>
                            <thead>
                            <tr>
                                <th>Course</th>
                                <th>Name</th>
                                <th>Assigned Teachers</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($courseRows)): ?>
                                <tr>
                                    <td colspan="3" class="empty-state">No courses with assigned teachers.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($courseRows as $row): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($row['code']); ?></strong></td>
                                        <td><?= htmlspecialchars($row['name']); ?></td>
                                        <td><?= (int) $row['assigned_teachers']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section class="page-card list-card">
                <div class="list-header">
                    <div>
                        <h2 class="page-title">Courses Without Active Teacher</h2>
                    </div>
                </div>

                <div class="table-card">
                    <div class="table-wrapper">
                        <table>
                            <thead>
                            <tr>
                                <th>Course</th>
                                <th>Name</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($coursesWithoutTeacher)): ?>
                                <tr>
                                    <td colspan="2" class="empty-state">All courses have at least one active EE.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($coursesWithoutTeacher as $course): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($course['code']); ?></strong></td>
                                        <td><?= htmlspecialchars($course['name']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../../includes/protected_footer.php'; ?>

</body>
</html>