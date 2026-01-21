<?php
session_start();
require_once __DIR__ . '/db.php';

$role = $_POST['role'] ?? '';
$identifier = trim($_POST['username'] ?? '');
$pass = $_POST['password'] ?? '';

$user = null;

if ($role === 'consumer') {
    $sql = "SELECT * FROM users 
            WHERE role='consumer' 
            AND (service_number=? OR mobile=?)
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $identifier, $identifier);
} else {

    $sql = "SELECT * FROM users WHERE role=? AND (name=? OR service_number=?) LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $role, $identifier, $identifier);
}

$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();

if ($user) {
    $password_valid = false;
    if (password_verify($pass, $user['password'])) {
        $password_valid = true;
    } elseif ($user['password'] === $pass) {
        $password_valid = true;
    }
    
    if ($password_valid) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['service_number'] = $user['service_number'];
        $_SESSION['first_login'] = $user['first_login'] ?? 0;

        if (($user['role'] === 'consumer' || $user['role'] === 'employee') && ($user['first_login'] ?? 0) == 1) {
            header("Location: change_password.php");
            exit;
        }

        header("Location: index.php");
        exit;
    }
}

$_SESSION['error'] = "Invalid credentials";
header("Location: index.php");
exit;

