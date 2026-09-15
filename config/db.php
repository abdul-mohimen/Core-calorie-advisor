<?php
/* ============ CORE CALORIE ADVISOR — PDO Connection (SQL-injection safe) ============ */
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dbName = env('DB_NAME', 'core_calorie_advisor');
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            env('DB_HOST', '127.0.0.1'), env('DB_PORT', '3306'), $dbName);
        try {
            $pdo = new PDO($dsn, env('DB_USER', 'root'), env('DB_PASS', ''), [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            // Auto-fallback for space vs underscore database naming in XAMPP phpMyAdmin
            $altName = str_contains($dbName, '_') ? str_replace('_', ' ', $dbName) : str_replace(' ', '_', $dbName);
            $altDsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                env('DB_HOST', '127.0.0.1'), env('DB_PORT', '3306'), $altName);
            try {
                $pdo = new PDO($altDsn, env('DB_USER', 'root'), env('DB_PASS', ''), [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
                return $pdo;
            } catch (PDOException $e2) {
                @file_put_contents(dirname(__DIR__) . '/logs/db-error.log',
                    date('c') . ' ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
                die('<h2 style="font-family:sans-serif">⚠️ Database connect nahi hui.</h2>
                     <p style="font-family:sans-serif">XAMPP me MySQL start karo aur <b>sql/core_calorie_advisor.sql</b> ko phpMyAdmin me import karo. (.env me DB settings check karo)</p>');
            }
        }
    }
    return $pdo;
}
