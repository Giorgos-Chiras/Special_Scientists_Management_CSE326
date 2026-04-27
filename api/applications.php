<?php
require_once __DIR__ . '/api_helpers.php';
require_once __DIR__ . '/../includes/crud/applications_crud.php';
require_once __DIR__ . '/../includes/crud/application_evaluators_crud.php';
require_once __DIR__ . '/../includes/crud/recruitment_periods_crud.php';

$user = requireAuth($pdo);
$action = $_GET['action'] ?? '';

//GET All applications
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
    if ($user['role'] === 'candidate') {
        $applications = getApplicationsByUserId($pdo, (int) $user['id']);
    } elseif ($user['role'] === 'evaluator') {
        $applications = getApplicationsForEvaluator($pdo, (int) $user['id']);
    } elseif (in_array($user['role'], ['hr', 'admin'], true)) {
        $applications = getAllApplications($pdo);
    } else {
        jsonResponse(['success' => false, 'error' => 'Access denied.'], 403);
    }

    jsonResponse([
        'success' => true,
        'data' => $applications
    ]);
}

// GET View Application (One)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'view') {
    $id = (int) ($_GET['id'] ?? 0);

    if ($id <= 0) {
        jsonResponse(['success' => false, 'error' => 'Application ID is required.'], 400);
    }

    if ($user['role'] === 'candidate') {
        $application = getApplicationByIdForCandidate($pdo, $id, (int) $user['id']);
    } elseif ($user['role'] === 'evaluator') {
        $application = getApplicationByIdForEvaluator($pdo, $id, (int) $user['id']);
    } elseif (in_array($user['role'], ['hr', 'admin'], true)) {
        $application = getApplicationById($pdo, $id);
    } else {
        jsonResponse(['success' => false, 'error' => 'Access denied.'], 403);
    }

    if (!$application) {
        jsonResponse(['success' => false, 'error' => 'Application not found.'], 404);
    }

    jsonResponse([
        'success' => true,
        'data' => $application
    ]);
}


//POST Create Application, Candidate Only
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
    requireRole($user, ['candidate']);

    $data = getInput();

    $courseId = (int) ($data['course_id'] ?? 0);
    $title = trim($data['title'] ?? '');
    $coverLetter = trim($data['cover_letter'] ?? '');
    $qualifications = trim($data['qualifications'] ?? '');
    $status = $data['status'] ?? 'draft';

    if (!in_array($status, ['draft', 'submitted'], true)) {
        jsonResponse(['success' => false, 'error' => 'Invalid status.'], 400);
    }

    if ($courseId <= 0 || $title === '') {
        jsonResponse(['success' => false, 'error' => 'Course and title are required.'], 400);
    }

    if ($status === 'submitted' && ($coverLetter === '' || $qualifications === '')) {
        jsonResponse(['success' => false, 'error' => 'Cover letter and qualifications are required before submitting.'], 400);
    }

    $activePeriod = getCurrentRecruitmentPeriod($pdo);

    if (!$activePeriod) {
        jsonResponse(['success' => false, 'error' => 'No active recruitment period.'], 400);
    }

    createApplication(
        $pdo,
        (int) $user['id'],
        $courseId,
        (int) $activePeriod['id'],
        $title,
        $status,
        $coverLetter,
        $qualifications,
        null,
        null
    );

    jsonResponse([
        'success' => true,
        'message' => 'Application created successfully.'
    ], 201);
}


//POST Update Application
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update') {
    requireRole($user, ['candidate']);

    $data = getInput();

    $id = (int) ($data['id'] ?? 0);
    $courseId = (int) ($data['course_id'] ?? 0);
    $title = trim($data['title'] ?? '');
    $coverLetter = trim($data['cover_letter'] ?? '');
    $qualifications = trim($data['qualifications'] ?? '');
    $status = $data['status'] ?? 'draft';

    if ($id <= 0 || $courseId <= 0 || $title === '') {
        jsonResponse(['success' => false, 'error' => 'Application ID, course and title are required.'], 400);
    }

    if (!in_array($status, ['draft', 'submitted'], true)) {
        jsonResponse(['success' => false, 'error' => 'Invalid status.'], 400);
    }

    if ($status === 'submitted' && ($coverLetter === '' || $qualifications === '')) {
        jsonResponse(['success' => false, 'error' => 'Cover letter and qualifications are required before submitting.'], 400);
    }

    $existing = getApplicationByIdForCandidate($pdo, $id, (int) $user['id']);

    if (!$existing || $existing['status'] !== 'draft') {
        jsonResponse(['success' => false, 'error' => 'Only your draft applications can be updated.'], 403);
    }

    updateCandidateApplication(
        $pdo,
        $id,
        (int) $user['id'],
        $courseId,
        $title,
        $status,
        $coverLetter,
        $qualifications,
        $existing['cv_file_path'] ?? null,
        $existing['cv_original_name'] ?? null
    );

    jsonResponse([
        'success' => true,
        'message' => 'Application updated successfully.'
    ]);
}

/*
|--------------------------------------------------------------------------
| POST: update status
|--------------------------------------------------------------------------
| evaluator => only assigned applications
| hr/admin  => any application
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update_status') {
    requireRole($user, ['evaluator', 'hr', 'admin']);

    $data = getInput();

    $id = (int) ($data['id'] ?? 0);
    $status = $data['status'] ?? '';

    $allowedStatuses = ['under_review', 'approved', 'rejected'];

    if ($id <= 0 || !in_array($status, $allowedStatuses, true)) {
        jsonResponse(['success' => false, 'error' => 'Valid application ID and status are required.'], 400);
    }

    if (
        $user['role'] === 'evaluator'
        && !evaluatorCanAccessApplication($pdo, $id, (int) $user['id'])
    ) {
        jsonResponse(['success' => false, 'error' => 'You are not assigned to this application.'], 403);
    }

    updateApplicationStatus($pdo, $id, $status);

    jsonResponse([
        'success' => true,
        'message' => 'Application status updated successfully.'
    ]);
}

/*
|--------------------------------------------------------------------------
| POST: delete application
|--------------------------------------------------------------------------
| candidate only
| only own draft applications
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete') {
    requireRole($user, ['candidate']);

    $data = getInput();
    $id = (int) ($data['id'] ?? 0);

    if ($id <= 0) {
        jsonResponse(['success' => false, 'error' => 'Application ID is required.'], 400);
    }

    $existing = getApplicationByIdForCandidate($pdo, $id, (int) $user['id']);

    if (!$existing || $existing['status'] !== 'draft') {
        jsonResponse(['success' => false, 'error' => 'Only your draft applications can be deleted.'], 403);
    }

    deleteApplication($pdo, $id);

    jsonResponse([
        'success' => true,
        'message' => 'Application deleted successfully.'
    ]);
}

jsonResponse(['success' => false, 'error' => 'Endpoint not found.'], 404);