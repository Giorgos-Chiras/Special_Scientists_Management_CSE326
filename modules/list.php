<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/crud/applications_crud.php';
require_once __DIR__ . '/../includes/crud/application_evaluators_crud.php';
require_once __DIR__ . '/../includes/crud/courses_crud.php';
require_once __DIR__ . '/../includes/crud/recruitment_periods_crud.php';
require_once __DIR__ . '/../includes/crud/system_settings_crud.php';
require_once __DIR__ . '/../utils/status_utils.php';
require_once __DIR__ . '/../utils/pdf_utils.php';
require_once __DIR__ . '/../utils/time_utils.php';

$pageTitle = 'Applications';
$userId = (int) $_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? 'candidate';

$isCandidate = $userRole === 'candidate';
$isEvaluator = $userRole === 'evaluator';
$isHr = $userRole === 'hr';

if (!$isCandidate && !$isEvaluator && !$isHr) {
    header('Location: dashboard.php');
    exit;
}

$action = $_GET['action'] ?? 'list';
$errors = [];
$editApplication = null;

$uploadDir = __DIR__ . '/../uploads/cvs/';
$uploadWebPath = '../uploads/cvs/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

function buildQualificationsString(array|string|null $input): string
{
    if (is_array($input)) {
        $items = array_filter(array_map('trim', $input));
        return implode("\n", array_map(fn($item) => '* ' . ltrim($item, '* '), $items));
    }

    return trim((string) $input);
}

function parseQualificationsForForm(?string $qualifications): array
{
    $qualifications = trim((string) $qualifications);

    if ($qualifications === '') {
        return [''];
    }

    return array_values(array_filter(array_map(function ($line) {
        return trim(ltrim(trim($line), '* '));
    }, explode("\n", $qualifications))));
}

try {
    $settings = array_merge(
            ['applications_open' => '0'],
            getSystemSettingsMap($pdo)
    );
} catch (Throwable $e) {
    $settings = ['applications_open' => '0'];
}

$activePeriod = getCurrentRecruitmentPeriod($pdo);

$systemApplicationsOpen = ($settings['applications_open'] ?? '0') === '1';
$applicationsOpen = $systemApplicationsOpen && $activePeriod;

if ((!$isCandidate || !$applicationsOpen) && in_array($action, ['create', 'edit'], true)) {
    $action = 'list';
}

