<?php
// Simple error log viewer - DELETE THIS FILE AFTER DEBUGGING

$logFile = __DIR__ . '/logs/error.log';

header('Content-Type: text/plain; charset=utf-8');

if (!file_exists($logFile)) {
    echo "No error log found at: $logFile\n";
    exit;
}

echo "=== LAST 50 LINES OF ERROR LOG ===\n\n";

$lines = file($logFile);
$last50 = array_slice($lines, -50);

foreach ($last50 as $line) {
    echo $line;
}

echo "\n\n=== END OF LOG ===\n";
