<?php
/**
 * SQL Server Connection Test
 * Upload this file to your web server and visit it in a browser
 */
?>
<!DOCTYPE html>
<html>
<head>
    <title>GATE Portal - Database Connection Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; max-width: 800px; margin: 0 auto; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #0066cc; padding-bottom: 10px; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { background: #e7f3ff; padding: 15px; border-left: 4px solid #0066cc; margin: 15px 0; }
        .test-item { padding: 10px; margin: 10px 0; border-left: 4px solid #ccc; background: #f9f9f9; }
        .test-item.pass { border-left-color: #28a745; }
        .test-item.fail { border-left-color: #dc3545; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 GATE Portal - Database Connection Test</h1>
        
        <?php
        echo "<h2>1. PHP Version</h2>";
        echo "<div class='test-item pass'>";
        echo "PHP Version: <strong>" . phpversion() . "</strong>";
        echo "</div>";

        echo "<h2>2. Required PHP Extensions</h2>";
        $extensions = get_loaded_extensions();
        
        $required = ['pdo', 'pdo_sqlsrv', 'sqlsrv'];
        foreach ($required as $ext) {
            $installed = in_array($ext, $extensions);
            $class = $installed ? 'pass' : 'fail';
            $status = $installed ? '✓ Installed' : '✗ NOT Installed';
            echo "<div class='test-item {$class}'>";
            echo "<strong>{$ext}:</strong> {$status}";
            echo "</div>";
        }

        echo "<h2>3. Environment File (.env)</h2>";
        $envFile = __DIR__ . '/.env';
        if (file_exists($envFile)) {
            echo "<div class='test-item pass'>✓ .env file exists</div>";
            
            // Parse .env
            $envVars = [];
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
                list($key, $value) = explode('=', $line, 2);
                $envVars[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
            }
            
            echo "<div class='info'>";
            echo "<strong>Database Configuration:</strong><br>";
            echo "DB_TYPE: " . ($envVars['DB_TYPE'] ?? 'NOT SET') . "<br>";
            echo "DB_HOST: " . ($envVars['DB_HOST'] ?? 'NOT SET') . "<br>";
            echo "DB_USER: " . ($envVars['DB_USER'] ?? 'NOT SET') . "<br>";
            echo "DB_NAME: " . ($envVars['DB_NAME'] ?? 'NOT SET') . "<br>";
            echo "DB_PASS: " . (isset($envVars['DB_PASS']) && $envVars['DB_PASS'] ? '****** (set)' : 'NOT SET') . "<br>";
            echo "</div>";
        } else {
            echo "<div class='test-item fail'>✗ .env file NOT found at: {$envFile}</div>";
            echo "<div class='info'>Create a .env file in the root directory with your database credentials.</div>";
        }

        echo "<h2>4. Database Connection Test</h2>";
        
        if (!in_array('pdo_sqlsrv', $extensions)) {
            echo "<div class='test-item fail'>";
            echo "✗ Cannot test connection - pdo_sqlsrv extension not installed";
            echo "</div>";
            echo "<div class='info'>";
            echo "<strong>To install SQL Server drivers:</strong><br>";
            echo "1. Download from: <a href='https://docs.microsoft.com/en-us/sql/connect/php/download-drivers-php-sql-server' target='_blank'>Microsoft Drivers for PHP</a><br>";
            echo "2. Copy php_sqlsrv.dll and php_pdo_sqlsrv.dll to PHP extensions folder<br>";
            echo "3. Add to php.ini:<br>";
            echo "<pre>extension=php_sqlsrv.dll\nextension=php_pdo_sqlsrv.dll</pre>";
            echo "4. Restart web server (IIS or Apache)";
            echo "</div>";
        } else {
            // Try connection
            $dbHost = $envVars['DB_HOST'] ?? 'clestudtrack02.wsu.ac.za';
            $dbUser = $envVars['DB_USER'] ?? 'smmakola';
            $dbPass = $envVars['DB_PASS'] ?? 'Kgau123@M';
            $dbName = $envVars['DB_NAME'] ?? 'gate_portal';
            $dbType = $envVars['DB_TYPE'] ?? 'sqlsrv';
            
            try {
                if ($dbType === 'sqlsrv') {
                    $dsn = "sqlsrv:Server={$dbHost};Database={$dbName}";
                } else {
                    $dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8";
                }
                
                $pdo = new PDO($dsn, $dbUser, $dbPass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                
                echo "<div class='test-item pass'>";
                echo "✓ <strong>Database connection successful!</strong><br>";
                echo "Connected to: <strong>{$dbName}</strong> on <strong>{$dbHost}</strong>";
                echo "</div>";
                
                // Test query
                echo "<h2>5. Database Tables Check</h2>";
                try {
                    if ($dbType === 'sqlsrv') {
                        $stmt = $pdo->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME");
                    } else {
                        $stmt = $pdo->query("SHOW TABLES");
                    }
                    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    if (count($tables) > 0) {
                        echo "<div class='test-item pass'>";
                        echo "✓ Found <strong>" . count($tables) . "</strong> tables:<br>";
                        echo "<pre>" . implode("\n", $tables) . "</pre>";
                        echo "</div>";
                        
                        // Check for required tables
                        $required_tables = ['users', 'alumni_profiles', 'opportunities', 'candidate_submissions'];
                        $missing = array_diff($required_tables, $tables);
                        if (empty($missing)) {
                            echo "<div class='test-item pass'>✓ All core tables exist</div>";
                        } else {
                            echo "<div class='test-item fail'>✗ Missing tables: " . implode(', ', $missing) . "</div>";
                        }
                    } else {
                        echo "<div class='test-item fail'>✗ No tables found - database may not be set up</div>";
                    }
                } catch (PDOException $e) {
                    echo "<div class='test-item fail'>✗ Error querying tables: " . $e->getMessage() . "</div>";
                }
                
                // Test admin user
                echo "<h2>6. Admin Account Check</h2>";
                try {
                    $stmt = $pdo->query("SELECT email, role FROM users WHERE role IN ('super_admin', 'admin') ORDER BY role");
                    $admins = $stmt->fetchAll();
                    
                    if (count($admins) > 0) {
                        echo "<div class='test-item pass'>";
                        echo "✓ Found <strong>" . count($admins) . "</strong> admin account(s):<br>";
                        echo "<ul>";
                        foreach ($admins as $admin) {
                            echo "<li>{$admin['email']} ({$admin['role']})</li>";
                        }
                        echo "</ul>";
                        echo "</div>";
                    } else {
                        echo "<div class='test-item fail'>✗ No admin accounts found</div>";
                    }
                } catch (PDOException $e) {
                    echo "<div class='test-item fail'>✗ Error checking admin accounts: " . $e->getMessage() . "</div>";
                }
                
            } catch (PDOException $e) {
                echo "<div class='test-item fail'>";
                echo "✗ <strong>Database connection failed</strong><br><br>";
                echo "<strong>Error Message:</strong><br>";
                echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
                echo "<strong>Error Code:</strong> " . $e->getCode() . "<br>";
                echo "</div>";
                
                echo "<div class='info'>";
                echo "<strong>Common Solutions:</strong><br>";
                echo "• Verify SQL Server is running<br>";
                echo "• Check firewall allows port 1433<br>";
                echo "• Verify SQL Server allows remote connections<br>";
                echo "• Check SQL Server authentication mode (must allow SQL Server Authentication)<br>";
                echo "• Verify credentials are correct in .env file<br>";
                echo "</div>";
            }
        }
        
        echo "<h2>✅ Next Steps</h2>";
        echo "<div class='info'>";
        echo "If all tests pass, you can access the portal at:<br>";
        echo "<strong><a href='index.php'>Go to GATE Portal Login</a></strong><br><br>";
        echo "Default login:<br>";
        echo "Email: <strong>admin@gateportal.ac</strong><br>";
        echo "Password: <strong>Admin@1234</strong>";
        echo "</div>";
        ?>
        
    </div>
</body>
</html>
