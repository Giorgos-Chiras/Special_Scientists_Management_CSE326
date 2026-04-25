<?php
session_start();

require_once '../includes/db.php';

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter your email and password.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

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

                default: // candidate
                    header('Location: ../modules/list.php');
                    break;
            }

            exit;
        } else {
            $error = 'Wrong email or password.';
        }
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

            <h1 class="auth-title">Welcome back</h1>
            <p class="auth-subtitle">Sign in to your account</p>

            <?php if (isset($_GET['registered'])): ?>
                <p class="success">Registration successful. You can now sign in.</p>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <p class="error"><?= htmlspecialchars($error); ?></p>
            <?php endif; ?>

            <form method="POST" action="" class="auth-form">

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

                <button type="submit" class="btn btn-primary auth-submit">
                    Sign in
                </button>
            </form>

            <p class="auth-link">
                Don’t have an account?
                <a href="register.php">Sign up</a>
            </p>

        </div>

    </div>
</div>

</body>
</html>