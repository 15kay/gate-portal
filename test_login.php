<?php
// Test script to verify alumni login credentials
require_once 'config/db.php';

echo "===========================================\n";
echo "GATE Portal - Login Credentials Test\n";
echo "===========================================\n\n";

// Test password
$test_password = 'Alumni@123';
echo "Testing password: $test_password\n\n";

// Alumni emails to test
$alumni_emails = [
    'john.doe@alumni.wsu.ac.za',
    'sarah.smith@alumni.wsu.ac.za',
    'michael.jones@alumni.wsu.ac.za',
    'linda.williams@alumni.wsu.ac.za',
    'david.brown@alumni.wsu.ac.za',
    'emma.davis@alumni.wsu.ac.za',
    'james.wilson@alumni.wsu.ac.za',
    'olivia.taylor@alumni.wsu.ac.za'
];

echo "Testing Alumni Accounts:\n";
echo "------------------------\n";

foreach ($alumni_emails as $email) {
    $stmt = $pdo->prepare("SELECT id, full_name, email, password, role FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        $password_match = password_verify($test_password, $user['password']);
        $status = $password_match ? '✓ PASS' : '✗ FAIL';
        $color = $password_match ? "\033[32m" : "\033[31m";
        $reset = "\033[0m";
        
        echo "{$color}{$status}{$reset} | {$user['full_name']} ({$email})\n";
        
        if (!$password_match) {
            echo "      Hash in DB: {$user['password']}\n";
            echo "      Expected: " . password_hash($test_password, PASSWORD_DEFAULT) . "\n";
        }
    } else {
        echo "\033[31m✗ NOT FOUND\033[0m | {$email}\n";
    }
}

echo "\n";
echo "Testing Admin Account:\n";
echo "----------------------\n";

$admin_email = 'admin@gateportal.ac';
$admin_password = 'Admin@1234';

$stmt = $pdo->prepare("SELECT id, full_name, email, password, role FROM users WHERE email = ?");
$stmt->execute([$admin_email]);
$admin = $stmt->fetch();

if ($admin) {
    $password_match = password_verify($admin_password, $admin['password']);
    $status = $password_match ? '✓ PASS' : '✗ FAIL';
    $color = $password_match ? "\033[32m" : "\033[31m";
    $reset = "\033[0m";
    
    echo "{$color}{$status}{$reset} | {$admin['full_name']} ({$admin_email})\n";
    
    if (!$password_match) {
        echo "      Hash in DB: {$admin['password']}\n";
    }
} else {
    echo "\033[31m✗ NOT FOUND\033[0m | {$admin_email}\n";
}

echo "\n===========================================\n";
echo "Test Complete\n";
echo "===========================================\n";
?>
