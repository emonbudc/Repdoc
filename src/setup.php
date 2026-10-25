<?php
declare(strict_types=1);

/**
 * Repdoc — one-time setup & migration.
 *
 * Safe to run more than once. It:
 *   1. creates the central tables in /love/ (users, tools, transactions, usage_log)
 *   2. seeds the per-tool price list (every price starts at 0 = free)
 *   3. creates the default admin when no admin account exists
 *   4. imports users from any legacy per-tool auth/*.db it can find
 *
 * Run it once after deploying, then delete it (or keep it — it never overwrites
 * an existing admin password or an existing user's balance).
 */

require_once __DIR__ . '/bootstrap.php';

// Once an administrator exists this page stops exposing anything: only a
// logged-in admin sees details. First run (no admin yet) stays open so the
// site can be bootstrapped at all.
$existingAdmins = 0;
$probe = repdoc_db();
if ($probe) {
    try {
        $existingAdmins = (int) $probe->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    } catch (Throwable $e) {
        $existingAdmins = 0;
    }
}

if ($existingAdmins > 0 && !is_admin()) {
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="bn"><meta charset="utf-8"><title>403</title>'
       . '<body style="font:16px/1.7 system-ui,sans-serif;max-width:520px;margin:80px auto;text-align:center">'
       . '<h1 style="font-size:1.2rem">সেটআপ আগেই সম্পন্ন হয়েছে</h1>'
       . '<p>এই পাতাটি শুধু অ্যাডমিনের জন্য।</p>'
       . '<p><a href="../public/login/">লগইন করুন</a> · <a href="../">হোমপেজ</a></p>'
       . '</body></html>';
    exit;
}

$lines  = [];
$fatal  = null;

// ── 0. Site URL: auto-detected, optional manual override ─────────
$__https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443');
$autoUrl = 'http' . ($__https ? 's' : '') . '://'
    . ((string) ($_SERVER['HTTP_HOST'] ?? 'localhost')) . repdoc_base_url() . '/';
unset($__https);

