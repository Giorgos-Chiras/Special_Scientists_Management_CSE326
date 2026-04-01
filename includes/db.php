<?php

$host = 'localhost';
$dbname = 'special_scientists_project';
$username = 'root';
$port = '3308';
$password = '';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";

    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "Connected successfully";
} catch (PDOException $e) {
    die('Database connection failed.');
}