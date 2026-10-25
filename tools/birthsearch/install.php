<?php
/**
 * install.php — InfinityFree-safe web installer / chunked importer.
 *
 * WHY THIS EXISTS: InfinityFree auto-deletes any single file larger than 10 MB.
 * database.db (~28 MB) therefore can NEVER work there — uploads show "zero results".
 * The fix is MySQL (no per-file limit). This script imports data/*.docx
 * (each < 1 MB) into MySQL ONE FILE PER REQUEST so PHP time limits are never hit.
 *
 * STEPS on InfinityFree:
 *   1. Control panel → MySQL Databases → create database + user, note host/name/user/pass.
 *   2. Edit config.php: DB_DRIVER='mysql' + fill MYSQL_HOST/DB/USER/PASS.
 *   3. FTP-upload everything EXCEPT database.db and bsearch.zip.
 *   4. Visit https://<your-site>/install.php → "Create tables" → "Import all".
 *   5. Open index.php, verify records appear, then DELETE install.php.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/DocxParser.php';

// This importer is destructive, so it is restricted to administrators.
require_admin();

@set_time_limit(120);
@ini_set('memory_limit', '256M');

header('Content-Type: text/html; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

function installJson($data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ── JSON endpoints (called by the progress UI, one file per request) ─────────
if ($action === 'init') {
    try {
        require_once __DIR__ . '/includes/SearchEngine.php';
        $db = getPDO(); // exits with a readable message if MySQL credentials are wrong
        new SearchEngine($db); // ensureSchema() creates the MySQL table + upgrades columns
        installJson(['ok' => true, 'message' => 'Tables ready.']);
    } catch (\Throwable $e) {
        installJson(['ok' => false, 'error' => $e->getMessage()], 500);
    }
}

if ($action === 'status') {
    try {
        $db = getPDO();
        $files = docxListFiles(DATA_DIR);
        $imported = [];
        try {
            $stmt = $db->query('SELECT source_file, COUNT(*) AS c FROM records GROUP BY source_file');
            while ($r = $stmt->fetch()) {
                $imported[$r['source_file']] = (int) $r['c'];
            }
        } catch (\Throwable $e) {
            // table may not exist yet — treat everything as pending
        }
        $total = 0;
        foreach ($imported as $c) $total += $c;
        installJson(['ok' => true, 'driver' => isMySQL() ? 'mysql' : 'sqlite', 'files' => $files, 'imported' => $imported, 'total' => $total]);
    } catch (\Throwable $e) {
        installJson(['ok' => false, 'error' => $e->getMessage()], 500);
    }
}

if ($action === 'import') {
    $file = basename(trim($_GET['file'] ?? $_POST['file'] ?? ''));
    if ($file === '' || stripos($file, '.docx') === false) {
        installJson(['ok' => false, 'error' => 'Invalid file name.'], 400);
    }
    $path = DATA_DIR . DIRECTORY_SEPARATOR . $file;
    if (!is_file($path)) {
        installJson(['ok' => false, 'error' => 'File not found on server: ' . $file], 404);
    }
    try {
        $db = getPDO();
        $parsed = docxParseFile($path);
        $records = docxExtractRecords($parsed['pages'], $file);

        $db->beginTransaction();
        // Idempotent per-file import: remove previous rows for this file first.
        $del = $db->prepare('DELETE FROM records WHERE source_file = ?');
        $del->execute([$file]);

        $cols = '(registration_number, name, father_name, mother_name, date_of_birth, gender, birth_place, permanent_address, registration_date, certificate_date, ward, page_number, serial, source_file, search_text)';
        $inserted = 0;
        foreach (array_chunk($records, 100) as $chunk) {
            $placeholders = implode(',', array_fill(0, count($chunk), '(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'));
            $flat = [];
            foreach ($chunk as $r) {
                foreach ($r as $v) $flat[] = $v;
            }
            $stmt = $db->prepare("INSERT INTO records $cols VALUES $placeholders");
            $stmt->execute($flat);
            $inserted += count($chunk);
        }
        $db->commit();
        installJson(['ok' => true, 'file' => $file, 'inserted' => $inserted, 'parse_error' => $parsed['error']]);
    } catch (\Throwable $e) {
        if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
            $db->rollBack();
        }
        installJson(['ok' => false, 'file' => $file ?? '', 'error' => $e->getMessage()], 500);
    }
}

if ($action === 'finish') {
    try {
        $db = getPDO();
        $before = (int) dbQuerySingle($db, 'SELECT COUNT(*) FROM records');
        $db->exec('DELETE FROM records WHERE id NOT IN (SELECT MIN(id) FROM records GROUP BY name, registration_number)');
        $after = (int) dbQuerySingle($db, 'SELECT COUNT(*) FROM records');
        @file_put_contents(__DIR__ . '/installed.lock', date('c'));
        installJson(['ok' => true, 'before' => $before, 'after' => $after, 'removed' => $before - $after]);
    } catch (\Throwable $e) {
        installJson(['ok' => false, 'error' => $e->getMessage()], 500);
    }
}

// ── HTML wizard UI ────────────────────────────────────────────────────────────
$checks = [
    'PHP version >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>=') . ' (' . PHP_VERSION . ')',
    'PDO MySQL driver (pDO_mysql)' => extension_loaded('pdo_mysql') ? true : 'MISSING — enable pdo_mysql',
    'ZipArchive (reads .docx)' => class_exists('ZipArchive') ? true : 'MISSING',
    'SimpleXML (parses .docx)' => class_exists('SimpleXMLElement') ? true : 'MISSING',
    'mbstring (Bangla text)' => extension_loaded('mbstring') ? true : 'MISSING',
    'data/ folder with .docx' => (function () {
        $n = count(docxListFiles(DATA_DIR));
        return $n > 0 ? true . " ($n files found)" : 'EMPTY — upload your .docx files to data/';
    })(),
    "DB driver = 'mysql'" => isMySQL() ? true : ((is_file(DB_PATH) ? true . ' (SQLite auto-mode — local only; hosting needs MySQL, just re-upload config.php)' : "NOT SET — re-upload config.php (auto-mode picks MySQL when database.db is absent)")),
];
$locked = is_file(__DIR__ . '/installed.lock');
?>
<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ইনস্টলার — বালুয়া ইউনিয়ন ডিজিটাল সেন্টার</title>
<link rel="icon" type="image/png" href="image/favicon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+Bengali:wght@400;500;600;700&family=Noto+Serif+Bengali:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="app">
    <div class="govt-strip" aria-hidden="true"></div>
    <header class="topbar">
        <div class="topbar-container">
            <a href="index.php" class="brand" title="Balua Union Digital Center — Home">
                <img class="brand-logo" src="image/logo.png" alt="Balua Union Digital Center">
            </a>
            <div class="topbar-actions">
                <div class="user-chip" title="ইনস্টলার">
                    <div class="user-avatar" aria-hidden="true">স</div>
                    <div class="user-details">
                        <span class="user-name">ইনস্টলার</span>
                        <span class="user-role">Setup</span>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <nav class="navbar" aria-label="প্রধান নেভিগেশন">
        <div class="navbar-inner">
            <div class="nav-links">
                <a href="index.php" class="nav-link">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <span>অনুসন্ধান</span>
                </a>
            </div>
        </div>
    </nav>

    <main class="main-content">
        <section class="search-panel" aria-labelledby="installHeading">
            <div class="search-header-row">
                <div class="search-title-wrap">
                    <h2 id="installHeading">ইনস্টলার — MySQL Import</h2>
                    <p>বড় SQLite ফাইল হোস্টিং সীমার কারণে MySQL-এ import করুন। প্রতিটি .docx আলাদা request-এ import হয়।</p>
                </div>
            </div>

            <?php if ($locked): ?>
            <div class="verify-status-box warning" style="margin-bottom:12px;">ইনস্টলার আগেই একবার চালানো হয়েছে (installed.lock আছে)। আবার import করলে প্রতিটি ফাইল idempotent — আগের rows মুছে নতুন করে ঢুকবে।</div>
            <?php endif; ?>

            <h3 class="install-step">ধাপ ০ — যাচাই</h3>
            <?php $allOk = true; foreach ($checks as $label => $res):
                $ok = $res === true || (is_string($res) && strpos($res, '1') === 0);
                if (is_string($res) && strpos($res, '1') === 0) $res = substr($res, 1);
                if (!$ok) $allOk = false; ?>
            <div class="install-check"><span><?= htmlspecialchars($label) ?></span><span class="badge-status <?= $ok ? 'verified' : 'incomplete' ?>"><?= $ok ? '✓' : '✗' ?> <?= htmlspecialchars((string) $res === '1' ? '' : (string) $res) ?></span></div>
            <?php endforeach; ?>

            <h3 class="install-step">ধাপ ১ — টেবিল</h3>
            <div class="install-actions">
                <button type="button" class="btn-secondary" id="btnInit">টেবিল তৈরি</button>
            </div>

            <h3 class="install-step">ধাপ ২ — ডাটা Import</h3>
            <div class="install-actions">
                <button type="button" class="btn-primary" id="btnImport">সব বই Import</button>
                <button type="button" class="btn-secondary" id="btnFinish">ফিনিশ (Deduplicate)</button>
            </div>

            <div class="install-progress"><div class="install-progress-fill" id="barFill"></div></div>
            <div class="install-stat" id="statLine">প্রস্তুত।</div>
            <div class="install-log" id="log">লগ এখানে দেখা যাবে…</div>
            <div id="finalBox"></div>
        </section>
    </main>

    <footer class="app-footer">
        <div class="app-footer-inner">
            <p class="credit">Made with ❤️ by <a href="https://github.com/emonbaluahat" target="_blank" rel="noopener">Abdullah Al Emon</a> | &copy; REPDOC</p>
        </div>
    </footer>
</div>

<script>
const $ = (id) => document.getElementById(id);
const logBox = $('log'), barFill = $('barFill'), statLine = $('statLine'), finalBox = $('finalBox');
function log(msg) { logBox.textContent += '\n' + msg; logBox.scrollTop = logBox.scrollHeight; }
async function api(action, file) {
  let url = 'install.php?action=' + encodeURIComponent(action);
  if (file) url += '&file=' + encodeURIComponent(file);
  const res = await fetch(url);
  const data = await res.json();
  if (!res.ok && !data.error) throw new Error('HTTP ' + res.status);
  return data;
}
$('btnInit').onclick = async () => {
  log('Tables তৈরি হচ্ছে…');
  try {
    const d = await api('init');
    log(d.ok ? 'OK: ' + d.message : 'FAIL: ' + d.error);
  } catch (e) { log('FAIL: ' + e.message); }
};
$('btnImport').onclick = async () => {
  $('btnImport').disabled = true;
  try {
    const s = await api('status');
    if (!s.ok) { log('FAIL: ' + s.error); return; }
    const files = s.files || [];
    if (!files.length) { log('data/ ফোল্ডারে কোনো .docx পাওয়া যায়নি।'); return; }
    log('মোট ফাইল: ' + files.length);
    let done = 0, totalInserted = 0;
    for (const f of files) {
      statLine.textContent = `Import হচ্ছে (${done + 1}/${files.length}): ${f}`;
      try {
        const r = await api('import', f);
        if (r.ok) { totalInserted += r.inserted; log(`OK: ${f} → ${r.inserted} records`); }
        else log(`FAIL: ${f}: ${r.error}`);
      } catch (e) { log(`FAIL: ${f}: ${e.message}`); }
      done++;
      barFill.style.width = Math.round(done / files.length * 100) + '%';
    }
    statLine.textContent = `সম্পন্ন: ${done}/${files.length} ফাইল, মোট ${totalInserted} records (deduplicate বাকি)।`;
    log('Import শেষ। এবার "ফিনিশ" চাপুন।');
  } finally { $('btnImport').disabled = false; }
};
$('btnFinish').onclick = async () => {
  try {
    const d = await api('finish');
    if (d.ok) {
      finalBox.innerHTML = `<div class="verify-status-box success" style="margin-top:12px;">সম্পন্ন! মোট ${d.after} unique records (ডুপ্লিকেট বাদ: ${d.removed})। <a href="index.php" style="font-weight:800;">index.php খুলুন</a> — যাচাই শেষে <b>install.php ফাইলটি সার্ভার থেকে DELETE করুন</b>।</div>`;
      log(`DONE: ${d.after} unique records.`);
    } else log('FAIL: ' + d.error);
  } catch (e) { log('FAIL: ' + e.message); }
};
</script>
</body>
</html>
