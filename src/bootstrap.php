<?php
declare(strict_types=1);

/**
 * Repdoc — central bootstrap.
 *
 * One include that every tool and page uses for:
 *   • session + authentication (replaces the old per-tool auth/*.php files)
 *   • database access — all databases live in /love/ (see love/.htaccess)
 *   • CSRF tokens and flash messages
 *   • the credit wallet: per-tool prices, charging, ledger, usage log
 *
 * Storage: SQLite in /love/ on a development machine, MySQL on InfinityFree.
 * The 'auto' driver keeps ONE config working in both places — see
 * repdoc_uses_mysql() for the decision and love/ for the files.
 *
 * Design system: design-system/repdoc/MASTER.md
 */

if (!defined('REPDOC_ROOT')) {
    define('REPDOC_ROOT', dirname(__DIR__));
}
if (!defined('REPDOC_LOVE')) {
    define('REPDOC_LOVE', REPDOC_ROOT . DIRECTORY_SEPARATOR . 'love');
}

// ── Server-side config (hosting only, never committed) ─────────────
// love/hosting-config.php is generated at deploy time from GitHub Secrets
// (see .github/workflows/deploy.yml) and excluded from Git + FTP sync.
// It calls putenv()/$_SERVER for MYSQL_*, DB_DRIVER, and API keys.
// Local dev has no such file, so SQLite `auto` behavior is unchanged.
$__hostingConfig = REPDOC_LOVE . DIRECTORY_SEPARATOR . 'hosting-config.php';
if (is_file($__hostingConfig)) {
    require_once $__hostingConfig;
}
unset($__hostingConfig);

/**
 * Environment read that survives hosts with putenv() disabled.
 * Checks real env, then $_SERVER / $_ENV (both set by hosting-config.php).
 */
if (!function_exists('repdoc_env')) {
    function repdoc_env(string $key, string $default = ''): string
    {
        $v = getenv($key);
        if (is_string($v) && $v !== '') return $v;
        if (isset($_SERVER[$key]) && (string) $_SERVER[$key] !== '') return (string) $_SERVER[$key];
        if (isset($_ENV[$key]) && (string) $_ENV[$key] !== '') return (string) $_ENV[$key];
        return $default;
    }
}

// ── Database configuration ───────────────────────────────────────
// 'auto'  → SQLite locally, MySQL on the InfinityFree host  (recommended)
// 'sqlite'|'mysql' → force one driver when debugging.
// Secrets come from environment only — no hardcoded defaults.
// See .env.example. Local dev uses SQLite so empty MySQL values are safe.
if (!defined('DB_DRIVER'))  define('DB_DRIVER',  repdoc_env('DB_DRIVER', 'auto') ?: 'auto');
if (!defined('MYSQL_HOST')) define('MYSQL_HOST', repdoc_env('MYSQL_HOST'));
if (!defined('MYSQL_DB'))   define('MYSQL_DB',   repdoc_env('MYSQL_DB'));
if (!defined('MYSQL_USER')) define('MYSQL_USER', repdoc_env('MYSQL_USER'));
if (!defined('MYSQL_PASS')) define('MYSQL_PASS', repdoc_env('MYSQL_PASS'));
if (!defined('MYSQL_PORT')) define('MYSQL_PORT', repdoc_env('MYSQL_PORT', '3306') ?: '3306');

// All databases live in /love/.
if (!defined('CENTRAL_DB_PATH')) define('CENTRAL_DB_PATH', REPDOC_LOVE . DIRECTORY_SEPARATOR . 'repdoc.db');
if (!defined('RECORDS_DB_PATH')) define('RECORDS_DB_PATH', REPDOC_LOVE . DIRECTORY_SEPARATOR . 'birth_records.db');

// ── Small helpers ────────────────────────────────────────────────
if (!function_exists('e')) {
    /** HTML-escape for text + quoted attributes. Bengali-safe, no double-encode. */
    function e($v): string
    {
        return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
    }
}

/**
 * Per-request CSP nonce (for Report-Only monitoring and future nonce-based enforcement).
 * No 'unsafe-eval' is ever emitted. Call repdoc_csp_nonce() and add nonce="..." to a script
 * once it is externalized; inline scripts remain allowed by the enforced policy until migrated.
 */
if (!function_exists('repdoc_csp_nonce')) {
    function repdoc_csp_nonce(): string
    {
        if (empty($GLOBALS['repdoc_csp_nonce'])) {
            $GLOBALS['repdoc_csp_nonce'] = bin2hex(random_bytes(16));
        }
        return (string) $GLOBALS['repdoc_csp_nonce'];
    }
}

/**
 * Send baseline security headers (defense-in-depth; NOT a substitute for output encoding).
 * Enforced policy stays compatible with existing inline print/QR scripts (no 'unsafe-eval').
 * A stricter nonce-based policy is sent as Report-Only for monitoring; enforce only after
 * verifying all routes and moving inline scripts to external files with nonces/hashes.
 * Call before any output on document/form routes.
 */
if (!function_exists('repdoc_security_headers')) {
    function repdoc_security_headers(): void
    {
        if (headers_sent() || PHP_SAPI === 'cli') return;
        // Hide PHP version where feasible (see also expose_php=Off recommendation).
        if (function_exists('header_remove')) @header_remove('X-Powered-By');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: same-origin');
        // Enforced: keep permissive enough for generated docs: inline scripts/styles for print + QR,
        // Google/maateen fonts, cdnjs QR lib, local images/data URIs. No 'unsafe-eval'.
        $csp = "default-src 'self'; "
            . "script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://bicea.org https://*.bicea.org; "
            . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.maateen.me https://sonnetdp.github.io; "
            . "font-src 'self' data: https://fonts.gstatic.com https://fonts.maateen.me; "
            . "img-src 'self' data: blob: https:; "
            . "connect-src 'self' https://bicea.org https://*.bicea.org; "
            . "frame-src 'self' https://bicea.org https://*.bicea.org; "
            . "object-src 'none'; base-uri 'self'; form-action 'self'; "
            . "frame-ancestors 'self'";
        header('Content-Security-Policy: ' . $csp);
        // Report-Only: stricter nonce-based candidate (no 'unsafe-inline'). Monitor violations
        // before enforcing; inline scripts without nonce will report but NOT break.
        $nonce = repdoc_csp_nonce();
        $cspRo = "default-src 'self'; "
            . "script-src 'self' 'nonce-" . $nonce . "' https://cdnjs.cloudflare.com https://bicea.org https://*.bicea.org; "
            . "style-src 'self' 'nonce-" . $nonce . "' https://fonts.googleapis.com https://fonts.maateen.me https://sonnetdp.github.io; "
            . "font-src 'self' data: https://fonts.gstatic.com https://fonts.maateen.me; "
            . "img-src 'self' data: blob: https:; "
            . "connect-src 'self' https://bicea.org https://*.bicea.org; "
            . "frame-src 'self' https://bicea.org https://*.bicea.org; "
            . "object-src 'none'; base-uri 'self'; form-action 'self'; "
            . "frame-ancestors 'self'";
        header('Content-Security-Policy-Report-Only: ' . $cspRo);
    }
}
repdoc_security_headers();

/**
 * Validate an image upload without trusting client-supplied MIME/filename.
 * Returns sanitized safe filename (uniqid + whitelisted ext) or '' when invalid.
 * Checks: upload error, size, whitelisted extension, finfo MIME, getimagesize.
 * Storage dir is created 0755; caller must move_uploaded_file() to dir . $safeName.
 */
