<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/crud/users_crud.php';

header('Content-Type: application/json');

function jsonResponse(array $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function getInput(): array
{
    $json = json_decode(file_get_contents('php://input'), true);

    if (is_array($json)) {
        return $json;
    }

    return $_POST;
}

function getAuthenticatedUser(PDO $pdo): ?array
{
    $email = $_SERVER['PHP_AUTH_USER'] ?? '';
    $password = $_SERVER['PHP_AUTH_PW'] ?? '';

    if ($email === '' || $password === '') {
        return null;
    }

    $user = getUserByEmail($pdo, $email);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return null;
    }

    return $user;
}

function requireAuth(PDO $pdo): array
{
    $user = getAuthenticatedUser($pdo);

    if (!$user) {
        header('WWW-Authenticate: Basic realm="API"');
        jsonResponse(['success' => false, 'error' => 'Authentication required.'], 401);
    }

    return $user;
}

function requireRole(array $user, array $roles): void
{
    if (!in_array($user['role'], $roles, true)) {
        jsonResponse(['success' => false, 'error' => 'Access denied.'], 403);
    }
}