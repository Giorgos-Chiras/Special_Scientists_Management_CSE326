<?php


function getMaterialsByCourse(PDO $pdo, int $courseId): array {
    $stmt = $pdo->prepare("SELECT * FROM course_materials WHERE course_id = :course_id ORDER BY created_at DESC");
    $stmt->execute([':course_id' => $courseId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getMaterialById(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare("SELECT * FROM course_materials WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ?: null;
}

function createMaterial(PDO $pdo, int $courseId, string $title, ?string $pdfPath): bool {
    $stmt = $pdo->prepare("INSERT INTO course_materials (course_id, title, pdf_path) VALUES (:course_id, :title, :pdf_path)");
    return $stmt->execute([
        ':course_id' => $courseId,
        ':title'     => $title,
        ':pdf_path'  => $pdfPath
    ]);
}

function updateMaterial(PDO $pdo, int $id, string $title, ?string $pdfPath): bool {
    $sql = "UPDATE course_materials SET title = :title";
    $params = [':title' => $title, ':id' => $id];

    if ($pdfPath !== null) {
        $sql .= ", pdf_path = :pdf_path";
        $params[':pdf_path'] = $pdfPath;
    }

    $sql .= " WHERE id = :id";
    return $stmt = $pdo->prepare($sql)->execute($params);
}

function deleteMaterial(PDO $pdo, int $id): bool {
    // Note: In a real app, you'd also unlink() the actual PDF file from storage here
    $stmt = $pdo->prepare("DELETE FROM course_materials WHERE id = :id");
    return $stmt->execute([':id' => $id]);
}