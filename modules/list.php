<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once '../includes/db.php';


$keyword = trim($_GET['keyword'] ?? '');

$sql = "
    SELECT applications.id, applications.title, applications.department, applications.status, applications.created_at, users.username
    FROM applications
    INNER JOIN users ON applications.user_id = users.id
";

$params = [];

if ($keyword !== '') {
    $sql .= " WHERE applications.title LIKE :kw OR applications.department LIKE :kw OR users.username LIKE :kw";
    $params['kw'] = '%' . $keyword . '%';
}

$sql .= " ORDER BY applications.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$applications = $stmt->fetchAll();

function getStatusClass(string $status): string
{
    return match ($status) {
        'approved' => 'status-pill status-approved',
        'rejected' => 'status-pill status-rejected',
        default => 'status-pill status-pending',
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applications List</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/list.css">
</head>
<body>

<div class="list-layout">
    <div class="top-brand">Special Scientists <strong>C.U.T.</strong></div>

    <div class="page-shell">
        <div class="page-card list-card">
            <div class="list-header">
                <div>
                    <h1 class="page-title">Applications</h1>
                    <p class="page-subtitle">Search and browse application records.</p>
                </div>

                <div class="list-actions">
                    <a href="dashboard.php" class="btn btn-secondary">Dashboard</a>
                    <a href="../auth/logout.php" class="btn btn-danger">Logout</a>
                </div>
            </div>

            <div class="search-card">
                <form method="GET" action="" class="search-form">
                    <div class="search-group">
                        <label for="keyword">Keyword Search</label>
                        <input
                            type="text"
                            id="keyword"
                            name="keyword"
                            placeholder="Search by title, department, or username"
                            value="<?php echo htmlspecialchars($keyword); ?>"
                        >
                    </div>

                    <button type="submit" class="btn btn-primary">Search</button>
                </form>
            </div>

            <p class="results-meta">
                <?php echo count($applications); ?> result(s) found
            </p>

            <div class="table-card">
                <?php if (empty($applications)): ?>
                    <div class="empty-state">
                        No applications found.
                    </div>
                <?php else: ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Department</th>
                                <th>Status</th>
                                <th>User</th>
                                <th>Created At</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($applications as $application): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($application['id']); ?></td>
                                    <td><?php echo htmlspecialchars($application['title']); ?></td>
                                    <td><?php echo htmlspecialchars($application['department']); ?></td>
                                    <td>
                                            <span class="<?php echo htmlspecialchars(getStatusClass($application['status'])); ?>">
                                                <?php echo htmlspecialchars($application['status']); ?>
                                            </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($application['username']); ?></td>
                                    <td><?php echo htmlspecialchars($application['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

</body>
</html>