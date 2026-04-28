<?php

/**
 * CHANGE THIS TO YOUR OWN DATABASE CREDENTIALS!!
 */
$host = 'localhost';
$dbname = 'special_scientists_project';
$username = 'project_user';
$password = 'PassWord123!';
$port = '3306';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";

    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

} catch (PDOException $e) {
    die('Database connection failed.');
}