<?php
echo "<h2>Testing Database Connection</h2>";

// Load .env
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || strpos($line, '=') === false) continue;
        [$k, $v] = explode('=', $line, 2);
        $_ENV[trim($k)] = trim($v);
    }
}

$host = $_ENV['DB_HOST'] ?? 'localhost';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';
$name = $_ENV['DB_NAME'] ?? 'gate_portal';
$type = $_ENV['DB_TYPE'] ?? 'mysql';

echo "<p><strong>Configuration:</strong></p>";
echo "<ul>";
echo "<li>Type: {$type}</li>";
echo "<li>Host: {$host}</li>";
echo "<li>User: {$user}</li>";
echo "<li>Database: {$name}</li>";
echo "</ul>";

try {
    $pdo = new PDO("mysql:host={$host};charset=utf8", $user, $pass);
    echo "<p style='color:green'>✓ MySQL connection successful!</p>";
    
    // Check if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE '{$name}'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green'>✓ Database '{$name}' exists</p>";
        
        // Check tables
        $pdo->exec("USE `{$name}`");
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        echo "<p style='color:green'>✓ Found " . count($tables) . " tables</p>";
        
        if (count($tables) > 0) {
            echo "<p><strong>Next step:</strong> <a href='index.php'>Go to GATE Portal</a></p>";
        } else {
            echo "<p style='color:orange'>⚠ Database is empty. Run: <code>php setup_database.php</code></p>";
        }
    } else {
        echo "<p style='color:orange'>⚠ Database '{$name}' does not exist</p>";
        echo "<p><strong>Next step:</strong> Run <code>php setup_database.php</code> or import sql/gate_portal.sql via phpMyAdmin</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red'>✗ Connection failed: " . $e->getMessage() . "</p>";
    echo "<p><strong>Solution:</strong> Make sure MySQL is running in XAMPP Control Panel</p>";
}
