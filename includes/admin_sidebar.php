<aside class="sidebar">
    <div class="sidebar-top">
        <div class="sidebar-brand">
            <span>EE</span> <strong>Admin</strong>
        </div>
        <p class="sidebar-text">Management Panel</p>
    </div>

    <nav class="sidebar-nav">
        <a href="../modules/admin.php?=users" class="<?= isActive('users', $currentPage); ?>">Manage Users</a>
        <a href="../modules/admin.php?page=recruitment" class="<?= isActive('recruitment', $currentPage); ?>">Manage Recruitment</a>
        <a href="../modules/admin.php?page=system" class="<?= isActive('system', $currentPage); ?>">Configure System</a>
        <a href="../modules/admin.php?page=reports" class="<?= isActive('reports', $currentPage); ?>">Reports</a>
    </nav>

    <div class="sidebar-bottom">
        <a href="../auth/logout.php" class="btn btn-danger sidebar-logout js-confirm-logout">Logout</a>    </div>
</aside>
