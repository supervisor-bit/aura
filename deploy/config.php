<?php
declare(strict_types=1);

// ─── Prostředí ────────────────────────────────────────────────────────────────
define('APP_ENV', 'production');
define('APP_NAME', 'AURA');
define('APP_ROOT', __DIR__);
define('BASE_URL', '/');          // app.martvi.cz — virtual host

// ─── Chybové hlášení ──────────────────────────────────────────────────────────
ini_set('display_errors', '0');
error_reporting(0);

// ─── Databáze ─────────────────────────────────────────────────────────────────
// Synology MariaDB 10 — socket /run/mysqld/mysqld10.sock
// Fallback na TCP: host=127.0.0.1;port=3306
define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);          // MariaDB 10 na Synology
define('DB_NAME', 'aura');
define('DB_USER', 'root');
define('DB_PASS', 'Mates_19760216');
define('DB_CHARSET', 'utf8mb4');
define('DB_SOCKET', '/run/mysqld/mysqld10.sock');

// ─── Singleton PDO připojení ───────────────────────────────────────────────────
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        // Zkusit socket, fallback na TCP
        try {
            $dsn = sprintf(
                'mysql:unix_socket=%s;dbname=%s;charset=%s',
                DB_SOCKET, DB_NAME, DB_CHARSET
            );
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (\PDOException $e) {
            // Fallback na TCP
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
            );
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }
    }
    return $pdo;
}

// ─── Pomocné funkce ───────────────────────────────────────────────────────────

/** Vrátí sanitizovaný string z $_GET / $_POST. */
function input(string $key, string $from = 'post', mixed $default = ''): string
{
    $source = $from === 'get' ? $_GET : $_POST;
    return isset($source[$key]) ? trim((string)$source[$key]) : (string)$default;
}

/** Odešle JSON odpověď a ukončí běh. */
function json_response(mixed $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}

/** Přesměrování. */
function redirect(string $path): never
{
    header('Location: ' . BASE_URL . ltrim($path, '/'));
    exit;
}

/** Bezpečný HTML výstup. */
function e(mixed $val): string
{
    return htmlspecialchars((string)$val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Načte a vykreslí view soubor. */
function view(string $template, array $data = []): void
{
    extract($data);
    require APP_ROOT . '/views/' . $template . '.php';
}
