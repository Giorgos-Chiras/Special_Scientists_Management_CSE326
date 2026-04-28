<?php
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['hr', 'admin'], true)) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../utils/time_utils.php';

$pageTitle = 'Full LMS Sync';

$pdo->exec("
    CREATE TABLE IF NOT EXISTS system_settings (
        setting_key   VARCHAR(100) PRIMARY KEY,
        setting_value TEXT NULL
    )
");

$stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'auto_lms_sync' LIMIT 1");
$stmt->execute();
$autoSyncValue = $stmt->fetchColumn();

if ($autoSyncValue === false) {
    $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('auto_lms_sync', '0')")->execute();
    $autoSyncValue = '0';
}
$autoSyncEnabled = $autoSyncValue === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['run_full_sync'])) {
        $stmt = $pdo->prepare("
            UPDATE lms_enrollments le
            INNER JOIN users u ON u.id = le.user_id
            SET le.synced_at = NOW()
            WHERE u.role = 'ee'
        ");
        $stmt->execute();
        $updated = $stmt->rowCount();

        $_SESSION['flash'] = [
                'type'  => 'success',
                'title' => 'Full Sync Complete',
                'text'  => "Sync timestamp updated for {$updated} enrollment record(s).",
        ];
        header('Location: full_sync.php');
        exit;
    }

    if (isset($_POST['toggle_auto_sync'])) {
        $newValue = $autoSyncEnabled ? '0' : '1';
        $pdo->prepare("
            INSERT INTO system_settings (setting_key, setting_value) VALUES ('auto_lms_sync', :v)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ")->execute([':v' => $newValue]);

        $_SESSION['flash'] = [
                'type'  => 'success',
                'title' => $newValue === '1' ? 'Auto Sync Enabled' : 'Auto Sync Disabled',
                'text'  => $newValue === '1'
                        ? 'Automatic LMS sync is now active.'
                        : 'Automatic LMS sync has been turned off.',
        ];
        header('Location: full_sync.php');
        exit;
    }
}

$statusFilter = $_GET['status'] ?? 'all';
$whereSql = "WHERE u.role = 'ee'";

switch ($statusFilter) {
    case 'active':       $whereSql .= " AND le.lms_access = 1";                         break;
    case 'disabled':     $whereSql .= " AND le.lms_access = 0";                         break;
    case 'never_synced': $whereSql .= " AND le.synced_at IS NULL AND le.id IS NOT NULL"; break;
    case 'missing':      $whereSql .= " AND le.id IS NULL";                              break;
}

$summary = $pdo->query("
    SELECT
        COUNT(DISTINCT u.id)                                              AS total_ee,
        COUNT(le.id)                                                      AS total_enrollments,
        SUM(CASE WHEN le.lms_access = 1 THEN 1 ELSE 0 END)              AS active_access,
        SUM(CASE WHEN le.lms_access = 0 THEN 1 ELSE 0 END)              AS disabled_access,
        SUM(CASE WHEN le.synced_at IS NULL AND le.id IS NOT NULL THEN 1 ELSE 0 END) AS never_synced,
        SUM(CASE WHEN le.id IS NULL THEN 1 ELSE 0 END)                  AS users_without_courses,
        MAX(le.synced_at)                                                 AS last_full_sync
    FROM users u
    LEFT JOIN lms_enrollments le ON le.user_id = u.id
    WHERE u.role = 'ee'
")->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("
    SELECT
        u.id   AS user_id,
        u.username,
        u.email,
        c.id   AS course_id,
        c.code AS course_code,
        c.name AS course_name,
        le.id  AS enrollment_id,
        le.lms_access,
        le.synced_at
    FROM users u
    LEFT JOIN lms_enrollments le ON le.user_id = u.id
    LEFT JOIN courses c          ON c.id = le.course_id
    {$whereSql}
    ORDER BY u.username ASC
");
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<?php if ($flash): ?>
    <div id="flash-data"
         data-type="<?= htmlspecialchars($flash['type']) ?>"
         data-title="<?= htmlspecialchars($flash['title']) ?>"
         data-text="<?= htmlspecialchars($flash['text']) ?>"></div>
<?php endif; ?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../../includes/evaluation_sidebar.php'; ?>

    <main class="dashboard-content">
        <div class="page-shell">
            <?php require_once __DIR__ . '/../../includes/protected_topbar.php'; ?>

            <section class="page-card list-card">
                <div class="list-header">
                    <div>

                    </div>

                    <div class="list-actions">
                        <form method="POST" class="inline-form js-confirm-form">
                            <input type="hidden" name="run_full_sync" value="1">
                            <button type="submit" class="btn btn-primary"
                                    data-title="Run full sync?"
                                    data-text="This will update the sync timestamp for all EE LMS enrollment records."
                                    data-confirm="Run Sync">
                                Run Full Sync
                            </button>
                        </form>

                        <form method="POST" class="inline-form js-confirm-form">
                            <input type="hidden" name="toggle_auto_sync" value="1">
                            <?php if ($autoSyncEnabled): ?>
                                <button type="submit" class="btn btn-danger"
                                        data-title="Disable automatic sync?"
                                        data-text="Automatic LMS sync will be turned off."
                                        data-confirm="Disable">
                                    Disable Auto Sync
                                </button>
                            <?php else: ?>
                                <button type="submit" class="btn btn-secondary"
                                        data-title="Enable automatic sync?"
                                        data-text="Automatic LMS sync will be turned on."
                                        data-confirm="Enable">
                                    Enable Auto Sync
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <div class="application-admin-meta">
                    <div>
                        <span>Auto Sync</span>
                        <strong class="<?= $autoSyncEnabled ? 'text-success' : 'text-muted' ?>">
                            <?= $autoSyncEnabled ? 'Enabled' : 'Disabled' ?>
                        </strong>
                    </div>
                    <div><span>Total EEs</span><strong><?= (int) ($summary['total_ee'] ?? 0) ?></strong></div>
                    <div><span>Enrollments</span><strong><?= (int) ($summary['total_enrollments'] ?? 0) ?></strong></div>
                    <div><span>Active Access</span><strong><?= (int) ($summary['active_access'] ?? 0) ?></strong></div>
                    <div><span>Disabled Access</span><strong><?= (int) ($summary['disabled_access'] ?? 0) ?></strong></div>
                    <div><span>Never Synced</span><strong><?= (int) ($summary['never_synced'] ?? 0) ?></strong></div>
                    <div><span>Without Courses</span><strong><?= (int) ($summary['users_without_courses'] ?? 0) ?></strong></div>
                    <div>
                        <span>Last Full Sync</span>
                        <strong>
                            <?= $summary['last_full_sync']
                                    ? htmlspecialchars(formatFullDateTime($summary['last_full_sync']))
                                    : 'Never' ?>
                        </strong>
                    </div>
                </div>
            </section>

            <section class="page-card list-card">
                <div class="list-header">
                    <div>
                        <h2 class="page-title">Sync Results</h2>
                        <p class="page-subtitle">Current LMS enrollment status for every specialist employee.</p>
                    </div>
                </div>

                <form method="GET" class="admin-form" style="margin-bottom: 22px;">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" class="admin-select">
                                <option value="all"          <?= $statusFilter === 'all'          ? 'selected' : '' ?>>All</option>
                                <option value="active"       <?= $statusFilter === 'active'       ? 'selected' : '' ?>>Active Access</option>
                                <option value="disabled"     <?= $statusFilter === 'disabled'     ? 'selected' : '' ?>>Disabled Access</option>
                                <option value="never_synced" <?= $statusFilter === 'never_synced' ? 'selected' : '' ?>>Never Synced</option>
                                <option value="missing"      <?= $statusFilter === 'missing'      ? 'selected' : '' ?>>No Enrollment</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Apply</button>
                        <a href="full_sync.php" class="btn btn-secondary">Reset</a>
                    </div>
                </form>

                <div class="table-card">
                    <div class="table-wrapper">
                        <table>
                            <thead>
                            <tr>
                                <th>Specialist Employee</th>
                                <th>Email</th>
                                <th>Course</th>
                                <th>LMS Access</th>
                                <th>Sync Status</th>
                                <th>Last Sync</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($rows)): ?>
                                <tr><td colspan="6" class="empty-state">No records match your filters.</td></tr>
                            <?php else: ?>
                                <?php foreach ($rows as $row):
                                    $hasCourse = !empty($row['course_code']);
                                    $isActive  = (int) ($row['lms_access'] ?? 0) === 1;

                                    if (!$hasCourse) {
                                        $accessLabel = 'No Course';   $accessClass = 'status-rejected';
                                        $syncLabel   = 'No Enrollment'; $syncClass = 'status-rejected';
                                    } elseif ($isActive) {
                                        $accessLabel = 'Active';   $accessClass = 'status-approved';
                                        $syncLabel   = 'Synced';   $syncClass   = 'status-approved';
                                    } else {
                                        $accessLabel = 'Disabled'; $accessClass = 'status-rejected';
                                        $syncLabel   = 'Review';   $syncClass   = 'status-pending';
                                    }
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['username']) ?></td>
                                        <td><?= htmlspecialchars($row['email']) ?></td>
                                        <td>
                                            <?php if ($hasCourse): ?>
                                                <strong><?= htmlspecialchars($row['course_code']) ?></strong><br>
                                                <small><?= htmlspecialchars($row['course_name']) ?></small>
                                            <?php else: ?>
                                                <span style="color: var(--text-muted, #9ca3af);">None assigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="status-pill <?= $accessClass ?>"><?= $accessLabel ?></span></td>
                                        <td><span class="status-pill <?= $syncClass ?>"><?= $syncLabel ?></span></td>
                                        <td><?= $row['synced_at'] ? htmlspecialchars(formatFullDateTime($row['synced_at'])) : 'Never' ?></td>
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
        const flash = document.getElementById('flash-data');
        if (flash && flash.dataset.title && typeof Swal !== 'undefined') {
            Swal.fire({
                icon:               flash.dataset.type  || 'success',
                title:              flash.dataset.title || 'Done',
                html:               flash.dataset.text  || '',
                confirmButtonColor: '#2563eb'
            });
        }

        document.querySelectorAll('.js-confirm-form').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                const btn = form.querySelector('button[type="submit"]');
                if (typeof Swal === 'undefined') { form.submit(); return; }

                Swal.fire({
                    title:              btn.dataset.title   || 'Are you sure?',
                    text:               btn.dataset.text    || '',
                    icon:               'warning',
                    showCancelButton:   true,
                    confirmButtonColor: btn.classList.contains('btn-danger') ? '#dc2626' : '#2563eb',
                    cancelButtonColor:  '#64748b',
                    confirmButtonText:  btn.dataset.confirm || 'Confirm',
                    cancelButtonText:   'Cancel'
                }).then(result => {
                    if (result.isConfirmed) form.submit();
                });
            });
        });
    });
</script>

<?php require_once __DIR__ . '/../../includes/protected_footer.php'; ?>
</body>
</html>