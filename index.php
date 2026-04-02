<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Special Scientists Management</title>

    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/landing.css">

</head>
<body>

<div class="landing-wrapper">

    <div class="top-brand">
        Special Scientists <strong>C.U.T.</strong>
    </div>

    <div class="landing-hero">
        <div class="landing-card">

            <h1>Special Scientists Management</h1>

            <p class="landing-text">
                A simple web application for managing special scientists,
                applications, and user access for the Cyprus University of Technology.
            </p>

            <div class="landing-actions">

                <?php if (isset($_SESSION['user_id'])): ?>

                    <a href="modules/dashboard.php" class = "secondary-btn">
                        Dashboard
                    </a>

                    <a href="modules/list.php" class="secondary-btn">
                        Applications
                    </a>

                    <a href="auth/logout.php" class="secondary-btn">
                        Logout
                    </a>

                <?php else: ?>

                 <a href="auth/login.php">
                        <button type="button" class="btn btn-primary">Login</button>
                    </a>
                <?php endif; ?>

            </div>

            <?php if (isset($_SESSION['user_id'])): ?>
                <p class="welcome-box">
                    Logged in as
                    <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
                    (<?php echo htmlspecialchars($_SESSION['role']); ?>)
                </p>
            <?php endif; ?>

        </div>
    </div>

</div>

</body>
</html>
