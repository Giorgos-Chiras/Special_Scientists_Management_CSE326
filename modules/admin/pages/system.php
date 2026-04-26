<?php
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/crud/system_settings_crud.php';

$errors = [];

$defaultSettings = [
        'system_name' => 'Special Scientists C.U.T.',
        'contact_email' => 'support@test.com',
        'applications_open' => '1',
        'maintenance_mode' => '0',
        'footer_text' => 'Special Scientists Recruitment System'
];

foreach ($defaultSettings as $key => $value) {
    upsertSystemSetting($pdo, $key, $value);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $systemName = trim($_POST['system_name'] ?? '');
    $contactEmail = trim($_POST['contact_email'] ?? '');
    $applicationsOpen = $_POST['applications_open'] ?? '0';
    $maintenanceMode = $_POST['maintenance_mode'] ?? '0';
    $footerText = trim($_POST['footer_text'] ?? '');

    if ($systemName === '') {
        $errors[] = 'System name is required.';
    }

    if ($contactEmail === '' || !filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid contact email is required.';
    }

    if (!in_array($applicationsOpen, ['0', '1'], true)) {
        $errors[] = 'Invalid applications setting.';
    }

    if (!in_array($maintenanceMode, ['0', '1'], true)) {
        $errors[] = 'Invalid maintenance mode setting.';
    }

    if ($footerText === '') {
        $errors[] = 'Footer text is required.';
    }

    if (empty($errors)) {
        $settingsToUpdate = [
                'system_name' => $systemName,
                'contact_email' => $contactEmail,
                'applications_open' => $applicationsOpen,
                'maintenance_mode' => $maintenanceMode,
                'footer_text' => $footerText
        ];

        foreach ($settingsToUpdate as $key => $value) {
            upsertSystemSetting($pdo, $key, $value);
        }

        $_SESSION['flash'] = [
                'type' => 'success',
                'title' => 'Settings updated',
                'text' => 'System settings were saved successfully.'
        ];

        header('Location: admin.php?page=system');
        exit;
    }
}

$settings = array_merge($defaultSettings, getSystemSettingsMap($pdo));
?>

<section class="page-card list-card">
    <div class="list-header">
        <div>
            <h1 class="page-title">Configure System</h1>
            <p class="page-subtitle">Manage global settings for the recruitment system.</p>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="error">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <section class="search-card">
        <form method="POST" action="admin.php?page=system" class="admin-form js-validate-form" novalidate>
            <div class="form-group">
                <label for="system_name">System Name</label>
                <input
                        type="text"
                        id="system_name"
                        name="system_name"
                        value="<?= htmlspecialchars($settings['system_name']); ?>"
                        required
                >
            </div>

            <div class="form-group">
                <label for="contact_email">Contact Email</label>
                <input
                        type="email"
                        id="contact_email"
                        name="contact_email"
                        value="<?= htmlspecialchars($settings['contact_email']); ?>"
                        required
                >
            </div>

            <div class="form-group">
                <label for="applications_open">Applications</label>
                <select id="applications_open" name="applications_open" class="admin-select">
                    <option value="1" <?= $settings['applications_open'] === '1' ? 'selected' : ''; ?>>Open</option>
                    <option value="0" <?= $settings['applications_open'] === '0' ? 'selected' : ''; ?>>Closed</option>
                </select>
            </div>

            <div class="form-group">
                <label for="maintenance_mode">Maintenance Mode</label>
                <select id="maintenance_mode" name="maintenance_mode" class="admin-select">
                    <option value="0" <?= $settings['maintenance_mode'] === '0' ? 'selected' : ''; ?>>Disabled</option>
                    <option value="1" <?= $settings['maintenance_mode'] === '1' ? 'selected' : ''; ?>>Enabled</option>
                </select>
            </div>

            <div class="form-group full-width">
                <label for="footer_text">Footer Text</label>
                <input
                        type="text"
                        id="footer_text"
                        name="footer_text"
                        value="<?= htmlspecialchars($settings['footer_text']); ?>"
                        required
                >
            </div>

            <div class="form-actions">
                <button type="submit" name="update_settings" class="btn btn-primary">Save Settings</button>
            </div>
        </form>
    </section>
</section>