if (!function_exists('repdoc_validate_image_upload')) {
    function repdoc_validate_image_upload(array $file, int $maxBytes = 2097152): string
    {
        if (($file['error'] ?? 1) !== 0) return '';
        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > $maxBytes) return '';
        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            // CLI/test harness may pass a plain file path; allow it for validation only.
            if (!is_file($tmp)) return '';
        }
        $ext = strtolower((string) pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if ($ext === 'jpg') $ext = 'jpeg';
        if (!in_array($ext, ['jpeg', 'png'], true)) return '';
        // Server-side MIME check (never trust $_FILES['type']).
        $mime = '';
        try {
            if (class_exists('finfo')) {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = (string) $finfo->file($tmp);
            }
        } catch (Throwable $e) {}
        if ($mime !== '' && !in_array($mime, ['image/jpeg', 'image/png'], true)) return '';
        // Ensure it is a real image (blocks polyglot PHP-in-image without GD re-encode).
        $info = @getimagesize($tmp);
        if ($info === false) return '';
        if (!in_array($info[2] ?? 0, [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) return '';
        $safeExt = ($info[2] === IMAGETYPE_PNG) ? 'png' : 'jpg';
        return uniqid('img_', true) . '.' . $safeExt;
    }
}

/** True on a development machine: CLI, localhost/127.0.0.1/[::1], *.local, *.test. */
function repdoc_is_local_request(): bool
{
    if (PHP_SAPI === 'cli') return true;

    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
    if ($host === '') return false;

    if (preg_match('/^\[(.+)\](?::\d+)?$/', $host, $m)) {
        $host = $m[1];
    } else {
        $host = (string) preg_replace('/:\d+$/', '', $host);
    }
    $host = trim($host, '[]');

    if (in_array($host, ['localhost', '127.0.0.1', '::1', '0.0.0.0'], true)) return true;
    return str_ends_with($host, '.local') || str_ends_with($host, '.test');
}

/** True when MySQL should be used (InfinityFree) instead of the local SQLite files. */
function repdoc_uses_mysql(): bool
{
    $driver = strtolower((string) DB_DRIVER);
    if ($driver === 'mysql')  return true;
    if ($driver === 'sqlite') return false;

    // 'auto' — decide per request.
    if (is_file(CENTRAL_DB_PATH)) return false;   // local files exist → local
    if (repdoc_is_local_request()) return false;  // never dial the host from a dev box
    return true;                                   // InfinityFree: 10 MB per-file limit
}

/** Web path of the project root relative to the document root ('' when at the root). */
function repdoc_base_url(): string
{
    static $base = null;
    if ($base !== null) return $base;

    // Manual override (set on setup.php, stored in love/hosting-config.php).
    $override = repdoc_env('REPDOC_BASE_URL');
    if (!is_string($override) || $override === '') {
        $override = defined('REPDOC_BASE_URL') ? (string) REPDOC_BASE_URL : '';
    }
    if ($override !== '') {
        $override = trim($override);
        if (preg_match('#^https?://#i', $override)) {
            $urlPath  = parse_url($override, PHP_URL_PATH);
            $override = is_string($urlPath) ? $urlPath : '';
        }
        $override = trim($override, '/');
        $base     = $override === '' ? '' : '/' . $override;
        return $base;
    }

    $docRoot = str_replace('\\', '/', (string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
    $root    = str_replace('\\', '/', REPDOC_ROOT);
    if ($docRoot !== '' && str_starts_with($root, $docRoot)) {
        $base = rtrim(substr($root, strlen($docRoot)), '/');
    } else {
        $base = '';
    }
    return $base;
}

/** Absolute site path for a project-relative path, e.g. repdoc_url('public/login/'). */
function repdoc_url(string $path = ''): string
{
    return repdoc_base_url() . '/' . ltrim($path, '/');
}

/**
 * Turn a `?next=` value into a safe, same-site URL (blocks open redirects).
 * `next` is stored project-relative, e.g. /genaretor/birth/search/index.php.
 */
function repdoc_safe_next(?string $next): string
{
    $next = (string) $next;
    if ($next === '' || $next[0] !== '/' || str_starts_with($next, '//') || str_contains($next, '://')) {
        return repdoc_url('/');
    }
    return repdoc_base_url() . $next;
}

/**
 * Default landing page by role: admins → admin panel, approved UDC
 * entrepreneurs → UDC panel, everyone else → homepage.
 */
function repdoc_panel_home(): string
{
    if (is_admin()) return repdoc_url('admin/');
    if (is_udc()) return repdoc_url('Entrepreneur/panel/');
    return repdoc_url('/');
}

/** Send a redirect and stop. */
function redirect(string $url): never
{
    if (!headers_sent()) {
        header('Location: ' . $url);
    }
    exit;
}

// ── Session ──────────────────────────────────────────────────────
if (!function_exists('repdoc_session')) {
    function repdoc_session(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443');
        @ini_set('session.cookie_httponly', '1');
        @ini_set('session.cookie_samesite', 'Lax');
        @ini_set('session.cookie_secure', $https ? '1' : '0');
        if (PHP_SAPI !== 'cli' && !headers_sent()) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure'   => $https,
            ]);
            session_name('repdoc_session');
        }
        @session_start();
    }
}
repdoc_session();

// Count public page views for the homepage counter. No-op for CLI, admin
// panels, bots and non-GET requests (see repdoc_track_view()).
repdoc_track_view();

// ── Database connections ─────────────────────────────────────────
/** Shared PDO options for every connection. */
function repdoc_pdo_options(): array
{
    return [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
}

/** MySQL connection (one per request, shared by central + records). */
function repdoc_mysql_pdo(): ?PDO
{
    static $pdo = null;
    static $failed = false;
    if ($pdo instanceof PDO) return $pdo;
    if ($failed) return null;

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', MYSQL_HOST, MYSQL_PORT, MYSQL_DB);
    try {
        $pdo = new PDO($dsn, MYSQL_USER, MYSQL_PASS, repdoc_pdo_options());
    } catch (Throwable $e) {
        $failed = true;
        return null;
    }
    return $pdo;
}

/**
 * Open a SQLite file, read-write with a read-only fallback (shared-host safe).
 * $create = true lets PDO create an empty database (used for love/repdoc.db).
 */
function repdoc_sqlite_pdo(string $path, bool $create = false): ?PDO
{
    if (!is_file($path)) {
        if (!$create) return null;
        $dir = dirname($path);
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) return null;
        if (!is_writable($dir)) return null;
    }

    $dir = dirname($path);
    if (is_dir($dir) && !is_writable($dir)) @chmod($dir, 0755);
    if (is_file($path) && !is_writable($path)) @chmod($path, 0644);

    try {
        $pdo = new PDO('sqlite:' . $path, null, null, repdoc_pdo_options());
        foreach (['PRAGMA journal_mode=DELETE', 'PRAGMA synchronous=NORMAL', 'PRAGMA busy_timeout=5000'] as $pragma) {
            try { $pdo->exec($pragma); } catch (Throwable $e) {}
        }
        return $pdo;
    } catch (Throwable $e) {
        try {
            return new PDO('sqlite:' . $path . '?mode=ro', null, null, repdoc_pdo_options());
        } catch (Throwable $e2) {
            return null;
        }
    }
}

/**
 * Central database (users, wallet, prices, ledger, usage).
 * Returns null when it is not reachable so pages can degrade instead of 500-ing.
 */
function repdoc_db(): ?PDO
{
    static $pdo = null;
    static $resolved = false;
    if ($resolved) return $pdo;
    $resolved = true;

    $pdo = repdoc_uses_mysql() ? repdoc_mysql_pdo() : repdoc_sqlite_pdo(CENTRAL_DB_PATH, true);
    if ($pdo instanceof PDO) {
        try {
            repdoc_ensure_schema($pdo);
        } catch (Throwable $e) {
            // Leave the connection; callers surface their own errors.
        }
    }
    return $pdo;
}

/** Records database (the indexed birth records). On MySQL it is the same connection. */
function repdoc_records_db(): ?PDO
{
    static $pdo = null;
    static $resolved = false;
    if ($resolved) return $pdo;
    $resolved = true;

    $pdo = repdoc_uses_mysql() ? repdoc_mysql_pdo() : repdoc_sqlite_pdo(RECORDS_DB_PATH);
    return $pdo;
}

