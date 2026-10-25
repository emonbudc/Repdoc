<?php
declare(strict_types=1);

/**
 * Repdoc — entrepreneur (UDC) panel layout.
 * UDC = approved entrepreneur (see is_udc()). Guards every panel page.
 */

require_once dirname(__DIR__, 3) . '/src/bootstrap.php';
require_login();

if (!is_udc() && !is_admin()) {
    redirect(repdoc_url('Entrepreneur/apply/'));
}

/** Every tool an entrepreneur may open. URL/check are site-root relative. */
function udc_tools(): array
{
    $tools = [
        ['key' => 'birth',      'name' => 'Birth Certificate Generator', 'nav' => 'জন্ম নিবন্ধন', 'cat' => 'Birth', 'icon' => 'a-file', 'url' => 'genaretor/birth/', 'check' => 'genaretor/birth/index.php'],
        ['key' => 'birthsearch', 'name' => 'Record Search (alt copy)', 'nav' => 'রেকর্ড সার্চ', 'cat' => 'Birth', 'icon' => 'a-file', 'url' => 'tools/birthsearch/', 'check' => 'tools/birthsearch/index.php', 'admin_only' => true],
        ['key' => 'nid',        'name' => 'National ID Card (NID)', 'nav' => 'জাতীয় পরিচয়পত্র (NID)', 'cat' => 'Identity', 'icon' => 'a-id', 'url' => 'genaretor/nid/', 'check' => 'genaretor/nid/index.php'],
        ['key' => 'smartnid',   'name' => 'Smart NID', 'nav' => 'স্মার্ট NID', 'cat' => 'Identity', 'icon' => 'a-id', 'url' => 'genaretor/smartnid/', 'check' => 'genaretor/smartnid/index.php'],
        ['key' => 'passport',   'name' => 'পাসপোর্ট সাইজ ছবি', 'nav' => 'পাসপোর্ট সাইজ ছবি', 'cat' => 'Identity', 'icon' => 'a-id', 'url' => 'tools/passport/', 'check' => 'tools/passport/index.php'],
        ['key' => 'certificate', 'name' => 'Education Certificates', 'nav' => 'শিক্ষা সনদ', 'cat' => 'Education', 'icon' => 'a-file', 'url' => 'genaretor/certificate/', 'check' => 'genaretor/certificate/index.php'],
        ['key' => 'prottoyon-tool', 'name' => 'Prottoyon (Attestation)', 'nav' => 'প্রত্যয়ন তৈরি', 'cat' => 'Education', 'icon' => 'a-file', 'url' => 'genaretor/prottoyon/', 'check' => 'genaretor/prottoyon/index.php'],
        ['key' => 'cvmaker',    'name' => 'CV Maker', 'nav' => 'সিভি মেকার', 'cat' => 'Documents', 'icon' => 'a-grid', 'url' => 'genaretor/cvmaker/', 'check' => 'genaretor/cvmaker/index.php'],
        ['key' => 'land',       'name' => 'ভূমি খতিয়ান অনুসন্ধান', 'nav' => 'ভূমি খতিয়ান', 'cat' => 'Land', 'icon' => 'a-file', 'url' => 'tools/land/', 'check' => 'tools/land/index.php'],
        ['key' => 'dwamcbp',    'name' => 'DWAMCBP', 'nav' => 'DWAMCBP', 'cat' => 'Documents', 'icon' => 'a-grid', 'url' => 'tools/dwamcbp/', 'check' => 'tools/dwamcbp/index.php'],
        ['key' => 'showcase',   'name' => 'Template Showcase', 'nav' => 'টেমপ্লেট শোকেস', 'cat' => 'Templates', 'icon' => 'a-grid', 'url' => 'showcase/', 'check' => 'showcase/index.php'],
        ['key' => 'offlinefiles', 'name' => 'PSD / AI / PLP Files', 'nav' => 'PSD / AI / PLP ফাইল', 'cat' => 'Templates', 'icon' => 'a-file', 'url' => 'offlinefiles/', 'check' => 'offlinefiles/index.php'],
        ['key' => 'officefiles', 'name' => 'Word / Excel Files', 'nav' => 'Word / Excel ফাইল', 'cat' => 'Templates', 'icon' => 'a-file', 'url' => 'officefiles/', 'check' => 'officefiles/index.php'],
        ['key' => 'apply',      'name' => 'Entrepreneur Application Form', 'nav' => 'উদ্যোক্তা আবেদন', 'cat' => 'Applications', 'icon' => 'a-id', 'url' => 'Entrepreneur/apply/', 'check' => 'Entrepreneur/apply/index.php'],
    ];

    $siteRoot = dirname(__DIR__, 3);
    foreach ($tools as &$t) {
        $t['live'] = is_file($siteRoot . '/' . $t['check']);
    }
    unset($t);

    return array_values(array_filter(
        $tools,
        static fn(array $t): bool => $t['live'] && empty($t['admin_only'])
    ));
}

function udc_nav_items(): array
{
    $items = [
        'index.php' => ['label' => 'ড্যাশবোর্ড', 'icon' => 'a-grid', 'url' => 'Entrepreneur/panel/index.php'],
    ];
    if (has_archive_access()) {
        $items['landing.php'] = ['label' => 'ডকুমেন্ট আর্কাইভ', 'icon' => 'a-file', 'url' => 'Entrepreneur/panel/landing.php'];
    }
    $items['prottoyons.php'] = ['label' => 'প্রত্যয়নসমূহ', 'icon' => 'a-file', 'url' => 'Entrepreneur/panel/prottoyons.php'];

    foreach (udc_tools() as $t) {
        $items['tool:' . $t['key']] = ['label' => $t['nav'], 'icon' => $t['icon'], 'url' => $t['url']];
    }

    $items['office.php'] = ['label' => 'অফিস সেটিংস', 'icon' => 'a-id', 'url' => 'genaretor/prottoyon/office.php'];

    return $items;
}

