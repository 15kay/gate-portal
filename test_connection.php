<?php
echo "=== Database Connection Test ===\n\n";

// Load .env
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || strpos($line, '=') === false) continue;
        [$k, $v] = explode('=', $line, 2);
        $_ENV[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
    }
}

$host = $_ENV['DB_HOST'] ?? 'localhost';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';
$name = $_ENV['DB_NAME'] ?? 'gate_portal';

echo "Host: {$host}\n";
echo "User: {$user}\n";
echo "Pass: " . (empty($pass) ? '(empty)' : str_repeat('*', strlen($pass))) . "\n";
echo "Name: {$name}\n\n";

echo "Testing connection...\n";

try {
    // Test without database first
    $pdo = new PDO(
        "mysql:host={$host};charset=utf8",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]
    );
    echo "✓ Connected to MySQL server\n\n";
    
    // Check if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE '{$name}'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Database '{$name}' exists\n";
    } else {
        echo "✗ Database '{$name}' does NOT exist\n";
        echo "  Run: CREATE DATABASE {$name};\n";
    }
    
    // List all databases
    echo "\nAvailable databases:\n";
    $dbs = $pdo->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($dbs as $db) {
        echo "  - {$db}\n";
    }
    
} catch (PDOException $e) {
    echo "✗ Connection failed\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";
}
