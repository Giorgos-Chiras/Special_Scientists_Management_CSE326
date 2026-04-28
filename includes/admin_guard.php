<?php
session_start();


if (!isset($_SESSION['user_id'])) {
    header("Location: /Special_Scientists_Management_CSE326/auth/login.php");
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    header("Location: /Special_Scientists_Management_CSE326/index.php");
    exit;
}
