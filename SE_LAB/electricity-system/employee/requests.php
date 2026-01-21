<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'employee') {
    header('Location: ../index.php');
    exit;
}
header('Location: bills.php');
exit;