/** Create the central tables when missing (idempotent, cheap). */
function repdoc_ensure_schema(PDO $db): void
{
    static $done = false;
    if ($done) return;

    $mysql = ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql');
    $id    = $mysql ? 'INT AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $text  = $mysql ? 'VARCHAR(255)' : 'TEXT';
    $suffix = $mysql ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : '';

    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id $id,
        fullname $text NOT NULL,
        email $text NOT NULL UNIQUE,
        phone $text DEFAULT '',
        password $text NOT NULL,
        role VARCHAR(32) NOT NULL DEFAULT 'user',
        status VARCHAR(32) NOT NULL DEFAULT 'pending',
        balance INT NOT NULL DEFAULT 0,
        note TEXT,
        last_login_at DATETIME NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )$suffix");

    $db->exec("CREATE TABLE IF NOT EXISTS tools (
        tool_key $text PRIMARY KEY,
        name $text NOT NULL,
        price INT NOT NULL DEFAULT 0,
        active INT NOT NULL DEFAULT 1,
        updated_at DATETIME NULL
    )$suffix");

    $db->exec("CREATE TABLE IF NOT EXISTS transactions (
        id $id,
        user_id INT NOT NULL,
        delta INT NOT NULL,
        balance_after INT NOT NULL,
        tool_key $text DEFAULT '',
        reason $text DEFAULT '',
        ref $text DEFAULT '',
        actor_id INT NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )$suffix");

    $db->exec("CREATE TABLE IF NOT EXISTS usage_log (
        id $id,
        user_id INT NOT NULL,
        tool_key $text NOT NULL,
        status VARCHAR(32) NOT NULL,
        detail TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )$suffix");

    $db->exec("CREATE TABLE IF NOT EXISTS entrepreneur_apps (
        id $id,
        fullname $text NOT NULL,
        mobile $text NOT NULL,
        email $text DEFAULT '',
        union_parishad $text NOT NULL,
        upazila $text NOT NULL,
        district $text NOT NULL,
        message TEXT,
        status VARCHAR(32) NOT NULL DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )$suffix");

    $db->exec("CREATE TABLE IF NOT EXISTS document_logs (
        id $id,
        user_id INT NOT NULL,
        tool_key $text NOT NULL,
        ref $text DEFAULT '',
        dob $text DEFAULT '',
        photo $text DEFAULT '',
        signature $text DEFAULT '',
        data TEXT,
        cert_no $text DEFAULT '',
        status VARCHAR(32) NOT NULL DEFAULT 'valid',
        office TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )$suffix");

    // Per-file descriptions for the public file showcase (admin/files.php).
    // Keyed by format folder + filename; edited from the admin panel.
    $db->exec("CREATE TABLE IF NOT EXISTS file_descriptions (
        file_type VARCHAR(32) NOT NULL,
        file_name VARCHAR(150) NOT NULL,
        description TEXT,
        updated_at DATETIME NULL,
        PRIMARY KEY (file_type, file_name)
    )$suffix");

    // UDC office profile: chairman/pouro admin + office identity per operator.
    $db->exec("CREATE TABLE IF NOT EXISTS office_profiles (
        user_id INT PRIMARY KEY,
        office_type VARCHAR(32) NOT NULL DEFAULT 'union',
        office_name $text NOT NULL DEFAULT '',
        chairman_name $text NOT NULL DEFAULT '',
        upazila $text DEFAULT '',
        district $text DEFAULT '',
        union_code VARCHAR(7) NOT NULL DEFAULT '',
        postcode VARCHAR(16) DEFAULT '',
        phone VARCHAR(32) DEFAULT '',
        email VARCHAR(190) DEFAULT '',
        website VARCHAR(190) DEFAULT '',
        logo_path $text DEFAULT '',
        stamp_path $text DEFAULT '',
        updated_at DATETIME NULL
    )$suffix");

    // Atomic certificate-number allocation (YY + union_code + 8-digit sequence).
    $db->exec("CREATE TABLE IF NOT EXISTS number_sequences (
        prefix VARCHAR(16) NOT NULL PRIMARY KEY,
        last_value INT NOT NULL DEFAULT 0
    )$suffix");

    // Bangladesh geo (divisions → districts → upazilas → unions), seeded from
    // genaretor/prottoyon/geo/*.json (nuhil/bangladesh-geocode dataset).
    $db->exec("CREATE TABLE IF NOT EXISTS geo_divisions (
        id INT PRIMARY KEY,
        name_en $text NOT NULL,
        name_bn $text NOT NULL
    )$suffix");
    $db->exec("CREATE TABLE IF NOT EXISTS geo_districts (
        id INT PRIMARY KEY,
        division_id INT NOT NULL,
        name_en $text NOT NULL,
        name_bn $text NOT NULL
    )$suffix");
    $db->exec("CREATE TABLE IF NOT EXISTS geo_upazilas (
        id INT PRIMARY KEY,
        district_id INT NOT NULL,
        name_en $text NOT NULL,
        name_bn $text NOT NULL
    )$suffix");
    $db->exec("CREATE TABLE IF NOT EXISTS geo_unions (
        id INT PRIMARY KEY,
        upazila_id INT NOT NULL,
        name_en $text NOT NULL,
        name_bn $text NOT NULL
    )$suffix");
    $db->exec("CREATE TABLE IF NOT EXISTS geo_municipalities (
        id INT PRIMARY KEY,
        district_id INT NOT NULL,
        name_en $text NOT NULL,
        name_bn $text NOT NULL
    )$suffix");
    $db->exec("CREATE TABLE IF NOT EXISTS geo_city_corporations (
        id INT PRIMARY KEY,
        district_id INT NOT NULL,
        name_en $text NOT NULL,
        name_bn $text NOT NULL
    )$suffix");

    // Lightweight migrations for databases created before these columns existed.
    $migrate = [
        ['document_logs', 'cert_no', 'ALTER TABLE document_logs ADD COLUMN cert_no ' . ($mysql ? 'VARCHAR(255)' : 'TEXT')],
        ['document_logs', 'status', "ALTER TABLE document_logs ADD COLUMN status VARCHAR(32) NOT NULL DEFAULT 'valid'"],
        ['document_logs', 'office', 'ALTER TABLE document_logs ADD COLUMN office TEXT'],
        ['entrepreneur_apps', 'chairman_name_bn', 'ALTER TABLE entrepreneur_apps ADD COLUMN chairman_name_bn ' . ($mysql ? 'VARCHAR(255)' : 'TEXT')],
        ['entrepreneur_apps', 'chairman_name_en', 'ALTER TABLE entrepreneur_apps ADD COLUMN chairman_name_en ' . ($mysql ? 'VARCHAR(255)' : 'TEXT')],
        ['entrepreneur_apps', 'leader_title', 'ALTER TABLE entrepreneur_apps ADD COLUMN leader_title VARCHAR(32)'],
        ['entrepreneur_apps', 'archive_access', 'ALTER TABLE entrepreneur_apps ADD COLUMN archive_access INT NOT NULL DEFAULT 0'],
        ['office_profiles', 'union_code', 'ALTER TABLE office_profiles ADD COLUMN union_code VARCHAR(7) NOT NULL DEFAULT \'\''],
        ['office_profiles', 'postcode', 'ALTER TABLE office_profiles ADD COLUMN postcode ' . ($mysql ? 'VARCHAR(16)' : 'TEXT')],
        ['office_profiles', 'phone', 'ALTER TABLE office_profiles ADD COLUMN phone ' . ($mysql ? 'VARCHAR(32)' : 'TEXT')],
        ['office_profiles', 'email', 'ALTER TABLE office_profiles ADD COLUMN email ' . ($mysql ? 'VARCHAR(190)' : 'TEXT')],
        ['office_profiles', 'website', 'ALTER TABLE office_profiles ADD COLUMN website ' . ($mysql ? 'VARCHAR(190)' : 'TEXT')],
        ['office_profiles', 'logo_path', 'ALTER TABLE office_profiles ADD COLUMN logo_path ' . ($mysql ? 'VARCHAR(255)' : 'TEXT')],
        ['office_profiles', 'stamp_path', 'ALTER TABLE office_profiles ADD COLUMN stamp_path ' . ($mysql ? 'VARCHAR(255)' : 'TEXT')],
    ];
    foreach ($migrate as [$mtable, $mcol, $msql]) {
        try {
            $has = false;
            if ($mysql) {
                $st = $db->query('SHOW COLUMNS FROM ' . $mtable);
                foreach ($st->fetchAll() as $c) {
                    if (($c['Field'] ?? '') === $mcol) { $has = true; break; }
                }
            } else {
                $st = $db->query('PRAGMA table_info(' . $mtable . ')');
                foreach ($st->fetchAll() as $c) {
                    if (($c['name'] ?? '') === $mcol) { $has = true; break; }
                }
            }
            if (!$has) $db->exec($msql);
        } catch (Throwable $e) {}
    }

    // Keep seeded tool display names in English (names are system-controlled;
    // only price/active are editable, so syncing is safe).
    try {
        $sync = $db->prepare('UPDATE tools SET name = ? WHERE tool_key = ?');
        foreach (repdoc_default_tools() as $syncKey => $syncName) {
            $sync->execute([$syncName, $syncKey]);
        }
    } catch (Throwable $e) {}

    // Ad configuration tables: centralize all ad placements and ad code so
    // the admin can manage Adsterra blocks without editing templates by hand.
    $db->exec("CREATE TABLE IF NOT EXISTS ad_settings (
        setting_key VARCHAR(64) PRIMARY KEY,
        setting_value TEXT NOT NULL DEFAULT ''
    )" . ($mysql ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : ''));

    $db->exec("CREATE TABLE IF NOT EXISTS ad_positions (
        id $id,
        slug VARCHAR(100) NOT NULL UNIQUE,
        name VARCHAR(120) NOT NULL,
        description TEXT DEFAULT '',
        is_active INT NOT NULL DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )" . ($mysql ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : ''));

    $db->exec("CREATE TABLE IF NOT EXISTS ads (
        id $id,
        name VARCHAR(120) NOT NULL,
        type VARCHAR(32) NOT NULL DEFAULT 'banner',
        code TEXT NOT NULL DEFAULT '',
        status VARCHAR(32) NOT NULL DEFAULT 'draft',
        start_date DATETIME NULL,
        end_date DATETIME NULL,
        priority INT NOT NULL DEFAULT 10,
        rotation_weight INT NOT NULL DEFAULT 10,
        device_target VARCHAR(32) NOT NULL DEFAULT 'all',
        is_active INT NOT NULL DEFAULT 1,
        created_by INT NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )" . ($mysql ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : ''));

    $db->exec("CREATE TABLE IF NOT EXISTS ad_assignments (
        id $id,
        ad_id INT NOT NULL,
        position_id INT NOT NULL,
        page_slug VARCHAR(120) NOT NULL DEFAULT 'all',
        device_type VARCHAR(32) NOT NULL DEFAULT 'all',
        is_enabled INT NOT NULL DEFAULT 1,
        priority INT NOT NULL DEFAULT 10,
        rotation_weight INT NOT NULL DEFAULT 10,
        start_date DATETIME NULL,
        end_date DATETIME NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ad_id) REFERENCES ads(id) ON DELETE CASCADE,
        FOREIGN KEY (position_id) REFERENCES ad_positions(id) ON DELETE CASCADE
    )" . ($mysql ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : ''));

    $db->exec("CREATE TABLE IF NOT EXISTS ad_logs (
        id $id,
        ad_id INT NOT NULL,
        position_id INT NOT NULL,
        page_slug VARCHAR(120) NOT NULL DEFAULT 'all',
        user_ip VARCHAR(64) DEFAULT '',
        user_agent TEXT DEFAULT '',
        device_type VARCHAR(32) DEFAULT 'all',
        viewed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ad_id) REFERENCES ads(id) ON DELETE CASCADE,
        FOREIGN KEY (position_id) REFERENCES ad_positions(id) ON DELETE CASCADE
    )" . ($mysql ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci' : ''));

    $db->exec("CREATE TABLE IF NOT EXISTS site_views (
        view_day VARCHAR(10) NOT NULL PRIMARY KEY,
        views INTEGER NOT NULL DEFAULT 0,
        updated_at DATETIME NULL
    )" . $suffix);

    // Unauthorized / broken requests (403 + 404), aggregated per path+IP.
    $db->exec("CREATE TABLE IF NOT EXISTS bad_requests (
        id $id,
        sig CHAR(32) NOT NULL,
        status INT NOT NULL DEFAULT 404,
        method VARCHAR(8) DEFAULT '',
        path VARCHAR(180) NOT NULL DEFAULT '',
        query TEXT NULL,
        ip VARCHAR(45) DEFAULT '',
        user_agent TEXT NULL,
        referer TEXT NULL,
        probe INT NOT NULL DEFAULT 0,
        hits INT NOT NULL DEFAULT 1,
        first_seen DATETIME NULL,
        last_seen DATETIME NULL,
        UNIQUE (sig)
    )$suffix");

    $defaultPositions = [
        ['home_top_banner', 'Homepage Top Banner', 'Above the homepage tools section'],
        ['home_middle_banner', 'Homepage Middle Banner', 'Inside the homepage content flow'],
        ['sidebar_right', 'Sidebar Right', 'Right rail or sidebar slot'],
        ['footer_banner', 'Footer Banner', 'Bottom-of-page banner'],
        ['home_rail_tall', 'Homepage Rail — 160×600', 'Tall banner in the homepage sidebar'],
        ['home_rail_medium', 'Homepage Rail — 160×300', 'Medium banner in the homepage sidebar'],
        ['home_mobile_banner', 'Homepage Mobile — 320×50', 'Mobile-only compact banner'],
        ['home_native_banner', 'Homepage Native Banner', 'Native ad widget in the homepage tools area'],
        ['sitewide_social_bar', 'Social Bar', 'Adsterra Social Bar script near the page closing body tag'],
        ['article_top', 'Article Top', 'At the top of article/detail pages'],
        ['article_bottom', 'Article Bottom', 'Below content on article/detail pages'],
    ];
    foreach ($defaultPositions as [$slug, $name, $desc]) {
        try {
            $find = $db->prepare('SELECT id FROM ad_positions WHERE slug = ? LIMIT 1');
            $find->execute([$slug]);
            if ($find->fetchColumn() === false) {
                $st = $db->prepare('INSERT INTO ad_positions (slug, name, description, is_active, created_at) VALUES (?, ?, ?, 1, ?)');
                $st->execute([$slug, $name, $desc, date('Y-m-d H:i:s')]);
            }
        } catch (Throwable $e) {}
    }

    $defaultSettings = [
        ['ads_enabled', '1'],
        ['default_rotation_mode', 'weighted'],
        ['allow_popunder', '0'],
        ['ads_debug_mode', '0'],
    ];
    foreach ($defaultSettings as [$key, $value]) {
        try {
            $find = $db->prepare('SELECT setting_key FROM ad_settings WHERE setting_key = ? LIMIT 1');
            $find->execute([$key]);
            if ($find->fetchColumn() === false) {
                $st = $db->prepare('INSERT INTO ad_settings (setting_key, setting_value) VALUES (?, ?)');
                $st->execute([$key, $value]);
            }
        } catch (Throwable $e) {}
    }

    // Imported from the approved repdoc.rf.gd Adsterra publisher account.
    // Seed only when the stable unit name is absent so admin edits are preserved.
    $adsterraUnits = [
        [
            'Adsterra Social Bar — 32031057', 'socialbar',
            '<script data-cfasync="false" src="https://bicea.org/14/4db418bff0b0ee62948a7c9aac879d1c"></script>',
            'sitewide_social_bar', 'all', 5,
        ],
        [
            'Adsterra Banner 468x60 — 32031058', 'banner',
            "<script> atOptions = { 'key' : 'f4dacf57e59b089ed913ee883e6a6dc6', 'format' : 'iframe', 'height' : 60, 'width' : 468, 'params' : {} }; </script>\n<script src=\"https://bicea.org/22/f4dacf57e59b089ed913ee883e6a6dc6\"></script>",
            'home_middle_banner', 'all', 10,
        ],
        [
            'Adsterra Banner 300x250 — 32031059', 'banner',
            "<script> atOptions = { 'key' : 'dd83ed95a3b1f6cb732bc1aa69e88471', 'format' : 'iframe', 'height' : 250, 'width' : 300, 'params' : {} }; </script>\n<script src=\"https://bicea.org/22/dd83ed95a3b1f6cb732bc1aa69e88471\"></script>",
            'sidebar_right', 'all', 10,
        ],
        [
            'Adsterra Banner 160x600 — 32031060', 'banner',
            "<script> atOptions = { 'key' : 'e23eebf0e4d1570a68920af89b097e5a', 'format' : 'iframe', 'height' : 600, 'width' : 160, 'params' : {} }; </script>\n<script src=\"https://bicea.org/22/e23eebf0e4d1570a68920af89b097e5a\"></script>",
            'home_rail_tall', 'all', 10,
        ],
        [
            'Adsterra Banner 160x300 — 32031061', 'banner',
            "<script> atOptions = { 'key' : 'a798a23274e5cbf0e8109376736b2997', 'format' : 'iframe', 'height' : 300, 'width' : 160, 'params' : {} }; </script>\n<script src=\"https://bicea.org/22/a798a23274e5cbf0e8109376736b2997\"></script>",
            'home_rail_medium', 'all', 10,
        ],
        [
            'Adsterra Banner 320x50 — 32031062', 'banner',
            "<script> atOptions = { 'key' : 'd697c38332ed2cf9cf26dc875d91d6f4', 'format' : 'iframe', 'height' : 50, 'width' : 320, 'params' : {} }; </script>\n<script src=\"https://bicea.org/22/d697c38332ed2cf9cf26dc875d91d6f4\"></script>",
            'home_mobile_banner', 'mobile', 10,
        ],
        [
            'Adsterra Banner 728x90 — 32031063', 'banner',
            "<script> atOptions = { 'key' : 'a9adc511cdc23e554145ad2a112c6870', 'format' : 'iframe', 'height' : 90, 'width' : 728, 'params' : {} }; </script>\n<script src=\"https://bicea.org/22/a9adc511cdc23e554145ad2a112c6870\"></script>",
            'home_top_banner', 'all', 10,
        ],
        [
            'Adsterra Native Banner — 32031064', 'native',
            '<script async="async" data-cfasync="false" src="https://bicea.org/21/c47d89e8101e6aa2a0ea80bc4f2d3f08"></script><div id="container-c47d89e8101e6aa2a0ea80bc4f2d3f08"></div>',
            'home_native_banner', 'all', 10,
        ],
    ];
    foreach ($adsterraUnits as [$name, $type, $code, $positionSlug, $deviceTarget, $priority]) {
        try {
            $find = $db->prepare('SELECT id FROM ads WHERE name = ? LIMIT 1');
            $find->execute([$name]);
            $adId = $find->fetchColumn();
            if ($adId === false || $adId === null) {
                $insertAd = $db->prepare('INSERT INTO ads (name, type, code, status, priority, rotation_weight, device_target, is_active, created_by, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, 1, 0, ?, ?)');
                $now = date('Y-m-d H:i:s');
                $insertAd->execute([$name, $type, $code, 'active', $priority, 10, $deviceTarget, $now, $now]);
                $adId = (int) $db->lastInsertId();
            } else {
                $adId = (int) $adId;
            }

            $findPosition = $db->prepare('SELECT id FROM ad_positions WHERE slug = ? LIMIT 1');
            $findPosition->execute([$positionSlug]);
            $positionId = $findPosition->fetchColumn();
            if ($positionId === false || $positionId === null) continue;
            $positionId = (int) $positionId;

            $findAssignment = $db->prepare('SELECT id FROM ad_assignments WHERE ad_id = ? AND position_id = ? LIMIT 1');
            $findAssignment->execute([$adId, $positionId]);
            if ($findAssignment->fetchColumn() === false) {
                $insertAssignment = $db->prepare('INSERT INTO ad_assignments (ad_id, position_id, page_slug, device_type, is_enabled, priority, rotation_weight, created_at) VALUES (?, ?, ?, ?, 1, ?, 10, ?)');
                $insertAssignment->execute([$adId, $positionId, 'all', 'all', $priority, date('Y-m-d H:i:s')]);
            }
        } catch (Throwable $e) {}
    }

    // Mark complete only after everything above succeeded, so a partial
    // failure is retried on the next request instead of skipped forever.
    $done = true;
}

