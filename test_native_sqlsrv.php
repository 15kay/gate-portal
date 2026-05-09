<?php
echo "=== Testing Native SQLSRV Connection ===\n\n";

$serverName = "clestudtrack02.wsu.ac.za";
$connectionInfo = [
    "Database" => "gate_portal",
    "UID" => "smmakola",
    "PWD" => "Kgau123@M",
    "CharacterSet" => "UTF-8",
    "TrustServerCertificate" => true,
    "Encrypt" => true
];

echo "Connecting to: $serverName\n";
echo "Database: gate_portal\n\n";

$conn = sqlsrv_connect($serverName, $connectionInfo);

if ($conn === false) {
    echo "✗ Connection FAILED\n\n";
    echo "Errors:\n";
    $errors = sqlsrv_errors();
    foreach ($errors as $error) {
        echo "  - " . $error['message'] . "\n";
    }
} else {
    echo "✓ Connection SUCCESSFUL!\n\n";
    
    // Test query
    $sql = "SELECT COUNT(*) as table_count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'";
    $stmt = sqlsrv_query($conn, $sql);
    
    if ($stmt) {
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        echo "Tables in database: " . $row['table_count'] . "\n\n";
        
        // Check for users table
        $sql = "SELECT COUNT(*) as admin_count FROM users WHERE role IN ('super_admin', 'admin')";
        $stmt = sqlsrv_query($conn, $sql);
        if ($stmt) {
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            echo "Admin accounts: " . $row['admin_count'] . "\n";
        }
    }
    
    sqlsrv_close($conn);
}
