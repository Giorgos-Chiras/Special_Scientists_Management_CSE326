<?php

require_once __DIR__ . '/../../../includes/db.php';

$errors = [];
$action = $_GET['action'] ?? 'list';
$editUser = null;
$search = trim($_GET['search'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';

    if ($username === '') {
        $errors[] = 'Username is required.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email is required.';
    }

    if ($password === '' || strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    if (!in_array($role, ['admin', 'user'], true)) {
        $errors[] = 'Invalid role selected.';
    }

    if (empty($errors)) {
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1");
        $checkStmt->execute([
            ':username' => $username,
            ':email' => $email
        ]);

        if ($checkStmt->fetch()) {
            $errors[] = 'Username or email already exists.';
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, password_hash, role)
                VALUES (:username, :email, :password_hash, :role)
            ");

            $stmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':password_hash' => $passwordHash,
                ':role' => $role
            ]);

            $_SESSION['flash'] = [
                'type' => 'success',
                'title' => 'User created',
                'text' => 'The user was created successfully.'
            ];

            header('Location: admin.php?page=users');
            exit;
        }
    }
}

if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];

    $stmt = $pdo->prepare("SELECT id, username, email, role, created_at FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $editUser = $stmt->fetch(PDO::FETCH_ASSOC);

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
    $role = $_POST['role'] ?? 'user';

    if ($username === '') {
        $errors[] = 'Username is required.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email is required.';
    }

    if (!in_array($role, ['admin', 'user'], true)) {
        $errors[] = 'Invalid role selected.';
    }

    if (empty($errors)) {
        $checkStmt = $pdo->prepare("
            SELECT id
            FROM users
            WHERE (username = :username OR email = :email) AND id != :id
            LIMIT 1
        ");
        $checkStmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':id' => $id
        ]);

        if ($checkStmt->fetch()) {
            $errors[] = 'Another user already uses that username or email.';
        } else {
            if ($password !== '') {
                if (strlen($password) < 6) {
                    $errors[] = 'Password must be at least 6 characters.';
                } else {
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                    $stmt = $pdo->prepare("
                        UPDATE users
                        SET username = :username,
                            email = :email,
                            password_hash = :password_hash,
                            role = :role
                        WHERE id = :id
                    ");

                    $stmt->execute([
                        ':username' => $username,
                        ':email' => $email,
                        ':password_hash' => $passwordHash,
                        ':role' => $role,
                        ':id' => $id
                    ]);

                    $_SESSION['flash'] = [
                        'type' => 'success',
                        'title' => 'User updated',
                        'text' => 'The user was updated successfully.'
                    ];

                    header('Location: admin.php?page=users');
                    exit;
                }
            } else {
                $stmt = $pdo->prepare("
                    UPDATE users
                    SET username = :username,
                        email = :email,
                        role = :role
                    WHERE id = :id
                ");

                $stmt->execute([
                    ':username' => $username,
                    ':email' => $email,
                    ':role' => $role,
                    ':id' => $id
                ]);

                $_SESSION['flash'] = [
                    'type' => 'success',
                    'title' => 'User updated',
                    'text' => 'The user was updated successfully.'
                ];

                header('Location: admin.php?page=users');
                exit;
            }
        }
    }

    if (!empty($errors)) {
        $action = 'edit';
        $editUser = [
            'id' => $id,
            'username' => $username,
            'email' => $email,
            'role' => $role
        ];
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $id = (int) ($_POST['id'] ?? 0);

    if ($id > 0) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);

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

$sql = "SELECT id, username, email, role, created_at FROM users";
$params = [];

if ($search !== '') {
    $sql .= " WHERE username LIKE :search OR email LIKE :search OR role LIKE :search";
    $params[':search'] = '%' . $search . '%';
}

$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                        <option value="user" <?= (($_POST['role'] ?? '') === 'user') ? 'selected' : ''; ?>>User</option>
                        <option value="admin" <?= (($_POST['role'] ?? '') === 'admin') ? 'selected' : ''; ?>>Admin</option>
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
                        <option value="user" <?= (($editUser['role'] ?? $_POST['role'] ?? '') === 'user') ? 'selected' : ''; ?>>User</option>
                        <option value="admin" <?= (($editUser['role'] ?? $_POST['role'] ?? '') === 'admin') ? 'selected' : ''; ?>>Admin</option>
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