<aside class="sidebar">
    <div class="sidebar-top">
        <div class="sidebar-brand">
            <span>EE</span> <strong>Admin</strong>
        </div>
        <p class="sidebar-text">Management Panel</p>
    </div>

    <nav class="sidebar-nav">
        <a href="admin.php?page=users" class="<?= isActive('users', $currentPage); ?>">Manage Users</a>
        <a href="admin.php?page=recruitment" class="<?= in_array($currentPage, ['recruitment', 'faculties', 'departments', 'courses', 'periods', 'evaluator_assignments'], true) ? 'sidebar-link active' : 'sidebar-link'; ?>">Manage Recruitment</a>
        <a href="admin.php?page=system" class="<?= isActive('system', $currentPage); ?>">Configure System</a>
        <a href="admin.php?page=reports" class="<?= isActive('reports', $currentPage); ?>">Reports</a>
        <a href="admin.php?page=admin_dashboard" class="<?= ($currentPage === 'admin_dashboard') ? 'sidebar-link active' : 'sidebar-link'; ?>">Account Settings</a>

        <a href="../auth/logout.php" class="btn btn-danger sidebar-logout js-confirm-logout">Logout</a>
    </nav>
</aside>