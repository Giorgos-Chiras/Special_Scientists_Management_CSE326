<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../includes/db.php';

$pageTitle = 'Applications';
$search = trim($_GET['search'] ?? '');

$sql = "SELECT id, title, status, created_at FROM applications";
$params = [];

if ($search !== '') {
    $sql .= " WHERE title LIKE :search OR status LIKE :search";
    $params[':search'] = '%' . $search . '%';
}

$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Applications</title>
        <link rel="stylesheet" href="../assets/css/style.css">
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
                        <h2 class="page-title">Applications</h2>
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
                                    placeholder="Search by title or status"
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
                                <th>Title</th>
                                <th>Status</th>
                                <th>Created</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($applications)): ?>
                                <tr>
                                    <td colspan="4" class="empty-state">No applications found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($applications as $application): ?>
                                    <tr>
                                        <td><?= (int) $application['id']; ?></td>
                                        <td><?= htmlspecialchars($application['title']); ?></td>
                                        <td><?= htmlspecialchars($application['status']); ?></td>
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