<?php
session_start();
if (!empty($_SESSION['role'])) {
    if ($_SESSION['role'] === 'alumni') {
        header('Location: /alumni/dashboard.php');
    } elseif ($_SESSION['role'] === 'employer') {
        header('Location: /employer/dashboard.php');
    } else {
        header('Location: /admin/dashboard.php');
    }
    exit;
}
header('Location: /auth/login.php');
exit;
