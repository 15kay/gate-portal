<?php
session_start();
if (!empty($_SESSION['role'])) {
    if ($_SESSION['role'] === 'alumni') {
        header('Location: /gate-portal/alumni/dashboard.php');
    } elseif ($_SESSION['role'] === 'employer') {
        header('Location: /gate-portal/employer/dashboard.php');
    } else {
        header('Location: /gate-portal/admin/dashboard.php');
    }
    exit;
}
header('Location: /gate-portal/auth/login.php');
exit;
