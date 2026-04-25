<?php
require_once '../includes/db.php';

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

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1');
        $stmt->execute([
                ':username' => $username,
                ':email' => $email
        ]);

        if ($stmt->fetch()) {
            $errors[] = 'Username or email already in use.';
        }
    }

    if (empty($errors)) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare('
            INSERT INTO users (username, email, password_hash, role)
            VALUES (:username, :email, :password_hash, :role)
        ');

        $stmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':password_hash' => $passwordHash,
                ':role' => 'candidate'
        ]);

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

    <style>
        .auth-logo {
            text-align: center;
            margin-bottom: 18px;
            font-size: 26px;
            font-weight: 700;
            color: #0f172a;
        }

        .auth-logo span {
            color: #2563eb;
        }

        .auth-card {
            max-width: 420px;
            margin: 0 auto;
        }
    </style>
</head>
<body>

<div class="auth-page">
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-logo">
                EE <span>C.U.T.</span>
            </div>

            <h1 class="auth-title">Create account</h1>
            <p class="auth-subtitle">Sign up to start your application</p>

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
                            required
                    >
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?= htmlspecialchars($email); ?>"
                            required
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                            type="password"
                            id="password"
                            name="password"
                            required
                    >
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            required
                    >
                </div>

                <button type="submit" class="btn btn-primary auth-submit">
                    Sign up
                </button>
            </form>

            <p class="auth-link">
                Already have an account?
                <a href="login.php">Sign in</a>
            </p>
        </div>
    </div>
</div>

</body>
</html>