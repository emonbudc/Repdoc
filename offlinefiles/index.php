<?php
declare(strict_types=1);

/**
 * Repdoc — PSD / AI / PLP offline files.
 *
 * Public, indexable landing section for the homepage "PSD / AI / PLP"
 * card (replaces the old Sonod template entry). Copy stays honest:
 * files are auto-listed from offlinefiles/{psd,ai,plp}/ — when the
 * folders are empty the page says so instead of claiming downloads
 * that are not published yet. Listing is public; the bytes are gated
 * behind approved login via download.php (one account, every tool).
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';
require_once dirname(__DIR__) . '/src/seo.php';

$SEO_TITLE = 'PSD / AI / PLP টেমপ্লেট — অফলাইন ফাইল ডাউনলোড — Repdoc';
$SEO_DESC  = 'Repdoc অফলাইন ফাইল: PSD (Photoshop), AI (Illustrator) ও PLP (PixelLab) টেমপ্লেট ডাউনলোড করুন। লগ ইন করে যেকোনো ফরম্যাট আনলক করুন।';
$CANON     = repdoc_canonical('offlinefiles/');
$HOME      = repdoc_url('/');
$LOGIN_URL = repdoc_url('public/login/');
$APPLY_URL = repdoc_url('Entrepreneur/apply/');

$currentUser = current_user();

/** Allowed extensions per format folder. */
$FORMATS = [
    'psd' => ['label' => 'PSD', 'full' => 'Photoshop',  'exts' => ['psd', 'psb', 'zip']],
    'ai'  => ['label' => 'AI',  'full' => 'Illustrator', 'exts' => ['ai', 'eps', 'zip']],
    'plp' => ['label' => 'PLP', 'full' => 'PixelLab',    'exts' => ['plp', 'zip']],
];

function offlinefiles_human_size(int $bytes): string
{
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1024 * 1024) return number_format($bytes / 1024, 1) . ' KB';
    if ($bytes < 1024 * 1024 * 1024) return number_format($bytes / (1024 * 1024), 1) . ' MB';
    return number_format($bytes / (1024 * 1024 * 1024), 2) . ' GB';
}

/** Scan one format folder; returns newest-first file rows. */
function offlinefiles_scan(string $dir, string $type, array $exts): array
{
    $out = [];
    if (!is_dir($dir)) return $out;
    foreach (@scandir($dir) ?: [] as $name) {
        if ($name === '.' || $name === '..') continue;
        if (str_starts_with($name, '.')) continue;
        $ext = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, $exts, true)) continue;
        $path = $dir . DIRECTORY_SEPARATOR . $name;
        if (!is_file($path)) continue;
        $out[] = [
            'name'  => $name,
            'type'  => $type,
            'ext'   => $ext,
            'size'  => (int) @filesize($path),
            'mtime' => (int) @filemtime($path),
        ];
    }
    usort($out, static fn(array $a, array $b): int => $b['mtime'] <=> $a['mtime']);
    return $out;
}

$files = [];
foreach ($FORMATS as $type => $meta) {
    foreach (offlinefiles_scan(__DIR__ . DIRECTORY_SEPARATOR . $type, $type, $meta['exts']) as $row) {
        $files[] = $row;
    }
}
usort($files, static fn(array $a, array $b): int => $b['mtime'] <=> $a['mtime']);

$counts = ['all' => count($files), 'psd' => 0, 'ai' => 0, 'plp' => 0];
foreach ($files as $f) {
    $counts[$f['type']] = ($counts[$f['type']] ?? 0) + 1;
}

