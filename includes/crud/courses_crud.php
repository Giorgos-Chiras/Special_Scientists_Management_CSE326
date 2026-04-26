<?php

function getAllCourses(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT 
            c.id,
            c.department_id,
            c.name,
            c.code,
            d.name AS department_name,
            f.name AS faculty_name
        FROM courses c
        INNER JOIN departments d ON c.department_id = d.id
        INNER JOIN faculties f ON d.faculty_id = f.id
        ORDER BY f.name ASC, d.name ASC, c.name ASC
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCourseById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.department_id,
            c.name,
            c.code,
            d.name AS department_name,
            f.name AS faculty_name
        FROM courses c
        INNER JOIN departments d ON c.department_id = d.id
        INNER JOIN faculties f ON d.faculty_id = f.id
        WHERE c.id = :id
        LIMIT 1
    ");

    $stmt->execute([':id' => $id]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);

    return $course ?: null;
}

function getCoursesByDepartmentId(PDO $pdo, int $departmentId): array
{
    $stmt = $pdo->prepare("
        SELECT id, department_id, name, code
        FROM courses
        WHERE department_id = :department_id
        ORDER BY name ASC
    ");

    $stmt->execute([':department_id' => $departmentId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function courseExists(PDO $pdo, int $departmentId, string $name, ?int $excludeCourseId = null): bool
{
    $sql = "
        SELECT id
        FROM courses
        WHERE department_id = :department_id
          AND name = :name
    ";

    $params = [
        ':department_id' => $departmentId,
        ':name' => $name
    ];

    if ($excludeCourseId !== null) {
        $sql .= " AND id != :id";
        $params[':id'] = $excludeCourseId;
    }

    $sql .= " LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
}

function createCourse(PDO $pdo, int $departmentId, string $name, ?string $code = null): bool
{
    $stmt = $pdo->prepare("
        INSERT INTO courses (department_id, name, code)
        VALUES (:department_id, :name, :code)
    ");

    return $stmt->execute([
        ':department_id' => $departmentId,
        ':name' => $name,
        ':code' => $code
    ]);
}

function updateCourse(PDO $pdo, int $id, int $departmentId, string $name, ?string $code = null): bool
{
    $stmt = $pdo->prepare("
        UPDATE courses
        SET department_id = :department_id,
            name = :name,
            code = :code
        WHERE id = :id
    ");

    return $stmt->execute([
        ':id' => $id,
        ':department_id' => $departmentId,
        ':name' => $name,
        ':code' => $code
    ]);
}

function deleteCourse(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare("
        DELETE FROM courses
        WHERE id = :id
    ");

    return $stmt->execute([':id' => $id]);
}



function getCoursesForApplicationForm(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT
            c.id,
            c.name AS course_name,
            c.code AS course_code,
            d.name AS department_name,
            f.name AS faculty_name
        FROM courses c
        INNER JOIN departments d ON c.department_id = d.id
        INNER JOIN faculties f ON d.faculty_id = f.id
        ORDER BY f.name ASC, d.name ASC, c.name ASC
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function searchCourses(PDO $pdo, string $search = ''): array
{
    $sql = "
        SELECT 
            c.id,
            c.department_id,
            c.name,
            c.code,
            d.name AS department_name,
            f.name AS faculty_name
        FROM courses c
        INNER JOIN departments d ON c.department_id = d.id
        INNER JOIN faculties f ON d.faculty_id = f.id
    ";

    $params = [];

    if ($search !== '') {
        $sql .= "
            WHERE c.name LIKE :search 
               OR c.code LIKE :search 
               OR d.name LIKE :search 
               OR f.name LIKE :search
        ";
        $params[':search'] = '%' . $search . '%';
    }

    $sql .= " ORDER BY c.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}