<?php
declare(strict_types=1);

// ─── Prostředí ────────────────────────────────────────────────────────────────
define('APP_ENV', getenv('APP_ENV') ?: 'development');
define('APP_NAME', 'AURA');
define('APP_ROOT', __DIR__);
define('BASE_URL', '/');          // změň pokud běží v podadresáři, např. '/aura/'

// ─── Chybové hlášení ──────────────────────────────────────────────────────────
if (APP_ENV === 'development') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

// ─── Databáze ─────────────────────────────────────────────────────────────────
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', (int)(getenv('DB_PORT') ?: 3306));
define('DB_NAME', getenv('DB_NAME') ?: 'aura_v2');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: 'root');
define('DB_CHARSET', 'utf8mb4');
define('DB_SOCKET', getenv('DB_SOCKET') ?: '/Applications/MAMP/tmp/mysql/mysql.sock');

// ─── Singleton PDO připojení ───────────────────────────────────────────────────
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:unix_socket=%s;dbname=%s;charset=%s',
            DB_SOCKET, DB_NAME, DB_CHARSET
        );
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
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
    extract($data, EXTR_SKIP);
    $file = APP_ROOT . '/views/' . $template . '.php';
    if (!file_exists($file)) {
        throw new RuntimeException("View nenalezen: {$template}");
    }
    require $file;
}
