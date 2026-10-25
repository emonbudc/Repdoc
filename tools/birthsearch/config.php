<?php
declare(strict_types=1);

/**
 * BDRIS search (second install) — configuration & access control.
 *
 * Same role as genaretor/birth/search/config.php: this single guard covers
 * index.php, manage.php and every api/*.php endpoint of this copy.
 *
 * Both installs share ONE records database and ONE user/wallet database, so
 * they can never drift apart again:
 *   love/birth_records.db   love/repdoc.db
 */

if (!defined('REPDOC_ROOT')) {
    define('REPDOC_ROOT', dirname(__DIR__, 2)); // tools/birthsearch → project root
}
require_once REPDOC_ROOT . '/src/bootstrap.php';

/* Admin-only: record search is never public, never UDC. JSON 403 for api/*, else login redirect / 403 page. */
/* Files that define REPDOC_SKIP_AUTH before including (e.g. public health.php diagnostics) bypass this guard. */
if (!is_admin() && !defined('REPDOC_SKIP_AUTH')) {
    $isApi = strpos((string) ($_SERVER['SCRIPT_NAME'] ?? ''), '/api/') !== false;
    if ($isApi) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'forbidden', 'message' => 'Admins only.']);
        exit;
    }
    require_admin();
}

if (!defined('DB_PATH'))          define('DB_PATH', RECORDS_DB_PATH);
if (!defined('DATA_DIR'))         define('DATA_DIR', __DIR__ . '/data');
if (!defined('RESULTS_PER_PAGE')) define('RESULTS_PER_PAGE', 50);

// External service keys come from environment only — no hardcoded defaults.
// See .env.example. Empty values disable the optional enrichment path.
if (!defined('OCRSPACE_API_KEY'))   define('OCRSPACE_API_KEY', repdoc_env('OCRSPACE_API_KEY'));
if (!defined('GEMINI_API_KEY'))     define('GEMINI_API_KEY', repdoc_env('GEMINI_API_KEY'));
if (!defined('CAPTCHA_SOLVER_URL')) define('CAPTCHA_SOLVER_URL', repdoc_env('CAPTCHA_SOLVER_URL', 'http://localhost:8001') ?: 'http://localhost:8001');

/** Strip invalid UTF-8 so json_encode never fails on messy OCR text. */
function fixUtf8($v)
{
    if (is_string($v)) {
        $fixed = @iconv('UTF-8', 'UTF-8//IGNORE', $v);
        return $fixed !== false ? $fixed : $v;
    }
    if (is_array($v)) return array_map('fixUtf8', $v);
    return $v;
}

/** True when MySQL (InfinityFree) is the active driver. */
function isMySQL(): bool
{
    return repdoc_uses_mysql();
}

/** Shared PDO for the records database, or an explanatory error page. */
function getPDO(): PDO
{
    $pdo = repdoc_records_db();
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    if (repdoc_uses_mysql()) {
        echo "MySQL connection failed. Check MYSQL_HOST/DB/USER/PASS in src/bootstrap.php.\n";
        if (repdoc_is_local_request()) {
            echo "You appear to be on a local machine, where the InfinityFree host does not resolve.\n";
            echo "Use the local SQLite database instead: run 'php build.php' (or set DB_DRIVER=sqlite).\n";
        }
    } else {
        echo "Records database not found: " . DB_PATH . "\n";
        echo "Local: run 'php build.php' (or copy an existing database.db into love/).\n";
        echo "Hosting: set DB_DRIVER = 'mysql'.\n";
    }
    exit;
}

function getDB(): PDO
{
    return getPDO();
}

/** Portable "SELECT single scalar value" helper for both drivers. */
function dbQuerySingle(PDO $db, string $sql, array $params = []): mixed
{
    if (empty($params)) {
        $val = $db->query($sql)->fetchColumn();
    } else {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $val = $stmt->fetchColumn();
    }
    return $val === false ? null : $val;
}

// ── Access control ───────────────────────────────────────────────
if (PHP_SAPI !== 'cli' && !defined('REPDOC_SKIP_AUTH')) {
    if (!is_logged_in()) {
        if (str_contains((string) ($_SERVER['REQUEST_URI'] ?? ''), '/api/')) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok'        => false,
                'error'     => 'auth',
                'message'   => 'এই টুল ব্যবহার করতে লগইন করতে হবে।',
                'login_url' => repdoc_url('public/login/'),
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        require_login();
    }
    require_login();
}
