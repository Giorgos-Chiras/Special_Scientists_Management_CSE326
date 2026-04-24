<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../includes/db.php';

$pageTitle = 'Applications';
$userId = (int) $_SESSION['user_id'];
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

if (!$applicationsOpen && in_array($action, ['create', 'edit'])) {
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

function uploadCv(array &$errors, ?array $existingApplication = null): array
{
    global $uploadDir, $uploadWebPath;

    $result = [
            'path'          => $existingApplication['cv_file_path'] ?? null,
            'original_name' => $existingApplication['cv_original_name'] ?? null,
    ];

    if (empty($_FILES['cv_file']['name'])) {
        return $result;
    }

    if ($_FILES['cv_file']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'CV upload failed.';
        return $result;
    }

    $fileTmp      = $_FILES['cv_file']['tmp_name'];
    $originalName = $_FILES['cv_file']['name'];
    $extension    = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $mimeType     = mime_content_type($fileTmp);

    if ($extension !== 'pdf' || $mimeType !== 'application/pdf') {
        $errors[] = 'CV must be a PDF file.';
        return $result;
    }

    if ($_FILES['cv_file']['size'] > 5 * 1024 * 1024) {
        $errors[] = 'CV file must be smaller than 5MB.';
        return $result;
    }

    $newFileName = 'cv_' . ($_SESSION['user_id'] ?? 'user') . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.pdf';
    $destination = $uploadDir . $newFileName;

    if (!move_uploaded_file($fileTmp, $destination)) {
        $errors[] = 'Could not save CV file.';
        return $result;
    }

    return [
            'path'          => $uploadWebPath . $newFileName,
            'original_name' => $originalName,
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_application'])) {
    if (!$applicationsOpen) {
        $errors[] = 'Applications are currently closed.';
    }

    $courseId      = (int) ($_POST['course_id'] ?? 0);
    $title         = trim($_POST['title'] ?? '');
    $coverLetter   = trim($_POST['cover_letter'] ?? '');
    $qualifications = trim($_POST['qualifications'] ?? '');
    $submitType    = $_POST['submit_type'] ?? 'draft';
    $status        = $submitType === 'submit' ? 'submitted' : 'draft';

    if ($courseId <= 0)  $errors[] = 'Course is required.';
    if ($title === '')   $errors[] = 'Application title is required.';

    if ($status === 'submitted') {
        if ($coverLetter === '')    $errors[] = 'Cover letter is required before submitting.';
        if ($qualifications === '') $errors[] = 'Qualifications are required before submitting.';
    }

    $cvData = uploadCv($errors);

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO applications (
                user_id, course_id, period_id, title, status,
                cover_letter, qualifications, cv_file_path, cv_original_name
            ) VALUES (
                :user_id, :course_id, :period_id, :title, :status,
                :cover_letter, :qualifications, :cv_file_path, :cv_original_name
            )
        ");

        $stmt->execute([
                ':user_id'        => $userId,
                ':course_id'      => $courseId,
                ':period_id'      => (int) $activePeriod['id'],
                ':title'          => $title,
                ':status'         => $status,
                ':cover_letter'   => $coverLetter,
                ':qualifications' => $qualifications,
                ':cv_file_path'   => $cvData['path'],
                ':cv_original_name' => $cvData['original_name'],
        ]);

        $_SESSION['flash'] = [
                'type'  => 'success',
                'title' => $status === 'submitted' ? 'Application submitted' : 'Draft saved',
                'text'  => $status === 'submitted'
                        ? 'Your application was submitted for review.'
                        : 'Your application was saved as a draft.',
        ];

        header('Location: list.php');
        exit;
    }

    $action = 'create';
}

if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];

    $stmt = $pdo->prepare("
        SELECT * FROM applications
        WHERE id = :id AND user_id = :user_id
        LIMIT 1
    ");
    $stmt->execute([':id' => $id, ':user_id' => $userId]);
    $editApplication = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$editApplication) {
        $_SESSION['flash'] = [
                'type'  => 'error',
                'title' => 'Application not found',
                'text'  => 'The selected application could not be found.',
        ];
        header('Location: list.php');
        exit;
    }

    if ($editApplication['status'] !== 'draft') {
        $_SESSION['flash'] = [
                'type'  => 'error',
                'title' => 'Cannot edit',
                'text'  => 'Only draft applications can be edited.',
        ];
        header('Location: list.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_application'])) {
    if (!$applicationsOpen) {
        $errors[] = 'Applications are currently closed.';
    }

    $id             = (int) ($_POST['id'] ?? 0);
    $courseId       = (int) ($_POST['course_id'] ?? 0);
    $title          = trim($_POST['title'] ?? '');
    $coverLetter    = trim($_POST['cover_letter'] ?? '');
    $qualifications = trim($_POST['qualifications'] ?? '');
    $submitType     = $_POST['submit_type'] ?? 'draft';
    $status         = $submitType === 'submit' ? 'submitted' : 'draft';

    $stmt = $pdo->prepare("
        SELECT * FROM applications
        WHERE id = :id AND user_id = :user_id
        LIMIT 1
    ");
    $stmt->execute([':id' => $id, ':user_id' => $userId]);
    $existingApplication = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existingApplication || $existingApplication['status'] !== 'draft') {
        $errors[] = 'Only draft applications can be updated.';
    }

    if ($courseId <= 0)  $errors[] = 'Course is required.';
    if ($title === '')   $errors[] = 'Application title is required.';

    if ($status === 'submitted') {
        if ($coverLetter === '')    $errors[] = 'Cover letter is required before submitting.';
        if ($qualifications === '') $errors[] = 'Qualifications are required before submitting.';
    }

    $cvData = uploadCv($errors, $existingApplication ?: null);

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            UPDATE applications
            SET course_id       = :course_id,
                title           = :title,
                status          = :status,
                cover_letter    = :cover_letter,
                qualifications  = :qualifications,
                cv_file_path    = :cv_file_path,
                cv_original_name = :cv_original_name
            WHERE id = :id AND user_id = :user_id
        ");

        $stmt->execute([
                ':course_id'       => $courseId,
                ':title'           => $title,
                ':status'          => $status,
                ':cover_letter'    => $coverLetter,
                ':qualifications'  => $qualifications,
                ':cv_file_path'    => $cvData['path'],
                ':cv_original_name' => $cvData['original_name'],
                ':id'              => $id,
                ':user_id'         => $userId,
        ]);

        $_SESSION['flash'] = [
                'type'  => 'success',
                'title' => $status === 'submitted' ? 'Application submitted' : 'Draft updated',
                'text'  => $status === 'submitted'
                        ? 'Your application was submitted for review.'
                        : 'Your draft was updated successfully.',
        ];

        header('Location: list.php');
        exit;
    }

    $action = 'edit';
    $editApplication = [
            'id'              => $id,
            'course_id'       => $courseId,
            'title'           => $title,
            'status'          => 'draft',
            'cover_letter'    => $coverLetter,
            'qualifications'  => $qualifications,
            'cv_file_path'    => $existingApplication['cv_file_path'] ?? null,
            'cv_original_name' => $existingApplication['cv_original_name'] ?? null,
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_application'])) {
    $id = (int) ($_POST['id'] ?? 0);

    if ($id > 0) {
        $stmt = $pdo->prepare("
            SELECT cv_file_path FROM applications
            WHERE id = :id AND user_id = :user_id
            LIMIT 1
        ");
        $stmt->execute([':id' => $id, ':user_id' => $userId]);
        $applicationToDelete = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($applicationToDelete) {
            if (!empty($applicationToDelete['cv_file_path'])) {
                $filePath   = realpath(__DIR__ . '/../' . str_replace('../', '', $applicationToDelete['cv_file_path']));
                $uploadBase = realpath($uploadDir);

                if ($filePath && $uploadBase && str_starts_with($filePath, $uploadBase) && file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            $deleteStmt = $pdo->prepare("DELETE FROM applications WHERE id = :id AND user_id = :user_id");
            $deleteStmt->execute([':id' => $id, ':user_id' => $userId]);

            $_SESSION['flash'] = [
                    'type'  => 'success',
                    'title' => 'Application deleted',
                    'text'  => 'Your application was deleted successfully.',
            ];
        }
    }

    header('Location: list.php');
    exit;
}

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
        c.name  AS course_name,
        c.code  AS course_code,
        d.name  AS department_name,
        f.name  AS faculty_name,
        rp.title AS period_title
    FROM applications a
    INNER JOIN courses c           ON a.course_id = c.id
    INNER JOIN departments d       ON c.department_id = d.id
    INNER JOIN faculties f         ON d.faculty_id = f.id
    INNER JOIN recruitment_periods rp ON a.period_id = rp.id
    WHERE a.user_id = :user_id
    ORDER BY a.id DESC
");
$stmt->execute([':user_id' => $userId]);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

function applicationStatusLabel(string $status): string
{
    return match ($status) {
        'draft'        => 'Draft',
        'submitted'    => 'Submitted',
        'under_review' => 'Under Review',
        'approved'     => 'Approved',
        'rejected'     => 'Rejected',
        default        => ucfirst(str_replace('_', ' ', $status)),
    };
}

function applicationStatusClass(string $status): string
{
    return match ($status) {
        'draft'        => 'status-pending',
        'submitted'    => 'status-submitted',
        'under_review' => 'status-review',
        'approved'     => 'status-approved',
        'rejected'     => 'status-rejected',
        default        => 'status-pending',
    };
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
                        The system administrator has disabled new applications. You can still view your existing applications below.
                    </div>
                <?php elseif (!$activePeriod): ?>
                    <div class="notice notice-warning">
                        <strong>No active recruitment period.</strong>
                        There is no open recruitment period right now. You can still view your existing applications below.
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

                <?php if (($action === 'create' || ($action === 'edit' && $editApplication)) && $applicationsOpen): ?>
                    <!-- ── CREATE / EDIT FORM ── -->
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
                    <!-- ── APPLICATION LIST ── -->
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

                                        <span class="status-pill <?= applicationStatusClass($application['status']); ?>">
                                            <?= htmlspecialchars(applicationStatusLabel($application['status'])); ?>
                                        </span>
                                    </div>

                                    <div class="application-admin-meta">
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
                                            <small><?= htmlspecialchars($application['updated_at'] ?? $application['created_at']); ?></small>
                                        </div>
                                    </div>

                                    <div class="application-admin-actions">
                                        <?php if ($application['status'] === 'draft' && $applicationsOpen): ?>
                                            <a href="list.php?action=edit&id=<?= (int) $application['id']; ?>" class="btn btn-secondary btn-sm-custom">Edit</a>
                                        <?php endif; ?>

                                        <?php if (!empty($application['cv_file_path'])): ?>
                                            <a href="<?= htmlspecialchars($application['cv_file_path']); ?>" target="_blank" class="btn btn-secondary btn-sm-custom">View CV</a>
                                        <?php endif; ?>

                                        <form method="POST" action="list.php" class="inline-form">
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
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/protected_footer.php'; ?>