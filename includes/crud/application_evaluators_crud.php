<?php

function getAllApplicationEvaluators(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT
            ae.id,
            ae.application_id,
            ae.evaluator_id,
            ae.assigned_at,
            a.title AS application_title,
            u.username AS evaluator_name,
            u.email AS evaluator_email
        FROM application_evaluators ae
        INNER JOIN applications a ON ae.application_id = a.id
        INNER JOIN users u ON ae.evaluator_id = u.id
        ORDER BY ae.assigned_at DESC
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getApplicationEvaluatorById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("
        SELECT
            ae.id,
            ae.application_id,
            ae.evaluator_id,
            ae.assigned_at,
            a.title AS application_title,
            u.username AS evaluator_name,
            u.email AS evaluator_email
        FROM application_evaluators ae
        INNER JOIN applications a ON ae.application_id = a.id
        INNER JOIN users u ON ae.evaluator_id = u.id
        WHERE ae.id = :id
        LIMIT 1
    ");

    $stmt->execute([':id' => $id]);
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);

    return $assignment ?: null;
}

function getEvaluatorsByApplicationId(PDO $pdo, int $applicationId): array
{
    $stmt = $pdo->prepare("
        SELECT
            ae.id,
            ae.application_id,
            ae.evaluator_id,
            ae.assigned_at,
            u.username AS evaluator_name,
            u.email AS evaluator_email
        FROM application_evaluators ae
        INNER JOIN users u ON ae.evaluator_id = u.id
        WHERE ae.application_id = :application_id
        ORDER BY ae.assigned_at DESC
    ");

    $stmt->execute([':application_id' => $applicationId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAssignmentsByEvaluatorId(PDO $pdo, int $evaluatorId): array
{
    $stmt = $pdo->prepare("
        SELECT
            ae.id,
            ae.application_id,
            ae.evaluator_id,
            ae.assigned_at,
            a.title AS application_title,
            a.status AS application_status
        FROM application_evaluators ae
        INNER JOIN applications a ON ae.application_id = a.id
        WHERE ae.evaluator_id = :evaluator_id
        ORDER BY ae.assigned_at DESC
    ");

    $stmt->execute([':evaluator_id' => $evaluatorId]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function applicationEvaluatorExists(PDO $pdo, int $applicationId, int $evaluatorId): bool
{
    $stmt = $pdo->prepare("
        SELECT id
        FROM application_evaluators
        WHERE application_id = :application_id
          AND evaluator_id = :evaluator_id
        LIMIT 1
    ");

    $stmt->execute([
        ':application_id' => $applicationId,
        ':evaluator_id' => $evaluatorId
    ]);

    return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
}

function evaluatorCanAccessApplication(PDO $pdo, int $applicationId, int $evaluatorId): bool
{
    return applicationEvaluatorExists($pdo, $applicationId, $evaluatorId);
}

function assignEvaluatorToApplication(PDO $pdo, int $applicationId, int $evaluatorId): bool
{
    $stmt = $pdo->prepare("
        INSERT INTO application_evaluators (application_id, evaluator_id)
        VALUES (:application_id, :evaluator_id)
    ");

    return $stmt->execute([
        ':application_id' => $applicationId,
        ':evaluator_id' => $evaluatorId
    ]);
}

function deleteApplicationEvaluator(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare("
        DELETE FROM application_evaluators
        WHERE id = :id
    ");

    return $stmt->execute([':id' => $id]);
}

function deleteEvaluatorFromApplication(PDO $pdo, int $applicationId, int $evaluatorId): bool
{
    $stmt = $pdo->prepare("
        DELETE FROM application_evaluators
        WHERE application_id = :application_id
          AND evaluator_id = :evaluator_id
    ");

    return $stmt->execute([
        ':application_id' => $applicationId,
        ':evaluator_id' => $evaluatorId
    ]);
}