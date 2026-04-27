<?php
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/crud/users_crud.php';

$errors = [];
$userId = (int) $_SESSION['user_id'];
$user = getUserAccountById($pdo, $userId);

if (!$user) {
    $_SESSION = [];
    session_destroy();
    header('Location: ../auth/login.php');
    exit;
}

$currentUsername = $user['username'];
$currentEmail    = $user['email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_account'])) {
    $username           = trim($_POST['username'] ?? '');
    $email              = trim($_POST['email'] ?? '');
    $currentPassword    = $_POST['current_password'] ?? '';
    $newPassword        = $_POST['new_password'] ?? '';
    $confirmNewPassword = $_POST['confirm_new_password'] ?? '';

    if ($username === '') {
        $errors[] = 'Name is required.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email is required.';
    }

    if ($newPassword !== '' || $confirmNewPassword !== '') {
        if ($currentPassword === '') {
            $errors[] = 'Current password is required to change password.';
        } elseif (!password_verify($currentPassword, $user['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        }

        if (strlen($newPassword) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        }

        if ($newPassword !== $confirmNewPassword) {
            $errors[] = 'New passwords do not match.';
        }
    }

    if (userExistsByUsernameOrEmail($pdo, $username, $email, $userId)) {
        $errors[] = 'Another account already uses that name or email.';
    }

    if (empty($errors)) {
        if ($newPassword !== '') {
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            updateUserProfileWithPassword($pdo, $userId, $username, $email, $newPasswordHash);
        } else {
            updateUserProfile($pdo, $userId, $username, $email);
        }

        $_SESSION['username'] = $username;
        $_SESSION['email']    = $email;

        $_SESSION['flash'] = [
                'type'  => 'success',
                'title' => 'Updated',
                'text'  => 'Changes saved successfully.'
        ];

        header('Location: admin.php?page=admin_dashboard');
        exit;
    }

    $currentUsername = $username;
    $currentEmail    = $email;
}
?>

<section class="page-card account-card">
    <div class="list-header">
        <div>
            <h1 class="page-title">Account Settings</h1>
            <p class="page-subtitle">Update your name and password.</p>
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

    <form method="POST" action="" class="account-form js-validate-form" novalidate>
        <div class="account-grid">
            <div class="account-panel">
                <h3 class="account-section-title">Profile</h3>

                <div class="account-field">
                    <label for="username" class="account-label">Name</label>
                    <input type="text" id="username" name="username" class="account-input" value="<?= htmlspecialchars($currentUsername); ?>" required>
                </div>

                <div class="account-field">
                    <label for="email" class="account-label">Email</label>
                    <input type="email" id="email" name="email" class="account-input" value="<?= htmlspecialchars($currentEmail); ?>" required readonly>
                </div>
            </div>

            <div class="account-panel">
                <h3 class="account-section-title">Change Password</h3>
                <p class="page-subtitle" style="margin-bottom: 16px;">Leave blank to keep your current password.</p>

                <div class="account-field">
                    <label for="current_password" class="account-label">Current Password</label>
                    <input type="password" id="current_password" name="current_password" class="account-input">
                </div>

                <div class="account-field">
                    <label for="new_password" class="account-label">New Password</label>
                    <input type="password" id="new_password" name="new_password" class="account-input">
                </div>

                <div class="account-field">
                    <label for="confirm_new_password" class="account-label">Confirm New Password</label>
                    <input type="password" id="confirm_new_password" name="confirm_new_password" class="account-input">
                </div>
            </div>
        </div>

        <div class="account-actions">
            <button type="submit" name="update_account" class="btn btn-primary">Save Changes</button>
            <a href="admin.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</section>