function offlinefiles_search_index(array $f): string
{
    return mb_strtolower($f['name'] . ' ' . $f['type'] . ' ' . $f['ext'], 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($SEO_TITLE) ?></title>
    <meta name="description" content="<?= e($SEO_DESC) ?>">
    <meta name="theme-color" content="#F8FAFC">
    <?php
    repdoc_seo_head([
        'title'       => $SEO_TITLE,
        'description' => $SEO_DESC,
        'canonical'   => $CANON,
        'type'        => 'website',
        'image'       => repdoc_canonical('src/assets/image/logo.png'),
        'imageWidth'  => 480,
        'imageHeight' => 160,
        'imageAlt'    => 'Repdoc logo',
        'locale'      => 'bn_BD',
    ]);
    repdoc_jsonld([
        '@type'         => 'WebPage',
        '@id'           => $CANON . '#page',
        'name'          => 'PSD / AI / PLP টেমপ্লেট — Repdoc',
        'url'           => $CANON,
        'description'   => $SEO_DESC,
        'inLanguage'    => 'bn',
        'isPartOf'      => ['@id' => repdoc_canonical() . '#website'],
        'breadcrumb'    => ['@id' => $CANON . '#breadcrumb'],
    ]);
    repdoc_jsonld([
        '@type'           => 'BreadcrumbList',
        '@id'             => $CANON . '#breadcrumb',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => repdoc_canonical()],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'PSD / AI / PLP টেমপ্লেট', 'item' => $CANON],
        ],
    ]);
    ?>
    <link rel="icon" type="image/png" href="<?= e(repdoc_url('public/favicon.png')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Bengali:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/tokens.css?v=1')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/app.css?v=1')) ?>">
    <style>
        .landing-wrap { max-width: 860px; margin: 0 auto; padding: 40px 20px 64px; }
        .crumbs { font-size: .85rem; margin: 0 0 16px; color: var(--text-muted); }
        .crumbs a { color: inherit; }
        .landing-card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 32px; }
        .landing-card h1 { margin: 0 0 12px; }
        .landing-card h2 { margin: 28px 0 8px; font-size: 1.1rem; }
        .landing-card p, .landing-card li { line-height: 1.7; }
        .landing-actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 24px; }
        .off-toolbar { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 20px; }
        .off-search { flex: 1 1 220px; min-height: 44px; padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; font: inherit; background: var(--bg, #fff); }
        .off-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 14px; }
        .off-chip { min-height: 40px; padding: 8px 14px; border: 1.5px solid var(--border); border-radius: 8px; background: transparent; font: inherit; cursor: pointer; }
        .off-chip.is-active { background: var(--primary, #0E6B4A); color: #fff; border-color: var(--primary, #0E6B4A); }
        .off-list { list-style: none; margin: 20px 0 0; padding: 0; display: grid; gap: 12px; }
        .off-row { display: flex; align-items: center; gap: 14px; border: 1px solid var(--border); border-radius: 10px; padding: 14px 16px; background: #fff; }
        .off-badge { font-family: 'JetBrains Mono', ui-monospace, monospace; font-size: .75rem; font-weight: 600; letter-spacing: .08em; border: 1.5px solid var(--primary, #0E6B4A); color: var(--primary, #0E6B4A); border-radius: 6px; padding: 4px 8px; white-space: nowrap; }
        .off-name { font-weight: 600; overflow-wrap: anywhere; }
        .off-meta { font-size: .85rem; color: var(--text-muted); margin-top: 2px; }
        .off-dl { margin-left: auto; white-space: nowrap; }
        .off-empty { border: 1px dashed var(--border); border-radius: 10px; padding: 28px 20px; text-align: center; margin-top: 20px; }
        .off-empty strong { display: block; margin-bottom: 6px; }
        .off-note { font-size: .85rem; color: var(--text-muted); margin-top: 16px; }
    </style>
</head>
<body class="auth-body">
    <main class="landing-wrap">
        <p class="crumbs" aria-label="Breadcrumb"><a href="<?= e($HOME) ?>">হোম</a> › PSD / AI / PLP টেমপ্লেট</p>
        <div class="landing-card">
            <a class="auth-brand" href="<?= e($HOME) ?>">
                <img src="<?= e(repdoc_url('src/assets/image/logo.png')) ?>" alt="" width="96" height="32" loading="lazy" decoding="async">
                <span><strong>Repdoc</strong><small>repdoc.freedev.app · operator toolkit</small></span>
            </a>

            <h1>PSD / AI / PLP টেমপ্লেট</h1>
            <p>অফলাইন এডিটের জন্য রেডি ফাইল — Photoshop (PSD), Illustrator (AI) ও
                PixelLab (PLP)। ফাইল পছন্দ করে ডাউনলোড করুন, নিজের কম্পিউটারে এডিট করুন।</p>

            <div class="off-toolbar" role="search">
                <input class="off-search" id="offSearch" type="search"
                       placeholder="ফাইল খুঁজুন — নাম লিখুন…" aria-label="ফাইল খুঁজুন" autocomplete="off">
            </div>
            <div class="off-chips" role="group" aria-label="ফরম্যাট">
                <button type="button" class="off-chip is-active" data-type="all" aria-pressed="true">সব (<?= (int) $counts['all'] ?>)</button>
                <button type="button" class="off-chip" data-type="psd" aria-pressed="false">PSD (<?= (int) $counts['psd'] ?>)</button>
                <button type="button" class="off-chip" data-type="ai" aria-pressed="false">AI (<?= (int) $counts['ai'] ?>)</button>
                <button type="button" class="off-chip" data-type="plp" aria-pressed="false">PLP (<?= (int) $counts['plp'] ?>)</button>
            </div>

            <?php if ($files === []): ?>
                <div class="off-empty" role="status">
                    <strong>এখনো কোনো ফাইল যোগ হয়নি</strong>
                    <p>PSD, AI ও PLP প্যাক প্রকাশ হলে এখানেই তালিকাভুক্ত হবে। Word ও PDF
                        ডকুমেন্টের জন্য জেনারেটর ব্যবহার করুন।</p>
                </div>
            <?php else: ?>
                <ul class="off-list" id="offList">
                    <?php foreach ($files as $f):
                        $dl = repdoc_url('offlinefiles/download.php') . '?type=' . rawurlencode($f['type']) . '&file=' . rawurlencode($f['name']);
                        if ($currentUser === null) {
                            $dl = $LOGIN_URL . '?next=' . rawurlencode('/offlinefiles/');
                        }
                        $label = $FORMATS[$f['type']]['label'] ?? strtoupper($f['type']);
                    ?>
                        <li class="off-row" data-type="<?= e($f['type']) ?>" data-search="<?= e(offlinefiles_search_index($f)) ?>">
                            <span class="off-badge"><?= e($label) ?></span>
                            <span>
                                <span class="off-name"><?= e($f['name']) ?></span>
                                <span class="off-meta" style="display:block"><?= e(strtoupper($f['ext'])) ?> • <?= e(offlinefiles_human_size($f['size'])) ?><?php if ($f['mtime'] > 0): ?> • <?= e(date('d-m-Y', $f['mtime'])) ?><?php endif; ?></span>
                            </span>
                            <a class="btn <?= $currentUser === null ? 'btn-secondary' : 'btn-primary' ?> off-dl"
                               href="<?= e($dl) ?>"><?= $currentUser === null ? 'লগ ইন করে ডাউনলোড' : 'ডাউনলোড' ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="off-empty" id="offNoResult" role="status" hidden>
                    <strong>কোনো ফাইল পাওয়া যায়নি</strong>
                    <p>অন্য নাম বা ফরম্যাট চেষ্টা করুন।</p>
                </div>
            <?php endif; ?>

            <h2>কীভাবে নেবেন</h2>
            <ol>
                <li><a href="<?= e($APPLY_URL) ?>">অপারেটর অ্যাকাউন্টের জন্য আবেদন করুন</a> — অনুমোদিত অপারেটররাই ডাউনলোড করতে পারেন।</li>
                <li><a href="<?= e($LOGIN_URL) ?>">লগ ইন করুন</a> — একটি অ্যাকাউন্টেই সব ফরম্যাট।</li>
                <li>এই পেজে ফিরে পছন্দের PSD / AI / PLP ফাইলে ডাউনলোড চাপুন।</li>
            </ol>

            <div class="landing-actions">
                <?php if ($currentUser === null): ?>
                    <a class="btn btn-primary" href="<?= e($LOGIN_URL) ?>">লগ ইন</a>
                <?php endif; ?>
                <a class="btn btn-secondary" href="<?= e($HOME) ?>">হোমে ফিরুন</a>
            </div>
            <p class="off-note">Independent tools site — not a government portal.</p>
        </div>
    </main>
    <script>
    (function () {
        var search = document.getElementById('offSearch');
        var list = document.getElementById('offList');
        var noResult = document.getElementById('offNoResult');
        if (!list) return;
        var rows = Array.prototype.slice.call(list.querySelectorAll('.off-row'));
        var activeType = 'all';
        function apply() {
            var q = (search ? search.value : '').toLowerCase().trim();
            var visible = 0;
            rows.forEach(function (row) {
                var okType = (activeType === 'all') || (row.getAttribute('data-type') === activeType);
                var hay = row.getAttribute('data-search') || '';
                var okQuery = (q === '') || (hay.toLowerCase().indexOf(q) !== -1);
                var show = okType && okQuery;
                row.hidden = !show;
                if (show) visible++;
            });
            if (noResult) noResult.hidden = visible !== 0;
        }
        document.querySelectorAll('.off-chip').forEach(function (chip) {
            chip.addEventListener('click', function () {
                document.querySelectorAll('.off-chip').forEach(function (c) {
                    c.classList.remove('is-active');
                    c.setAttribute('aria-pressed', 'false');
                });
                chip.classList.add('is-active');
                chip.setAttribute('aria-pressed', 'true');
                activeType = chip.getAttribute('data-type') || 'all';
                apply();
            });
        });
        if (search) search.addEventListener('input', apply);
    })();
    </script>
</body>
</html>
