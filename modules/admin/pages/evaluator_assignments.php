<?php
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/crud/applications_crud.php';
require_once __DIR__ . '/../../../includes/crud/application_evaluators_crud.php';
require_once __DIR__ . '/../../../includes/crud/users_crud.php';
require_once __DIR__ . '/../../../utils/time_utils.php';


$errors = [];

function setEvaluatorAssignmentFlash(string $type, string $title, string $text): void
{
    $_SESSION['flash'] = [
            'type' => $type,
            'title' => $title,
            'text' => $text,
    ];
}

function redirectToEvaluatorAssignments(): void
{
    header('Location: admin.php?page=evaluator_assignments');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_assignment'])) {
    $applicationId = (int)($_POST['application_id'] ?? 0);
    $evaluatorId = (int)($_POST['evaluator_id'] ?? 0);

    if ($applicationId <= 0) {
        $errors[] = 'Application is required.';
    }

    if ($evaluatorId <= 0) {
        $errors[] = 'Evaluator is required.';
    }

    if (empty($errors)) {
        if (applicationEvaluatorExists($pdo, $applicationId, $evaluatorId)) {
            $errors[] = 'This evaluator is already assigned to that application.';
        } else {
            assignEvaluatorToApplication($pdo, $applicationId, $evaluatorId);
            setEvaluatorAssignmentFlash('success', 'Assignment created', 'The evaluator was assigned successfully.');
            redirectToEvaluatorAssignments();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_assignment'])) {
    $id = (int)($_POST['id'] ?? 0);

    if ($id > 0) {
        deleteApplicationEvaluator($pdo, $id);
        setEvaluatorAssignmentFlash('success', 'Assignment removed', 'The evaluator assignment was removed successfully.');
    }

    redirectToEvaluatorAssignments();
}

$applications = getAllApplications($pdo);
$applicationById = [];
foreach ($applications as $application) {
    $applicationById[(int)$application['id']] = $application;
}

$evaluators = array_values(array_filter(
        getAllUsers($pdo),
        static fn(array $user): bool => ($user['role'] ?? '') === 'evaluator'
));

usort($evaluators, static fn(array $a, array $b): int => strcasecmp($a['username'], $b['username']));

$assignments = getAllApplicationEvaluators($pdo);
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
        <form method="POST" action="admin.php?page=evaluator_assignments" class="admin-form js-validate-form"
              novalidate>
            <div class="form-group">
                <label for="application_id">Application</label>
                <select id="application_id" name="application_id" class="admin-select" required>
                    <option value="">Select application</option>
                    <?php foreach ($applications as $application): ?>
                        <option value="<?= (int)$application['id']; ?>">
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
                        <option value="<?= (int)$evaluator['id']; ?>">
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
                        <?php $assignedApplication = $applicationById[(int)$assignment['application_id']] ?? []; ?>
                        <tr>
                            <td><?= (int)$assignment['id']; ?></td>
                            <td><?= htmlspecialchars($assignment['application_title']); ?></td>
                            <td><?= htmlspecialchars($assignedApplication['candidate_name'] ?? 'Unknown candidate'); ?></td>
                            <td><?= htmlspecialchars($assignment['evaluator_name']); ?></td>
                            <td><?= htmlspecialchars(formatFullDateTime($assignment['assigned_at'])); ?></td>
                            <td>
                                <div class="table-actions">
                                    <form method="POST" action="admin.php?page=evaluator_assignments"
                                          class="inline-form">
                                        <input type="hidden" name="id" value="<?= (int)$assignment['id']; ?>">
                                        <input type="hidden" name="delete_assignment" value="1">
                                        <button
                                                type="button"
                                                class="btn btn-danger btn-sm-custom js-delete-button"
                                                data-title="Remove assignment?"
                                                data-text="This will unassign the evaluator from the application.",
                                        >
                                            Remove
                                        </button>
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