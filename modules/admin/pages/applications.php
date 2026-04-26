<?php
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../utils/status_utils.php';
require_once __DIR__ . '/../../../includes/crud/applications_crud.php';
require_once __DIR__ . '/../../../includes/crud/courses_crud.php';
require_once __DIR__ . '/../../../includes/crud/recruitment_periods_crud.php';
require_once __DIR__ . '/../../../includes/crud/users_crud.php';
require_once __DIR__ . '/../../../utils/time_utils.php';


$errors          = [];
$action          = $_GET['action'] ?? 'list';
$editApplication = null;
$search          = trim($_GET['search'] ?? '');

$statuses = getApplicationStatuses();

function setApplicationFlash(string $type, string $title, string $text): void
{
    $_SESSION['flash'] = [
            'type'  => $type,
            'title' => $title,
            'text'  => $text,
    ];
}

function redirectToApplications(): void
{
    header('Location: admin.php?page=applications');
    exit;
}

function validateApplicationForm(
        int $userId,
        int $courseId,
        int $periodId,
        string $title,
        string $status,
        array $statuses
): array {
    $errors = [];

    if ($userId <= 0) $errors[] = 'Candidate is required.';
    if ($courseId <= 0) $errors[] = 'Course is required.';
    if ($periodId <= 0) $errors[] = 'Recruitment period is required.';
    if ($title === '') $errors[] = 'Application title is required.';
    if (!array_key_exists($status, $statuses)) $errors[] = 'Invalid status selected.';

    return $errors;
}

function applicationMatchesSearch(array $application, string $search): bool
{
    if ($search === '') return true;

    $needle = mb_strtolower($search);
    $fields = [
            'title', 'status', 'candidate_name', 'candidate_email', 'course_name',
            'course_code', 'department_name', 'faculty_name', 'period_title'
    ];

    foreach ($fields as $field) {
        if (isset($application[$field]) && mb_strpos(mb_strtolower((string) $application[$field]), $needle) !== false) {
            return true;
        }
    }

    return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_application'])) {
    $userId         = (int) ($_POST['user_id'] ?? 0);
    $courseId       = (int) ($_POST['course_id'] ?? 0);
    $periodId       = (int) ($_POST['period_id'] ?? 0);
    $title          = trim($_POST['title'] ?? '');
    $status         = $_POST['status'] ?? 'draft';
    $coverLetter    = trim($_POST['cover_letter'] ?? '');
    $qualifications = trim($_POST['qualifications'] ?? '');

    $errors = validateApplicationForm($userId, $courseId, $periodId, $title, $status, $statuses);

    if (empty($errors)) {
        createApplication($pdo, $userId, $courseId, $periodId, $title, $status, $coverLetter, $qualifications);
        setApplicationFlash('success', 'Application created', 'The application was created successfully.');
        redirectToApplications();
    }

    $action = 'create';
}