// ── 0b. Database auth: test connection + save (never prints password)
$dbMsg = null;
$dbOk  = null;
$configFile = REPDOC_LOVE . DIRECTORY_SEPARATOR . 'hosting-config.php';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['db_action'])) {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $dbMsg = 'Invalid request — please reload and try again.';
        $dbOk  = false;
    } else {
        $fDriver = strtolower(trim((string) ($_POST['db_driver'] ?? 'auto')));
        if (!in_array($fDriver, ['auto', 'mysql', 'sqlite'], true)) $fDriver = 'auto';
        $fHost = trim((string) ($_POST['mysql_host'] ?? ''));
        $fDb   = trim((string) ($_POST['mysql_db'] ?? ''));
        $fUser = trim((string) ($_POST['mysql_user'] ?? ''));
        $fPass = (string) ($_POST['mysql_pass'] ?? '');
        if ($fPass === '') $fPass = repdoc_env('MYSQL_PASS'); // keep stored
        $fPort = trim((string) ($_POST['mysql_port'] ?? '3306')) ?: '3306';
        $fBase = trim((string) ($_POST['base_url'] ?? ''));
        if (str_contains($fBase, '://')) $fBase = ''; // path only, never a full URL

        // Test with the posted values (fresh PDO, current request constants untouched).
        $testErr = null;
        try {
            if ($fDriver === 'sqlite') {
                $t = repdoc_sqlite_pdo(CENTRAL_DB_PATH, false);
                if (!$t && !is_writable(REPDOC_LOVE)) $testErr = 'love/ is not writable.';
            } else {
                if ($fHost === '' || $fDb === '' || $fUser === '') {
                    $testErr = 'MySQL host, database, and user are required.';
                } else {
                    $t = new PDO(
                        sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $fHost, $fPort, $fDb),
                        $fUser, $fPass,
                        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 8]
                    );
                    $t->query('SELECT 1');
                }
            }
        } catch (Throwable $e) {
            $testErr = 'Connection failed: ' . $e->getMessage();
        }

        if ($_POST['db_action'] === 'test') {
            $dbOk  = $testErr === null;
            $dbMsg = $testErr === null ? 'Connection OK.' : $testErr;
        } else { // save
            if ($testErr !== null) {
                $dbOk = false;
                $dbMsg = $testErr . ' — not saved.';
            } elseif (!is_writable(REPDOC_LOVE) && !is_file($configFile)) {
                $dbOk = false;
                $dbMsg = 'love/ is not writable — cannot save config.';
            } else {
                // Preserve optional service keys already in the environment (e.g. from deploy secrets).
                $keep = [
                    'OCRSPACE_API_KEY'     => repdoc_env('OCRSPACE_API_KEY'),
                    'GEMINI_API_KEY'       => repdoc_env('GEMINI_API_KEY'),
                    'POLLINATIONS_API_KEY' => repdoc_env('POLLINATIONS_API_KEY'),
                    'CAPTCHA_SOLVER_URL'   => repdoc_env('CAPTCHA_SOLVER_URL', 'http://localhost:8001') ?: 'http://localhost:8001',
                ];
                $cfg = "<?php\n// Saved from src/setup.php. Never commit. Protected by love/.htaccess.\n";
                $vals = ['DB_DRIVER' => $fDriver, 'MYSQL_HOST' => $fHost, 'MYSQL_DB' => $fDb,
                         'MYSQL_USER' => $fUser, 'MYSQL_PASS' => $fPass, 'MYSQL_PORT' => $fPort,
                         'REPDOC_BASE_URL' => $fBase] + $keep;
                foreach ($vals as $k => $v) {
                    $q = var_export($v, true);
                    $cfg .= "putenv('$k=' . $q);\n\$_SERVER['$k'] = $q;\n\$_ENV['$k'] = $q;\n";
                }
                if (@file_put_contents($configFile, $cfg) === false) {
                    $dbOk = false;
                    $dbMsg = 'Could not write love/hosting-config.php.';
                } else {
                    @chmod($configFile, 0600);
                    $dbOk = true;
                    $dbMsg = 'Saved. Reload this page to continue setup with the new connection.';
                }
            }
        }
    }
}
// Current effective values for display (password shown only as set/empty).
$cur = [
    'driver' => repdoc_env('DB_DRIVER') ?: 'auto (default)',
    'host'   => repdoc_env('MYSQL_HOST') ?: '—',
    'db'     => repdoc_env('MYSQL_DB') ?: '—',
    'user'   => repdoc_env('MYSQL_USER') ?: '—',
    'port'   => repdoc_env('MYSQL_PORT', '3306') ?: '3306',
    'pass'   => repdoc_env('MYSQL_PASS') !== '' ? 'set' : 'empty',
    'base'   => repdoc_env('REPDOC_BASE_URL') !== '' ? repdoc_env('REPDOC_BASE_URL') : '(auto-detect)',
    'config' => is_file($configFile) ? 'present' : 'missing',
];

