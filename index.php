<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

// ─── Session ──────────────────────────────────────────────────────────────────
session_set_cookie_params([
    'httponly'  => true,
    'samesite'  => 'Lax',
    'secure'    => ($_SERVER['HTTPS'] ?? '') === 'on',
]);
session_start();

// ─── CSRF token ───────────────────────────────────────────────────────────────
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ─── Autoload (bez Composeru) ─────────────────────────────────────────────────
spl_autoload_register(function (string $class): void {
    $dirs = [APP_ROOT . '/controllers/', APP_ROOT . '/models/'];
    foreach ($dirs as $dir) {
        $file = $dir . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// ─── Router ───────────────────────────────────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';

// Odstranit BASE_URL prefix z URI
$base = rtrim(BASE_URL, '/');
if ($base !== '' && str_starts_with($uri, $base)) {
    $uri = substr($uri, strlen($base));
}
$uri = '/' . ltrim($uri ?: '/', '/');

// Oddělit segmenty: /clients/show/5 → ['clients','show','5']
$segments = array_values(array_filter(explode('/', $uri)));
$resource = $segments[0] ?? '';
$action   = $segments[1] ?? 'index';
$id       = isset($segments[2]) ? (int)$segments[2] : null;

// JSON body pro AJAX požadavky
$jsonBody = [];
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (str_contains($contentType, 'application/json')) {
    $raw = file_get_contents('php://input');
    if ($raw) {
        try { $jsonBody = json_decode($raw, true, 512, JSON_THROW_ON_ERROR); }
        catch (\JsonException) { $jsonBody = []; }
    }
}

// ─── Routovací tabulka ────────────────────────────────────────────────────────
// Klíč: "METHOD:resource:action" — PHP pole nemůže mít pole jako klíč
$routes = [
    // Klienti
    'GET:clients:index'    => ['ClientController',  'index'],
    'GET:clients:search'   => ['ClientController',  'search'],
    'POST:clients:store'   => ['ClientController',  'store'],
    'GET:clients:show'     => ['ClientController',  'show'],
    'POST:clients:update'  => ['ClientController',  'update'],
    'POST:clients:delete'  => ['ClientController',  'delete'],

    // Návštěvy
    'GET:visits:index'     => ['VisitController',   'index'],
    'POST:visits:store'    => ['VisitController',   'store'],
    'GET:visits:show'      => ['VisitController',   'show'],
    'POST:visits:update'   => ['VisitController',   'update'],
    'POST:visits:delete'   => ['VisitController',   'delete'],
    'POST:visits:billing'  => ['VisitController',   'billing'],

    // Produkty / ceník
    'GET:products:search'   => ['ProductController', 'search'],
    'GET:products:index'    => ['ProductController', 'index'],
    'GET:products:grouped'  => ['ProductController', 'grouped'],
    'GET:products:grouped-retail' => ['ProductController', 'groupedRetail'],
    'POST:products:store'   => ['ProductController', 'store'],
    'POST:products:update'  => ['ProductController', 'update'],
    'POST:products:delete'  => ['ProductController', 'delete'],

    // Dashboard
    'GET:dashboard:stats'   => ['ClientController',    'dashboardStats'],

    // Prodeje
    'GET:sales:index'       => ['SaleController', 'index'],
    'GET:sales:for-visit'   => ['SaleController', 'forVisit'],
    'POST:sales:store'      => ['SaleController', 'store'],
    'POST:sales:update'     => ['SaleController', 'update'],
    'POST:sales:delete'     => ['SaleController', 'delete'],
    'POST:sales:delete-by-visit' => ['SaleController', 'deleteByVisit'],

    // Číselníky
    'GET:codelists:index'   => ['CodeListController', 'index'],
    'POST:codelists:store'  => ['CodeListController', 'store'],
    'POST:codelists:update'  => ['CodeListController', 'update'],
    'POST:codelists:reorder' => ['CodeListController', 'reorder'],
    'POST:codelists:delete'  => ['CodeListController', 'delete'],

    // Účetnictví
    'GET:accounting:yearly'     => ['AccountingController', 'yearly'],
    'GET:accounting:export-csv' => ['AccountingController', 'exportCsv'],
    'GET:accounting:daily'      => ['AccountingController', 'daily'],
    'POST:accounting:close-day' => ['AccountingController', 'closeDay'],
    'GET:accounting:closings'   => ['AccountingController', 'closings'],

    // Nastavení aplikace
    'POST:settings:change-password' => ['SettingsController', 'changePassword'],
    'GET:settings:backup'           => ['SettingsController', 'backup'],
    'GET:settings:get-username'     => ['SettingsController', 'getUsername'],
    'GET:settings:get-salon'        => ['SettingsController', 'getSalon'],
    'POST:settings:save-salon'      => ['SettingsController', 'saveSalon'],
    'GET:settings:data-stats'       => ['SettingsController', 'dataStats'],
    'POST:settings:purge-data'      => ['SettingsController', 'purgeData'],

    // Štítky
    'GET:tags:index'     => ['TagController', 'index'],
    'POST:tags:store'    => ['TagController', 'store'],
    'POST:tags:update'   => ['TagController', 'update'],
    'POST:tags:delete'   => ['TagController', 'delete'],
    'POST:tags:reorder'  => ['TagController', 'reorder'],

    // Poznámky klienta
    'GET:notes:index'    => ['NoteController', 'index'],
    'POST:notes:store'   => ['NoteController', 'store'],
    'POST:notes:update'  => ['NoteController', 'update'],
    'POST:notes:delete'  => ['NoteController', 'delete'],

    // Statistiky
    'GET:stats:consumption'     => ['StatsController', 'consumption'],
];

// ─── Dispatch ─────────────────────────────────────────────────────────────────
$routeKey = "{$method}:{$resource}:{$action}";

// ─── CSRF ochrana pro POST ────────────────────────────────────────────────────
if ($method === 'POST') {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        json_response(['error' => 'Neplatný CSRF token'], 403);
    }
}

// ─── Auth routes (veřejné) ────────────────────────────────────────────────────

// Kontrola, zda existují přihlašovací údaje
$stmSetup = db()->query("SELECT COUNT(*) FROM app_settings WHERE setting_key = 'auth_user' AND setting_value IS NOT NULL AND setting_value != ''");
$isSetupDone = (int)$stmSetup->fetchColumn() > 0;

if ($routeKey === 'POST:auth:setup') {
    if ($isSetupDone) {
        json_response(['error' => 'Účet již existuje'], 400);
    }
    $user = trim($jsonBody['username'] ?? '');
    $pass = $jsonBody['password'] ?? '';
    if (strlen($user) < 2) json_response(['error' => 'Uživatelské jméno musí mít alespoň 2 znaky'], 400);
    if (strlen($pass) < 4) json_response(['error' => 'Heslo musí mít alespoň 4 znaky'], 400);

    $hash = password_hash($pass, PASSWORD_DEFAULT);
    $stm = db()->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $stm->execute(['auth_user', $user]);
    $stm->execute(['auth_pass', $hash]);
    $_SESSION['logged_in'] = true;
    $_SESSION['user'] = $user;
    json_response(['message' => 'Účet vytvořen']);
}

if ($routeKey === 'POST:auth:login') {
    $user = $jsonBody['username'] ?? '';
    $pass = $jsonBody['password'] ?? '';
    $stm = db()->prepare('SELECT setting_value FROM app_settings WHERE setting_key = ?');
    $stm->execute(['auth_user']);
    $dbUser = $stm->fetchColumn() ?: '';
    $stm->execute(['auth_pass']);
    $dbPass = $stm->fetchColumn() ?: '';
    if ($user === $dbUser && password_verify($pass, $dbPass)) {
        $_SESSION['logged_in'] = true;
        $_SESSION['user'] = $user;
        json_response(['message' => 'Přihlášení úspěšné']);
    } else {
        json_response(['error' => 'Nesprávné přihlašovací údaje'], 401);
    }
}
if ($routeKey === 'POST:auth:logout') {
    session_destroy();
    // Start a fresh session so the login page gets a valid CSRF token
    session_start();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    json_response(['message' => 'Odhlášení úspěšné']);
}
if ($routeKey === 'GET:auth:check') {
    json_response(['logged_in' => !empty($_SESSION['logged_in'])]);
}

// ─── Ověření přihlášení ───────────────────────────────────────────────────────
if (empty($_SESSION['logged_in'])) {
    if ($method === 'GET' && ($uri === '/' || $resource === '')) {
        // Show login page or setup page
        view('login', ['needsSetup' => !$isSetupDone]);
        exit;
    }
    // API requests without auth
    json_response(['error' => 'Nepřihlášen'], 401);
}

if (isset($routes[$routeKey])) {
    [$controllerClass, $controllerMethod] = $routes[$routeKey];
    $controller = new $controllerClass();
    $controller->$controllerMethod($id, $jsonBody);
} elseif ($method === 'GET' && ($uri === '/' || $resource === '')) {
    $controller = new ClientController();
    $controller->index(null, []);
} else {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Endpoint nenalezen', 'uri' => $uri, 'key' => $routeKey]);
}
