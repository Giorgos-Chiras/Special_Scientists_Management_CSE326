<?php

function getAllDepartments(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT 
            d.id,
            d.faculty_id,
            d.name,
            f.name AS faculty_name
        FROM departments d
        INNER JOIN faculties f ON d.faculty_id = f.id
        ORDER BY f.name ASC, d.name ASC
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getDepartmentById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("
        SELECT 
            d.id,
            d.faculty_id,
            d.name,
            f.name AS faculty_name
        FROM departments d
        INNER JOIN faculties f ON d.faculty_id = f.id
        WHERE d.id = :id
        LIMIT 1
    ");

    $stmt->execute([':id' => $id]);
    $department = $stmt->fetch(PDO::FETCH_ASSOC);

    return $department ?: null;
}

function getDepartmentsByFacultyId(PDO $pdo, int $facultyId): array
{
    $stmt = $pdo->prepare("
        SELECT id, faculty_id, name
        FROM departments
        WHERE faculty_id = :faculty_id
        ORDER BY name ASC
    ");

    $stmt->execute([':faculty_id' => $facultyId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function departmentExists(PDO $pdo, int $facultyId, string $name, ?int $excludeDepartmentId = null): bool
{
    $sql = "
        SELECT id
        FROM departments
        WHERE faculty_id = :faculty_id
          AND name = :name
    ";

    $params = [
        ':faculty_id' => $facultyId,
        ':name' => $name
    ];

    if ($excludeDepartmentId !== null) {
        $sql .= " AND id != :id";
        $params[':id'] = $excludeDepartmentId;
    }

    $sql .= " LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
}

function createDepartment(PDO $pdo, int $facultyId, string $name): bool
{
    $stmt = $pdo->prepare("
        INSERT INTO departments (faculty_id, name)
        VALUES (:faculty_id, :name)
    ");

    return $stmt->execute([
        ':faculty_id' => $facultyId,
        ':name' => $name
    ]);
}

function updateDepartment(PDO $pdo, int $id, int $facultyId, string $name): bool
{
    $stmt = $pdo->prepare("
        UPDATE departments
        SET faculty_id = :faculty_id,
            name = :name
        WHERE id = :id
    ");

    return $stmt->execute([
        ':id' => $id,
        ':faculty_id' => $facultyId,
        ':name' => $name
    ]);
}

function deleteDepartment(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare("
        DELETE FROM departments
        WHERE id = :id
    ");

    return $stmt->execute([':id' => $id]);
}


function searchDepartments(PDO $pdo, string $search = ''): array
{
    $sql = "
        SELECT 
            d.id,
            d.faculty_id,
            d.name,
            f.name AS faculty_name
        FROM departments d
        INNER JOIN faculties f ON d.faculty_id = f.id
    ";

    $params = [];

    if ($search !== '') {
        $sql .= " WHERE d.name LIKE :search OR f.name LIKE :search";
        $params[':search'] = '%' . $search . '%';
    }

    $sql .= " ORDER BY d.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getDepartmentsForCourseForm(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT 
            d.id,
            d.name,
            f.name AS faculty_name
        FROM departments d
        INNER JOIN faculties f ON d.faculty_id = f.id
        ORDER BY f.name ASC, d.name ASC
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