$db = repdoc_db();
if (!$db) {
    $fatal = repdoc_uses_mysql()
        ? 'MySQL-এ সংযোগ করা যায়নি। MYSQL_HOST / MYSQL_DB / MYSQL_USER / MYSQL_PASS পরীক্ষা করুন।'
        : 'love/repdoc.db তৈরি করা যায়নি — love/ ফোল্ডারটি লিখনযোগ্য কিনা দেখুন।';
} else {
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    $lines[] = 'ডেটাবেস ড্রাইভার: <strong>' . e($driver) . '</strong>'
        . (repdoc_uses_mysql() ? ' (InfinityFree)' : ' (' . e(CENTRAL_DB_PATH) . ')');

    // ── 2. Seed the tool price list ──────────────────────────────
    $seeded = 0;
    foreach (repdoc_default_tools() as $key => $name) {
        try {
            $st = $db->prepare('SELECT COUNT(*) FROM tools WHERE tool_key = ?');
            $st->execute([$key]);
            if ((int) $st->fetchColumn() === 0) {
                $db->prepare('INSERT INTO tools (tool_key, name, price, active, updated_at) VALUES (?, ?, 0, 1, ?)')
                   ->execute([$key, $name, date('Y-m-d H:i:s')]);
                $seeded++;
            }
        } catch (Throwable $ex) {
            $lines[] = '⚠️ টুল সিড ব্যর্থ (' . e($key) . '): ' . e($ex->getMessage());
        }
    }
    $lines[] = "টুল প্রাইস-লিস্ট: {$seeded} টি নতুন এন্ট্রি যোগ হয়েছে (মোট " . count(repdoc_default_tools()) . ')';

    // ── 3. Default admin ─────────────────────────────────────────
    try {
        $adminCount = (int) $db->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
        if ($adminCount === 0) {
            $db->prepare("INSERT INTO users (fullname, email, password, role, status, balance, created_at)
                          VALUES (?, ?, ?, 'admin', 'approved', 0, ?)")
               ->execute(['Administrator', 'admin@admin.com', password_hash('admin123', PASSWORD_DEFAULT), date('Y-m-d H:i:s')]);
            $lines[] = '✅ ডিফল্ট অ্যাডমিন তৈরি হয়েছে: <strong>admin@admin.com / admin123</strong> — এখনই পাসওয়ার্ড বদলান।';
        } else {
            $lines[] = "ℹ️ অ্যাডমিন আগেই আছে ({$adminCount} টি) — নতুন করে তৈরি করা হয়নি।";
        }
    } catch (Throwable $ex) {
        $lines[] = '⚠️ অ্যাডমিন তৈরি ব্যর্থ: ' . e($ex->getMessage());
    }

    // ── 4. Import users from legacy per-tool databases ───────────
    $legacy = [
        REPDOC_ROOT . '/genaretor/birth/auth/birth.db',
        REPDOC_ROOT . '/genaretor/birth/search/demo/auth/birth.db',
        REPDOC_ROOT . '/tools/birthsearch/demo/auth/birth.db',
    ];

    $imported = 0;
    $skipped  = 0;
    foreach ($legacy as $file) {
        if (!is_file($file)) continue;
        try {
            $old = new PDO('sqlite:' . $file, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
            foreach ($old->query('SELECT fullname, email, password, role, status, created_at FROM users') as $u) {
                $st = $db->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
                $st->execute([$u['email']]);
                if ((int) $st->fetchColumn() > 0) { $skipped++; continue; }

                $db->prepare('INSERT INTO users (fullname, email, password, role, status, balance, created_at)
                              VALUES (?, ?, ?, ?, ?, 0, ?)')
                   ->execute([
                       $u['fullname'], $u['email'], $u['password'],
                       $u['role'] ?: 'user',
                       in_array($u['status'], ['pending', 'approved', 'rejected'], true) ? $u['status'] : 'pending',
                       $u['created_at'] ?: date('Y-m-d H:i:s'),
                   ]);
                $imported++;
            }
        } catch (Throwable $ex) {
            $lines[] = '⚠️ ইমপোর্ট ব্যর্থ (' . e(basename(dirname($file))) . '): ' . e($ex->getMessage());
        }
    }
    if ($imported || $skipped) {
        $lines[] = "পুরনো ইউজার ইমপোর্ট: {$imported} টি যোগ, {$skipped} টি আগেই ছিল।";
    }

    $lines[] = 'মোট ইউজার: <strong>' . (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn() . '</strong>, '
             . 'অ্যাডমিন: <strong>' . (int) $db->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn() . '</strong>';
    $lines[] = '⚠️ কাজ শেষ হলে এই ফাইলটি (<code>src/setup.php</code>) মুছে ফেলুন।';
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Repdoc — সেটআপ</title>
    <link rel="stylesheet" href="assets/css/tokens.css?v=1">
    <link rel="stylesheet" href="assets/css/app.css?v=1">
</head>
<body class="auth-body">
    <main class="auth-card" style="max-width:640px">
        <p class="eyebrow">Repdoc Setup</p>
        <h1>সেটআপ ও মাইগ্রেশন</h1>

        <h2 style="font-size:1rem;margin:18px 0 8px">সাইট URL</h2>
        <p style="margin:0 0 8px">Auto-detected: <strong><?= e($autoUrl) ?></strong></p>

        <h2 style="font-size:1rem;margin:18px 0 8px">ডেটাবেস সংযোগ</h2>
        <p style="margin:0 0 8px">Config file: <strong><?= e($cur['config']) ?></strong>
        · Driver: <strong><?= e($cur['driver']) ?></strong>
        · Host: <strong><?= e($cur['host']) ?></strong>
        · DB: <strong><?= e($cur['db']) ?></strong>
        · User: <strong><?= e($cur['user']) ?></strong>
        · Port: <strong><?= e($cur['port']) ?></strong>
        · Password: <strong><?= e($cur['pass']) ?></strong>
        · Base override: <strong><?= e($cur['base']) ?></strong></p>

        <?php if ($dbMsg !== null): ?>
            <div class="alert <?= $dbOk ? 'alert-success' : 'alert-error' ?>"><?= e($dbMsg) ?></div>
        <?php endif; ?>

        <form method="post" style="display:grid;gap:8px;margin:0 0 16px">
            <?= csrf_field() ?>
            <label>Base URL override (optional, blank = auto-detect)
                <input type="text" name="base_url" value="<?= e(trim((string) ($_POST['base_url'] ?? ''))) ?>" placeholder="/Repdoc">
            </label>
            <label>Driver
                <select name="db_driver">
                    <?php foreach (['auto', 'mysql', 'sqlite'] as $o): ?>
                        <option value="<?= $o ?>" <?= (($_POST['db_driver'] ?? repdoc_env('DB_DRIVER') ?: 'auto') === $o) ? 'selected' : '' ?>><?= $o ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>MySQL host
                <input type="text" name="mysql_host" value="<?= e((string) ($_POST['mysql_host'] ?? repdoc_env('MYSQL_HOST') ?: '')) ?>" placeholder="sql313.infinityfree.com">
            </label>
            <label>MySQL database
                <input type="text" name="mysql_db" value="<?= e((string) ($_POST['mysql_db'] ?? repdoc_env('MYSQL_DB') ?: '')) ?>">
            </label>
            <label>MySQL user
                <input type="text" name="mysql_user" value="<?= e((string) ($_POST['mysql_user'] ?? repdoc_env('MYSQL_USER') ?: '')) ?>">
            </label>
            <label>MySQL password (blank = keep stored)
                <input type="password" name="mysql_pass" value="" autocomplete="new-password">
            </label>
            <label>MySQL port
                <input type="text" name="mysql_port" value="<?= e((string) ($_POST['mysql_port'] ?? repdoc_env('MYSQL_PORT') ?: '3306')) ?>">
            </label>
            <div style="display:flex;gap:8px">
                <button class="btn btn-secondary" type="submit" name="db_action" value="test">Test connection</button>
                <button class="btn btn-primary" type="submit" name="db_action" value="save">Save + reload</button>
            </div>
        </form>

        <?php if ($fatal !== null): ?>
            <div class="alert alert-error"><?= e($fatal) ?></div>
        <?php else: ?>
            <ul class="setup-list">
                <?php foreach ($lines as $line): ?>
                    <li><?= $line ?></li>
                <?php endforeach; ?>
            </ul>
            <a class="btn btn-primary" href="../public/login/">লগইন পেজে যান</a>
        <?php endif; ?>
    </main>
</body>
</html>
