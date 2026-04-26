<?php
$pageTitle = $pageTitle ?? 'Account Settings';

$username = $_SESSION['username'] ?? 'User';
$avatarLetter = strtoupper(substr($username, 0, 1));
?>
<div class="protected-topbar">
    <div>
        <h1 class="protected-page-title"><?= htmlspecialchars($pageTitle); ?></h1>
    </div>

    <a class="protected-profile-link">
        <span class="protected-profile-avatar"><?= htmlspecialchars($avatarLetter); ?></span>
        <span class="protected-profile-name"><?= htmlspecialchars($username); ?></span>
    </a>
</div>