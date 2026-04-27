<?php

function getAllFaculties(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT id, name
        FROM faculties
        ORDER BY name ASC
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getFacultyById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("
        SELECT id, name
        FROM faculties
        WHERE id = :id
        LIMIT 1
    ");

    $stmt->execute([':id' => $id]);
    $faculty = $stmt->fetch(PDO::FETCH_ASSOC);

    return $faculty ?: null;
}

function facultyExistsByName(PDO $pdo, string $name, ?int $excludeFacultyId = null): bool
{
    $sql = "
        SELECT id
        FROM faculties
        WHERE name = :name
    ";

    $params = [
        ':name' => $name
    ];

    if ($excludeFacultyId !== null) {
        $sql .= " AND id != :id";
        $params[':id'] = $excludeFacultyId;
    }

    $sql .= " LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
}

function createFaculty(PDO $pdo, string $name): bool
{
    $stmt = $pdo->prepare("
        INSERT INTO faculties (name)
        VALUES (:name)
    ");

    return $stmt->execute([
        ':name' => $name
    ]);
}

function updateFaculty(PDO $pdo, int $id, string $name): bool
{
    $stmt = $pdo->prepare("
        UPDATE faculties
        SET name = :name
        WHERE id = :id
    ");

    return $stmt->execute([
        ':id' => $id,
        ':name' => $name
    ]);
}

function deleteFaculty(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare("
        DELETE FROM faculties
        WHERE id = :id
    ");

    return $stmt->execute([
        ':id' => $id
    ]);
}

function searchFaculties(PDO $pdo, string $search = ''): array
{
    $sql = "
        SELECT id, name
        FROM faculties
    ";

    $params = [];

    if ($search !== '') {
        $sql .= " WHERE name LIKE :search";
        $params[':search'] = '%' . $search . '%';
    }

    $sql .= " ORDER BY id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}