function udc_page_start(string $title, string $active): void
{
    repdoc_security_headers();
    $me = current_user();
    $name = $me['fullname'] !== '' ? $me['fullname'] : $me['email'];
    $initial = strtoupper((string) mb_substr($name, 0, 1, 'UTF-8'));
    ?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> — UDC Panel</title>
    <meta name="robots" content="noindex">
    <link rel="icon" type="image/png" href="<?= e(repdoc_url('public/favicon.png')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Bengali:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/tokens.css?v=1')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/app.css?v=1')) ?>">
</head>
<body class="admin-body">
    <svg class="sprite" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <symbol id="a-grid" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></symbol>
            <symbol id="a-file" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></symbol>
            <symbol id="a-id" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><circle cx="8.5" cy="10" r="2"/><path d="M5 16.5a3.6 3.6 0 0 1 7 0"/><line x1="14.5" y1="9.5" x2="19" y2="9.5"/><line x1="14.5" y1="13.5" x2="19" y2="13.5"/></symbol>
            <symbol id="a-login" viewBox="0 0 24 24"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></symbol>
            <symbol id="a-menu" viewBox="0 0 24 24"><line x1="4" y1="7" x2="20" y2="7"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="17" x2="20" y2="17"/></symbol>
            <symbol id="a-x" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></symbol>
            <symbol id="a-external" viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></symbol>
        </defs>
    </svg>

    <div class="admin-shell">
        <div class="admin-scrim" id="adminScrim" hidden></div>

        <aside class="admin-sidebar" id="adminSidebar" aria-label="UDC panel">
            <a class="admin-brand" href="index.php" aria-label="UDC Panel — Dashboard">
                <img src="<?= e(repdoc_url('src/assets/image/logo.png')) ?>" alt="Repdoc" height="36">
            </a>

            <p class="admin-side-label">UDC Panel</p>
            <nav aria-label="Panel navigation">
                <ul class="admin-links">
                    <?php foreach (udc_nav_items() as $file => $item): ?>
                        <?php
                        $href = repdoc_url($item['url']);
                        $isActive = $file === $active;
                        ?>
                        <li>
                            <a class="admin-link<?= $isActive ? ' is-active' : '' ?>"
                               href="<?= e($href) ?>"<?= $isActive ? ' aria-current="page"' : '' ?>>
                                <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#<?= e($item['icon']) ?>"/></svg>
                                <span class="admin-link-label"><?= e($item['label']) ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>

            <div class="admin-side-user">
                <span class="admin-avatar" aria-hidden="true"><?= e($initial !== '' ? $initial : 'U') ?></span>
                <span class="who">
                    <strong><?= e($name) ?></strong>
                    <span><?= e($me['email']) ?></span>
                </span>
                <span class="admin-side-actions">
                    <a class="icon-btn" href="<?= e(repdoc_url('/')) ?>" aria-label="View site" title="View site">
                        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#a-external"/></svg>
                    </a>
                    <a class="icon-btn" href="<?= e(repdoc_url('public/logout.php')) ?>" aria-label="Logout" title="Logout">
                        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#a-login"/></svg>
                    </a>
                </span>
            </div>
        </aside>

        <div class="admin-content">
            <header class="admin-topbar">
                <button type="button" class="icon-btn" id="adminMenuBtn"
                        aria-label="Open navigation" aria-controls="adminSidebar" aria-expanded="false">
                    <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#a-menu"/></svg>
                </button>
                <p class="admin-crumb">UDC <span aria-hidden="true">/</span> <?= e($title) ?></p>
                <div class="admin-topbar-right">
                    <a class="icon-btn" href="<?= e(repdoc_url('public/logout.php')) ?>" aria-label="Logout" title="Logout">
                        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#a-login"/></svg>
                    </a>
                </div>
            </header>

            <main class="admin-main">
                <div class="container">
    <?php
}

function udc_page_end(): void
{
    ?>
                </div>
            </main>
        </div>
    </div>

    <script>
    (function () {
        var btn = document.getElementById('adminMenuBtn');
        var sidebar = document.getElementById('adminSidebar');
        var scrim = document.getElementById('adminScrim');
        if (!btn || !sidebar) return;
        function setOpen(open) {
            sidebar.classList.toggle('is-open', open);
            if (scrim) scrim.hidden = !open;
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
        btn.addEventListener('click', function () {
            setOpen(!sidebar.classList.contains('is-open'));
        });
        if (scrim) scrim.addEventListener('click', function () { setOpen(false); });
    })();
    </script>
</body>
</html>
    <?php
}

/** Render a flash/notice alert once. */
function udc_notice(): void
{
    $flash = flash_get();
    if (!$flash) return;
    $map = ['ok' => 'alert-success', 'error' => 'alert-error', 'warn' => 'alert-warning'];
    $class = $map[$flash['type']] ?? 'alert-info';
    echo '<div class="alert ' . $class . '" role="status">' . e((string) $flash['message']) . '</div>';
}
