<?php
session_start();

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/crud/recruitment_periods_crud.php';
require_once __DIR__ . '/utils/time_utils.php';

$isLoggedIn = isset($_SESSION['user_id']);
$username = $_SESSION['username'] ?? 'User';
$role = $_SESSION['role'] ?? '';

// Mirror the same role-based redirect as login.php
function getDashboardUrl(string $role): string {
    switch ($role) {
        case 'admin':
            return 'modules/admin.php';
        case 'hr':
        case 'ee':
            return 'modules/evaluation/lms_sync.php';
        default:
            return 'modules/list.php';
    }
}

$dashboardUrl = getDashboardUrl($role);

$currentPeriod = getCurrentRecruitmentPeriod($pdo);
$nextPeriod = getNextRecruitmentPeriod($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Special Scientists Management</title>

    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/list.css">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>

<div class="landing-page">

    <header class="landing-nav">
        <div class="brand">
            EE <span>C.U.T.</span>
        </div>

        <nav class="nav-actions">
            <?php if ($isLoggedIn): ?>
                <span class="nav-user">
                    <?= htmlspecialchars($username); ?> · <?= htmlspecialchars($role); ?>
                </span>

                <a href="<?= $dashboardUrl ?>" class="btn btn-primary">Go to Dashboard</a>
                <a href="auth/logout.php" class="btn btn-secondary">Logout</a>
            <?php else: ?>
                <a href="auth/login.php" class="btn btn-secondary">Login</a>
                <a href="auth/register.php" class="btn btn-primary">Register</a>
            <?php endif; ?>
        </nav>
    </header>

    <main class="hero">
        <section class="hero-content">
            <div class="badge">
                Cyprus University of Technology · Recruitment Platform
            </div>

            <h1>
                Special Scientists Applications
            </h1>
            <br>

            <div class="hero-actions">
                <?php if ($isLoggedIn): ?>
                    <a href="<?= $dashboardUrl ?>" class="btn btn-primary">Continue to Dashboard</a>
                <?php else: ?>
                    <a href="auth/login.php" class="btn btn-primary">Sign In</a>
                <?php endif; ?>
            </div>

            <?php if ($isLoggedIn): ?>
                <p class="welcome-box">
                    Welcome back,
                    <strong><?= htmlspecialchars($username); ?></strong>.
                    You are logged in as
                    <strong><?= htmlspecialchars($role); ?></strong>.
                </p>
            <?php endif; ?>
        </section>

        <section class="hero-panel">
            <div class="dashboard-card">
                <div class="card-top">
                    <div class="card-title">Recruitment Periods</div>
                </div>

                <div class="recruitment-period-table">
                    <table class="admin-table">
                        <thead>
                        <tr>
                            <th>Period</th>
                            <th>Status</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                        </tr>
                        </thead>

                        <tbody>
                        <?php if ($currentPeriod): ?>
                            <tr>
                                <td><?= htmlspecialchars($currentPeriod['title']); ?></td>
                                <td>
                                    <span class="status-pill status-approved">Current</span>
                                </td>
                                <td><?= htmlspecialchars(formatDate($currentPeriod['start_date'])); ?></td>
                                <td><?= htmlspecialchars(formatDate($currentPeriod['end_date'])); ?></td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td colspan="4">No active period.</td>
                            </tr>
                        <?php endif; ?>

                        <?php if ($nextPeriod): ?>
                            <tr>
                                <td><?= htmlspecialchars($nextPeriod['title']); ?></td>
                                <td>
                                    <span class="status-pill status-submitted">Next</span>
                                </td>
                                <td><?= htmlspecialchars(formatDate($nextPeriod['start_date'])); ?></td>
                                <td><?= htmlspecialchars(formatDate($nextPeriod['end_date'])); ?></td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td colspan="4">No upcoming period.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </main>


</div>

</body>
</html>