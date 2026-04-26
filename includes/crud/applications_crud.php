<?php

function getAllApplications(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT 
            a.id,
            a.user_id,
            a.course_id,
            a.period_id,
            a.title,
            a.status,
            a.cover_letter,
            a.qualifications,
            a.cv_file_path,
            a.cv_original_name,
            a.created_at,
            a.updated_at,
            u.username AS candidate_name,
            u.email AS candidate_email,
            c.name AS course_name,
            c.code AS course_code,
            d.name AS department_name,
            f.name AS faculty_name,
            rp.title AS period_title
        FROM applications a
        INNER JOIN users u ON a.user_id = u.id
        INNER JOIN courses c ON a.course_id = c.id
        INNER JOIN departments d ON c.department_id = d.id
        INNER JOIN faculties f ON d.faculty_id = f.id
        INNER JOIN recruitment_periods rp ON a.period_id = rp.id
        ORDER BY a.created_at DESC
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getApplicationById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("
        SELECT 
            a.id,
            a.user_id,
            a.course_id,
            a.period_id,
            a.title,
            a.status,
            a.cover_letter,
            a.qualifications,
            a.cv_file_path,
            a.cv_original_name,
            a.created_at,
            a.updated_at,
            u.username AS candidate_name,
            u.email AS candidate_email,
            u.role AS candidate_role,
            c.name AS course_name,
            c.code AS course_code,
            d.name AS department_name,
            f.name AS faculty_name,
            rp.title AS period_title,
            rp.start_date,
            rp.end_date
        FROM applications a
        INNER JOIN users u ON a.user_id = u.id
        INNER JOIN courses c ON a.course_id = c.id
        INNER JOIN departments d ON c.department_id = d.id
        INNER JOIN faculties f ON d.faculty_id = f.id
        INNER JOIN recruitment_periods rp ON a.period_id = rp.id
        WHERE a.id = :id
        LIMIT 1
    ");

    $stmt->execute([':id' => $id]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    return $application ?: null;
}

function getApplicationByIdForEvaluator(PDO $pdo, int $applicationId, int $evaluatorId): ?array
{
    $stmt = $pdo->prepare("
        SELECT 
            a.id,
            a.user_id,
            a.course_id,
            a.period_id,
            a.title,
            a.status,
            a.cover_letter,
            a.qualifications,
            a.cv_file_path,
            a.cv_original_name,
            a.created_at,
            a.updated_at,
            u.username AS candidate_name,
            u.email AS candidate_email,
            u.role AS candidate_role,
            c.name AS course_name,
            c.code AS course_code,
            d.name AS department_name,
            f.name AS faculty_name,
            rp.title AS period_title,
            rp.start_date,
            rp.end_date
        FROM applications a
        INNER JOIN users u ON a.user_id = u.id
        INNER JOIN courses c ON a.course_id = c.id
        INNER JOIN departments d ON c.department_id = d.id
        INNER JOIN faculties f ON d.faculty_id = f.id
        INNER JOIN recruitment_periods rp ON a.period_id = rp.id
        INNER JOIN application_evaluators ae ON ae.application_id = a.id
        WHERE a.id = :application_id
          AND ae.evaluator_id = :evaluator_id
        LIMIT 1
    ");

    $stmt->execute([
        ':application_id' => $applicationId,
        ':evaluator_id' => $evaluatorId
    ]);

    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    return $application ?: null;
}
function getApplicationsByUserId(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare("
        SELECT  
            a.id, 
            a.user_id, 
            a.course_id, 
            a.period_id, 
            a.title, 
            a.status,
            a.cover_letter,
            a.qualifications,
            a.cv_file_path,
            a.cv_original_name,
            a.created_at, 
            a.updated_at, 
            c.name AS course_name, 
            c.code AS course_code,
            d.name AS department_name,
            f.name AS faculty_name,
            rp.title AS period_title 
        FROM applications a 
        INNER JOIN courses c ON a.course_id = c.id
        INNER JOIN departments d ON c.department_id = d.id
        INNER JOIN faculties f ON d.faculty_id = f.id
        INNER JOIN recruitment_periods rp ON a.period_id = rp.id 
        WHERE a.user_id = :user_id 
        ORDER BY a.created_at DESC 
    ");

    $stmt->execute([':user_id' => $userId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function getApplicationsForEvaluator(PDO $pdo, int $evaluatorId): array
{
    $stmt = $pdo->prepare("
        SELECT  
            a.id,
            a.user_id,
            a.course_id,
            a.period_id,
            a.title,
            a.status,
            a.cover_letter,
            a.qualifications,
            a.cv_file_path,
            a.cv_original_name,
            a.created_at,
            a.updated_at,
            u.username AS candidate_name,
            u.email AS candidate_email,
            c.name AS course_name,
            c.code AS course_code,
            d.name AS department_name,
            f.name AS faculty_name,
            rp.title AS period_title
        FROM applications a
        INNER JOIN application_evaluators ae ON ae.application_id = a.id
        INNER JOIN users u ON a.user_id = u.id
        INNER JOIN courses c ON a.course_id = c.id
        INNER JOIN departments d ON c.department_id = d.id
        INNER JOIN faculties f ON d.faculty_id = f.id
        INNER JOIN recruitment_periods rp ON a.period_id = rp.id
        WHERE ae.evaluator_id = :evaluator_id
        ORDER BY a.created_at DESC
    ");

    $stmt->execute([':evaluator_id' => $evaluatorId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function createApplication(
    PDO $pdo,
    int $userId,
    int $courseId,
    int $periodId,
    string $title,
    string $status = 'draft',
    ?string $coverLetter = null,
    ?string $qualifications = null,
    ?string $cvFilePath = null,
    ?string $cvOriginalName = null
): bool {
    $stmt = $pdo->prepare("
        INSERT INTO applications (
            user_id,
            course_id,
            period_id,
            title,
            status,
            cover_letter,
            qualifications,
            cv_file_path,
            cv_original_name
        )
        VALUES (
            :user_id,
            :course_id,
            :period_id,
            :title,
            :status,
            :cover_letter,
            :qualifications,
            :cv_file_path,
            :cv_original_name
        )
    ");

    return $stmt->execute([
        ':user_id' => $userId,
        ':course_id' => $courseId,
        ':period_id' => $periodId,
        ':title' => $title,
        ':status' => $status,
        ':cover_letter' => $coverLetter,
        ':qualifications' => $qualifications,
        ':cv_file_path' => $cvFilePath,
        ':cv_original_name' => $cvOriginalName
    ]);
}

function updateApplication(
    PDO $pdo,
    int $id,
    int $courseId,
    int $periodId,
    string $title,
    string $status,
    ?string $coverLetter = null,
    ?string $qualifications = null,
    ?string $cvFilePath = null,
    ?string $cvOriginalName = null,
    ?int $userId = null
): bool {
    $userSql = $userId !== null ? 'user_id = :user_id,' : '';

    $stmt = $pdo->prepare("
        UPDATE applications
        SET {$userSql}
            course_id = :course_id,
            period_id = :period_id,
            title = :title,
            status = :status,
            cover_letter = :cover_letter,
            qualifications = :qualifications,
            cv_file_path = :cv_file_path,
            cv_original_name = :cv_original_name
        WHERE id = :id
    ");

    $params = [
        ':id' => $id,
        ':course_id' => $courseId,
        ':period_id' => $periodId,
        ':title' => $title,
        ':status' => $status,
        ':cover_letter' => $coverLetter,
        ':qualifications' => $qualifications,
        ':cv_file_path' => $cvFilePath,
        ':cv_original_name' => $cvOriginalName
    ];

    if ($userId !== null) {
        $params[':user_id'] = $userId;
    }

    return $stmt->execute($params);
}
function updateApplicationStatus(PDO $pdo, int $id, string $status): bool
{
    $stmt = $pdo->prepare("
        UPDATE applications
        SET status = :status
        WHERE id = :id
    ");

    return $stmt->execute([
        ':id' => $id,
        ':status' => $status
    ]);
}

function deleteApplication(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare("
        DELETE FROM applications
        WHERE id = :id
    ");

    return $stmt->execute([':id' => $id]);
}

function getApplicationByIdForCandidate(PDO $pdo, int $applicationId, int $userId): ?array
{
    $stmt = $pdo->prepare("
        SELECT  
            a.id,
            a.user_id,
            a.course_id,
            a.period_id,
            a.title,
            a.status,
            a.cover_letter,
            a.qualifications,
            a.cv_file_path,
            a.cv_original_name,
            a.created_at,
            a.updated_at,
            u.username AS candidate_name,
            u.email AS candidate_email,
            u.role AS candidate_role,
            c.name AS course_name,
            c.code AS course_code,
            d.name AS department_name,
            f.name AS faculty_name,
            rp.title AS period_title,
            rp.start_date,
            rp.end_date
        FROM applications a
        INNER JOIN users u ON a.user_id = u.id
        INNER JOIN courses c ON a.course_id = c.id
        INNER JOIN departments d ON c.department_id = d.id
        INNER JOIN faculties f ON d.faculty_id = f.id
        INNER JOIN recruitment_periods rp ON a.period_id = rp.id
        WHERE a.id = :application_id
          AND a.user_id = :user_id
        LIMIT 1
    ");

    $stmt->execute([
        ':application_id' => $applicationId,
        ':user_id' => $userId
    ]);

    $application = $stmt->fetch(PDO::FETCH_ASSOC);

    return $application ?: null;
}

function updateCandidateApplication(
    PDO $pdo,
    int $id,
    int $userId,
    int $courseId,
    string $title,
    string $status,
    ?string $coverLetter = null,
    ?string $qualifications = null,
    ?string $cvFilePath = null,
    ?string $cvOriginalName = null
): bool {
    $stmt = $pdo->prepare("
        UPDATE applications
        SET
            course_id = :course_id,
            title = :title,
            status = :status,
            cover_letter = :cover_letter,
            qualifications = :qualifications,
            cv_file_path = :cv_file_path,
            cv_original_name = :cv_original_name
        WHERE id = :id
          AND user_id = :user_id
          AND status = 'draft'
    ");

    return $stmt->execute([
        ':id' => $id,
        ':user_id' => $userId,
        ':course_id' => $courseId,
        ':title' => $title,
        ':status' => $status,
        ':cover_letter' => $coverLetter,
        ':qualifications' => $qualifications,
        ':cv_file_path' => $cvFilePath,
        ':cv_original_name' => $cvOriginalName
    ]);
}