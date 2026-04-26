<?php

require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/crud/users_crud.php';

$errors = [];
$action = $_GET['action'] ?? 'list';
$editUser = null;
$search = trim($_GET['search'] ?? '');

$allowedRoles = ['admin', 'candidate', 'evaluator', 'hr', 'ee'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'candidate';

    if ($username === '') {
        $errors[] = 'Username is required.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email is required.';
    }

    if ($password === '' || strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    if (!in_array($role, $allowedRoles, true)) {
        $errors[] = 'Invalid role selected.';
    }

    if (empty($errors) && userExistsByUsernameOrEmail($pdo, $username, $email)) {
        $errors[] = 'Username or email already exists.';
    }

    if (empty($errors)) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        createUser($pdo, $username, $email, $passwordHash, $role);

        $_SESSION['flash'] = [
                'type' => 'success',
                'title' => 'User created',
                'text' => 'The user was created successfully.'
        ];

        header('Location: admin.php?page=users');
        exit;
    }
}

if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $editUser = getUserById($pdo, $id);

    if (!$editUser) {
        $errors[] = 'User not found.';
        $action = 'list';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $id = (int) ($_POST['id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'candidate';

    if ($username === '') {
        $errors[] = 'Username is required.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email is required.';
    }

    if (!in_array($role, $allowedRoles, true)) {
        $errors[] = 'Invalid role selected.';
    }

    if ($password !== '' && strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    if (empty($errors) && userExistsByUsernameOrEmail($pdo, $username, $email, $id)) {
        $errors[] = 'Another user already uses that username or email.';
    }

    if (empty($errors)) {
        if ($password !== '') {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            updateUserAdminWithPassword($pdo, $id, $username, $email, $role, $passwordHash);
        } else {
            updateUserAdmin($pdo, $id, $username, $email, $role);
        }

        $_SESSION['flash'] = [
                'type' => 'success',
                'title' => 'User updated',
                'text' => 'The user was updated successfully.'
        ];

        header('Location: admin.php?page=users');
        exit;
    }

    $action = 'edit';
    $editUser = [
            'id' => $id,
            'username' => $username,
            'email' => $email,
            'role' => $role
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $id = (int) ($_POST['id'] ?? 0);

    if ($id > 0) {
        deleteUser($pdo, $id);

        $_SESSION['flash'] = [
                'type' => 'success',
                'title' => 'User deleted',
                'text' => 'The user was deleted successfully.'
        ];

        header('Location: admin.php?page=users');
        exit;
    }

    $errors[] = 'Invalid user selected.';
}

$users = searchUsers($pdo, $search);
?>

<section class="page-card list-card">
    <div class="list-header">
        <div>
            <h1 class="page-title">Manage Users</h1>
            <p class="page-subtitle">Add, edit, search, and manage system users.</p>
        </div>

        <div class="list-actions">
            <a href="admin.php?page=users" class="btn btn-secondary">User List</a>
            <a href="admin.php?page=users&action=create" class="btn btn-primary">Add User</a>
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
            <form method="POST" class="admin-form js-validate-form" novalidate>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?= htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role" class="admin-select">
                        <?php foreach ($allowedRoles as $allowedRole): ?>
                            <option value="<?= htmlspecialchars($allowedRole); ?>" <?= (($_POST['role'] ?? 'candidate') === $allowedRole) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($allowedRole); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" name="create_user" class="btn btn-primary">Create User</button>
                    <a href="admin.php?page=users" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </section>

    <?php elseif ($action === 'edit' && $editUser): ?>
        <section class="search-card">
            <form method="POST" class="admin-form">
                <input type="hidden" name="id" value="<?= (int) $editUser['id']; ?>">

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?= htmlspecialchars($editUser['username'] ?? $_POST['username'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($editUser['email'] ?? $_POST['email'] ?? ''); ?>" required>
                </div>

                <div class="form-group full-width">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password">
                    <small class="form-hint">Leave blank to keep current password.</small>
                </div>

                <div class="form-group full-width">
                    <label for="role">Role</label>
                    <select id="role" name="role" class="admin-select">
                        <?php foreach ($allowedRoles as $allowedRole): ?>
                            <option value="<?= htmlspecialchars($allowedRole); ?>" <?= (($editUser['role'] ?? $_POST['role'] ?? 'candidate') === $allowedRole) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($allowedRole); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" name="update_user" class="btn btn-primary">Update User</button>
                    <a href="admin.php?page=users" class="btn btn-secondary">Cancel</a>
                </div>


            </form>
        </section>

    <?php else: ?>
        <div class="search-card">
            <form method="GET" class="search-form">
                <input type="hidden" name="page" value="users">

                <div class="search-group">
                    <label for="search">Search users</label>
                    <input
                        type="text"
                        id="search"
                        name="search"
                        placeholder="Search by username, email, or role"
                        value="<?= htmlspecialchars($search); ?>"
                    >
                </div>

                <div class="list-actions">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <a href="admin.php?page=users" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>

        <div class="results-meta">
            Total users found: <strong><?= count($users); ?></strong>
        </div>

        <div class="table-card">
            <div class="table-wrapper">
                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="6" class="empty-state">No users found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= (int) $user['id']; ?></td>
                                <td><?= htmlspecialchars($user['username']); ?></td>
                                <td><?= htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="status-pill <?= $user['role'] === 'admin' ? 'status-approved' : 'status-pending'; ?>">
                                        <?= htmlspecialchars($user['role']); ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($user['created_at']); ?></td>
                                <td>
                                    <div class="table-actions">
                                        <a href="admin.php?page=users&action=edit&id=<?= (int) $user['id']; ?>" class="btn btn-secondary btn-sm-custom">Edit</a>

                                        <form method="POST" action="admin.php?page=users" class="inline-form">
                                            <input type="hidden" name="id" value="<?= (int) $user['id']; ?>">
                                            <button type="button" class="btn btn-danger btn-sm-custom js-delete-button">Delete</button>
                                            <input type="hidden" name="delete_user" value="1">
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