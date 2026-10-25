<?php
/**
 * health.php — deployment diagnostics.
 * Visit https://<your-site>/health.php to see why search shows zero results.
 * Shows driver, record counts, extensions, file state. NEVER prints passwords.
 * DELETE this file after debugging (it reveals record counts / paths).
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

$out = [
    'php_version' => PHP_VERSION,
    'time' => date('c'),
    'extensions' => [
        'pdo' => extension_loaded('pdo'),
        'pdo_mysql' => extension_loaded('pdo_mysql'),
        'pdo_sqlite' => extension_loaded('pdo_sqlite'),
        'sqlite3' => class_exists('SQLite3'),
        'zip' => class_exists('ZipArchive'),
        'simplexml' => class_exists('SimpleXMLElement'),
        'mbstring' => extension_loaded('mbstring'),
        'json' => extension_loaded('json'),
    ],
    'files' => [
        'database.db exists' => is_file(__DIR__ . '/database.db'),
        'database.db size' => is_file(__DIR__ . '/database.db') ? filesize(__DIR__ . '/database.db') : 0,
        'database.db > 10MB (deleted by InfinityFree!)' => is_file(__DIR__ . '/database.db') ? filesize(__DIR__ . '/database.db') > 10 * 1024 * 1024 : false,
    ],
    'data_dir' => ['exists' => is_dir(__DIR__ . '/data'), 'docx_count' => 0],
];

if (is_dir(__DIR__ . '/data')) {
    $n = 0;
    foreach (scandir(__DIR__ . '/data') as $f) {
        if (stripos($f, '.docx') !== false) $n++;
    }
    $out['data_dir']['docx_count'] = $n;
}

try {
    // Public diagnostics endpoint (no password is ever printed).
    // Opt out of the admin-only guard in config.php; delete after debugging.
    if (!defined('REPDOC_SKIP_AUTH')) define('REPDOC_SKIP_AUTH', true);
    require_once __DIR__ . '/config.php';
    $out['db_driver'] = isMySQL() ? 'mysql' : 'sqlite';
    if (isMySQL()) {
        $out['mysql'] = ['host' => MYSQL_HOST, 'db' => MYSQL_DB, 'user' => MYSQL_USER, 'port' => MYSQL_PORT];
    }
    $db = getPDO();
    $out['connection'] = 'OK (' . $db->getAttribute(PDO::ATTR_DRIVER_NAME) . ')';
    require_once __DIR__ . '/includes/SearchEngine.php';
    $engine = new SearchEngine($db);
    $stats = $engine->getStats();
    $out['records_total'] = $stats['records'];
    $out['books_indexed'] = $stats['books'];
    $stmt = $db->query('SELECT source_file, COUNT(*) AS c FROM records GROUP BY source_file ORDER BY source_file LIMIT 30');
    $out['per_book'] = $stmt->fetchAll();
    $sample = $db->query('SELECT id, name, registration_number, source_file FROM records LIMIT 1')->fetch();
    $out['sample_row'] = $sample ?: null;

    if ((int) $stats['records'] === 0) {
        $out['diagnosis'] = 'ZERO RECORDS: ' . (isMySQL()
            ? 'MySQL connected but empty — run install.php to import data/*.docx.'
            : 'SQLite database.db missing/empty — local: run php build.php; hosting: switch to MySQL (see config.php).');
    } else {
        $out['diagnosis'] = 'OK: database has records. If search still fails, check api/search.php response in browser devtools.';
    }
} catch (\Throwable $e) {
    $out['connection'] = 'FAILED: ' . $e->getMessage();
    $out['diagnosis'] = 'Database connection failed — check config.php credentials (MySQL) or database.db presence (SQLite).';
}

echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
