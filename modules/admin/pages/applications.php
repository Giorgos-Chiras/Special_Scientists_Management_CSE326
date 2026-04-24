<?php
require_once __DIR__ . '/../../../includes/db.php';

$errors = [];
$action = $_GET['action'] ?? 'list';
$editApplication = null;
$search = trim($_GET['search'] ?? '');

$statuses = [
        'draft' => 'Draft',
        'submitted' => 'Submitted',
        'under_review' => 'Under Review',
        'approved' => 'Approved',
        'rejected' => 'Rejected'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_application'])) {
    $userId = (int) ($_POST['user_id'] ?? 0);
    $courseId = (int) ($_POST['course_id'] ?? 0);
    $periodId = (int) ($_POST['period_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    $coverLetter = trim($_POST['cover_letter'] ?? '');
    $qualifications = trim($_POST['qualifications'] ?? '');

    if ($userId <= 0) $errors[] = 'Candidate is required.';
    if ($courseId <= 0) $errors[] = 'Course is required.';
    if ($periodId <= 0) $errors[] = 'Recruitment period is required.';
    if ($title === '') $errors[] = 'Application title is required.';

    if (!array_key_exists($status, $statuses)) {
        $errors[] = 'Invalid status selected.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO applications (
                user_id, course_id, period_id, title, status, cover_letter, qualifications
            )
            VALUES (
                :user_id, :course_id, :period_id, :title, :status, :cover_letter, :qualifications
            )
        ");

        $stmt->execute([
                ':user_id' => $userId,
                ':course_id' => $courseId,
                ':period_id' => $periodId,
                ':title' => $title,
                ':status' => $status,
                ':cover_letter' => $coverLetter,
                ':qualifications' => $qualifications
        ]);

        $_SESSION['flash'] = [
                'type' => 'success',
                'title' => 'Application created',
                'text' => 'The application was created successfully.'
        ];

        header('Location: admin.php?page=applications');
        exit;
    }

    $action = 'create';
}

if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];

    $stmt = $pdo->prepare("SELECT * FROM applications WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $editApplication = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$editApplication) {
        $_SESSION['flash'] = [
                'type' => 'error',
                'title' => 'Application not found',
                'text' => 'The selected application could not be found.'
        ];

        header('Location: admin.php?page=applications');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_application'])) {
    $id = (int) ($_POST['id'] ?? 0);
    $userId = (int) ($_POST['user_id'] ?? 0);
    $courseId = (int) ($_POST['course_id'] ?? 0);
    $periodId = (int) ($_POST['period_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    $coverLetter = trim($_POST['cover_letter'] ?? '');
    $qualifications = trim($_POST['qualifications'] ?? '');

    if ($userId <= 0) $errors[] = 'Candidate is required.';
    if ($courseId <= 0) $errors[] = 'Course is required.';
    if ($periodId <= 0) $errors[] = 'Recruitment period is required.';
    if ($title === '') $errors[] = 'Application title is required.';

    if (!array_key_exists($status, $statuses)) {
        $errors[] = 'Invalid status selected.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            UPDATE applications
            SET user_id = :user_id,
                course_id = :course_id,
                period_id = :period_id,
                title = :title,
                status = :status,
                cover_letter = :cover_letter,
                qualifications = :qualifications
            WHERE id = :id
        ");

        $stmt->execute([
                ':user_id' => $userId,
                ':course_id' => $courseId,
                ':period_id' => $periodId,
                ':title' => $title,
                ':status' => $status,
                ':cover_letter' => $coverLetter,
                ':qualifications' => $qualifications,
                ':id' => $id
        ]);

        $_SESSION['flash'] = [
                'type' => 'success',
                'title' => 'Application updated',
                'text' => 'The application was updated successfully.'
        ];

        header('Location: admin.php?page=applications');
        exit;
    }

    $action = 'edit';
    $editApplication = [
            'id' => $id,
            'user_id' => $userId,
            'course_id' => $courseId,
            'period_id' => $periodId,
            'title' => $title,
            'status' => $status,
            'cover_letter' => $coverLetter,
            'qualifications' => $qualifications
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id = (int) ($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';

    if ($id > 0 && array_key_exists($status, $statuses)) {
        $stmt = $pdo->prepare("UPDATE applications SET status = :status WHERE id = :id");
        $stmt->execute([
                ':status' => $status,
                ':id' => $id
        ]);

        $_SESSION['flash'] = [
                'type' => 'success',
                'title' => 'Status updated',
                'text' => 'Application status was updated successfully.'
        ];
    }

    header('Location: admin.php?page=applications');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_employee'])) {
    $id = (int) ($_POST['id'] ?? 0);

    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT user_id FROM applications WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $application = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($application) {
            $pdo->beginTransaction();

            $updateApplication = $pdo->prepare("UPDATE applications SET status = 'approved' WHERE id = :id");
            $updateApplication->execute([':id' => $id]);

            $updateUser = $pdo->prepare("UPDATE users SET role = 'ee' WHERE id = :user_id");
            $updateUser->execute([':user_id' => (int) $application['user_id']]);

            $pdo->commit();

            $_SESSION['flash'] = [
                    'type' => 'success',
                    'title' => 'Employee assigned',
                    'text' => 'Candidate was approved and assigned as EE.'
            ];
        }
    }

    header('Location: admin.php?page=applications');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_application'])) {
    $id = (int) ($_POST['id'] ?? 0);

    if ($id > 0) {
        $stmt = $pdo->prepare("DELETE FROM applications WHERE id = :id");
        $stmt->execute([':id' => $id]);

        $_SESSION['flash'] = [
                'type' => 'success',
                'title' => 'Application deleted',
                'text' => 'Application was deleted successfully.'
        ];
    }

    header('Location: admin.php?page=applications');
    exit;
}

$candidates = $pdo->query("
    SELECT id, username, email, role
    FROM users
    WHERE role IN ('candidate', 'ee')
    ORDER BY username ASC
")->fetchAll(PDO::FETCH_ASSOC);

$courses = $pdo->query("
    SELECT c.id, c.name, c.code, d.name AS department_name, f.name AS faculty_name
    FROM courses c
    INNER JOIN departments d ON c.department_id = d.id
    INNER JOIN faculties f ON d.faculty_id = f.id
    ORDER BY f.name ASC, d.name ASC, c.name ASC
")->fetchAll(PDO::FETCH_ASSOC);

$periods = $pdo->query("
    SELECT id, title, start_date, end_date, is_active
    FROM recruitment_periods
    ORDER BY is_active DESC, id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$sql = "
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
";

$params = [];

if ($search !== '') {
    $sql .= "
        WHERE a.title LIKE :search
        OR a.status LIKE :search
        OR u.username LIKE :search
        OR u.email LIKE :search
        OR c.name LIKE :search
        OR c.code LIKE :search
        OR d.name LIKE :search
        OR f.name LIKE :search
        OR rp.title LIKE :search
    ";
    $params[':search'] = '%' . $search . '%';
}

$sql .= " ORDER BY a.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

function adminStatusClass(string $status): string
{
    return match ($status) {
        'draft' => 'status-pending',
        'submitted' => 'status-submitted',
        'under_review' => 'status-review',
        'approved' => 'status-approved',
        'rejected' => 'status-rejected',
        default => 'status-pending',
    };
}
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
            <form method="POST" action="admin.php?page=applications<?= $action === 'edit' ? '&action=edit&id=' . (int) $editApplication['id'] : '&action=create'; ?>" class="admin-form js-validate-form" novalidate>
                <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="id" value="<?= (int) $editApplication['id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="user_id">Candidate</label>
                    <?php $selectedUser = $action === 'edit' ? ($editApplication['user_id'] ?? '') : ($_POST['user_id'] ?? ''); ?>
                    <select id="user_id" name="user_id" class="admin-select" required>
                        <option value="">Select candidate</option>
                        <?php foreach ($candidates as $candidate): ?>
                            <option value="<?= (int) $candidate['id']; ?>" <?= (string) $selectedUser === (string) $candidate['id'] ? 'selected' : ''; ?>>
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
                            <option value="<?= (int) $course['id']; ?>" <?= (string) $selectedCourse === (string) $course['id'] ? 'selected' : ''; ?>>
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
                            <option value="<?= (int) $period['id']; ?>" <?= (string) $selectedPeriod === (string) $period['id'] ? 'selected' : ''; ?>>
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
                            <option value="<?= htmlspecialchars($value); ?>" <?= $selectedStatus === $value ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group full-width">
                    <label for="title">Application Title</label>
                    <input type="text" id="title" name="title" value="<?= htmlspecialchars($action === 'edit' ? ($editApplication['title'] ?? '') : ($_POST['title'] ?? '')); ?>" required>
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
                    <input type="text" id="search" name="search" placeholder="Search by candidate, course, department, period or status" value="<?= htmlspecialchars($search); ?>">
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

                            <span class="status-pill <?= adminStatusClass($application['status']); ?>">
                                <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $application['status']))); ?>
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
                                <small><?= htmlspecialchars($application['updated_at'] ?? $application['created_at']); ?></small>
                            </div>
                        </div>

                        <form method="POST" action="admin.php?page=applications" class="application-status-box">
                            <input type="hidden" name="id" value="<?= (int) $application['id']; ?>">

                            <label for="status_<?= (int) $application['id']; ?>">Change Status</label>

                            <div class="status-change-row">
                                <select id="status_<?= (int) $application['id']; ?>" name="status" class="admin-select">
                                    <?php foreach ($statuses as $value => $label): ?>
                                        <option value="<?= htmlspecialchars($value); ?>" <?= $application['status'] === $value ? 'selected' : ''; ?>>
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