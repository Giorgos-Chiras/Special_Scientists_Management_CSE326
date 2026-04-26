<?php

function getAllSystemSettings(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT id, setting_key, setting_value
        FROM system_settings
        ORDER BY setting_key ASC
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getSystemSettingsMap(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT setting_key, setting_value
        FROM system_settings
    ");

    $settings = [];

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    return $settings;
}

function getSystemSettingByKey(PDO $pdo, string $key): ?array
{
    $stmt = $pdo->prepare("
        SELECT id, setting_key, setting_value
        FROM system_settings
        WHERE setting_key = :setting_key
        LIMIT 1
    ");

    $stmt->execute([':setting_key' => $key]);
    $setting = $stmt->fetch(PDO::FETCH_ASSOC);

    return $setting ?: null;
}

function getSystemSettingValue(PDO $pdo, string $key, ?string $default = null): ?string
{
    $setting = getSystemSettingByKey($pdo, $key);

    return $setting ? $setting['setting_value'] : $default;
}

function systemSettingExists(PDO $pdo, string $key): bool
{
    return getSystemSettingByKey($pdo, $key) !== null;
}

function createSystemSetting(PDO $pdo, string $key, ?string $value = null): bool
{
    $stmt = $pdo->prepare("
        INSERT INTO system_settings (setting_key, setting_value)
        VALUES (:setting_key, :setting_value)
    ");

    return $stmt->execute([
        ':setting_key' => $key,
        ':setting_value' => $value
    ]);
}

function updateSystemSetting(PDO $pdo, string $key, ?string $value = null): bool
{
    $stmt = $pdo->prepare("
        UPDATE system_settings
        SET setting_value = :setting_value
        WHERE setting_key = :setting_key
    ");

    return $stmt->execute([
        ':setting_key' => $key,
        ':setting_value' => $value
    ]);
}

function upsertSystemSetting(PDO $pdo, string $key, ?string $value = null): bool
{
    if (systemSettingExists($pdo, $key)) {
        return updateSystemSetting($pdo, $key, $value);
    }

    return createSystemSetting($pdo, $key, $value);
}

function deleteSystemSetting(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare("
        DELETE FROM system_settings
        WHERE id = :id
    ");

    return $stmt->execute([':id' => $id]);
}

function ensureDefaultSystemSettings(PDO $pdo, array $defaults): void
{
    foreach ($defaults as $key => $value) {
        if (!systemSettingExists($pdo, $key)) {
            createSystemSetting($pdo, $key, $value);
        }
    }
}

function updateSystemSettings(PDO $pdo, array $settings): void
{
    foreach ($settings as $key => $value) {
        upsertSystemSetting($pdo, $key, $value);
    }
}