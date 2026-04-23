<?php
require_once __DIR__ . '/../../../includes/db.php';

$errors = [];
$action = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_assignment'])) {
    $applicationId = (int) ($_POST['application_id'] ?? 0);
    $evaluatorId = (int) ($_POST['evaluator_id'] ?? 0);

    if ($applicationId <= 0) {
        $errors[] = 'Application is required.';
    }

    if ($evaluatorId <= 0) {
        $errors[] = 'Evaluator is required.';
    }

    if (empty($errors)) {
        $checkStmt = $pdo->prepare("
            SELECT id
            FROM application_evaluators
            WHERE application_id = :application_id AND evaluator_id = :evaluator_id
            LIMIT 1
        ");
        $checkStmt->execute([
                ':application_id' => $applicationId,
                ':evaluator_id' => $evaluatorId
        ]);

        if ($checkStmt->fetch()) {
            $errors[] = 'This evaluator is already assigned to that application.';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO application_evaluators (application_id, evaluator_id)
                VALUES (:application_id, :evaluator_id)
            ");
            $stmt->execute([
                    ':application_id' => $applicationId,
                    ':evaluator_id' => $evaluatorId
            ]);

            $_SESSION['flash'] = [
                    'type' => 'success',
                    'title' => 'Assignment created',
                    'text' => 'The evaluator was assigned successfully.'
            ];

            header('Location: admin.php?page=evaluator_assignments');
            exit;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_assignment'])) {
    $id = (int) ($_POST['id'] ?? 0);

    if ($id > 0) {
        $stmt = $pdo->prepare("DELETE FROM application_evaluators WHERE id = :id");
        $stmt->execute([':id' => $id]);

        $_SESSION['flash'] = [
                'type' => 'success',
                'title' => 'Assignment removed',
                'text' => 'The evaluator assignment was removed successfully.'
        ];

        header('Location: admin.php?page=evaluator_assignments');
        exit;
    }
}

$appStmt = $pdo->query("
    SELECT a.id, a.title, u.username AS candidate_name
    FROM applications a
    INNER JOIN users u ON a.user_id = u.id
    ORDER BY a.id DESC
");
$applications = $appStmt->fetchAll(PDO::FETCH_ASSOC);

$evaluatorStmt = $pdo->query("
    SELECT id, username, email
    FROM users
    WHERE role = 'evaluator'
    ORDER BY username ASC
");
$evaluators = $evaluatorStmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("
    SELECT
        ae.id,
        a.title AS application_title,
        u.username AS candidate_name,
        e.username AS evaluator_name,
        ae.assigned_at
    FROM application_evaluators ae
    INNER JOIN applications a ON ae.application_id = a.id
    INNER JOIN users u ON a.user_id = u.id
    INNER JOIN users e ON ae.evaluator_id = e.id
    ORDER BY ae.id DESC
");
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="page-card list-card">
    <div class="list-header">
        <div>
            <h1 class="page-title">Evaluator Assignments</h1>
            <p class="page-subtitle">Assign evaluators to candidate applications.</p>
        </div>

        <div class="list-actions">
            <a href="admin.php?page=recruitment" class="btn btn-secondary">Back</a>
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

    <section class="search-card">
        <form method="POST" action="admin.php?page=evaluator_assignments" class="admin-form js-validate-form" novalidate>
            <div class="form-group">
                <label for="application_id">Application</label>
                <select id="application_id" name="application_id" class="admin-select" required>
                    <option value="">Select application</option>
                    <?php foreach ($applications as $application): ?>
                        <option value="<?= (int) $application['id']; ?>">
                            <?= htmlspecialchars('#' . $application['id'] . ' - ' . $application['title'] . ' (' . $application['candidate_name'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="evaluator_id">Evaluator</label>
                <select id="evaluator_id" name="evaluator_id" class="admin-select" required>
                    <option value="">Select evaluator</option>
                    <?php foreach ($evaluators as $evaluator): ?>
                        <option value="<?= (int) $evaluator['id']; ?>">
                            <?= htmlspecialchars($evaluator['username'] . ' - ' . $evaluator['email']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit" name="create_assignment" class="btn btn-primary">Assign Evaluator</button>
            </div>
        </form>
    </section>

    <div class="results-meta">
        Total assignments found: <strong><?= count($assignments); ?></strong>
    </div>

    <div class="table-card">
        <div class="table-wrapper">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Application</th>
                    <th>Candidate</th>
                    <th>Evaluator</th>
                    <th>Assigned At</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($assignments)): ?>
                    <tr>
                        <td colspan="6" class="empty-state">No assignments found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($assignments as $assignment): ?>
                        <tr>
                            <td><?= (int) $assignment['id']; ?></td>
                            <td><?= htmlspecialchars($assignment['application_title']); ?></td>
                            <td><?= htmlspecialchars($assignment['candidate_name']); ?></td>
                            <td><?= htmlspecialchars($assignment['evaluator_name']); ?></td>
                            <td><?= htmlspecialchars($assignment['assigned_at']); ?></td>
                            <td>
                                <div class="table-actions">
                                    <form method="POST" action="admin.php?page=evaluator_assignments" class="inline-form">
                                        <input type="hidden" name="id" value="<?= (int) $assignment['id']; ?>">
                                        <input type="hidden" name="delete_assignment" value="1">
                                        <button type="button" class="btn btn-danger btn-sm-custom js-delete-button">Remove</button>
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
</section>