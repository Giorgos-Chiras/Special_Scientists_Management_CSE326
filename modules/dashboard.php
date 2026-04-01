<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>

<div class="dashboard-layout">
    <div class="top-brand">Special Scientists <strong>C.U.T.</strong></div>

    <div class="page-shell">
        <div class="page-card dashboard-card">
            <div class="dashboard-header">
                <div>
                    <p class="dashboard-eyebrow">Protected Area</p>
                    <h1 class="page-title">Dashboard</h1>
                    <p class="page-subtitle dashboard-welcome">
                        Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?>.
                    </p>
                </div>

                <div class="dashboard-actions">
                    <a href="list.php" class="btn btn-primary">Applications</a>
                    <a href="../auth/logout.php" class="btn btn-danger">Logout</a>
                </div>
            </div>

            <div class="dashboard-main">
                <div class="dashboard-hero">
                    <div class="dashboard-hero-content">
                        <span class="dashboard-tag">Session Active</span>
                        <h2>Your account overview</h2>
                        <p>
                            This page contains information about authenticated user
                        </p>
                    </div>
                </div>

                <div class="dashboard-info-grid">
                    <div class="dashboard-info-card">
                        <span class="info-label">Username</span>
                        <span class="info-value"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    </div>

                    <div class="dashboard-info-card">
                        <span class="info-label">Role</span>
                        <span class="info-value"><?php echo htmlspecialchars($_SESSION['role']); ?></span>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

</body>
</html>