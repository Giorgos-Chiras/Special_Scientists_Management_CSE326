<?php

function getAllUsers(PDO $pdo): array
{
    $stmt = $pdo->query("
        SELECT id, username, email, role, is_active, created_at
        FROM users
        ORDER BY created_at DESC
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getUserById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("
        SELECT id, username, email, role, is_active, created_at
        FROM users
        WHERE id = :id
        LIMIT 1
    ");

    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    return $user ?: null;
}

function getUserByEmail(PDO $pdo, string $email): ?array
{
    $stmt = $pdo->prepare("
        SELECT *
        FROM users
        WHERE email = :email
        LIMIT 1
    ");

    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    return $user ?: null;
}

function userExistsByUsernameOrEmail(PDO $pdo, string $username, string $email, ?int $excludeUserId = null): bool
{
    $sql = "
        SELECT id
        FROM users
        WHERE username = :username OR email = :email
    ";

    $params = [
        ':username' => $username,
        ':email' => $email
    ];

    if ($excludeUserId !== null) {
        $sql .= " AND id != :id";
        $params[':id'] = $excludeUserId;
    }

    $sql .= " LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
}

function createUser(PDO $pdo, string $username, string $email, string $passwordHash, string $role = 'candidate', int $isActive = 1): bool
{
    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, password_hash, role, is_active)
        VALUES (:username, :email, :password_hash, :role, :is_active)
    ");

    return $stmt->execute([
        ':username' => $username,
        ':email' => $email,
        ':password_hash' => $passwordHash,
        ':role' => $role,
        ':is_active' => $isActive
    ]);
}

function updateUser(PDO $pdo, int $id, string $username, string $email, string $role, int $isActive = 1): bool
{
    $stmt = $pdo->prepare("
        UPDATE users
        SET username = :username,
            email = :email,
            role = :role,
            is_active = :is_active
        WHERE id = :id
    ");

    return $stmt->execute([
        ':id' => $id,
        ':username' => $username,
        ':email' => $email,
        ':role' => $role,
        ':is_active' => $isActive
    ]);
}

function updateUserPassword(PDO $pdo, int $id, string $passwordHash): bool
{
    $stmt = $pdo->prepare("
        UPDATE users
        SET password_hash = :password_hash
        WHERE id = :id
    ");

    return $stmt->execute([
        ':id' => $id,
        ':password_hash' => $passwordHash
    ]);
}

function deleteUser(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare("
        DELETE FROM users
        WHERE id = :id
    ");

    return $stmt->execute([':id' => $id]);
}

function getUserAccountById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("
        SELECT id, username, email, password_hash, role, is_active, created_at
        FROM users
        WHERE id = :id
        LIMIT 1
    ");

    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    return $user ?: null;
}

function searchUsers(PDO $pdo, string $search = ''): array
{
    $sql = "
        SELECT id, username, email, role, created_at
        FROM users
    ";

    $params = [];

    if ($search !== '') {
        $sql .= " WHERE username LIKE :search OR email LIKE :search OR role LIKE :search";
        $params[':search'] = '%' . $search . '%';
    }

    $sql .= " ORDER BY id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function updateUserProfile(PDO $pdo, int $id, string $username, string $email): bool
{
    $stmt = $pdo->prepare("
        UPDATE users
        SET username = :username,
            email = :email
        WHERE id = :id
    ");

    return $stmt->execute([
        ':id' => $id,
        ':username' => $username,
        ':email' => $email
    ]);
}

function updateUserProfileWithPassword(PDO $pdo, int $id, string $username, string $email, string $passwordHash): bool
{
    $stmt = $pdo->prepare("
        UPDATE users
        SET username = :username,
            email = :email,
            password_hash = :password_hash
        WHERE id = :id
    ");

    return $stmt->execute([
        ':id' => $id,
        ':username' => $username,
        ':email' => $email,
        ':password_hash' => $passwordHash
    ]);
}

function updateUserAdmin(PDO $pdo, int $id, string $username, string $email, string $role): bool
{
    $stmt = $pdo->prepare("
        UPDATE users
        SET username = :username,
            email = :email,
            role = :role
        WHERE id = :id
    ");

    return $stmt->execute([
        ':id' => $id,
        ':username' => $username,
        ':email' => $email,
        ':role' => $role
    ]);
}

function updateUserAdminWithPassword(PDO $pdo, int $id, string $username, string $email, string $role, string $passwordHash): bool
{
    $stmt = $pdo->prepare("
        UPDATE users
        SET username = :username,
            email = :email,
            role = :role,
            password_hash = :password_hash
        WHERE id = :id
    ");

    return $stmt->execute([
        ':id' => $id,
        ':username' => $username,
        ':email' => $email,
        ':role' => $role,
        ':password_hash' => $passwordHash
    ]);
}
