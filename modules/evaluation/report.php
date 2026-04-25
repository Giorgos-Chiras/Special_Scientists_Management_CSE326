<?php
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['hr', 'ee'], true)) {
    header('Location: ../auth/login.php');
    exit;
}

$pageTitle = 'Evaluation Report';
?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Evaluation Report</title>
        <link rel="stylesheet" href="../../assets/css/style.css">
        <link rel="stylesheet" href="../../assets/css/admin.css">
        <link rel="stylesheet" href="../../assets/css/dashboard.css">
    </head>
<body>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../../includes/evaluation_sidebar.php'; ?>

    <main class="dashboard-content">
        <div class="page-shell">
            <?php require_once __DIR__ . '/../../includes/protected_topbar.php'; ?>

            <section class="page-card placeholder-card">
                <div class="placeholder-header">
                    <h1 class="page-title">Evaluation Report</h1>
                    <p class="page-subtitle">View evaluation statistics and summaries.</p>
                </div>

                <div class="placeholder-box">
                    <h3>Evaluation Reporting</h3>
                    <p>This page will show evaluation results, sync summaries, and report statistics for HR and EE users.</p>
                </div>
            </section>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../../includes/protected_footer.php'; ?>