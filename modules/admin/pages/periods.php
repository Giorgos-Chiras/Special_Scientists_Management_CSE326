<?php
require_once __DIR__ . '/../../../includes/db.php';

$errors = [];
$action = $_GET['action'] ?? 'list';
$editPeriod = null;
$search = trim($_GET['search'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_period'])) {
    $title = trim($_POST['title'] ?? '');
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if ($title === '') {
        $errors[] = 'Period title is required.';
    }

    if ($startDate === '') {
        $errors[] = 'Start date is required.';
    }

    if ($endDate === '') {
        $errors[] = 'End date is required.';
    }

    if ($startDate !== '' && $endDate !== '' && $endDate < $startDate) {
        $errors[] = 'End date cannot be before start date.';
    }

    if (empty($errors)) {
        if ($isActive === 1) {
            $pdo->exec("UPDATE recruitment_periods SET is_active = 0");
        }

        $stmt = $pdo->prepare("
            INSERT INTO recruitment_periods (title, start_date, end_date, is_active)
            VALUES (:title, :start_date, :end_date, :is_active)
        ");
        $stmt->execute([
                ':title' => $title,
                ':start_date' => $startDate,
                ':end_date' => $endDate,
                ':is_active' => $isActive
        ]);

        $_SESSION['flash'] = [
                'type' => 'success',
                'title' => 'Period created',
                'text' => 'The recruitment period was created successfully.'
        ];

        header('Location: admin.php?page=periods');
        exit;
    }
}

if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];

    $stmt = $pdo->prepare("SELECT id, title, start_date, end_date, is_active FROM recruitment_periods WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $editPeriod = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$editPeriod) {
        $_SESSION['flash'] = [
                'type' => 'error',
                'title' => 'Period not found',
                'text' => 'The selected period could not be found.'
        ];

        header('Location: admin.php?page=periods');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_period'])) {
    $id = (int) ($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if ($title === '') {
        $errors[] = 'Period title is required.';
    }

    if ($startDate === '') {
        $errors[] = 'Start date is required.';
    }

    if ($endDate === '') {
        $errors[] = 'End date is required.';
    }

    if ($startDate !== '' && $endDate !== '' && $endDate < $startDate) {
        $errors[] = 'End date cannot be before start date.';
    }

    if (empty($errors)) {
        if ($isActive === 1) {
            $pdo->exec("UPDATE recruitment_periods SET is_active = 0");
        }

        $stmt = $pdo->prepare("
            UPDATE recruitment_periods
            SET title = :title,
                start_date = :start_date,
                end_date = :end_date,
                is_active = :is_active
            WHERE id = :id
        ");
        $stmt->execute([
                ':title' => $title,
                ':start_date' => $startDate,
                ':end_date' => $endDate,
                ':is_active' => $isActive,
                ':id' => $id
        ]);

        $_SESSION['flash'] = [
                'type' => 'success',
                'title' => 'Period updated',
                'text' => 'The recruitment period was updated successfully.'
        ];

        header('Location: admin.php?page=periods');
        exit;
    }

    $action = 'edit';
    $editPeriod = [
            'id' => $id,
            'title' => $title,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_active' => $isActive
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_period'])) {
    $id = (int) ($_POST['id'] ?? 0);

    if ($id > 0) {
        $stmt = $pdo->prepare("DELETE FROM recruitment_periods WHERE id = :id");
        $stmt->execute([':id' => $id]);

        $_SESSION['flash'] = [
                'type' => 'success',
                'title' => 'Period deleted',
                'text' => 'The recruitment period was deleted successfully.'
        ];

        header('Location: admin.php?page=periods');
        exit;
    }
}

$sql = "SELECT id, title, start_date, end_date, is_active FROM recruitment_periods";
$params = [];

if ($search !== '') {
    $sql .= " WHERE title LIKE :search";
    $params[':search'] = '%' . $search . '%';
}

$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$periods = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="page-card list-card">
    <div class="list-header">
        <div>
            <h1 class="page-title">Recruitment Periods</h1>
            <p class="page-subtitle">Create, edit, and manage recruitment periods.</p>
        </div>

        <div class="list-actions">
            <a href="admin.php?page=recruitment" class="btn btn-secondary">Back</a>
            <a href="admin.php?page=periods" class="btn btn-secondary">Period List</a>
            <a href="admin.php?page=periods&action=create" class="btn btn-primary">Add Period</a>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($action === 'create' || ($action === 'edit' && $editPeriod)): ?>
        <section class="search-card">
            <form method="POST" action="admin.php?page=periods<?= $action === 'edit' ? '&action=edit&id=' . (int) $editPeriod['id'] : '&action=create' ?>" class="admin-form js-validate-form" novalidate>
                <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="id" value="<?= (int) $editPeriod['id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="title">Period Title</label>
                    <input type="text" id="title" name="title" value="<?= htmlspecialchars($action === 'edit' ? ($editPeriod['title'] ?? '') : ($_POST['title'] ?? '')); ?>" required>
                </div>

                <div class="form-group">
                    <label for="start_date">Start Date</label>
                    <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($action === 'edit' ? ($editPeriod['start_date'] ?? '') : ($_POST['start_date'] ?? '')); ?>" required>
                </div>

                <div class="form-group">
                    <label for="end_date">End Date</label>
                    <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($action === 'edit' ? ($editPeriod['end_date'] ?? '') : ($_POST['end_date'] ?? '')); ?>" required>
                </div>

                <div class="form-group">
                    <label for="is_active">Active Period</label>
                    <select id="is_active" name="is_active" class="admin-select">
                        <?php $activeValue = $action === 'edit' ? (string) ($editPeriod['is_active'] ?? '0') : (isset($_POST['is_active']) ? '1' : '0'); ?>
                        <option value="0" <?= $activeValue === '0' ? 'selected' : ''; ?>>No</option>
                        <option value="1" <?= $activeValue === '1' ? 'selected' : ''; ?>>Yes</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" name="<?= $action === 'edit' ? 'update_period' : 'create_period'; ?>" class="btn btn-primary">
                        <?= $action === 'edit' ? 'Update Period' : 'Create Period'; ?>
                    </button>
                    <a href="admin.php?page=periods" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </section>
    <?php else: ?>
        <div class="search-card">
            <form method="GET" action="admin.php" class="search-form">
                <input type="hidden" name="page" value="periods">

                <div class="search-group">
                    <label for="search">Search periods</label>
                    <input type="text" id="search" name="search" placeholder="Search by period title" value="<?= htmlspecialchars($search); ?>">
                </div>

                <div class="list-actions">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <a href="admin.php?page=periods" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>

        <div class="results-meta">
            Total periods found: <strong><?= count($periods); ?></strong>
        </div>

        <div class="table-card">
            <div class="table-wrapper">
                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($periods)): ?>
                        <tr>
                            <td colspan="6" class="empty-state">No periods found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($periods as $period): ?>
                            <tr>
                                <td><?= (int) $period['id']; ?></td>
                                <td><?= htmlspecialchars($period['title']); ?></td>
                                <td><?= htmlspecialchars($period['start_date']); ?></td>
                                <td><?= htmlspecialchars($period['end_date']); ?></td>
                                <td>
                                    <span class="status-pill <?= (int) $period['is_active'] === 1 ? 'status-approved' : 'status-pending'; ?>">
                                        <?= (int) $period['is_active'] === 1 ? 'active' : 'inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <a href="admin.php?page=periods&action=edit&id=<?= (int) $period['id']; ?>" class="btn btn-secondary btn-sm-custom">Edit</a>
                                        <form method="POST" action="admin.php?page=periods" class="inline-form">
                                            <input type="hidden" name="id" value="<?= (int) $period['id']; ?>">
                                            <input type="hidden" name="delete_period" value="1">
                                            <button type="button" class="btn btn-danger btn-sm-custom js-delete-button">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</section>