<?php
require_once '../includes/db.php';
require_once '../includes/crud/users_crud.php';

$errors = [];
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($username === '') {
        $errors[] = 'Username is required.';
    }

    if ($email === '') {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email is invalid.';
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    if ($confirmPassword === '') {
        $errors[] = 'Confirm password is required.';
    } elseif ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors) && userExistsByUsernameOrEmail($pdo, $username, $email)) {
        $errors[] = 'Username or email already in use.';
    }

    if (empty($errors)) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        createUser($pdo, $username, $email, $passwordHash);
        header('Location: login.php?registered=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>

<div class="auth-page modern-auth-page">
    <div class="auth-split">

        <section class="auth-showcase">
            <a href="../index.php" class="auth-brand">
                EE <span>C.U.T.</span>
            </a>
            <div class="auth-showcase-content">
                <h1>Create your account.</h1>
            </div>
        </section>

        <section class="auth-form-panel">
            <div class="auth-card modern-auth-card">

                <div class="auth-logo">
                    EE <span>C.U.T.</span>
                </div>

                <h1 class="auth-title">Create account</h1>
                <p class="auth-subtitle">Fill in your details to get started.</p>

                <?php if (!empty($errors)): ?>
                    <div class="error">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="auth-form">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input
                                type="text"
                                id="username"
                                name="username"
                                value="<?= htmlspecialchars($username); ?>"
                                placeholder="Your name"
                                required
                        >
                    </div>

                    <div class="form-group">
                        <label for="email">Email address</label>
                        <input
                                type="email"
                                id="email"
                                name="email"
                                value="<?= htmlspecialchars($email); ?>"
                                placeholder="you@example.com"
                                required
                        >
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="At least 8 characters"
                                required
                        >
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm password</label>
                        <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                placeholder="Repeat your password"
                                required
                        >
                    </div>

                    <button type="submit" class="btn btn-primary auth-submit">
                        Create account
                    </button>
                </form>

                <p class="auth-link">
                    Already have an account?
                    <a href="login.php">Sign in</a>
                </p>

            </div>
        </section>

    </div>
</div>

</body>
</html>