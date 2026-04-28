<?php

function uploadCv(array &$errors, ?array $existingApplication = null): array
{
    global $uploadDir, $uploadWebPath;

    $result = [
        'path'          => $existingApplication['cv_file_path'] ?? null,
        'original_name' => $existingApplication['cv_original_name'] ?? null,
    ];

    if (empty($_FILES['cv_file']['name'])) {
        return $result;
    }

    if ($_FILES['cv_file']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'CV upload failed.';
        return $result;
    }

    $fileTmp      = $_FILES['cv_file']['tmp_name'];
    $originalName = $_FILES['cv_file']['name'];
    $extension    = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $mimeType     = mime_content_type($fileTmp);

    if ($extension !== 'pdf' || $mimeType !== 'application/pdf') {
        $errors[] = 'CV must be a PDF file.';
        return $result;
    }

    if ($_FILES['cv_file']['size'] > 5 * 1024 * 1024) {
        $errors[] = 'CV file must be smaller than 5MB.';
        return $result;
    }

    $newFileName = 'cv_' . ($_SESSION['user_id'] ?? 'user') . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.pdf';
    $destination = $uploadDir . $newFileName;

    if (!move_uploaded_file($fileTmp, $destination)) {
        $errors[] = 'Could not save CV file.';
        return $result;
    }

    return [
        'path'          => $uploadWebPath . $newFileName,
        'original_name' => $originalName,
    ];
}


function uploadPdf(string $inputName, string $prefix, array &$errors): ?array
{
    global $uploadDir, $uploadWebPath;

    if (empty($_FILES[$inputName]['name'])) {
        return null;
    }

    if ($_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'File upload failed.';
        return null;
    }

    $fileTmp = $_FILES[$inputName]['tmp_name'];
    $originalName = $_FILES[$inputName]['name'];
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $mimeType = mime_content_type($fileTmp);

    if ($extension !== 'pdf' || $mimeType !== 'application/pdf') {
        $errors[] = 'File must be a PDF.';
        return null;
    }

    if ($_FILES[$inputName]['size'] > 10 * 1024 * 1024) {
        $errors[] = 'File must be smaller than 10MB.';
        return null;
    }

    $newFileName = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.pdf';
    $destination = $uploadDir . $newFileName;

    if (!move_uploaded_file($fileTmp, $destination)) {
        $errors[] = 'Could not save file.';
        return null;
    }

    return [
        'path' => $uploadWebPath . $newFileName,
        'original_name' => $originalName,
    ];
}