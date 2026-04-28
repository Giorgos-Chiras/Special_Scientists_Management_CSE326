<?php
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/crud/faculties_crud.php';
require_once __DIR__ . '/../../../includes/admin_guard.php';


$errors = [];
$action = $_GET['action'] ?? 'list';
$editFaculty = null;
$search = trim($_GET['search'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_faculty'])) {
    $name = trim($_POST['name'] ?? '');

    if ($name === '') {
        $errors[] = 'Faculty name is required.';
    }

    if (empty($errors) && facultyExistsByName($pdo, $name)) {
        $errors[] = 'A faculty with this name already exists.';
    }

    if (empty($errors)) {
        createFaculty($pdo, $name);

        $_SESSION['flash'] = [
                'type' => 'success',
                'title' => 'Faculty created',
                'text' => 'The faculty was created successfully.'
        ];

        header('Location: admin.php?page=faculties');
        exit;
    }
}

if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $editFaculty = getFacultyById($pdo, $id);

    if (!$editFaculty) {
        $_SESSION['flash'] = [
                'type' => 'error',
                'title' => 'Faculty not found',
                'text' => 'The selected faculty could not be found.'
        ];

        header('Location: admin.php?page=faculties');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_faculty'])) {
    $id = (int) ($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');

    if ($name === '') {
        $errors[] = 'Faculty name is required.';
    }

    if (empty($errors) && facultyExistsByName($pdo, $name, $id)) {
        $errors[] = 'Another faculty already uses that name.';
    }

    if (empty($errors)) {
        updateFaculty($pdo, $id, $name);

        $_SESSION['flash'] = [
                'type' => 'success',
                'title' => 'Faculty updated',
                'text' => 'The faculty was updated successfully.'
        ];

        header('Location: admin.php?page=faculties');
        exit;
    }

    $action = 'edit';
    $editFaculty = [
            'id' => $id,
            'name' => $name
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_faculty'])) {
    $id = (int) ($_POST['id'] ?? 0);

    if ($id > 0) {
        deleteFaculty($pdo, $id);

        $_SESSION['flash'] = [
                'type' => 'success',
                'title' => 'Faculty deleted',
                'text' => 'The faculty was deleted successfully.'
        ];

        header('Location: admin.php?page=faculties');
        exit;
    }
}

$faculties = searchFaculties($pdo, $search);
?>

<section class="page-card list-card">
    <div class="list-header">
        <div>
            <h1 class="page-title">Faculties</h1>
            <p class="page-subtitle">Create, edit, and manage faculties.</p>
        </div>

        <div class="list-actions">
            <a href="admin.php?page=recruitment" class="btn btn-secondary">Back</a>
            <a href="admin.php?page=faculties" class="btn btn-secondary">Faculty List</a>
            <a href="admin.php?page=faculties&action=create" class="btn btn-primary">Add Faculty</a>
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

    <?php if ($action === 'create'): ?>
        <section class="search-card">
            <form method="POST" action="admin.php?page=faculties&action=create" class="admin-form js-validate-form" novalidate>
                <div class="form-group full-width">
                    <label for="name">Faculty Name</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                </div>

                <div class="form-actions">
                    <button type="submit" name="create_faculty" class="btn btn-primary">Create Faculty</button>
                    <a href="admin.php?page=faculties" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </section>

    <?php elseif ($action === 'edit' && $editFaculty): ?>
        <section class="search-card">
            <form method="POST" action="admin.php?page=faculties&action=edit&id=<?= (int) $editFaculty['id']; ?>" class="admin-form js-validate-form" novalidate>
                <input type="hidden" name="id" value="<?= (int) $editFaculty['id']; ?>">

                <div class="form-group full-width">
                    <label for="name">Faculty Name</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($editFaculty['name']); ?>" required>
                </div>

                <div class="form-actions">
                    <button type="submit" name="update_faculty" class="btn btn-primary">Update Faculty</button>
                    <a href="admin.php?page=faculties" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </section>

    <?php else: ?>
        <div class="search-card">
            <form method="GET" action="admin.php" class="search-form">
                <input type="hidden" name="page" value="faculties">

                <div class="search-group">
                    <label for="search">Search faculties</label>
                    <input
                            type="text"
                            id="search"
                            name="search"
                            placeholder="Search by faculty name"
                            value="<?= htmlspecialchars($search); ?>"
                    >
                </div>

                <div class="list-actions">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <a href="admin.php?page=faculties" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>

        <div class="results-meta">
            Total faculties found: <strong><?= count($faculties); ?></strong>
        </div>

        <div class="table-card">
            <div class="table-wrapper">
                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Faculty Name</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($faculties)): ?>
                        <tr>
                            <td colspan="3" class="empty-state">No faculties found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($faculties as $faculty): ?>
                            <tr>
                                <td><?= (int) $faculty['id']; ?></td>
                                <td><?= htmlspecialchars($faculty['name']); ?></td>
                                <td>
                                    <div class="table-actions">
                                        <a href="admin.php?page=faculties&action=edit&id=<?= (int) $faculty['id']; ?>" class="btn btn-secondary btn-sm-custom">Edit</a>

                                        <form method="POST" action="admin.php?page=faculties" class="inline-form">
                                            <input type="hidden" name="id" value="<?= (int) $faculty['id']; ?>">
                                            <input type="hidden" name="delete_faculty" value="1">
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