<!-- <?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hr') {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../utils/time_utils.php';

$pageTitle = 'Full Sync';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_full_sync'])) {
    $stmt = $pdo->prepare("
        UPDATE lms_enrollments le
        INNER JOIN users u ON u.id = le.user_id
        SET le.synced_at = NOW()
        WHERE u.role = 'ee'
    ");
    $stmt->execute();

    $_SESSION['flash'] = [
        'type' => 'success',
        'title' => 'Full Sync Completed',
        'text' => 'All EE LMS enrollment records were checked successfully.'
    ];

    header('Location: full_sync.php');
    exit;
}

$summary = $pdo->query("
    SELECT
        COUNT(DISTINCT u.id) AS total_ee,
        COUNT(le.id) AS total_enrollments,
        SUM(CASE WHEN le.lms_access = 1 THEN 1 ELSE 0 END) AS active_access,
        SUM(CASE WHEN le.lms_access = 0 THEN 1 ELSE 0 END) AS disabled_access,
        SUM(CASE WHEN le.synced_at IS NULL THEN 1 ELSE 0 END) AS never_synced
    FROM users u
    LEFT JOIN lms_enrollments le ON le.user_id = u.id
    WHERE u.role = 'ee'
")->fetch(PDO::FETCH_ASSOC);

$rows = $pdo->query("
    SELECT
        u.id AS user_id,
        u.username,
        u.email,
        c.code AS course_code,
        c.name AS course_name,
        le.lms_access,
        le.synced_at
    FROM users u
    LEFT JOIN lms_enrollments le ON le.user_id = u.id
    LEFT JOIN courses c ON c.id = le.course_id
    WHERE u.role = 'ee'
    ORDER BY u.username ASC, c.code ASC
")->fetchAll(PDO::FETCH_ASSOC);

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

            <section class="page-card list-card">
                <div class="list-header">
                    <div></div>

                    <div class="list-actions">
                        <form method="POST" action="full_sync.php" class="inline-form js-confirm-form">
                            <input type="hidden" name="run_full_sync" value="1">

                            <button
                                type="submit"
                                class="btn btn-primary"
                                data-title="Run full sync?"
                                data-text="This will simulate checking all EE LMS enrollments."
                                data-confirm-text="Run Sync">
                                Run Full Sync
                            </button>
                        </form>
                    </div>
                </div>

                <div class="application-admin-meta">
                    <div>
                        <span>Total EEs</span>
                        <strong><?= (int) $summary['total_ee']; ?></strong>
                    </div>

                    <div>
                        <span>Total Enrollments</span>
                        <strong><?= (int) $summary['total_enrollments']; ?></strong>
                    </div>

                    <div>
                        <span>Active Access</span>
                        <strong><?= (int) $summary['active_access']; ?></strong>
                    </div>

                    <div>
                        <span>Disabled Access</span>
                        <strong><?= (int) $summary['disabled_access']; ?></strong>
                    </div>

                    <div>
                        <span>Never Synced</span>
                        <strong><?= (int) $summary['never_synced']; ?></strong>
                    </div>
                </div>
            </section>

            <section class="page-card list-card">
                <div class="list-header">
                    <div>
                        <h2 class="page-title">Sync Results</h2>
                        <p class="page-subtitle">Current LMS enrollment status for each EE and course.</p>
                    </div>
                </div>

                <div class="table-card">
                    <div class="table-wrapper">
                        <table>
                            <thead>
                            <tr>
                                <th>EE</th>
                                <th>Email</th>
                                <th>Course</th>
                                <th>LMS Status</th>
                                <th>Sync Status</th>
                                <th>Last Sync</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($rows)): ?>
                                <tr>
                                    <td colspan="6" class="empty-state">No EE users found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($rows as $row): ?>
                                    <?php
                                    $hasCourse = !empty($row['course_code']);
                                    $isActive = (int) ($row['lms_access'] ?? 0) === 1;

                                    if (!$hasCourse) {
                                        $statusLabel = 'No Course';
                                        $statusClass = 'status-rejected';
                                        $syncLabel = 'Missing Enrollment';
                                        $syncClass = 'status-rejected';
                                    } elseif ($isActive) {
                                        $statusLabel = 'Active';
                                        $statusClass = 'status-approved';
                                        $syncLabel = 'Synced';
                                        $syncClass = 'status-approved';
                                    } else {
                                        $statusLabel = 'Disabled';
                                        $statusClass = 'status-rejected';
                                        $syncLabel = 'Needs Review';
                                        $syncClass = 'status-pending';
                                    }
                                    ?>

                                    <tr>
                                        <td><?= htmlspecialchars($row['username']); ?></td>
                                        <td><?= htmlspecialchars($row['email']); ?></td>
                                        <td>
                                            <?php if ($hasCourse): ?>
                                                <strong><?= htmlspecialchars($row['course_code']); ?></strong><br>
                                                <small><?= htmlspecialchars($row['course_name']); ?></small>
                                            <?php else: ?>
                                                No course assigned
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="status-pill <?= $statusClass; ?>">
                                                <?= $statusLabel; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-pill <?= $syncClass; ?>">
                                                <?= $syncLabel; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?= $row['synced_at'] ? htmlspecialchars(formatFullDateTime($row['synced_at'])) : 'Never'; ?>
                                        </td>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const flashDataElement = document.getElementById('flash-data');

    if (flashDataElement && flashDataElement.dataset.title && typeof Swal !== 'undefined') {
        Swal.fire({
            icon: flashDataElement.dataset.type || 'success',
            title: flashDataElement.dataset.title || 'Done',
            html: flashDataElement.dataset.text || '',
            confirmButtonColor: '#2563eb'
        });
    }

    document.querySelectorAll('.js-confirm-form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();

            const button = form.querySelector('button[type="submit"]');

            if (typeof Swal === 'undefined') {
                form.submit();
                return;
            }

            Swal.fire({
                title: button.dataset.title || 'Are you sure?',
                text: button.dataset.text || '',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#2563eb',
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
});
</script>

<?php require_once __DIR__ . '/../../includes/protected_footer.php'; ?>

</body>
</html> -->