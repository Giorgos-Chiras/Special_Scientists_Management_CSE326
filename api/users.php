<?php
require_once __DIR__ . '/api_helpers.php';
require_once __DIR__ . '/../includes/crud/users_crud.php';

$action = $_GET['action'] ?? '';

//POST Register
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'register') {
    $data = getInput();

    $username = trim($data['username'] ?? '');
    $email = trim($data['email'] ?? '');
    $password = $data['password'] ?? '';
    $confirmPassword = $data['confirm_password'] ?? '';

    if ($username === '' || $email === '' || $password === '' || $confirmPassword === '') {
        jsonResponse(['success' => false, 'error' => 'All fields are required.'], 400);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['success' => false, 'error' => 'Invalid email.'], 400);
    }

    if (strlen($password) < 8) {
        jsonResponse(['success' => false, 'error' => 'Password must be at least 8 characters.'], 400);
    }

    if ($password !== $confirmPassword) {
        jsonResponse(['success' => false, 'error' => 'Passwords do not match.'], 400);
    }

    if (userExistsByUsernameOrEmail($pdo, $username, $email)) {
        jsonResponse(['success' => false, 'error' => 'Username or email already exists.'], 409);
    }

    createUser($pdo, $username, $email, password_hash($password, PASSWORD_DEFAULT));

    jsonResponse([
        'success' => true,
        'message' => 'Account created successfully.'
    ], 201);
}


//GET Current User
$user = requireAuth($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'profile') {
    unset($user['password_hash']);

    jsonResponse([
        'success' => true,
        'data' => $user
    ]);
}

jsonResponse(['success' => false, 'error' => 'Endpoint not found.'], 404);