<?php
require_once __DIR__ . '/../../../includes/db.php';

$errors = [];
$action = $_GET['action'] ?? 'list';
$editCourse = null;
$search = trim($_GET['search'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_course'])) {
    $departmentId = (int) ($_POST['department_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $code = trim($_POST['code'] ?? '');

    if ($departmentId <= 0) {
        $errors[] = 'Department is required.';
    }

    if ($name === '') {
        $errors[] = 'Course name is required.';
    }

    if (empty($errors)) {
        $checkStmt = $pdo->prepare("SELECT id FROM courses WHERE department_id = :department_id AND name = :name LIMIT 1");
        $checkStmt->execute([
                ':department_id' => $departmentId,
                ':name' => $name
        ]);

        if ($checkStmt->fetch()) {
            $errors[] = 'This course already exists under the selected department.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO courses (department_id, name, code) VALUES (:department_id, :name, :code)");
            $stmt->execute([
                    ':department_id' => $departmentId,
                    ':name' => $name,
                    ':code' => $code !== '' ? $code : null
            ]);

            $_SESSION['flash'] = [
                    'type' => 'success',
                    'title' => 'Course created',
                    'text' => 'The course was created successfully.'
            ];

            header('Location: admin.php?page=courses');
            exit;
        }
    }
}

if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];

    $stmt = $pdo->prepare("SELECT id, department_id, name, code FROM courses WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $editCourse = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$editCourse) {
        $_SESSION['flash'] = [
                'type' => 'error',
                'title' => 'Course not found',
                'text' => 'The selected course could not be found.'
        ];

        header('Location: admin.php?page=courses');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_course'])) {
    $id = (int) ($_POST['id'] ?? 0);
    $departmentId = (int) ($_POST['department_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $code = trim($_POST['code'] ?? '');

    if ($departmentId <= 0) {
        $errors[] = 'Department is required.';
    }

    if ($name === '') {
        $errors[] = 'Course name is required.';
    }

    if (empty($errors)) {
        $checkStmt = $pdo->prepare("
            SELECT id
            FROM courses
            WHERE department_id = :department_id AND name = :name AND id != :id
            LIMIT 1
        ");
        $checkStmt->execute([
                ':department_id' => $departmentId,
                ':name' => $name,
                ':id' => $id
        ]);

        if ($checkStmt->fetch()) {
            $errors[] = 'Another course already uses that name under the selected department.';
        } else {
            $stmt = $pdo->prepare("UPDATE courses SET department_id = :department_id, name = :name, code = :code WHERE id = :id");
            $stmt->execute([
                    ':department_id' => $departmentId,
                    ':name' => $name,
                    ':code' => $code !== '' ? $code : null,
                    ':id' => $id
            ]);

            $_SESSION['flash'] = [
                    'type' => 'success',
                    'title' => 'Course updated',
                    'text' => 'The course was updated successfully.'
            ];

            header('Location: admin.php?page=courses');
            exit;
        }
    }

    $action = 'edit';
    $editCourse = [
            'id' => $id,
            'department_id' => $departmentId,
            'name' => $name,
            'code' => $code
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_course'])) {
    $id = (int) ($_POST['id'] ?? 0);

    if ($id > 0) {
        $stmt = $pdo->prepare("DELETE FROM courses WHERE id = :id");
        $stmt->execute([':id' => $id]);

        $_SESSION['flash'] = [
                'type' => 'success',
                'title' => 'Course deleted',
                'text' => 'The course was deleted successfully.'
        ];

        header('Location: admin.php?page=courses');
        exit;
    }
}

$departmentStmt = $pdo->query("
    SELECT d.id, d.name, f.name AS faculty_name
    FROM departments d
    INNER JOIN faculties f ON d.faculty_id = f.id
    ORDER BY f.name ASC, d.name ASC
");
$departments = $departmentStmt->fetchAll(PDO::FETCH_ASSOC);

$sql = "
    SELECT c.id, c.name, c.code, d.name AS department_name, f.name AS faculty_name
    FROM courses c
    INNER JOIN departments d ON c.department_id = d.id
    INNER JOIN faculties f ON d.faculty_id = f.id
";
$params = [];

if ($search !== '') {
    $sql .= " WHERE c.name LIKE :search OR c.code LIKE :search OR d.name LIKE :search OR f.name LIKE :search";
    $params[':search'] = '%' . $search . '%';
}

$sql .= " ORDER BY c.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="page-card list-card">
    <div class="list-header">
        <div>
            <h1 class="page-title">Courses</h1>
            <p class="page-subtitle">Create, edit, and manage courses.</p>
        </div>

        <div class="list-actions">
            <a href="admin.php?page=recruitment" class="btn btn-secondary">Back</a>
            <a href="admin.php?page=courses" class="btn btn-secondary">Course List</a>
            <a href="admin.php?page=courses&action=create" class="btn btn-primary">Add Course</a>
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

    <?php if ($action === 'create' || ($action === 'edit' && $editCourse)): ?>
        <section class="search-card">
            <form method="POST" action="admin.php?page=courses<?= $action === 'edit' ? '&action=edit&id=' . (int) $editCourse['id'] : '&action=create' ?>" class="admin-form js-validate-form" novalidate>
                <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="id" value="<?= (int) $editCourse['id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="department_id">Department</label>
                    <select id="department_id" name="department_id" class="admin-select" required>
                        <option value="">Select department</option>
                        <?php
                        $selectedDepartment = $action === 'edit' ? ($editCourse['department_id'] ?? '') : ($_POST['department_id'] ?? '');
                        foreach ($departments as $department):
                            ?>
                            <option value="<?= (int) $department['id']; ?>" <?= (string) $selectedDepartment === (string) $department['id'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($department['faculty_name'] . ' - ' . $department['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="name">Course Name</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($action === 'edit' ? ($editCourse['name'] ?? '') : ($_POST['name'] ?? '')); ?>" required>
                </div>

                <div class="form-group full-width">
                    <label for="code">Course Code</label>
                    <input type="text" id="code" name="code" value="<?= htmlspecialchars($action === 'edit' ? ($editCourse['code'] ?? '') : ($_POST['code'] ?? '')); ?>">
                </div>

                <div class="form-actions">
                    <button type="submit" name="<?= $action === 'edit' ? 'update_course' : 'create_course'; ?>" class="btn btn-primary">
                        <?= $action === 'edit' ? 'Update Course' : 'Create Course'; ?>
                    </button>
                    <a href="admin.php?page=courses" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </section>
    <?php else: ?>
        <div class="search-card">
            <form method="GET" action="admin.php" class="search-form">
                <input type="hidden" name="page" value="courses">

                <div class="search-group">
                    <label for="search">Search courses</label>
                    <input type="text" id="search" name="search" placeholder="Search by course, code, department or faculty" value="<?= htmlspecialchars($search); ?>">
                </div>

                <div class="list-actions">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <a href="admin.php?page=courses" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>

        <div class="results-meta">
            Total courses found: <strong><?= count($courses); ?></strong>
        </div>

        <div class="table-card">
            <div class="table-wrapper">
                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Course</th>
                        <th>Code</th>
                        <th>Department</th>
                        <th>Faculty</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($courses)): ?>
                        <tr>
                            <td colspan="6" class="empty-state">No courses found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($courses as $course): ?>
                            <tr>
                                <td><?= (int) $course['id']; ?></td>
                                <td><?= htmlspecialchars($course['name']); ?></td>
                                <td><?= htmlspecialchars($course['code'] ?? ''); ?></td>
                                <td><?= htmlspecialchars($course['department_name']); ?></td>
                                <td><?= htmlspecialchars($course['faculty_name']); ?></td>
                                <td>
                                    <div class="table-actions">
                                        <a href="admin.php?page=courses&action=edit&id=<?= (int) $course['id']; ?>" class="btn btn-secondary btn-sm-custom">Edit</a>
                                        <form method="POST" action="admin.php?page=courses" class="inline-form">
                                            <input type="hidden" name="id" value="<?= (int) $course['id']; ?>">
                                            <input type="hidden" name="delete_course" value="1">
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