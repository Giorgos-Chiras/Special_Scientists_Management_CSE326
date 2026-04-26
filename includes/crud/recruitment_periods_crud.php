<?php

function getAllRecruitmentPeriods(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT id, title, start_date, end_date, is_active
        FROM recruitment_periods
        ORDER BY start_date DESC
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getActiveRecruitmentPeriods(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT id, title, start_date, end_date, is_active
        FROM recruitment_periods
        WHERE is_active = 1
        ORDER BY start_date DESC
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getRecruitmentPeriodById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("
        SELECT id, title, start_date, end_date, is_active
        FROM recruitment_periods
        WHERE id = :id
        LIMIT 1
    ");

    $stmt->execute([':id' => $id]);
    $period = $stmt->fetch(PDO::FETCH_ASSOC);

    return $period ?: null;
}

function createRecruitmentPeriod(PDO $pdo, string $title, string $startDate, string $endDate, int $isActive = 1): bool
{
    $stmt = $pdo->prepare("
        INSERT INTO recruitment_periods (title, start_date, end_date, is_active)
        VALUES (:title, :start_date, :end_date, :is_active)
    ");

    return $stmt->execute([
        ':title' => $title,
        ':start_date' => $startDate,
        ':end_date' => $endDate,
        ':is_active' => $isActive
    ]);
}

function updateRecruitmentPeriod(PDO $pdo, int $id, string $title, string $startDate, string $endDate, int $isActive = 1): bool
{
    $stmt = $pdo->prepare("
        UPDATE recruitment_periods
        SET title = :title,
            start_date = :start_date,
            end_date = :end_date,
            is_active = :is_active
        WHERE id = :id
    ");

    return $stmt->execute([
        ':id' => $id,
        ':title' => $title,
        ':start_date' => $startDate,
        ':end_date' => $endDate,
        ':is_active' => $isActive
    ]);
}


function deleteRecruitmentPeriod(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare("
        DELETE FROM recruitment_periods
        WHERE id = :id
    ");

    return $stmt->execute([':id' => $id]);
}

function recruitmentPeriodOverlaps(PDO $pdo, string $startDate, string $endDate, int $excludeId = 0): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM recruitment_periods
        WHERE id != :exclude_id
          AND :end_date >= start_date
          AND :start_date <= end_date
    ");

    $stmt->execute([
        ':exclude_id' => $excludeId,
        ':start_date' => $startDate,
        ':end_date' => $endDate
    ]);

    return (int) $stmt->fetchColumn() > 0;
}

function searchRecruitmentPeriods(PDO $pdo, string $search = ''): array
{
    $sql = "
        SELECT id, title, start_date, end_date, is_active
        FROM recruitment_periods
    ";

    $params = [];

    if ($search !== '') {
        $sql .= " WHERE title LIKE :search";
        $params[':search'] = '%' . $search . '%';
    }

    $sql .= " ORDER BY id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCurrentRecruitmentPeriod(PDO $pdo): ?array
{
    $stmt = $pdo->prepare("
        SELECT id, title, start_date, end_date, is_active
        FROM recruitment_periods
        WHERE CURDATE() BETWEEN start_date AND end_date
        ORDER BY start_date DESC
        LIMIT 1
    ");

    $stmt->execute();
    $period = $stmt->fetch(PDO::FETCH_ASSOC);

    return $period ?: null;
}

function getNextRecruitmentPeriod(PDO $pdo): ?array
{
    $stmt = $pdo->prepare("
        SELECT *
        FROM recruitment_periods
        WHERE start_date > CURDATE()
        ORDER BY start_date ASC
        LIMIT 1
    ");

    $stmt->execute();
    $period = $stmt->fetch(PDO::FETCH_ASSOC);

    return $period ?: null;
}