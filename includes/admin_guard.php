<?php
session_start();

//TODO: Add unathorized error page

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}
