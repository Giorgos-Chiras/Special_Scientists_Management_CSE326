<?php
session_start();

require_once '../includes/db.php';

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        if($_SESSION['role'] == 'admin'){
            header('Location: ../modules/admin.php');

        }
        else {
            header('Location: ../modules/dashboard.php');
        }

        exit;
    } else {
        $error = 'Wrong email or password';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>
<div class="auth-page">
    <div class="top-brand">Special Scientists <strong>C.U.T.</strong></div>

    <div class="auth-wrapper">
        <div class="auth-card">
            <h1 class="auth-title">Sign in</h1>
            <p class="auth-subtitle">Login to continue</p>

            <?php if (isset($_GET['registered'])): ?>
                <p class="success">Registration completed successfully. You can now sign in.</p>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <p class="error"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input
                            type="email"
                            id="email"
                            name="email"
                            value="<?php echo htmlspecialchars($email); ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                            type="password"
                            id="password"
                            name="password"
                    >
                </div>

                <button type="submit" class="btn btn-primary auth-submit">Sign in</button>
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