<?php
session_start();
session_destroy();
header('Location: /gate-portal/auth/login.php');
exit;
