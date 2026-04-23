<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../includes/db.php';

$pageTitle = 'Applications';
$search = trim($_GET['search'] ?? '');
$userId = (int) $_SESSION['user_id'];

$sql = "
    SELECT
        a.id,
        a.title,
        a.status,
        a.created_at,
        c.name AS course_name,
        c.code AS course_code,
        d.name AS department_name,
        f.name AS faculty_name,
        rp.title AS period_title,
        rp.start_date,
        rp.end_date
    FROM applications a
    INNER JOIN courses c ON a.course_id = c.id
    INNER JOIN departments d ON c.department_id = d.id
    INNER JOIN faculties f ON d.faculty_id = f.id
    INNER JOIN recruitment_periods rp ON a.period_id = rp.id
    WHERE a.user_id = :user_id
";

$params = [
        ':user_id' => $userId
];

if ($search !== '') {
    $sql .= "
        AND (
            a.title LIKE :search
            OR a.status LIKE :search
            OR c.name LIKE :search
            OR c.code LIKE :search
            OR d.name LIKE :search
            OR f.name LIKE :search
            OR rp.title LIKE :search
        )
    ";
    $params[':search'] = '%' . $search . '%';
}

$sql .= " ORDER BY a.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

function applicationStatusClass(string $status): string
{
    return match ($status) {
        'draft' => 'status-draft',
        'submitted' => 'status-submitted',
        'under_review' => 'status-review',
        'approved' => 'status-approved',
        'rejected' => 'status-rejected',
        default => 'status-draft',
    };
}

function applicationProgressWidth(string $status): string
{
    return match ($status) {
        'draft' => '20%',
        'submitted' => '40%',
        'under_review' => '70%',
        'approved' => '100%',
        'rejected' => '100%',
        default => '20%',
    };
}

function applicationProgressClass(string $status): string
{
    return match ($status) {
        'approved' => 'progress-approved',
        'rejected' => 'progress-rejected',
        default => 'progress-active',
    };
}

function applicationStatusLabel(string $status): string
{
    return match ($status) {
        'draft' => 'Draft',
        'submitted' => 'Submitted',
        'under_review' => 'Under Review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        default => ucfirst(str_replace('_', ' ', $status)),
    };
}
?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Applications</title>
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
                        <h2 class="page-title">My Applications</h2>
                        <p class="page-subtitle">View your submitted applications and track their current status.</p>
                    </div>
                </div>

                <div class="search-card">
                    <form method="GET" action="list.php" class="search-form">
                        <div class="search-group">
                            <label for="search">Search applications</label>
                            <input
                                    type="text"
                                    id="search"
                                    name="search"
                                    placeholder="Search by title, course, department, faculty, status or period"
                                    value="<?= htmlspecialchars($search); ?>"
                            >
                        </div>

                        <div class="list-actions">
                            <button type="submit" class="btn btn-primary">Search</button>
                            <a href="list.php" class="btn btn-secondary">Reset</a>
                        </div>
                    </form>
                </div>

                <div class="results-meta">
                    Total applications found: <strong><?= count($applications); ?></strong>
                </div>

                <div class="table-card">
                    <div class="table-wrapper">
                        <table>
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Application</th>
                                <th>Course</th>
                                <th>Department</th>
                                <th>Faculty</th>
                                <th>Period</th>
                                <th>Status</th>
                                <th>Progress</th>
                                <th>Created</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($applications)): ?>
                                <tr>
                                    <td colspan="9" class="empty-state">No applications found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($applications as $application): ?>
                                    <tr>
                                        <td><?= (int) $application['id']; ?></td>

                                        <td>
                                            <div class="application-title-cell">
                                                <strong><?= htmlspecialchars($application['title']); ?></strong>
                                            </div>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($application['course_name']); ?>
                                            <?php if (!empty($application['course_code'])): ?>
                                                <div class="table-subtext"><?= htmlspecialchars($application['course_code']); ?></div>
                                            <?php endif; ?>
                                        </td>

                                        <td><?= htmlspecialchars($application['department_name']); ?></td>

                                        <td><?= htmlspecialchars($application['faculty_name']); ?></td>

                                        <td>
                                            <?= htmlspecialchars($application['period_title']); ?>
                                            <div class="table-subtext">
                                                <?= htmlspecialchars($application['start_date']); ?> - <?= htmlspecialchars($application['end_date']); ?>
                                            </div>
                                        </td>

                                        <td>
                                            <span class="status-pill <?= applicationStatusClass($application['status']); ?>">
                                                <?= htmlspecialchars(applicationStatusLabel($application['status'])); ?>
                                            </span>
                                        </td>

                                        <td>
                                            <div class="progress-track">
                                                <div
                                                        class="progress-fill <?= applicationProgressClass($application['status']); ?>"
                                                        style="width: <?= applicationProgressWidth($application['status']); ?>;"
                                                ></div>
                                            </div>
                                        </td>

                                        <td><?= htmlspecialchars($application['created_at']); ?></td>
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

<?php require_once __DIR__ . '/../includes/protected_footer.php'; ?>