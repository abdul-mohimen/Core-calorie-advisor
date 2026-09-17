<?php
/* ============ CORE CALORIE ADVISOR — PDO Connection (SQL-injection safe) ============ */
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dbName = env('DB_NAME', 'core_calorie_advisor');
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            env('DB_HOST', '127.0.0.1'), env('DB_PORT', '3306'), $dbName);
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 5,
        ];
        $initSql = "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci, sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'";
        if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
            $options[PDO::MYSQL_ATTR_INIT_COMMAND] = $initSql;
        }
        try {
            $pdo = new PDO($dsn, env('DB_USER', 'root'), env('DB_PASS', ''), $options);
            $pdo->exec($initSql);
        } catch (PDOException $e) {
            // Auto-fallback for space vs underscore database naming in XAMPP phpMyAdmin
            $altName = str_contains($dbName, '_') ? str_replace('_', ' ', $dbName) : str_replace(' ', '_', $dbName);
            $altDsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                env('DB_HOST', '127.0.0.1'), env('DB_PORT', '3306'), $altName);
            try {
                $pdo = new PDO($altDsn, env('DB_USER', 'root'), env('DB_PASS', ''), $options);
                $pdo->exec($initSql);
                return $pdo;
            } catch (PDOException $e2) {
                @file_put_contents(dirname(__DIR__) . '/logs/db-error.log',
                    date('c') . ' ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
                die('<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Database Connection Error — Core Calorie Advisor</title>' .
                    '<style>body{margin:0;padding:40px;background:#0d0f14;color:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;box-sizing:border-box}' .
                    '.card{max-width:540px;background:#161922;border:1px solid rgba(255,255,255,0.08);border-radius:16px;padding:32px;box-shadow:0 12px 36px rgba(0,0,0,0.4)}' .
                    'h2{margin:0 0 12px;color:#ff6b1a;font-size:22px;display:flex;align-items:center;gap:10px}' .
                    'p{line-height:1.6;color:#9ca3af;font-size:14px;margin:0 0 16px}' .
                    'code{background:rgba(255,255,255,0.06);padding:2px 6px;border-radius:6px;color:#e5e7eb;font-family:monospace}' .
                    '.badge{display:inline-block;padding:6px 12px;border-radius:8px;background:rgba(255,107,26,0.15);color:#ff6b1a;font-size:12px;font-weight:600;margin-bottom:16px}' .
                    '</style></head><body><div class="card">' .
                    '<span class="badge">System Notice</span>' .
                    '<h2>⚠️ Database Connection Required</h2>' .
                    '<p>Core Calorie Advisor could not establish a connection to MySQL.</p>' .
                    '<p>Please verify that <b>MySQL is running in XAMPP</b> and that the database schema is imported:</p>' .
                    '<p><code>mysql -u root core_calorie_advisor &lt; sql/core_calorie_advisor.sql</code></p>' .
                    '<p style="font-size:12px;color:#6b7280;margin:0">Connection errors are logged securely to <code>logs/db-error.log</code>.</p>' .
                    '</div></body></html>');
            }
        }
    }
    return $pdo;
}
