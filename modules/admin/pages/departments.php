<?php
require_once __DIR__ . '/../../../includes/db.php';

$errors = [];
$action = $_GET['action'] ?? 'list';
$editDepartment = null;
$search = trim($_GET['search'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_department'])) {
    $facultyId = (int) ($_POST['faculty_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');

    if ($facultyId <= 0) {
        $errors[] = 'Faculty is required.';
    }

    if ($name === '') {
        $errors[] = 'Department name is required.';
    }

    if (empty($errors)) {
        $checkStmt = $pdo->prepare("SELECT id FROM departments WHERE faculty_id = :faculty_id AND name = :name LIMIT 1");
        $checkStmt->execute([
                ':faculty_id' => $facultyId,
                ':name' => $name
        ]);

        if ($checkStmt->fetch()) {
            $errors[] = 'This department already exists under the selected faculty.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO departments (faculty_id, name) VALUES (:faculty_id, :name)");
            $stmt->execute([
                    ':faculty_id' => $facultyId,
                    ':name' => $name
            ]);

            $_SESSION['flash'] = [
                    'type' => 'success',
                    'title' => 'Department created',
                    'text' => 'The department was created successfully.'
            ];

            header('Location: admin.php?page=departments');
            exit;
        }
    }
}

if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];

    $stmt = $pdo->prepare("SELECT id, faculty_id, name FROM departments WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $editDepartment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$editDepartment) {
        $_SESSION['flash'] = [
                'type' => 'error',
                'title' => 'Department not found',
                'text' => 'The selected department could not be found.'
        ];

        header('Location: admin.php?page=departments');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_department'])) {
    $id = (int) ($_POST['id'] ?? 0);
    $facultyId = (int) ($_POST['faculty_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');

    if ($facultyId <= 0) {
        $errors[] = 'Faculty is required.';
    }

    if ($name === '') {
        $errors[] = 'Department name is required.';
    }

    if (empty($errors)) {
        $checkStmt = $pdo->prepare("
            SELECT id
            FROM departments
            WHERE faculty_id = :faculty_id AND name = :name AND id != :id
            LIMIT 1
        ");
        $checkStmt->execute([
                ':faculty_id' => $facultyId,
                ':name' => $name,
                ':id' => $id
        ]);

        if ($checkStmt->fetch()) {
            $errors[] = 'Another department already uses that name under the selected faculty.';
        } else {
            $stmt = $pdo->prepare("UPDATE departments SET faculty_id = :faculty_id, name = :name WHERE id = :id");
            $stmt->execute([
                    ':faculty_id' => $facultyId,
                    ':name' => $name,
                    ':id' => $id
            ]);

            $_SESSION['flash'] = [
                    'type' => 'success',
                    'title' => 'Department updated',
                    'text' => 'The department was updated successfully.'
            ];

            header('Location: admin.php?page=departments');
            exit;
        }
    }

    $action = 'edit';
    $editDepartment = [
            'id' => $id,
            'faculty_id' => $facultyId,
            'name' => $name
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_department'])) {
    $id = (int) ($_POST['id'] ?? 0);

    if ($id > 0) {
        $stmt = $pdo->prepare("DELETE FROM departments WHERE id = :id");
        $stmt->execute([':id' => $id]);

        $_SESSION['flash'] = [
                'type' => 'success',
                'title' => 'Department deleted',
                'text' => 'The department was deleted successfully.'
        ];

        header('Location: admin.php?page=departments');
        exit;
    }
}

$facultyStmt = $pdo->query("SELECT id, name FROM faculties ORDER BY name ASC");
$faculties = $facultyStmt->fetchAll(PDO::FETCH_ASSOC);

$sql = "
    SELECT d.id, d.name, f.name AS faculty_name
    FROM departments d
    INNER JOIN faculties f ON d.faculty_id = f.id
";
$params = [];

if ($search !== '') {
    $sql .= " WHERE d.name LIKE :search OR f.name LIKE :search";
    $params[':search'] = '%' . $search . '%';
}

$sql .= " ORDER BY d.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="page-card list-card">
    <div class="list-header">
        <div>
            <h1 class="page-title">Departments</h1>
            <p class="page-subtitle">Create, edit, and manage departments.</p>
        </div>

        <div class="list-actions">
            <a href="admin.php?page=recruitment" class="btn btn-secondary">Back</a>
            <a href="admin.php?page=departments" class="btn btn-secondary">Department List</a>
            <a href="admin.php?page=departments&action=create" class="btn btn-primary">Add Department</a>
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

    <?php if ($action === 'create' || ($action === 'edit' && $editDepartment)): ?>
        <section class="search-card">
            <form method="POST" action="admin.php?page=departments<?= $action === 'edit' ? '&action=edit&id=' . (int) $editDepartment['id'] : '&action=create' ?>" class="admin-form js-validate-form" novalidate>
                <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="id" value="<?= (int) $editDepartment['id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="faculty_id">Faculty</label>
                    <select id="faculty_id" name="faculty_id" class="admin-select" required>
                        <option value="">Select faculty</option>
                        <?php
                        $selectedFaculty = $action === 'edit' ? ($editDepartment['faculty_id'] ?? '') : ($_POST['faculty_id'] ?? '');
                        foreach ($faculties as $faculty):
                            ?>
                            <option value="<?= (int) $faculty['id']; ?>" <?= (string) $selectedFaculty === (string) $faculty['id'] ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($faculty['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="name">Department Name</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($action === 'edit' ? ($editDepartment['name'] ?? '') : ($_POST['name'] ?? '')); ?>" required>
                </div>

                <div class="form-actions">
                    <button type="submit" name="<?= $action === 'edit' ? 'update_department' : 'create_department'; ?>" class="btn btn-primary">
                        <?= $action === 'edit' ? 'Update Department' : 'Create Department'; ?>
                    </button>
                    <a href="admin.php?page=departments" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </section>
    <?php else: ?>
        <div class="search-card">
            <form method="GET" action="admin.php" class="search-form">
                <input type="hidden" name="page" value="departments">

                <div class="search-group">
                    <label for="search">Search departments</label>
                    <input type="text" id="search" name="search" placeholder="Search by department or faculty" value="<?= htmlspecialchars($search); ?>">
                </div>

                <div class="list-actions">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <a href="admin.php?page=departments" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>

        <div class="results-meta">
            Total departments found: <strong><?= count($departments); ?></strong>
        </div>

        <div class="table-card">
            <div class="table-wrapper">
                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Department</th>
                        <th>Faculty</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($departments)): ?>
                        <tr>
                            <td colspan="4" class="empty-state">No departments found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($departments as $department): ?>
                            <tr>
                                <td><?= (int) $department['id']; ?></td>
                                <td><?= htmlspecialchars($department['name']); ?></td>
                                <td><?= htmlspecialchars($department['faculty_name']); ?></td>
                                <td>
                                    <div class="table-actions">
                                        <a href="admin.php?page=departments&action=edit&id=<?= (int) $department['id']; ?>" class="btn btn-secondary btn-sm-custom">Edit</a>
                                        <form method="POST" action="admin.php?page=departments" class="inline-form">
                                            <input type="hidden" name="id" value="<?= (int) $department['id']; ?>">
                                            <input type="hidden" name="delete_department" value="1">
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