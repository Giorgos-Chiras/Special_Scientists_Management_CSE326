<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../includes/db.php';
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

$settings = ['applications_open' => '0'];

try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Throwable $e) {
}

$periodStmt = $pdo->prepare("
    SELECT id, title, start_date, end_date
    FROM recruitment_periods
    WHERE CURDATE() BETWEEN start_date AND end_date
    ORDER BY start_date DESC
    LIMIT 1
");
$periodStmt->execute();
$activePeriod = $periodStmt->fetch(PDO::FETCH_ASSOC);

$systemApplicationsOpen = ($settings['applications_open'] ?? '0') === '1';
$applicationsOpen = $systemApplicationsOpen && $activePeriod;

if ((!$isCandidate || !$applicationsOpen) && in_array($action, ['create', 'edit'], true)) {
    $action = 'list';
}

$courses = $pdo->query("
    SELECT
        c.id,
        c.name AS course_name,
        c.code AS course_code,
        d.name AS department_name,
        f.name AS faculty_name
    FROM courses c
    INNER JOIN departments d ON c.department_id = d.id
    INNER JOIN faculties f ON d.faculty_id = f.id
    ORDER BY f.name ASC, d.name ASC, c.name ASC
")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_application']) && $isCandidate) {
    if (!$applicationsOpen) {
        $errors[] = 'Applications are currently closed.';
    }

    $courseId = (int) ($_POST['course_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $coverLetter = trim($_POST['cover_letter'] ?? '');
    $qualifications = trim($_POST['qualifications'] ?? '');
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

    $cvData = uploadCv($errors, $uploadDir, $uploadWebPath);

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO applications (
                user_id,
                course_id,
                period_id,
                title,
                status,
                cover_letter,
                qualifications,
                cv_file_path,
                cv_original_name
            ) VALUES (
                :user_id,
                :course_id,
                :period_id,
                :title,
                :status,
                :cover_letter,
                :qualifications,
                :cv_file_path,
                :cv_original_name
            )
        ");

        $stmt->execute([
                ':user_id' => $userId,
                ':course_id' => $courseId,
                ':period_id' => (int) $activePeriod['id'],
                ':title' => $title,
                ':status' => $status,
                ':cover_letter' => $coverLetter,
                ':qualifications' => $qualifications,
                ':cv_file_path' => $cvData['path'],
                ':cv_original_name' => $cvData['original_name'],
        ]);

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

    $stmt = $pdo->prepare("
        SELECT *
        FROM applications
        WHERE id = :id AND user_id = :user_id
        LIMIT 1
    ");
    $stmt->execute([
            ':id' => $id,
            ':user_id' => $userId
    ]);
    $editApplication = $stmt->fetch(PDO::FETCH_ASSOC);

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
    $qualifications = trim($_POST['qualifications'] ?? '');
    $submitType = $_POST['submit_type'] ?? 'draft';
    $status = $submitType === 'submit' ? 'submitted' : 'draft';

    $stmt = $pdo->prepare("
        SELECT *
        FROM applications
        WHERE id = :id AND user_id = :user_id
        LIMIT 1
    ");
    $stmt->execute([
            ':id' => $id,
            ':user_id' => $userId
    ]);
    $existingApplication = $stmt->fetch(PDO::FETCH_ASSOC);

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

    $cvData = uploadCv($errors, $uploadDir, $uploadWebPath, $existingApplication ?: null);

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            UPDATE applications
            SET course_id = :course_id,
                title = :title,
                status = :status,
                cover_letter = :cover_letter,
                qualifications = :qualifications,
                cv_file_path = :cv_file_path,
                cv_original_name = :cv_original_name
            WHERE id = :id AND user_id = :user_id
        ");

        $stmt->execute([
                ':course_id' => $courseId,
                ':title' => $title,
                ':status' => $status,
                ':cover_letter' => $coverLetter,
                ':qualifications' => $qualifications,
                ':cv_file_path' => $cvData['path'],
                ':cv_original_name' => $cvData['original_name'],
                ':id' => $id,
                ':user_id' => $userId,
        ]);

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

    if ($applicationId > 0 && in_array($newStatus, $allowedStatuses, true)) {
        $stmt = $pdo->prepare("
            SELECT ae.id
            FROM application_evaluators ae
            WHERE ae.application_id = :application_id
              AND ae.evaluator_id = :evaluator_id
            LIMIT 1
        ");
        $stmt->execute([
                ':application_id' => $applicationId,
                ':evaluator_id' => $userId
        ]);

        if ($stmt->fetch()) {
            $updateStmt = $pdo->prepare("
                UPDATE applications
                SET status = :status
                WHERE id = :id
            ");
            $updateStmt->execute([
                    ':status' => $newStatus,
                    ':id' => $applicationId
            ]);

            $_SESSION['flash'] = [
                    'type' => 'success',
                    'title' => 'Status updated',
                    'text' => 'The application status was updated successfully.',
            ];
        }
    }

    header('Location: list.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_application']) && $isCandidate) {
    $id = (int) ($_POST['id'] ?? 0);

    if ($id > 0) {
        $stmt = $pdo->prepare("
            SELECT cv_file_path
            FROM applications
            WHERE id = :id AND user_id = :user_id
            LIMIT 1
        ");
        $stmt->execute([
                ':id' => $id,
                ':user_id' => $userId
        ]);
        $applicationToDelete = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($applicationToDelete) {
            if (!empty($applicationToDelete['cv_file_path'])) {
                $filePath = realpath(__DIR__ . '/../' . str_replace('../', '', $applicationToDelete['cv_file_path']));
                $uploadBase = realpath($uploadDir);

                if ($filePath && $uploadBase && str_starts_with($filePath, $uploadBase) && file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            $pdo->prepare("
                DELETE FROM applications
                WHERE id = :id AND user_id = :user_id
            ")->execute([
                    ':id' => $id,
                    ':user_id' => $userId
            ]);

            $_SESSION['flash'] = [
                    'type' => 'success',
                    'title' => 'Application deleted',
                    'text' => 'Your application was deleted successfully.',
            ];
        }
    }

    header('Location: list.php');
    exit;
}

if ($isCandidate) {
    $stmt = $pdo->prepare("
        SELECT
            a.id,
            a.title,
            a.status,
            a.cover_letter,
            a.qualifications,
            a.cv_file_path,
            a.cv_original_name,
            a.created_at,
            a.updated_at,
            c.name AS course_name,
            c.code AS course_code,
            d.name AS department_name,
            f.name AS faculty_name,
            rp.title AS period_title
        FROM applications a
        INNER JOIN courses c ON a.course_id = c.id
        INNER JOIN departments d ON c.department_id = d.id
        INNER JOIN faculties f ON d.faculty_id = f.id
        INNER JOIN recruitment_periods rp ON a.period_id = rp.id
        WHERE a.user_id = :user_id
        ORDER BY a.id DESC
    ");
    $stmt->execute([':user_id' => $userId]);
} elseif ($isEvaluator) {
    $stmt = $pdo->prepare("
        SELECT
            a.id,
            a.title,
            a.status,
            a.cover_letter,
            a.qualifications,
            a.cv_file_path,
            a.cv_original_name,
            a.created_at,
            a.updated_at,
            u.username AS candidate_name,
            u.email AS candidate_email,
            c.name AS course_name,
            c.code AS course_code,
            d.name AS department_name,
            f.name AS faculty_name,
            rp.title AS period_title
        FROM application_evaluators ae
        INNER JOIN applications a ON ae.application_id = a.id
        INNER JOIN users u ON a.user_id = u.id
        INNER JOIN courses c ON a.course_id = c.id
        INNER JOIN departments d ON c.department_id = d.id
        INNER JOIN faculties f ON d.faculty_id = f.id
        INNER JOIN recruitment_periods rp ON a.period_id = rp.id
        WHERE ae.evaluator_id = :evaluator_id
        ORDER BY a.id DESC
    ");
    $stmt->execute([':evaluator_id' => $userId]);
} else {
    $stmt = $pdo->prepare("
        SELECT
            a.id,
            a.title,
            a.status,
            a.cover_letter,
            a.qualifications,
            a.cv_file_path,
            a.cv_original_name,
            a.created_at,
            a.updated_at,
            u.username AS candidate_name,
            u.email AS candidate_email,
            c.name AS course_name,
            c.code AS course_code,
            d.name AS department_name,
            f.name AS faculty_name,
            rp.title AS period_title
        FROM applications a
        INNER JOIN users u ON a.user_id = u.id
        INNER JOIN courses c ON a.course_id = c.id
        INNER JOIN departments d ON c.department_id = d.id
        INNER JOIN faculties f ON d.faculty_id = f.id
        INNER JOIN recruitment_periods rp ON a.period_id = rp.id
        ORDER BY a.id DESC
    ");
    $stmt->execute();
}

$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                        <div>
                            <h2 class="page-title"><?= $isEvaluator ? 'Assigned Applications' : 'All Applications'; ?></h2>
                            <p class="page-subtitle">
                                <?= $isEvaluator ? 'Review your assigned applications and update their status.' : 'View all candidate applications.'; ?>
                            </p>
                        </div>
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
                                <textarea id="cover_letter" name="cover_letter" rows="6"><?= htmlspecialchars($action === 'edit' ? ($editApplication['cover_letter'] ?? '') : ($_POST['cover_letter'] ?? '')); ?></textarea>
                            </div>

                            <div class="form-group full-width">
                                <label for="qualifications">Qualifications</label>
                                <textarea id="qualifications" name="qualifications" rows="5"><?= htmlspecialchars($action === 'edit' ? ($editApplication['qualifications'] ?? '') : ($_POST['qualifications'] ?? '')); ?></textarea>
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
                                                <strong><?= htmlspecialchars($application['candidate_name']); ?></strong>
                                                <small><?= htmlspecialchars($application['candidate_email']); ?></small>
                                            </div>
                                        <?php endif; ?>

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
                                            <small><?= htmlspecialchars(formatFullDateTime( $application['updated_at'] ?? formatFullDateTime($application['created_at']))); ?></small>
                                        </div>

                                        <?php if (!$isCandidate): ?>
                                            <div>
                                                <span>Cover Letter</span>
                                                <strong><?= !empty($application['cover_letter']) ? 'Provided' : 'Not provided'; ?></strong>
                                            </div>

                                            <div>
                                                <span>Qualifications</span>
                                                <strong><?= !empty($application['qualifications']) ? 'Provided' : 'Not provided'; ?></strong>
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
                                        <?php if ($isEvaluator || $isHr): ?>
                                            <a href="application_view.php?id=<?= (int) $application['id']; ?>" class="btn btn-primary btn-sm-custom">View</a>
                                        <?php endif; ?>

                                        <?php if ($isCandidate && $application['status'] === 'draft' && $applicationsOpen): ?>
                                            <a href="list.php?action=edit&id=<?= (int) $application['id']; ?>" class="btn btn-secondary btn-sm-custom">Edit</a>
                                        <?php endif; ?>

                                        <?php if (!empty($application['cv_file_path'])): ?>
                                            <a href="<?= htmlspecialchars($application['cv_file_path']); ?>" target="_blank" class="btn btn-secondary btn-sm-custom">View CV</a>
                                        <?php endif; ?>

                                        <?php if ($isCandidate): ?>
                                            <form method="POST" action="list.php" class="inline-form">
                                                <input type="hidden" name="id" value="<?= (int) $application['id']; ?>">
                                                <input type="hidden" name="delete_application" value="1">
                                                <button type="button" class="btn btn-danger btn-sm-custom js-delete-button">Delete</button>
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

<?php require_once __DIR__ . '/../includes/protected_footer.php'; ?>