<?php
/**
 * Database Helper Functions
 * Provides cross-database compatibility between MySQL and SQL Server
 */

/**
 * Get current date function based on database type
 */
function db_current_date() {
    return DB_TYPE === 'sqlsrv' ? 'CAST(GETDATE() AS DATE)' : 'CURDATE()';
}

/**
 * Get current datetime function based on database type
 */
function db_current_datetime() {
    return DB_TYPE === 'sqlsrv' ? 'GETDATE()' : 'NOW()';
}

/**
 * Get LIMIT clause based on database type
 */
function db_limit($count, $offset = 0) {
    if (DB_TYPE === 'sqlsrv') {
        return $offset > 0 ? "OFFSET $offset ROWS FETCH NEXT $count ROWS ONLY" : "OFFSET 0 ROWS FETCH NEXT $count ROWS ONLY";
    }
    return $offset > 0 ? "LIMIT $offset, $count" : "LIMIT $count";
}

/**
 * Get boolean value based on database type
 */
function db_bool($value) {
    if (DB_TYPE === 'sqlsrv') {
        return $value ? '1' : '0';
    }
    return $value ? '1' : '0';
}

/**
 * Convert boolean column for WHERE clause
 */
function db_bool_column($column) {
    if (DB_TYPE === 'sqlsrv') {
        return "$column = 1";
    }
    return "$column = 1";
}
