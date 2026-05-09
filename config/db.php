<?php
// ── 1. Global error handler (must be first) ───────────────────────────────────
require_once dirname(__DIR__) . '/includes/error_handler.php';

// ── 2. Load .env from project root ───────────────────────────────────────────
$_envFile = dirname(__DIR__) . '/.env';
if (file_exists($_envFile)) {
    foreach (file($_envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $_line) {
        if (str_starts_with(trim($_line), '#') || strpos($_line, '=') === false) continue;
        [$_k, $_v] = explode('=', $_line, 2);
        $_k = trim($_k);
        $_v = trim($_v, " \t\n\r\0\x0B\"'");
        if (!isset($_ENV[$_k])) {
            $_ENV[$_k] = $_v;
            putenv("{$_k}={$_v}");
        }
    }
}
unset($_envFile, $_line, $_k, $_v);

// ── 3. Error display — off in production, on in development ──────────────────
if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL);
}

// ── 4. Database credentials from environment ─────────────────────────────────
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'gate_portal');
define('DB_TYPE', $_ENV['DB_TYPE'] ?? 'mysql');

// ── 5. Connect — try native sqlsrv first, then PDO ───────────────────────────
try {
    if (DB_TYPE === 'sqlsrv') {
        // Try native SQL Server functions first (no PDO required)
        if (function_exists('sqlsrv_connect')) {
            $connectionInfo = [
                "Database" => DB_NAME,
                "UID" => DB_USER,
                "PWD" => DB_PASS,
                "CharacterSet" => "UTF-8",
                "ReturnDatesAsStrings" => true
            ];
            
            $conn = sqlsrv_connect(DB_HOST, $connectionInfo);
            
            if ($conn === false) {
                $errors = sqlsrv_errors();
                $errorMsg = $errors ? $errors[0]['message'] : 'Unknown error';
                throw new Exception("SQL Server connection failed: " . $errorMsg);
            }
            
            // Create PDO-compatible wrapper
            $pdo = new class($conn) {
                private $conn;
                
                public function __construct($conn) {
                    $this->conn = $conn;
                }
                
                public function query($sql) {
                    $stmt = sqlsrv_query($this->conn, $sql);
                    if ($stmt === false) {
                        $errors = sqlsrv_errors();
                        throw new PDOException("Query failed: " . ($errors ? $errors[0]['message'] : 'Unknown'));
                    }
                    return new class($stmt) {
                        private $stmt;
                        public function __construct($stmt) { $this->stmt = $stmt; }
                        public function fetch($mode = null) { 
                            return sqlsrv_fetch_array($this->stmt, SQLSRV_FETCH_ASSOC); 
                        }
                        public function fetchAll($mode = null) {
                            $results = [];
                            while ($row = sqlsrv_fetch_array($this->stmt, SQLSRV_FETCH_ASSOC)) {
                                $results[] = $row;
                            }
                            return $results;
                        }
                        public function fetchColumn() {
                            $row = sqlsrv_fetch_array($this->stmt, SQLSRV_FETCH_NUMERIC);
                            return $row ? $row[0] : false;
                        }
                    };
                }
                
                public function prepare($sql) {
                    return new class($this->conn, $sql) {
                        private $conn;
                        private $sql;
                        private $params = [];
                        
                        public function __construct($conn, $sql) {
                            $this->conn = $conn;
                            $this->sql = $sql;
                        }
                        
                        public function execute($params = []) {
                            $this->params = $params ? array_values($params) : [];
                            $stmt = sqlsrv_query($this->conn, $this->sql, $this->params);
                            if ($stmt === false) {
                                $errors = sqlsrv_errors();
                                throw new PDOException("Execute failed: " . ($errors ? $errors[0]['message'] : 'Unknown'));
                            }
                            return new class($stmt) {
                                private $stmt;
                                public function __construct($stmt) { $this->stmt = $stmt; }
                                public function fetch($mode = null) { 
                                    return sqlsrv_fetch_array($this->stmt, SQLSRV_FETCH_ASSOC); 
                                }
                                public function fetchAll($mode = null) {
                                    $results = [];
                                    while ($row = sqlsrv_fetch_array($this->stmt, SQLSRV_FETCH_ASSOC)) {
                                        $results[] = $row;
                                    }
                                    return $results;
                                }
                                public function fetchColumn() {
                                    $row = sqlsrv_fetch_array($this->stmt, SQLSRV_FETCH_NUMERIC);
                                    return $row ? $row[0] : false;
                                }
                                public function rowCount() {
                                    return sqlsrv_rows_affected($this->stmt);
                                }
                            };
                        }
                        
                        public function bindParam($param, &$value, $type = null) {
                            // Not needed for sqlsrv_query with params array
                        }
                    };
                }
                
                public function lastInsertId() {
                    $stmt = sqlsrv_query($this->conn, "SELECT @@IDENTITY AS id");
                    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                    return $row ? $row['id'] : null;
                }
            };
        } elseif (class_exists('PDO')) {
            // Fallback to PDO SQL Server
            $pdo = new PDO(
                'sqlsrv:Server=' . DB_HOST . ';Database=' . DB_NAME,
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::SQLSRV_ATTR_QUERY_TIMEOUT => 5,
                ]
            );
        } else {
            throw new Exception('Neither sqlsrv nor PDO extensions are available');
        }
    } else {
        // MySQL connection (default)
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT            => 5,
            ]
        );
    }
} catch (Exception $e) {
    log_app_error('database', 'Database connection failed: ' . $e->getMessage(), [
        'host' => DB_HOST,
        'name' => DB_NAME,
        'type' => DB_TYPE,
        'code' => method_exists($e, 'getCode') ? (string)$e->getCode() : 'N/A',
    ]);
    render_error_page(503, $e);
}