// ── Site view counter ────────────────────────────────────────────
/**
 * Record one public page view (one row per day, incremented).
 *
 * Skipped for CLI, non-GET requests, admin/panel/auth routes, bots and
 * prefetches, and for signed-in admins so the public counter stays honest.
 * $force is for CLI tests only.
 */
function repdoc_track_view(bool $force = false): bool
{
    static $done = false;
    if ($done && !$force) return false;
    $done = true;

    if (!$force) {
        if (PHP_SAPI === 'cli') return false;
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'GET') return false;
        // Apache ErrorDocument hits (403/404) are not page views.
        if (isset($_SERVER['REDIRECT_STATUS'])) return false;

        $path = '/' . ltrim((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH), '/');
        foreach (['/admin', '/Entrepreneur/panel', '/public', '/api', '/src', '/love', '/vendor'] as $prefix) {
            if (str_starts_with($path, $prefix)) return false;
        }

        $ua = strtolower((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
        if ($ua === '' || preg_match('~(bot|crawl|spider|slurp|bingpreview|facebookexternalhit|headless|curl|wget|python-requests|uptime|monitor)~', $ua)) {
            return false;
        }
        if (isset($_SERVER['HTTP_SEC_PURPOSE']) || isset($_SERVER['HTTP_X_PURPOSE']) || isset($_SERVER['HTTP_X_MOZ_PREFETCH'])) {
            return false;
        }

        $user = current_user();
        if ($user !== null && ($user['role'] ?? '') === 'admin') return false;
    }

    $db = repdoc_db();
    if (!$db) return false;

    $today = date('Y-m-d');
    $now   = date('Y-m-d H:i:s');
    try {
        if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $st = $db->prepare('INSERT INTO site_views (view_day, views, updated_at) VALUES (?, 1, ?) ON DUPLICATE KEY UPDATE views = views + 1, updated_at = VALUES(updated_at)');
        } else {
            $st = $db->prepare('INSERT INTO site_views (view_day, views, updated_at) VALUES (?, 1, ?) ON CONFLICT(view_day) DO UPDATE SET views = views + 1, updated_at = excluded.updated_at');
        }
        $st->execute([$today, $now]);
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

/** Totals for the homepage view counter. */
function repdoc_view_stats(): array
{
    $stats = ['total' => 0, 'today' => 0, 'since' => ''];
    $db = repdoc_db();
    if (!$db) return $stats;

    try {
        $stats['total'] = (int) $db->query('SELECT COALESCE(SUM(views), 0) FROM site_views')->fetchColumn();
        $st = $db->prepare('SELECT COALESCE(views, 0) FROM site_views WHERE view_day = ?');
        $st->execute([date('Y-m-d')]);
        $stats['today'] = (int) $st->fetchColumn();
        $stats['since'] = (string) ($db->query('SELECT MIN(view_day) FROM site_views')->fetchColumn() ?: '');
    } catch (Throwable $e) {}

    return $stats;
}

/**
 * Path fragments that usually mean someone is probing the server rather than
 * following a broken link. Used to tag rows in the admin Bad Requests screen.
 */
function repdoc_probe_patterns(): array
{
    return [
        'wp-admin', 'wp-login', 'wp-content', 'wp-includes', 'xmlrpc', 'wlwmanifest',
        'phpmyadmin', 'pma', 'adminer', 'myadmin', 'dbadmin', 'sqladmin',
        '.env', '.git', '.svn', '.htaccess', '.htpasswd', '.ds_store', '.aws', '.ssh',
        'config', 'backup', 'database', 'dump', '.sql', '.bak', '.old', '.zip', '.tar',
        'admin/login', 'admin-login', 'administrator', 'webadmin', 'cpanel', 'plesk', 'webmail',
        'vendor/', 'composer.json', 'composer.lock', 'package.json', 'dockerfile', 'docker-compose',
        'cgi-bin', 'shell', 'passwd', 'server-status', 'server-info', 'actuator',
        'swagger', 'phpinfo', 'graphql', '/api/v1', 'test.php', 'debug', 'autodiscover',
    ];
}

/** True when the path looks like automated probing instead of a broken link. */
function repdoc_is_probe(string $path, string $query = ''): bool
{
    $hay = strtolower($path . '?' . $query);
    foreach (repdoc_probe_patterns() as $needle) {
        if (str_contains($hay, $needle)) return true;
    }
    return false;
}

/**
 * Record a 403/404 hit into bad_requests (aggregated by status + path + IP).
 *
 * Served by 404.php, which .htaccess wires to ErrorDocument 403 and 404.
 * $force is for CLI tests only.
 */
function repdoc_log_bad_request(?int $status = null, ?string $uri = null, bool $force = false): bool
{
    if (PHP_SAPI === 'cli' && !$force) return false;

    $status = $status ?? (int) ($_SERVER['REDIRECT_STATUS'] ?? 404);
    if (!in_array($status, [403, 404], true)) $status = 404;

    $uri = $uri ?? (string) ($_SERVER['REDIRECT_URL'] ?? $_SERVER['REQUEST_URI'] ?? '/');
    $parts = parse_url($uri) ?: [];
    $path = '/' . ltrim((string) ($parts['path'] ?? '/'), '/');
    if ($path === '/' || $path === '') return false;

    $query = (string) ($parts['query'] ?? '');
    if ($query === '') $query = (string) ($_SERVER['QUERY_STRING'] ?? '');

    $path   = mb_substr($path, 0, 180, 'UTF-8');
    $query  = mb_substr($query, 0, 300, 'UTF-8');
    $ip     = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $ua     = mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 400, 'UTF-8');
    $ref    = mb_substr((string) ($_SERVER['HTTP_REFERER'] ?? ''), 0, 300, 'UTF-8');
    $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if ($method === '' || strlen($method) > 8) $method = 'GET';
    $probe  = repdoc_is_probe($path, $query) ? 1 : 0;

    $db = repdoc_db();
    if (!$db) return false;

    $now = date('Y-m-d H:i:s');
    $sig = md5($status . '|' . $path . '|' . $ip);

    try {
        $sql = 'INSERT INTO bad_requests (sig, status, method, path, query, ip, user_agent, referer, probe, hits, first_seen, last_seen)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?)';
        if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $sql .= ' ON DUPLICATE KEY UPDATE hits = hits + 1, last_seen = VALUES(last_seen), query = VALUES(query), user_agent = VALUES(user_agent), referer = VALUES(referer), probe = VALUES(probe)';
        } else {
            $sql .= ' ON CONFLICT(sig) DO UPDATE SET hits = hits + 1, last_seen = excluded.last_seen, query = excluded.query, user_agent = excluded.user_agent, referer = excluded.referer, probe = excluded.probe';
        }
        $st = $db->prepare($sql);
        $st->execute([$sig, $status, $method, $path, $query, $ip, $ua, $ref, $probe, $now, $now]);
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

/** Ad configuration helpers used by the public pages and admin panel. */
function repdoc_ad_setting(string $key, string $default = ''): string
{
    $db = repdoc_db();
    if (!$db) return $default;
    try {
        $st = $db->prepare('SELECT setting_value FROM ad_settings WHERE setting_key = ?');
        $st->execute([$key]);
        $value = $st->fetchColumn();
        if ($value !== false) return (string) $value;
    } catch (Throwable $e) {}
    return $default;
}

function repdoc_ads_enabled(): bool
{
    return strtolower(trim(repdoc_ad_setting('ads_enabled', '1'))) === '1' || strtolower(trim(repdoc_ad_setting('ads_enabled', '1'))) === 'true';
}

function repdoc_detect_device(): string
{
    $ua = strtolower((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
    if (preg_match('/(android|iphone|ipad|mobile|phone)/i', $ua)) return 'mobile';
    return 'desktop';
}

function repdoc_render_ad_slot(string $positionSlug, string $pageSlug = 'all', ?string $deviceType = null): string
{
    if (!repdoc_ads_enabled()) return '';

    $db = repdoc_db();
    if (!$db) return '';

    $positionSlug = trim($positionSlug);
    if ($positionSlug === '') return '';

    $pageSlug = trim($pageSlug) === '' ? 'all' : trim($pageSlug);
    $deviceType = strtolower(trim((string) ($deviceType ?? repdoc_detect_device())));
    if ($deviceType === '') $deviceType = 'all';
    $now = date('Y-m-d H:i:s');

    try {
        $sql = 'SELECT a.*, p.id AS position_id
                FROM ad_positions p
                INNER JOIN ad_assignments aa ON aa.position_id = p.id
                INNER JOIN ads a ON a.id = aa.ad_id
                WHERE p.slug = :position
                  AND aa.is_enabled = 1
                  AND a.is_active = 1
                  AND a.status = :status
                  AND (aa.page_slug = :page OR aa.page_slug = :all_page)
                  AND (a.device_target = :device OR a.device_target = :all_device OR a.device_target = :empty)
                  AND (a.start_date IS NULL OR a.start_date <= :start_now)
                  AND (a.end_date IS NULL OR a.end_date >= :end_now)
                ORDER BY a.priority DESC, a.rotation_weight DESC, a.id DESC
                LIMIT 1';
        $st = $db->prepare($sql);
        $st->execute([
            ':position' => $positionSlug,
            ':status' => 'active',
            ':page' => $pageSlug,
            ':all_page' => 'all',
            ':device' => $deviceType,
            ':all_device' => 'all',
            ':empty' => '',
            ':start_now' => $now,
            ':end_now' => $now,
        ]);
        $ad = $st->fetch();
        if (!$ad) return '';

        $html = trim((string) ($ad['code'] ?? ''));
        if ($html === '') return '';

        try {
            $logSt = $db->prepare('INSERT INTO ad_logs (ad_id, position_id, page_slug, user_ip, user_agent, device_type, viewed_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $logSt->execute([
                (int) $ad['id'],
                (int) ($ad['position_id'] ?? 0),
                $pageSlug,
                (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
                (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''),
                $deviceType,
                $now,
            ]);
        } catch (Throwable $e) {}

        return '<div class="repdoc-ad-slot repdoc-ad-slot-' . e($positionSlug) . '" data-ad-id="' . e((string) $ad['id']) . '">' . $html . '</div>';
    } catch (Throwable $e) {
        return '';
    }
}

// ── Authentication ───────────────────────────────────────────────
/** Session user, or null. Reads the session only — never the database. */
function current_user(): ?array
{
    if (empty($_SESSION['repdoc_user_id'])) return null;
    return [
        'id'       => (int) $_SESSION['repdoc_user_id'],
        'email'    => (string) ($_SESSION['repdoc_email'] ?? ''),
        'fullname' => (string) ($_SESSION['repdoc_fullname'] ?? ''),
        'role'     => (string) ($_SESSION['repdoc_role'] ?? 'user'),
    ];
}

function is_logged_in(): bool { return current_user() !== null; }

function is_admin(): bool { return (current_user()['role'] ?? '') === 'admin'; }

/** Put a user row into the session. */
function login_user(array $user): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        @session_regenerate_id(true);
    }
    $_SESSION['repdoc_user_id']  = (int) $user['id'];
    $_SESSION['repdoc_email']    = (string) $user['email'];
    $_SESSION['repdoc_fullname'] = (string) $user['fullname'];
    $_SESSION['repdoc_role']     = (string) ($user['role'] ?? 'user');

    $db = repdoc_db();
    if ($db) {
        try {
            $st = $db->prepare('UPDATE users SET last_login_at = ? WHERE id = ?');
            $st->execute([date('Y-m-d H:i:s'), (int) $user['id']]);
        } catch (Throwable $e) {}
    }
}

function logout_user(): void
{
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], (bool) $p['secure'], (bool) $p['httponly']);
        }
        @session_destroy();
    }
}

