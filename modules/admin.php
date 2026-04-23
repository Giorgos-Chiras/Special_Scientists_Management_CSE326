<?php
require_once '../includes/admin_guard.php';

$currentPage = $_GET['page'] ?? 'users';

$allowedPages = ['users', 'recruitment', 'system', 'reports'];

if (!in_array($currentPage, $allowedPages, true)) {
    $currentPage = 'dashboard';
}

function isActive($page, $currentPage) {
    return $page === $currentPage ? 'sidebar-link active' : 'sidebar-link';
}

require_once '../includes/admin_header.php';
require_once '../includes/admin_sidebar.php';
?>

    <main class="admin-content">
        <?php require_once __DIR__ . '/admin/pages/' . $currentPage . '.php'; ?>
    </main>

<?php require_once '../includes/admin_footer.php'; ?>