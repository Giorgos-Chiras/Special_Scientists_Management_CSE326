<?php
session_start();

require_once '../includes/db.php';
require_once '../includes/crud/users_crud.php';

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter your email and password.';
    } else {
        $user = getUserByEmail($pdo, $email);

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];

            switch ($user['role']) {
                case 'admin':
                    header('Location: ../modules/admin.php');
                    break;
                case 'hr':
                case 'ee':
                    header('Location: ../modules/evaluation/lms_sync.php');
                    break;
                default:
                    header('Location: ../modules/list.php');
                    break;
            }
            exit;
        }

        $error = 'Wrong email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in</title>
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
                <h1>Welcome back.</h1>
            </div>
        </section>

        <section class="auth-form-panel">
            <div class="auth-card modern-auth-card">

                <div class="auth-logo">
                    EE <span>C.U.T.</span>
                </div>

                <h1 class="auth-title">Sign in</h1>
                <p class="auth-subtitle">Enter your account details to continue.</p>

                <?php if (isset($_GET['registered'])): ?>
                    <p class="success">Registration successful. You can now sign in.</p>
                <?php endif; ?>

                <?php if ($error !== ''): ?>
                    <p class="error"><?= htmlspecialchars($error); ?></p>
                <?php endif; ?>

                <form method="POST" action="" class="auth-form">
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
                                placeholder="Enter your password"
                                required
                        >
                    </div>

                    <button type="submit" class="btn btn-primary auth-submit">
                        Sign in
                    </button>
                </form>

                <p class="auth-link">
                    Don't have an account?
                    <a href="register.php">Create one</a>
                </p>

            </div>
        </section>

    </div>
</div>

</body>
</html>