if ($action === 'edit' && isset($_GET['id'])) {
    $editApplication = getApplicationById($pdo, (int) $_GET['id']);

    if (!$editApplication) {
        setApplicationFlash('error', 'Application not found', 'The selected application could not be found.');
        redirectToApplications();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_application'])) {
    $id             = (int) ($_POST['id'] ?? 0);
    $userId         = (int) ($_POST['user_id'] ?? 0);
    $courseId       = (int) ($_POST['course_id'] ?? 0);
    $periodId       = (int) ($_POST['period_id'] ?? 0);
    $title          = trim($_POST['title'] ?? '');
    $status         = $_POST['status'] ?? 'draft';
    $coverLetter    = trim($_POST['cover_letter'] ?? '');
    $qualifications = trim($_POST['qualifications'] ?? '');

    $errors = validateApplicationForm($userId, $courseId, $periodId, $title, $status, $statuses);

    if (empty($errors)) {
        $existingApplication = getApplicationById($pdo, $id);

        updateApplication(
                $pdo,
                $id,
                $courseId,
                $periodId,
                $title,
                $status,
                $coverLetter,
                $qualifications,
                $existingApplication["cv_file_path"] ?? null,
                $existingApplication["cv_original_name"] ?? null,
                $userId
        );
        setApplicationFlash('success', 'Application updated', 'The application was updated successfully.');
        redirectToApplications();
    }

    $action = 'edit';
    $editApplication = [
            'id'             => $id,
            'user_id'        => $userId,
            'course_id'      => $courseId,
            'period_id'      => $periodId,
            'title'          => $title,
            'status'         => $status,
            'cover_letter'   => $coverLetter,
            'qualifications' => $qualifications,
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id     = (int) ($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';

    if ($id > 0 && array_key_exists($status, $statuses)) {
        updateApplicationStatus($pdo, $id, $status);
        setApplicationFlash('success', 'Status updated', 'Application status was updated successfully.');
    }

    redirectToApplications();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_employee'])) {
    $id = (int) ($_POST['id'] ?? 0);

    if ($id > 0) {
        $application = getApplicationById($pdo, $id);

        if ($application) {
            $user = getUserById($pdo, (int) $application['user_id']);

            if ($user) {
                $pdo->beginTransaction();
                updateApplicationStatus($pdo, $id, 'approved');
                updateUser($pdo, (int) $user['id'], $user['username'], $user['email'], 'ee', (int) $user['is_active']);
                $pdo->commit();

                setApplicationFlash('success', 'Employee assigned', 'Candidate was approved and assigned as EE.');
            }
        }
    }

    redirectToApplications();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_application'])) {
    $id = (int) ($_POST['id'] ?? 0);

    if ($id > 0) {
        deleteApplication($pdo, $id);
        setApplicationFlash('success', 'Application deleted', 'Application was deleted successfully.');
    }

    redirectToApplications();
}

$candidates = array_values(array_filter(
        getAllUsers($pdo),
        static fn (array $user): bool => in_array($user['role'], ['candidate', 'ee'], true)
));

usort($candidates, static fn (array $a, array $b): int => strcasecmp($a['username'], $b['username']));

$courses = getAllCourses($pdo);
$periods = getAllRecruitmentPeriods($pdo);

usort(
        $periods,
        static fn (array $a, array $b): int => ((int) $b['is_active'] <=> (int) $a['is_active']) ?: ((int) $b['id'] <=> (int) $a['id'])
);

$applications = array_values(array_filter(
        getAllApplications($pdo),
        static fn (array $application): bool => applicationMatchesSearch($application, $search)
));
?>

<section class="page-card list-card">
    <div class="list-header">
        <div>
            <h1 class="page-title">Applications</h1>
            <p class="page-subtitle">Create, review, update statuses, and view submitted details.</p>
        </div>

        <div class="list-actions">
            <a href="admin.php?page=recruitment" class="btn btn-secondary">Back</a>
            <a href="admin.php?page=applications" class="btn btn-secondary">Application List</a>
            <a href="admin.php?page=applications&action=create" class="btn btn-primary">Add Application</a>
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

    <?php if ($action === 'create' || ($action === 'edit' && $editApplication)): ?>
        <section class="search-card">
            <form method="POST"
                  action="admin.php?page=applications<?= $action === 'edit' ? '&action=edit&id=' . (int) $editApplication['id'] : '&action=create'; ?>"
                  class="admin-form js-validate-form"
                  novalidate>

                <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="id" value="<?= (int) $editApplication['id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="user_id">Candidate</label>
                    <?php $selectedUser = $action === 'edit' ? ($editApplication['user_id'] ?? '') : ($_POST['user_id'] ?? ''); ?>
                    <select id="user_id" name="user_id" class="admin-select" required>
                        <option value="">Select candidate</option>
                        <?php foreach ($candidates as $candidate): ?>
                            <option value="<?= (int) $candidate['id']; ?>"
                                    <?= (string) $selectedUser === (string) $candidate['id'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($candidate['username'] . ' - ' . $candidate['email'] . ' (' . $candidate['role'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="course_id">Course</label>
                    <?php $selectedCourse = $action === 'edit' ? ($editApplication['course_id'] ?? '') : ($_POST['course_id'] ?? ''); ?>
                    <select id="course_id" name="course_id" class="admin-select" required>
                        <option value="">Select course</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?= (int) $course['id']; ?>"
                                    <?= (string) $selectedCourse === (string) $course['id'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($course['faculty_name'] . ' - ' . $course['department_name'] . ' - ' . $course['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="period_id">Recruitment Period</label>
                    <?php $selectedPeriod = $action === 'edit' ? ($editApplication['period_id'] ?? '') : ($_POST['period_id'] ?? ''); ?>
                    <select id="period_id" name="period_id" class="admin-select" required>
                        <option value="">Select period</option>
                        <?php foreach ($periods as $period): ?>
                            <option value="<?= (int) $period['id']; ?>"
                                    <?= (string) $selectedPeriod === (string) $period['id'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($period['title'] . ((int) $period['is_active'] === 1 ? ' (active)' : '')); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <?php $selectedStatus = $action === 'edit' ? ($editApplication['status'] ?? 'draft') : ($_POST['status'] ?? 'draft'); ?>
                    <select id="status" name="status" class="admin-select" required>
                        <?php foreach ($statuses as $value => $label): ?>
                            <option value="<?= htmlspecialchars($value); ?>"
                                    <?= $selectedStatus === $value ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group full-width">
                    <label for="title">Application Title</label>
                    <input type="text" id="title" name="title"
                           value="<?= htmlspecialchars($action === 'edit' ? ($editApplication['title'] ?? '') : ($_POST['title'] ?? '')); ?>"
                           required>
                </div>

                <div class="form-group full-width">
                    <label for="cover_letter">Cover Letter</label>
                    <textarea id="cover_letter" name="cover_letter" rows="6"><?= htmlspecialchars($action === 'edit' ? ($editApplication['cover_letter'] ?? '') : ($_POST['cover_letter'] ?? '')); ?></textarea>
                </div>

                <div class="form-group full-width">
                    <label for="qualifications">Qualifications</label>
                    <textarea id="qualifications" name="qualifications" rows="5"><?= htmlspecialchars($action === 'edit' ? ($editApplication['qualifications'] ?? '') : ($_POST['qualifications'] ?? '')); ?></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" name="<?= $action === 'edit' ? 'update_application' : 'create_application'; ?>" class="btn btn-primary">
                        <?= $action === 'edit' ? 'Update Application' : 'Create Application'; ?>
                    </button>
                    <a href="admin.php?page=applications" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </section>

    <?php else: ?>
        <div class="search-card">
            <form method="GET" action="admin.php" class="search-form">
                <input type="hidden" name="page" value="applications">

                <div class="search-group">
                    <label for="search">Search applications</label>
                    <input type="text" id="search" name="search"
                           placeholder="Search by candidate, course, department, period or status"
                           value="<?= htmlspecialchars($search); ?>">
                </div>

                <div class="list-actions">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <a href="admin.php?page=applications" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>

        <div class="results-meta">
            Total applications found: <strong><?= count($applications); ?></strong>
        </div>

        <div class="applications-grid">
            <?php if (empty($applications)): ?>
                <div class="empty-state">No applications found.</div>
            <?php else: ?>
                <?php foreach ($applications as $application): ?>
                    <article class="application-admin-card">
                        <div class="application-admin-head">
                            <div>
                                <span class="application-admin-id">#<?= (int) $application['id']; ?></span>
                                <h3><?= htmlspecialchars($application['title']); ?></h3>
                            </div>

                            <span class="status-pill <?= getStatusCssClass($application['status']); ?>">
                                <?= htmlspecialchars(getStatusLabel($application['status'])); ?>
                            </span>
                        </div>

                        <div class="application-admin-meta">
                            <div>
                                <span>Candidate</span>
                                <strong><?= htmlspecialchars($application['candidate_name']); ?></strong>
                                <small><?= htmlspecialchars($application['candidate_email']); ?></small>
                            </div>

                            <div>
                                <span>Course</span>
                                <strong><?= htmlspecialchars($application['course_name']); ?></strong>
                                <small><?= htmlspecialchars($application['course_code'] ?? 'No code'); ?></small>
                            </div>

                            <div>
                                <span>Department</span>
                                <strong><?= htmlspecialchars($application['department_name']); ?></strong>
                                <small><?= htmlspecialchars($application['faculty_name']); ?></small>
                            </div>

                            <div>
                                <span>Period</span>
                                <strong><?= htmlspecialchars($application['period_title']); ?></strong>
                                <small><?= htmlspecialchars(formatFullDateTime($application['updated_at']) ?? $application['created_at']); ?></small>
                            </div>
                        </div>

                        <form method="POST" action="admin.php?page=applications" class="application-status-box">
                            <input type="hidden" name="id" value="<?= (int) $application['id']; ?>">

                            <label for="status_<?= (int) $application['id']; ?>">Change Status</label>

                            <div class="status-change-row">
                                <select id="status_<?= (int) $application['id']; ?>" name="status" class="admin-select">
                                    <?php foreach ($statuses as $value => $label): ?>
                                        <option value="<?= htmlspecialchars($value); ?>"
                                                <?= $application['status'] === $value ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <button type="submit" name="update_status" class="btn btn-primary btn-sm-custom">Update</button>
                            </div>
                        </form>

                        <div class="application-admin-actions">
                            <a href="admin.php?page=application_view&id=<?= (int) $application['id']; ?>" class="btn btn-primary btn-sm-custom">View</a>

                            <a href="admin.php?page=applications&action=edit&id=<?= (int) $application['id']; ?>" class="btn btn-secondary btn-sm-custom">Edit</a>

                            <?php if (!empty($application['cv_file_path'])): ?>
                                <a href="<?= htmlspecialchars($application['cv_file_path']); ?>" target="_blank" class="btn btn-secondary btn-sm-custom">View CV</a>
                            <?php endif; ?>

                            <form method="POST" action="admin.php?page=applications" class="inline-form">
                                <input type="hidden" name="id" value="<?= (int) $application['id']; ?>">
                                <input type="hidden" name="assign_employee" value="1">
                                <button type="submit" class="btn btn-primary btn-sm-custom">Assign as EE</button>
                            </form>

                            <form method="POST" action="admin.php?page=applications" class="inline-form">
                                <input type="hidden" name="id" value="<?= (int) $application['id']; ?>">
                                <input type="hidden" name="delete_application" value="1">
                                <button type="button" class="btn btn-danger btn-sm-custom js-delete-button">Delete</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>