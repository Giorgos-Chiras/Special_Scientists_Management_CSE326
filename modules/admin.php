<?php
require_once '../includes/admin_guard.php';

$currentPage = $_GET['page'] ?? 'users';

$allowedPages = [
        'users',
        'recruitment',
        'faculties',
        'departments',
        'courses',
        'periods',
        'evaluator_assignments',
        'applications',
        'application_view',
        'system',
        'reports'
];

if (!in_array($currentPage, $allowedPages, true)) {
    $currentPage = 'users';
}

function isActive($page, $currentPage)
{
    return $page === $currentPage ? 'sidebar-link active' : 'sidebar-link';
}

require_once '../includes/admin_header.php';
require_once '../includes/admin_sidebar.php';
?>

    <main class="admin-content">
        <?php require_once __DIR__ . '/admin/pages/' . $currentPage . '.php'; ?>
    </main>

<?php require_once '../includes/admin_footer.php'; ?>