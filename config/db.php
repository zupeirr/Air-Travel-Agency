<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1-level security sign-in check
$current_page = basename($_SERVER['PHP_SELF']);
$allowed_pages = ['login.php', 'logout.php'];

if (!in_array($current_page, $allowed_pages)) {
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

// ============================================================
// DATABASE CONNECTION — Travel Agency Management System
// ============================================================

define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'air travel agency');
define('DB_USER', 'root');       // Change to your MySQL username
define('DB_PASS', '');           // Change to your MySQL password
define('DB_CHARSET', 'utf8mb4');

// ── PDO connection (used by all pages) ──────────────────────
function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            // Show a clean error page instead of leaking credentials
            http_response_code(500);
            exit('<div style="font-family:sans-serif;padding:40px;color:#7f1d1d;background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;max-width:520px;margin:60px auto;">
                <strong>Database connection failed.</strong><br>
                Please check your database settings in <code>config/db.php</code>.<br><br>
                <em>Error: ' . htmlspecialchars($e->getMessage()) . '</em>
            </div>');
        }
    }

    return $pdo;
}

// ── Shorthand helpers ────────────────────────────────────────

/**
 * Run a SELECT and return all rows.
 * Usage: $rows = db_select("SELECT * FROM customers WHERE status = ?", ['active']);
 */
function db_select(string $sql, array $params = []): array {
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Run a SELECT and return a single row.
 */
function db_row(string $sql, array $params = []): ?array {
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Run an INSERT / UPDATE / DELETE.
 * Returns the number of affected rows.
 */
function db_execute(string $sql, array $params = []): int {
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

/**
 * Run an INSERT and return the new row ID.
 */
function db_insert(string $sql, array $params = []): string {
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    return getDB()->lastInsertId();
}

/**
 * Count rows.
 * Usage: $total = db_count("SELECT COUNT(*) FROM customers WHERE status = ?", ['active']);
 */
function db_count(string $sql, array $params = []): int {
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

// ── Code generators ──────────────────────────────────────────

/**
 * Generate a unique sequential code.
 * Usage: generate_code('customers', 'customer_code', 'CUS');
 * Returns: CUS-000042
 */
function generate_code(string $table, string $column, string $prefix): string {
    $pdo  = getDB();
    $stmt = $pdo->query("SELECT MAX(CAST(SUBSTRING_INDEX($column, '-', -1) AS UNSIGNED)) FROM `$table`");
    $last = (int) $stmt->fetchColumn();
    return $prefix . '-' . str_pad($last + 1, 6, '0', STR_PAD_LEFT);
}