/** Current request path (with query) so login can return the user to it. */
function repdoc_current_path(): string
{
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    // Strip the project base so `next` stays project-relative.
    $base = repdoc_base_url();
    if ($base !== '' && str_starts_with($uri, $base)) {
        $uri = substr($uri, strlen($base));
    }
    return $uri === '' ? '/' : $uri;
}

/**
 * Require a logged-in, approved user. Redirects to the universal login and
 * remembers where the user was heading.
 */
function require_login(): void
{
    $user = current_user();
    if ($user === null) {
        redirect(repdoc_url('public/login/') . '?next=' . rawurlencode(repdoc_current_path()));
    }

    // Re-check approval on each request so a revoked account loses access.
    $db = repdoc_db();
    if ($db) {
        try {
            $st = $db->prepare('SELECT status, role FROM users WHERE id = ?');
            $st->execute([$user['id']]);
            $row = $st->fetch();
            if (!$row || $row['status'] !== 'approved') {
                logout_user();
                redirect(repdoc_url('public/login/') . '?msg=pending');
            }
        } catch (Throwable $e) {
            // Database hiccup: keep the session rather than logging everyone out.
        }
    }
}

/** Require an administrator. */
function require_admin(): void
{
    if (!is_logged_in()) {
        redirect(repdoc_url('public/login/') . '?next=' . rawurlencode(repdoc_current_path()));
    }
    if (!is_admin()) {
        http_response_code(403);
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><meta charset="utf-8"><title>403</title>'
           . '<p style="font:16px/1.6 system-ui,sans-serif;padding:40px;text-align:center">'
           . 'Access denied (403) — this page is for admins only.<br><br>'
           . '<a href="' . e(repdoc_url('/')) . '">Back to homepage</a></p>';
        exit;
    }
}

