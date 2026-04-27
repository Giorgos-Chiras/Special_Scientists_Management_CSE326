<?php
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/crud/recruitment_periods_crud.php';
require_once __DIR__ . '/../../../utils/time_utils.php';

$errors = [];
$action = $_GET['action'] ?? 'list';
$editPeriod = null;
$search = trim($_GET['search'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_period'])) {
    $title = trim($_POST['title'] ?? '');
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';

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

    if (empty($errors) && recruitmentPeriodOverlaps($pdo, $startDate, $endDate)) {
        $errors[] = 'This period overlaps with an existing recruitment period.';
    }

    if (empty($errors)) {
        createRecruitmentPeriod($pdo, $title, $startDate, $endDate);

        $_SESSION['flash'] = [
                'type' => 'success',
                'title' => 'Period created',
                'text' => 'The recruitment period was created successfully.',
        ];

        header('Location: admin.php?page=periods');
        exit;
    }

    $action = 'create';
}

if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $editPeriod = getRecruitmentPeriodById($pdo, $id);

    if (!$editPeriod) {
        header('Location: admin.php?page=periods');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_period'])) {
    $id = (int) ($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';

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

    if (empty($errors) && recruitmentPeriodOverlaps($pdo, $startDate, $endDate, $id)) {
        $errors[] = 'This period overlaps with an existing recruitment period.';
    }

    if (empty($errors)) {
        updateRecruitmentPeriod($pdo, $id, $title, $startDate, $endDate);

        header('Location: admin.php?page=periods');
        exit;
    }

    $action = 'edit';
    $editPeriod = [
            'id' => $id,
            'title' => $title,
            'start_date' => $startDate,
            'end_date' => $endDate
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_period'])) {
    $id = (int) ($_POST['id'] ?? 0);

    if ($id > 0) {
        deleteRecruitmentPeriod($pdo, $id);
    }

    header('Location: admin.php?page=periods');
    exit;
}

$periods = searchRecruitmentPeriods($pdo, $search);
$today = date('Y-m-d');
?>

<section class="page-card list-card">
    <div class="list-header">
        <div>
            <h1 class="page-title">Recruitment Periods</h1>
            <p class="page-subtitle">Manage recruitment periods.</p>
        </div>

        <div class="list-actions">
            <a href="admin.php?page=recruitment" class="btn btn-secondary">Back</a>
            <a href="admin.php?page=periods&action=create" class="btn btn-primary">Add Period</a>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div
                id="flash-data"
                data-type="error"
                data-title="Please fix the following"
                data-text="<?= htmlspecialchars(implode('<br>', $errors)); ?>"
                style="display:none"
        ></div>
    <?php endif; ?>

    <?php if ($action === 'create' || ($action === 'edit' && $editPeriod)): ?>
        <section class="search-card">
            <form method="POST" class="admin-form">
                <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="id" value="<?= (int)$editPeriod['id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title"
                           value="<?= htmlspecialchars($editPeriod['title'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label>Start Date</label>
                    <input type="date" name="start_date"
                           value="<?= htmlspecialchars($editPeriod['start_date'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label>End Date</label>
                    <input type="date" name="end_date"
                           value="<?= htmlspecialchars($editPeriod['end_date'] ?? '') ?>" required>
                </div>

                <div class="form-actions">
                    <button type="submit" name="<?= $action === 'edit' ? 'update_period' : 'create_period'; ?>" class="btn btn-primary">
                        Save
                    </button>
                    <a href="admin.php?page=periods" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </section>

    <?php else: ?>
        <div class="table-card">
            <div class="table-wrapper">
                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($periods as $period): ?>
                        <?php $isActive = ($today >= $period['start_date'] && $today <= $period['end_date']); ?>
                        <tr>
                            <td><?= $period['id']; ?></td>
                            <td><?= htmlspecialchars($period['title']); ?></td>
                            <td><?= formatDate($period['start_date']); ?></td>
                            <td><?= formatDate($period['end_date']); ?></td>
                            <td>
                                <span class="status-pill <?= $isActive ? 'status-approved' : 'status-pending'; ?>">
                                    <?= $isActive ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <a href="admin.php?page=periods&action=edit&id=<?= $period['id']; ?>" class="btn btn-secondary btn-sm-custom">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</section>