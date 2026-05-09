<?php
/**
 * Database Setup Script
 * Run this ONCE to create the database and import the schema
 * 
 * Usage: php setup_database.php
 */

require_once __DIR__ . '/config/db.php';

echo "=== GATE Portal Database Setup ===\n\n";

// Step 1: Connect without database to create it
try {
    echo "1. Connecting to MySQL server...\n";
    $pdo_root = new PDO(
        'mysql:host=' . DB_HOST . ';charset=utf8',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "   ✓ Connected\n\n";
} catch (PDOException $e) {
    die("   ✗ Connection failed: " . $e->getMessage() . "\n");
}

// Step 2: Create database
try {
    echo "2. Creating database '" . DB_NAME . "'...\n";
    $pdo_root->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "   ✓ Database created\n\n";
} catch (PDOException $e) {
    die("   ✗ Failed: " . $e->getMessage() . "\n");
}

// Step 3: Import schema
try {
    echo "3. Importing schema from sql/gate_portal.sql...\n";
    $pdo_root->exec("USE `" . DB_NAME . "`");
    
    $sql = file_get_contents(__DIR__ . '/sql/gate_portal.sql');
    
    // Remove CREATE DATABASE and USE statements (we already did that)
    $sql = preg_replace('/CREATE DATABASE.*?;/i', '', $sql);
    $sql = preg_replace('/USE .*?;/i', '', $sql);
    
    // Execute
    $pdo_root->exec($sql);
    echo "   ✓ Schema imported\n\n";
} catch (PDOException $e) {
    die("   ✗ Failed: " . $e->getMessage() . "\n");
}

// Step 4: Verify
try {
    echo "4. Verifying installation...\n";
    $tables = $pdo_root->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "   ✓ Found " . count($tables) . " tables:\n";
    foreach ($tables as $table) {
        echo "     - {$table}\n";
    }
    echo "\n";
} catch (PDOException $e) {
    die("   ✗ Failed: " . $e->getMessage() . "\n");
}

// Step 5: Check default admin
try {
    $admin = $pdo_root->query("SELECT email, role FROM users WHERE role='super_admin' LIMIT 1")->fetch();
    if ($admin) {
        echo "5. Default admin account:\n";
        echo "   Email: {$admin['email']}\n";
        echo "   Password: Admin@1234\n";
        echo "   ⚠ CHANGE THIS PASSWORD IMMEDIATELY AFTER FIRST LOGIN\n\n";
    }
} catch (PDOException $e) {
    echo "5. Could not verify admin account\n\n";
}

echo "=== Setup Complete ===\n";
echo "Visit: http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/gate-portal/\n";