// Backwards-compatible aliases: existing tool code calls these names.
function isLoggedIn(): bool { return is_logged_in(); }
function requireLogin(): void { require_login(); }
function requireAdmin(): void { require_admin(); }

// ── CSRF + flash ─────────────────────────────────────────────────
function csrf_token(): string
{
    if (empty($_SESSION['repdoc_csrf'])) {
        $_SESSION['repdoc_csrf'] = bin2hex(random_bytes(32));
    }
    return (string) $_SESSION['repdoc_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(?string $token): bool
{
    return is_string($token)
        && !empty($_SESSION['repdoc_csrf'])
        && hash_equals((string) $_SESSION['repdoc_csrf'], $token);
}

function flash_set(string $type, string $message): void
{
    $_SESSION['repdoc_flash'] = ['type' => $type, 'message' => $message];
}

function flash_get(): ?array
{
    if (empty($_SESSION['repdoc_flash'])) return null;
    $flash = $_SESSION['repdoc_flash'];
    unset($_SESSION['repdoc_flash']);
    return is_array($flash) ? $flash : null;
}

// ── Credit wallet ────────────────────────────────────────────────
/** Tools that can be priced. Seeded into the `tools` table by src/setup.php. */
function repdoc_default_tools(): array
{
    return [
        'birth_generator' => 'Birth certificate generator',
        'death_generator' => 'Death certificate generator',
        'birth_search'    => 'Birth record search',
        'birth_export'    => 'Record export / download',
        'birth_detail'    => 'Record detail',
        'certificate'     => 'Education certificates',
        'cvmaker'         => 'CV maker',
        'nid'             => 'National ID card (NID)',
        'smartnid'        => 'Smart NID card',
        'passport_photo'  => 'Passport size photo',
        'prottoyon_agriculture' => 'Prottoyon: Agriculture',
        'prottoyon_annual_income' => 'Prottoyon: Annual income',
        'prottoyon_birth' => 'Prottoyon: Birth',
        'prottoyon_character' => 'Prottoyon: Character',
        'prottoyon_childless' => 'Prottoyon: Childless',
        'prottoyon_citizenship' => 'Prottoyon: Citizenship',
        'prottoyon_community' => 'Prottoyon: Community',
        'prottoyon_death' => 'Prottoyon: Death',
        'prottoyon_disabled' => 'Prottoyon: Disability',
        'prottoyon_family' => 'Prottoyon: Family',
        'prottoyon_financial_insolvency' => 'Prottoyon: Financial insolvency',
        'prottoyon_freedom_fighter' => 'Prottoyon: Freedom fighter',
        'prottoyon_guardian_consent' => 'Prottoyon: Guardian consent',
        'prottoyon_heir' => 'Prottoyon: Heir',
        'prottoyon_holding_tax' => 'Prottoyon: Holding tax',
        'prottoyon_landless' => 'Prottoyon: Landless',
        'prottoyon_living_person' => 'Prottoyon: Living person',
        'prottoyon_married' => 'Prottoyon: Married',
        'prottoyon_miscellaneous' => 'Prottoyon: Miscellaneous',
        'prottoyon_monthly_income' => 'Prottoyon: Monthly income',
        'prottoyon_nationality' => 'Prottoyon: Nationality',
        'prottoyon_new_voter' => 'Prottoyon: New voter',
        'prottoyon_nid_correction' => 'Prottoyon: NID correction',
        'prottoyon_no_objection' => 'Prottoyon: No objection',
        'prottoyon_orphan' => 'Prottoyon: Orphan',
        'prottoyon_passport' => 'Prottoyon: Passport NOC',
        'prottoyon_permanent_resident' => 'Prottoyon: Permanent resident',
        'prottoyon_power_of_attorney' => 'Prottoyon: Power of attorney',
        'prottoyon_premises_license' => 'Prottoyon: Premises license',
        'prottoyon_professional' => 'Prottoyon: Professional',
        'prottoyon_remarriage' => 'Prottoyon: Remarriage',
        'prottoyon_rohingya' => 'Prottoyon: Rohingya',
        'prottoyon_same_person' => 'Prottoyon: Same person',
        'prottoyon_succession' => 'Prottoyon: Succession',
        'prottoyon_trade_license' => 'Prottoyon: Trade license',
        'prottoyon_tribal' => 'Prottoyon: Tribal',
        'prottoyon_unemployment' => 'Prottoyon: Unemployment',
        'prottoyon_unmarried' => 'Prottoyon: Unmarried',
        'prottoyon_voter_info' => 'Prottoyon: Voter info',
        'prottoyon_voter_transfer' => 'Prottoyon: Voter transfer',
        'prottoyon_widow' => 'Prottoyon: Widow',
        'prottoyon_nagorik' => 'Prottoyon: Citizenship',
        'prottoyon_new-voter' => 'Prottoyon: New voter',
        'prottoyon_voter' => 'Prottoyon: Voter information',
        'prottoyon_vumihin' => 'Prottoyon: Landless',
        'prottoyon_onapotti' => 'Prottoyon: No objection',
        'prottoyon_rastakhonon' => 'Prottoyon: Road excavation',
        'prottoyon_nodibanga' => 'Prottoyon: River erosion',
        'prottoyon_trade' => 'Prottoyon: Trade licence',
        'prottoyon_yearlyincome' => 'Prottoyon: Annual income',
        'prottoyon_onumoti' => 'Prottoyon: Permission',
        'prottoyon_warish' => 'Prottoyon: Heir',
        'prottoyon_obibahito' => 'Prottoyon: Unmarried',
        'prottoyon_bibahito' => 'Prottoyon: Married',
        'prottoyon_ekoinam' => 'Prottoyon: Same name',
        'prottoyon_punobibaho' => 'Prottoyon: Remarriage',
        'prottoyon_protibondi' => 'Prottoyon: Disability',
        'prottoyon_prottyon' => 'Prottoyon: Attestation',
        'prottoyon_sonaton' => 'Prottoyon: Sanatan',
        'prottoyon_anumanik-boyosh-protyon' => 'Prottoyon: Estimated age',
        'prottoyon_protyonpotro-onnanno' => 'Prottoyon: Other certificate',
    ];
}

/** All tools with their price/status, keyed by tool_key. */
function tool_prices(): array
{
    $out = [];
    foreach (repdoc_default_tools() as $key => $name) {
        $out[$key] = ['tool_key' => $key, 'name' => $name, 'price' => 0, 'active' => 1];
    }

    $db = repdoc_db();
    if ($db) {
        try {
            foreach ($db->query('SELECT * FROM tools') as $row) {
                $out[$row['tool_key']] = [
                    'tool_key' => (string) $row['tool_key'],
                    'name'     => (string) $row['name'],
                    'price'    => (int) $row['price'],
                    'active'   => (int) $row['active'],
                ];
            }
        } catch (Throwable $e) {}
    }
    return $out;
}

function tool_price(string $key): int
{
    $tools = tool_prices();
    return (int) ($tools[$key]['price'] ?? 0);
}

function set_tool_price(string $key, int $price, bool $active = true): bool
{
    $db = repdoc_db();
    if (!$db) return false;

    $tools = tool_prices();
    $name  = $tools[$key]['name'] ?? repdoc_default_tools()[$key] ?? $key;

    $price = max(0, $price);
    try {
        $st = $db->prepare('SELECT COUNT(*) FROM tools WHERE tool_key = ?');
        $st->execute([$key]);
        if ((int) $st->fetchColumn() > 0) {
            $db->prepare('UPDATE tools SET price = ?, active = ?, updated_at = ? WHERE tool_key = ?')
               ->execute([$price, $active ? 1 : 0, date('Y-m-d H:i:s'), $key]);
        } else {
            $db->prepare('INSERT INTO tools (tool_key, name, price, active, updated_at) VALUES (?, ?, ?, ?, ?)')
               ->execute([$key, $name, $price, $active ? 1 : 0, date('Y-m-d H:i:s')]);
        }
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function user_balance(int $userId): int
{
    $db = repdoc_db();
    if (!$db) return 0;
    try {
        $st = $db->prepare('SELECT balance FROM users WHERE id = ?');
        $st->execute([$userId]);
        $v = $st->fetchColumn();
        return $v === false ? 0 : (int) $v;
    } catch (Throwable $e) {
        return 0;
    }
}

function log_usage(int $userId, string $toolKey, string $status, string $detail = ''): void
{
    $db = repdoc_db();
    if (!$db) return;
    try {
        $db->prepare('INSERT INTO usage_log (user_id, tool_key, status, detail, created_at) VALUES (?, ?, ?, ?, ?)')
           ->execute([$userId, $toolKey, $status, $detail, date('Y-m-d H:i:s')]);
    } catch (Throwable $e) {}
}

/**
 * Log one generated document with ALL submitted info.
 * Quick columns (ref/dob/photo/signature) power the admin list;
 * $data keeps every form field as JSON so nothing is lost.
 */
function log_document(string $toolKey, string $ref = '', string $dob = '', string $photo = '', string $signature = '', array $data = [], string $certNo = '', array $office = []): int
{
    $user = current_user();
    if ($user === null) return 0;
    $db = repdoc_db();
    if (!$db) return 0;
    try {
        $json = $data ? json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
        $officeJson = $office ? json_encode($office, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
        $db->prepare('INSERT INTO document_logs (user_id, tool_key, ref, dob, photo, signature, data, cert_no, status, office, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
           ->execute([$user['id'], $toolKey, $ref, $dob, $photo, $signature, $json, $certNo, 'valid', $officeJson, date('Y-m-d H:i:s')]);
        return (int) $db->lastInsertId();
    } catch (Throwable $e) {}
    return 0;
}

/** True when the current user is an approved entrepreneur (UDC office operator). */
function is_udc(): bool
{
    $user = current_user();
    if ($user === null || ($user['email'] ?? '') === '') return false;
    $db = repdoc_db();
    if (!$db) return false;
    try {
        $st = $db->prepare("SELECT COUNT(*) FROM entrepreneur_apps WHERE email = ? AND status = 'approved'");
        $st->execute([$user['email']]);
        return (int) $st->fetchColumn() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * True when the current user may open the UDC document archive.
 * Admins always can; UDC users need a per-application grant from the admin
 * panel (entrepreneur_apps.archive_access).
 */
function has_archive_access(): bool
{
    if (is_admin()) return true;

    $user = current_user();
    if ($user === null || ($user['email'] ?? '') === '') return false;

    $db = repdoc_db();
    if (!$db) return false;

    try {
        $st = $db->prepare("SELECT COUNT(*) FROM entrepreneur_apps WHERE email = ? AND status = 'approved' AND archive_access = 1");
        $st->execute([$user['email']]);
        return (int) $st->fetchColumn() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

/** This operator's office profile, or defaults when not set yet. */
function office_profile(?int $userId = null): array
{
    $out = [
        'office_type' => 'union', 'office_name' => '', 'chairman_name' => '', 'upazila' => '', 'district' => '',
        'union_code' => '', 'postcode' => '', 'phone' => '', 'email' => '', 'website' => '',
        'logo_path' => '', 'stamp_path' => '',
    ];
    if ($userId === null) {
        $user = current_user();
        if ($user === null) return $out;
        $userId = $user['id'];
    }
    $db = repdoc_db();
    if (!$db) return $out;
    try {
        $st = $db->prepare('SELECT office_type, office_name, chairman_name, upazila, district, union_code, postcode, phone, email, website, logo_path, stamp_path FROM office_profiles WHERE user_id = ?');
        $st->execute([$userId]);
        $row = $st->fetch();
        if ($row) {
            foreach (array_keys($out) as $k) {
                if (isset($row[$k]) && $row[$k] !== '') $out[$k] = (string) $row[$k];
            }
        }
    } catch (Throwable $e) {}
    return $out;
}

/** Save this operator's office profile (insert or update). */
function save_office_profile(int $userId, array $profile): bool
{
    $db = repdoc_db();
    if (!$db) return false;
    $type = in_array($profile['office_type'] ?? '', ['union', 'municipality', 'city_corporation'], true)
        ? (string) $profile['office_type'] : 'union';
    $vals = [
        $userId, $type,
        (string) ($profile['office_name'] ?? ''), (string) ($profile['chairman_name'] ?? ''),
        (string) ($profile['upazila'] ?? ''), (string) ($profile['district'] ?? ''),
        substr(preg_replace('/\D/', '', (string) ($profile['union_code'] ?? '')) ?? '', 0, 7),
        (string) ($profile['postcode'] ?? ''), (string) ($profile['phone'] ?? ''),
        (string) ($profile['email'] ?? ''), (string) ($profile['website'] ?? ''),
        (string) ($profile['logo_path'] ?? ''), (string) ($profile['stamp_path'] ?? ''),
        date('Y-m-d H:i:s'),
    ];
    try {
        $mysql = ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql');
        if ($mysql) {
            $db->prepare('INSERT INTO office_profiles (user_id, office_type, office_name, chairman_name, upazila, district, union_code, postcode, phone, email, website, logo_path, stamp_path, updated_at)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                          ON DUPLICATE KEY UPDATE office_type = VALUES(office_type), office_name = VALUES(office_name),
                          chairman_name = VALUES(chairman_name), upazila = VALUES(upazila), district = VALUES(district),
                          union_code = VALUES(union_code), postcode = VALUES(postcode), phone = VALUES(phone),
                          email = VALUES(email), website = VALUES(website), logo_path = VALUES(logo_path),
                          stamp_path = VALUES(stamp_path), updated_at = VALUES(updated_at)')->execute($vals);
        } else {
            $db->prepare('INSERT INTO office_profiles (user_id, office_type, office_name, chairman_name, upazila, district, union_code, postcode, phone, email, website, logo_path, stamp_path, updated_at)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                          ON CONFLICT(user_id) DO UPDATE SET office_type = excluded.office_type, office_name = excluded.office_name,
                          chairman_name = excluded.chairman_name, upazila = excluded.upazila, district = excluded.district,
                          union_code = excluded.union_code, postcode = excluded.postcode, phone = excluded.phone,
                          email = excluded.email, website = excluded.website, logo_path = excluded.logo_path,
                          stamp_path = excluded.stamp_path, updated_at = excluded.updated_at')->execute($vals);
        }
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

/** Seed Bangladesh geo tables once from the bundled JSON dataset. */
function repdoc_seed_geo(): bool
{
    static $done = false;
    if ($done) return true;
    $done = true;

    $db = repdoc_db();
    if (!$db) return false;

    $dir = REPDOC_ROOT . DIRECTORY_SEPARATOR . 'genaretor' . DIRECTORY_SEPARATOR . 'prottoyon' . DIRECTORY_SEPARATOR . 'geo' . DIRECTORY_SEPARATOR;
    $load = static function (string $file) use ($dir): array {
        $raw = @file_get_contents($dir . $file);
        if ($raw === false) return [];
        $json = json_decode($raw, true);
        if (!is_array($json)) return [];
        foreach ($json as $entry) {
            if (is_array($entry) && isset($entry['data']) && is_array($entry['data'])) return $entry['data'];
        }
        // Flat array format (municipalities.json).
        if (isset($json[0]) && is_array($json[0]) && isset($json[0]['id'])) return $json;
        return [];
    };
    $countOf = static function (string $table) use ($db): int {
        try {
            return (int) $db->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn();
        } catch (Throwable $e) {
            return -1;
        }
    };

    try {
        $db->beginTransaction();
        if ($countOf('geo_divisions') === 0) {
        $st = $db->prepare('INSERT INTO geo_divisions (id, name_en, name_bn) VALUES (?, ?, ?)');
        foreach ($load('divisions.json') as $r) {
            $st->execute([(int) ($r['id'] ?? 0), (string) ($r['name'] ?? ''), (string) ($r['bn_name'] ?? '')]);
        }
        }
        if ($countOf('geo_districts') === 0) {
        $st = $db->prepare('INSERT INTO geo_districts (id, division_id, name_en, name_bn) VALUES (?, ?, ?, ?)');
        foreach ($load('districts.json') as $r) {
            $st->execute([(int) ($r['id'] ?? 0), (int) ($r['division_id'] ?? 0), (string) ($r['name'] ?? ''), (string) ($r['bn_name'] ?? '')]);
        }
        }
        if ($countOf('geo_upazilas') === 0) {
        $st = $db->prepare('INSERT INTO geo_upazilas (id, district_id, name_en, name_bn) VALUES (?, ?, ?, ?)');
        foreach ($load('upazilas.json') as $r) {
            $st->execute([(int) ($r['id'] ?? 0), (int) ($r['district_id'] ?? 0), (string) ($r['name'] ?? ''), (string) ($r['bn_name'] ?? '')]);
        }
        }
        if ($countOf('geo_unions') === 0) {
        $st = $db->prepare('INSERT INTO geo_unions (id, upazila_id, name_en, name_bn) VALUES (?, ?, ?, ?)');
        foreach ($load('unions.json') as $r) {
            $st->execute([(int) ($r['id'] ?? 0), (int) ($r['upazilla_id'] ?? 0), (string) ($r['name'] ?? ''), (string) ($r['bn_name'] ?? '')]);
        }
        }
        if ($countOf('geo_municipalities') === 0) {
        $st = $db->prepare('INSERT INTO geo_municipalities (id, district_id, name_en, name_bn) VALUES (?, ?, ?, ?)');
        foreach ($load('municipalities.json') as $r) {
            $st->execute([(int) ($r['id'] ?? 0), (int) ($r['district_id'] ?? 0), (string) ($r['name'] ?? ''), (string) ($r['bn_name'] ?? '')]);
        }
        }
        if ($countOf('geo_city_corporations') === 0) {
        $st = $db->prepare('INSERT INTO geo_city_corporations (id, district_id, name_en, name_bn) VALUES (?, ?, ?, ?)');
        foreach ($load('city_corporations.json') as $r) {
            $st->execute([(int) ($r['id'] ?? 0), (int) ($r['district_id'] ?? 0), (string) ($r['name'] ?? ''), (string) ($r['bn_name'] ?? '')]);
        }
        }
        $db->commit();
        return true;
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        return false;
    }
}

/** Write a ledger row. Must be called inside the surrounding transaction. */
function repdoc_ledger_insert(PDO $db, int $userId, int $delta, int $balanceAfter, string $toolKey, string $reason, string $ref, int $actorId): void
{
    $db->prepare('INSERT INTO transactions (user_id, delta, balance_after, tool_key, reason, ref, actor_id, created_at)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
       ->execute([$userId, $delta, $balanceAfter, $toolKey, $reason, $ref, $actorId, date('Y-m-d H:i:s')]);
}

/**
 * Charge a user for one use of a tool. Atomic: the balance can never go negative.
 *
 * @return array{ok:bool,charged:int,balance:int,reason:string,message:string}
 */
function charge_tool(string $toolKey, string $ref = ''): array
{
    $user = current_user();
    if ($user === null) {
        return ['ok' => false, 'charged' => 0, 'balance' => 0, 'reason' => 'auth', 'message' => 'লগইন প্রয়োজন।'];
    }

    $userId  = $user['id'];
    $balance = user_balance($userId);

    // A tool switched Off in the admin panel (admin/tool-*.php, the Active
    // switch) refuses to run — admins included, because that switch is the
    // maintenance/close switch for the tool.
    $toolRow = tool_prices()[$toolKey] ?? null;
    if ($toolRow !== null && (int) $toolRow['active'] !== 1) {
        log_usage($userId, $toolKey, 'denied_inactive', 'tool-off');
        return [
            'ok'      => false,
            'charged' => 0,
            'balance' => $balance,
            'reason'  => 'inactive',
            'message' => 'এই টুলটি বর্তমানে বন্ধ আছে। Admin → Tools থেকে আবার চালু করা যাবে।',
        ];
    }

    // Admins are never charged.
    if (is_admin()) {
        log_usage($userId, $toolKey, 'ok', 'admin-free');
        return ['ok' => true, 'charged' => 0, 'balance' => $balance, 'reason' => 'admin', 'message' => ''];
    }

    $price = tool_price($toolKey);

    // A price of 0 means the tool is free — no ledger noise.
    if ($price <= 0) {
        log_usage($userId, $toolKey, 'ok', 'free');
        return ['ok' => true, 'charged' => 0, 'balance' => $balance, 'reason' => 'free', 'message' => ''];
    }

    $db = repdoc_db();
    if (!$db) {
        log_usage($userId, $toolKey, 'error', 'db-unavailable');
        return ['ok' => false, 'charged' => 0, 'balance' => 0, 'reason' => 'db', 'message' => 'ডেটাবেস unavailable।'];
    }

    // Idempotency: a repeated ref (e.g. a retried generation) is not charged twice.
    if ($ref !== '') {
        try {
            $st = $db->prepare('SELECT COUNT(*) FROM transactions WHERE user_id = ? AND ref = ?');
            $st->execute([$userId, $ref]);
            if ((int) $st->fetchColumn() > 0) {
                log_usage($userId, $toolKey, 'ok', 'duplicate-ref:' . $ref);
                return ['ok' => true, 'charged' => 0, 'balance' => $balance, 'reason' => 'duplicate', 'message' => ''];
            }
        } catch (Throwable $e) {}
    }

    try {
        $db->beginTransaction();

        // Conditional UPDATE = the "insufficient funds" check and the debit in one step.
        $st = $db->prepare('UPDATE users SET balance = balance - ? WHERE id = ? AND balance >= ?');
        $st->execute([$price, $userId, $price]);

        if ($st->rowCount() === 0) {
            $db->rollBack();
            log_usage($userId, $toolKey, 'denied_insufficient', 'need:' . $price . ' have:' . $balance);
            return [
                'ok'      => false,
                'charged' => 0,
                'balance' => $balance,
                'reason'  => 'insufficient',
                'message' => 'ব্যালেন্স পর্যাপ্ত নয় — প্রয়োজন ' . $price . ', আছে ' . $balance . '।',
            ];
        }

        $st2 = $db->prepare('SELECT balance FROM users WHERE id = ?');
        $st2->execute([$userId]);
        $after = (int) $st2->fetchColumn();

        repdoc_ledger_insert($db, $userId, -$price, $after, $toolKey, 'tool-use', $ref, 0);
        $db->commit();

        log_usage($userId, $toolKey, 'ok', 'charged:' . $price);
        return ['ok' => true, 'charged' => $price, 'balance' => $after, 'reason' => 'charged', 'message' => ''];
    } catch (Throwable $ex) {
        if ($db->inTransaction()) $db->rollBack();
        log_usage($userId, $toolKey, 'error', 'charge-failed');
        return ['ok' => false, 'charged' => 0, 'balance' => $balance, 'reason' => 'error', 'message' => 'চার্জ করা যায়নি।'];
    }
}

/**
 * Admin credit/debit adjustment.
 *
 * @return array{ok:bool,balance:int,message:string}
 */
function adjust_balance(int $userId, int $delta, string $reason, int $actorId = 0): array
{
    $db = repdoc_db();
    if (!$db) return ['ok' => false, 'balance' => 0, 'message' => 'Database unavailable.'];
    if ($delta === 0) return ['ok' => false, 'balance' => user_balance($userId), 'message' => 'Amount cannot be zero.'];

    try {
        $db->beginTransaction();

        // Conditional UPDATE also blocks a manual debit below zero.
        if ($delta < 0) {
            $st = $db->prepare('UPDATE users SET balance = balance + ? WHERE id = ? AND balance >= ?');
            $st->execute([$delta, $userId, abs($delta)]);
            if ($st->rowCount() === 0) {
                $db->rollBack();
                return ['ok' => false, 'balance' => user_balance($userId), 'message' => 'Balance cannot go below zero.'];
            }
        } else {
            $db->prepare('UPDATE users SET balance = balance + ? WHERE id = ?')->execute([$delta, $userId]);
        }

        $st2 = $db->prepare('SELECT balance FROM users WHERE id = ?');
        $st2->execute([$userId]);
        $after = (int) $st2->fetchColumn();

        repdoc_ledger_insert($db, $userId, $delta, $after, '', $reason, '', $actorId);
        $db->commit();

        return ['ok' => true, 'balance' => $after, 'message' => 'Balance updated.'];
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        return ['ok' => false, 'balance' => user_balance($userId), 'message' => 'Could not update.'];
    }
}

/** Ledger rows for one user (or everyone when $userId is null). */
function ledger(int $limit = 50, ?int $userId = null): array
{
    $db = repdoc_db();
    if (!$db) return [];
    $limit = max(1, min(500, $limit));

    try {
        if ($userId === null) {
            $st = $db->prepare('SELECT t.*, u.fullname, u.email FROM transactions t
                                LEFT JOIN users u ON u.id = t.user_id
                                ORDER BY t.id DESC LIMIT ' . $limit);
            $st->execute();
        } else {
            $st = $db->prepare('SELECT * FROM transactions WHERE user_id = ? ORDER BY id DESC LIMIT ' . $limit);
            $st->execute([$userId]);
        }
        return $st->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

require_once __DIR__ . '/prottoyon.php';
require_once __DIR__ . '/prottoyon_certificate.php';