$courses = getCoursesForApplicationForm($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_application']) && $isCandidate) {
    if (!$applicationsOpen) {
        $errors[] = 'Applications are currently closed.';
    }

    $courseId = (int) ($_POST['course_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $coverLetter = trim($_POST['cover_letter'] ?? '');
    $qualifications = buildQualificationsString($_POST['qualifications'] ?? []);
    $submitType = $_POST['submit_type'] ?? 'draft';
    $status = $submitType === 'submit' ? 'submitted' : 'draft';

    if ($courseId <= 0) {
        $errors[] = 'Course is required.';
    }

    if ($title === '') {
        $errors[] = 'Application title is required.';
    }

    if ($status === 'submitted') {
        if ($coverLetter === '') {
            $errors[] = 'Cover letter is required before submitting.';
        }

        if ($qualifications === '') {
            $errors[] = 'Qualifications are required before submitting.';
        }
    }

    $cvData = uploadCv($errors);
    if (empty($errors)) {
        createApplication(
                $pdo,
                $userId,
                $courseId,
                (int) $activePeriod['id'],
                $title,
                $status,
                $coverLetter,
                $qualifications,
                $cvData['path'],
                $cvData['original_name']
        );

        $_SESSION['flash'] = [
                'type' => 'success',
                'title' => $status === 'submitted' ? 'Application submitted' : 'Draft saved',
                'text' => $status === 'submitted'
                        ? 'Your application was submitted for review.'
                        : 'Your application was saved as a draft.',
        ];

        header('Location: list.php');
        exit;
    }

    $action = 'create';
}

if ($action === 'edit' && isset($_GET['id']) && $isCandidate) {
    $id = (int) $_GET['id'];

    $editApplication = getApplicationByIdForCandidate($pdo, $id, $userId);

    if (!$editApplication) {
        $_SESSION['flash'] = [
                'type' => 'error',
                'title' => 'Application not found',
                'text' => 'The selected application could not be found.',
        ];

        header('Location: list.php');
        exit;
    }

    if ($editApplication['status'] !== 'draft') {
        $_SESSION['flash'] = [
                'type' => 'error',
                'title' => 'Cannot edit',
                'text' => 'Only draft applications can be edited.',
        ];

        header('Location: list.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_application']) && $isCandidate) {
    if (!$applicationsOpen) {
        $errors[] = 'Applications are currently closed.';
    }

    $id = (int) ($_POST['id'] ?? 0);
    $courseId = (int) ($_POST['course_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $coverLetter = trim($_POST['cover_letter'] ?? '');
    $qualifications = buildQualificationsString($_POST['qualifications'] ?? []);
    $submitType = $_POST['submit_type'] ?? 'draft';
    $status = $submitType === 'submit' ? 'submitted' : 'draft';

    $existingApplication = getApplicationByIdForCandidate($pdo, $id, $userId);

    if (!$existingApplication || $existingApplication['status'] !== 'draft') {
        $errors[] = 'Only draft applications can be updated.';
    }

    if ($courseId <= 0) {
        $errors[] = 'Course is required.';
    }

    if ($title === '') {
        $errors[] = 'Application title is required.';
    }

    if ($status === 'submitted') {
        if ($coverLetter === '') {
            $errors[] = 'Cover letter is required before submitting.';
        }

        if ($qualifications === '') {
            $errors[] = 'Qualifications are required before submitting.';
        }
    }

    $cvData = uploadCv($errors, $existingApplication);
    if (empty($errors)) {
        updateCandidateApplication(
                $pdo,
                $id,
                $userId,
                $courseId,
                $title,
                $status,
                $coverLetter,
                $qualifications,
                $cvData['path'],
                $cvData['original_name']
        );

        $_SESSION['flash'] = [
                'type' => 'success',
                'title' => $status === 'submitted' ? 'Application submitted' : 'Draft updated',
                'text' => $status === 'submitted'
                        ? 'Your application was submitted for review.'
                        : 'Your draft was updated successfully.',
        ];

        header('Location: list.php');
        exit;
    }

    $action = 'edit';
    $editApplication = [
            'id' => $id,
            'course_id' => $courseId,
            'title' => $title,
            'status' => 'draft',
            'cover_letter' => $coverLetter,
            'qualifications' => $qualifications,
            'cv_file_path' => $existingApplication['cv_file_path'] ?? null,
            'cv_original_name' => $existingApplication['cv_original_name'] ?? null,
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status']) && $isEvaluator) {
    $applicationId = (int) ($_POST['id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';
    $allowedStatuses = ['under_review', 'approved', 'rejected'];

    if (
            $applicationId > 0
            && in_array($newStatus, $allowedStatuses, true)
            && evaluatorCanAccessApplication($pdo, $applicationId, $userId)
    ) {
        updateApplicationStatus($pdo, $applicationId, $newStatus);

        $_SESSION['flash'] = [
                'type' => 'success',
                'title' => 'Status updated',
                'text' => 'The application status was updated successfully.',
        ];
    }

    header('Location: list.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_application']) && $isCandidate) {
    $id = (int) ($_POST['id'] ?? 0);

    if ($id > 0) {
        $applicationToDelete = getApplicationByIdForCandidate($pdo, $id, $userId);

        if ($applicationToDelete && $applicationToDelete['status'] === 'draft') {
            if (!empty($applicationToDelete['cv_file_path'])) {
                $filePath = realpath(__DIR__ . '/../' . str_replace('../', '', $applicationToDelete['cv_file_path']));
                $uploadBase = realpath($uploadDir);

                if (
                        $filePath
                        && $uploadBase
                        && strpos($filePath, $uploadBase) === 0
                        && file_exists($filePath)
                ) {
                    unlink($filePath);
                }
            }

            deleteApplication($pdo, $id, $userId);

            $_SESSION['flash'] = [
                    'type' => 'success',
                    'title' => 'Application deleted',
                    'text' => 'Your draft application was deleted successfully.',
            ];
        }
    }

    header('Location: list.php');
    exit;
}

if ($isCandidate) {
    $applications = getApplicationsByUserId($pdo, $userId);
} elseif ($isEvaluator) {
    $applications = getApplicationsForEvaluator($pdo, $userId);
} else {
    $applications = getAllApplications($pdo);
}

$qualificationValues = [''];

if ($action === 'edit' && $editApplication) {
    $qualificationValues = parseQualificationsForForm($editApplication['qualifications'] ?? '');
} elseif (!empty($_POST['qualifications'])) {
    $qualificationValues = is_array($_POST['qualifications'])
            ? array_values(array_filter(array_map('trim', $_POST['qualifications'])))
            : parseQualificationsForForm($_POST['qualifications']);
}

if (empty($qualificationValues)) {
    $qualificationValues = [''];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Applications</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/list.css?v=<?= filemtime(__DIR__ . '/../assets/css/list.css'); ?>">
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
                <?php if ($isCandidate): ?>
                    <div class="list-header">
                        <div class="period-display">
                            <?php if ($activePeriod): ?>
                                <span class="status-pill status-approved">Active</span>
                                <div>
                                    <strong><?= htmlspecialchars($activePeriod['title']); ?></strong>
                                    <small><?= htmlspecialchars($activePeriod['start_date']); ?> – <?= htmlspecialchars($activePeriod['end_date']); ?></small>
                                </div>
                            <?php else: ?>
                                <span class="status-pill status-rejected">Inactive</span>
                                <div>
                                    <strong>No active period</strong>
                                    <small>Today is not inside any recruitment period.</small>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="list-actions">
                            <?php if ($applicationsOpen): ?>
                                <a href="list.php?action=create" class="btn btn-primary">Start Application</a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!$systemApplicationsOpen): ?>
                        <div class="notice notice-warning">
                            <strong>Applications are closed.</strong>
                            The system administrator has disabled new applications.
                        </div>
                    <?php elseif (!$activePeriod): ?>
                        <div class="notice notice-warning">
                            <strong>No active recruitment period.</strong>
                            There is no open recruitment period right now.
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="list-header">

                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div
                            id="flash-data"
                            data-type="error"
                            data-title="Please fix the following"
                            data-text="<?= htmlspecialchars(implode('<br>', $errors)); ?>"
                            style="display:none"
                    ></div>
                <?php endif; ?>

                <?php if ($isCandidate && (($action === 'create' || ($action === 'edit' && $editApplication)) && $applicationsOpen)): ?>
                    <section class="search-card">
                        <form
                                method="POST"
                                enctype="multipart/form-data"
                                action="<?= $action === 'edit' ? 'list.php?action=edit&id=' . (int) $editApplication['id'] : 'list.php?action=create'; ?>"
                                class="admin-form js-validate-form"
                                novalidate
                        >
                            <?php if ($action === 'edit'): ?>
                                <input type="hidden" name="id" value="<?= (int) $editApplication['id']; ?>">
                            <?php endif; ?>

                            <div class="form-group full-width">
                                <label for="title">Application Title</label>
                                <input
                                        type="text"
                                        id="title"
                                        name="title"
                                        value="<?= htmlspecialchars($action === 'edit' ? ($editApplication['title'] ?? '') : ($_POST['title'] ?? '')); ?>"
                                        required
                                >
                            </div>

                            <div class="form-group full-width">
                                <label for="course_id">Course</label>
                                <?php $selectedCourse = $action === 'edit' ? ($editApplication['course_id'] ?? '') : ($_POST['course_id'] ?? ''); ?>
                                <select id="course_id" name="course_id" class="admin-select" required>
                                    <option value="">Select course</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option
                                                value="<?= (int) $course['id']; ?>"
                                                <?= (string) $selectedCourse === (string) $course['id'] ? 'selected' : ''; ?>
                                        >
                                            <?= htmlspecialchars($course['faculty_name'] . ' – ' . $course['department_name'] . ' – ' . $course['course_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group full-width">
                                <label for="cover_letter">Cover Letter</label>
                                <textarea
                                        id="cover_letter"
                                        name="cover_letter"
                                        class="cover-letter-box"
                                ><?= htmlspecialchars($action === 'edit' ? ($editApplication['cover_letter'] ?? '') : ($_POST['cover_letter'] ?? '')); ?></textarea>
                            </div>

                            <div class="form-group full-width">
                                <label>Qualifications</label>

                                <div id="qualification-builder" class="qualification-builder">
                                    <?php foreach ($qualificationValues as $qualification): ?>
                                        <div class="qualification-row">
                                            <input
                                                    type="text"
                                                    name="qualifications[]"
                                                    value="<?= htmlspecialchars($qualification); ?>"
                                                    placeholder="Enter qualification"
                                            >

                                            <div class="qualification-actions">
                                                <button type="button" class="qualification-add-btn">+</button>
                                                <button type="button" class="qualification-remove-btn">−</button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                            </div>

                            <div class="form-group full-width">
                                <label for="cv_file">CV (PDF)</label>
                                <input type="file" id="cv_file" name="cv_file" accept="application/pdf">

                                <?php if ($action === 'edit' && !empty($editApplication['cv_file_path'])): ?>
                                    <small>
                                        Current CV:
                                        <a href="<?= htmlspecialchars($editApplication['cv_file_path']); ?>" target="_blank">
                                            <?= htmlspecialchars($editApplication['cv_original_name'] ?? 'View CV'); ?>
                                        </a>
                                    </small>
                                <?php endif; ?>
                            </div>

                            <div class="form-actions">
                                <button
                                        type="submit"
                                        name="<?= $action === 'edit' ? 'update_application' : 'save_application'; ?>"
                                        value="1"
                                        class="btn btn-secondary"
                                        onclick="this.form.submit_type.value='draft'"
                                >Save Draft</button>

                                <button
                                        type="submit"
                                        name="<?= $action === 'edit' ? 'update_application' : 'save_application'; ?>"
                                        value="1"
                                        class="btn btn-primary"
                                        onclick="this.form.submit_type.value='submit'"
                                >Submit for Review</button>

                                <input type="hidden" name="submit_type" value="draft">
                                <a href="list.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </section>
                <?php else: ?>
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
                                        <?php if (!$isCandidate): ?>
                                            <div>
                                                <span>Candidate</span>
                                                <strong><?= htmlspecialchars($application['candidate_name'] ?? 'Unknown candidate'); ?></strong>
                                                <small><?= htmlspecialchars($application['candidate_email'] ?? 'No email'); ?></small>
                                            </div>
                                        <?php endif; ?>

                                        <div>
                                            <span>Course</span>
                                            <strong><?= htmlspecialchars($application['course_name'] ?? 'No course'); ?></strong>
                                            <small><?= htmlspecialchars($application['course_code'] ?? 'No code'); ?></small>
                                        </div>

                                        <div>
                                            <span>Department</span>
                                            <strong><?= htmlspecialchars($application['department_name'] ?? 'No department'); ?></strong>
                                            <small><?= htmlspecialchars($application['faculty_name'] ?? 'No faculty'); ?></small>
                                        </div>

                                        <div>
                                            <span>Period</span>
                                            <strong><?= htmlspecialchars($application['period_title'] ?? 'No period'); ?></strong>
                                            <small><?= htmlspecialchars(formatFullDateTime($application['updated_at'] ??
                                                        formatFullDateTime($application['created_at']))); ?></small>
                                        </div>

                                        <?php if (!$isCandidate): ?>
                                            <div>
                                                <span>Cover Letter</span>
                                                <div class="fixed-text-panel">
                                                    <p><?= nl2br(htmlspecialchars($application['cover_letter'] ?: 'No cover letter provided.')); ?></p>
                                                </div>
                                            </div>

                                            <div>
                                                <span>Qualifications</span>
                                                <div class="fixed-text-panel">
                                                    <p><?= nl2br(htmlspecialchars($application['qualifications'] ?: 'No qualifications provided.')); ?></p>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($isEvaluator): ?>
                                        <form method="POST" action="list.php" class="application-status-box">
                                            <input type="hidden" name="id" value="<?= (int) $application['id']; ?>">

                                            <label for="status_<?= (int) $application['id']; ?>">Change Status</label>

                                            <div class="status-change-row">
                                                <select id="status_<?= (int) $application['id']; ?>" name="status" class="admin-select">
                                                    <option value="under_review" <?= $application['status'] === 'under_review' ? 'selected' : ''; ?>>Under Review</option>
                                                    <option value="approved" <?= $application['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                                    <option value="rejected" <?= $application['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                                </select>

                                                <button type="submit" name="update_status" class="btn btn-primary btn-sm-custom">Update</button>
                                            </div>
                                        </form>
                                    <?php endif; ?>

                                    <div class="application-admin-actions">
                                        <a href="application_view.php?id=<?= (int) $application['id']; ?>" class="btn btn-primary btn-sm-custom">
                                            View
                                        </a>

                                        <?php if ($isCandidate && $application['status'] === 'draft' && $applicationsOpen): ?>
                                            <a href="list.php?action=edit&id=<?= (int) $application['id']; ?>" class="btn btn-secondary btn-sm-custom">Edit</a>
                                        <?php endif; ?>

                                        <?php if (!empty($application['cv_file_path'])): ?>
                                            <a href="<?= htmlspecialchars($application['cv_file_path']); ?>" target="_blank" class="btn btn-secondary btn-sm-custom">View CV</a>
                                        <?php endif; ?>

                                        <?php if ($isCandidate && $application['status'] === 'draft'): ?>
                                            <form method="POST" action="list.php" class="inline-form">
                                                <input type="hidden" name="id" value="<?= (int) $application['id']; ?>">
                                                <input type="hidden" name="delete_application" value="1">
                                                <button type="submit" class="btn btn-danger btn-sm-custom">
                                                    Delete
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const builder = document.getElementById('qualification-builder');

        function createRow(value = '') {
            const row = document.createElement('div');
            row.className = 'qualification-row';

            row.innerHTML = `
            <input type="text" name="qualifications[]" value="${value}" placeholder="Enter qualification">
            <div class="qualification-actions">
                <button type="button" class="qualification-add-btn">+</button>
                <button type="button" class="qualification-remove-btn">−</button>
            </div>
        `;

            row.querySelector('.qualification-add-btn').onclick = () => {
                builder.appendChild(createRow());
            };

            row.querySelector('.qualification-remove-btn').onclick = () => {
                if (builder.children.length > 1) {
                    row.remove();
                } else {
                    row.querySelector('input').value = '';
                }
            };

            return row;
        }

        document.querySelectorAll('.qualification-row').forEach(row => {
            row.querySelector('.qualification-add-btn').onclick = () => {
                builder.appendChild(createRow());
            };

            row.querySelector('.qualification-remove-btn').onclick = () => {
                if (builder.children.length > 1) {
                    row.remove();
                } else {
                    row.querySelector('input').value = '';
                }
            };
        });
    });
</script>

<?php require_once __DIR__ . '/../includes/protected_footer.php'; ?>
</body>
</html>