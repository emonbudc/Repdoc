<?php
declare(strict_types=1);

/**
 * Repdoc — entrepreneur (UDC) panel layout.
 * UDC = approved entrepreneur (see is_udc()). Guards every panel page.
 *
 * The shell mirrors admin/includes/layout.php so both panels share one look:
 * same sidebar, icon rail, topbar, stat cards, panels and tables (admin.css).
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
        ['key' => 'death',      'name' => 'Death Certificate Generator', 'nav' => 'মৃত্যু নিবন্ধন', 'cat' => 'Death', 'icon' => 'a-file', 'url' => 'genaretor/death/', 'check' => 'genaretor/death/index.php'],
        ['key' => 'birthsearch', 'name' => 'Record Search (alt copy)', 'nav' => 'রেকর্ড সার্চ', 'cat' => 'Birth', 'icon' => 'a-file', 'url' => 'tools/birthsearch/', 'check' => 'tools/birthsearch/index.php', 'admin_only' => true],
        ['key' => 'nid',        'name' => 'National ID Card (NID)', 'nav' => 'জাতীয় পরিচয়পত্র (NID)', 'cat' => 'Identity', 'icon' => 'a-id', 'url' => 'genaretor/nid/', 'check' => 'genaretor/nid/index.php'],
        ['key' => 'smartnid',   'name' => 'Smart NID', 'nav' => 'স্মার্ট NID', 'cat' => 'Identity', 'icon' => 'a-id', 'url' => 'genaretor/smartnid/', 'check' => 'genaretor/smartnid/index.php'],
        ['key' => 'passport',   'name' => 'পাসপোর্ট সাইজ ছবি', 'nav' => 'পাসপোর্ট সাইজ ছবি', 'cat' => 'Identity', 'icon' => 'a-camera', 'url' => 'tools/passport/', 'check' => 'tools/passport/index.php'],
        ['key' => 'certificate', 'name' => 'Education Certificates', 'nav' => 'শিক্ষা সনদ', 'cat' => 'Education', 'icon' => 'a-award', 'url' => 'genaretor/certificate/', 'check' => 'genaretor/certificate/index.php'],
        ['key' => 'prottoyon-tool', 'name' => 'Prottoyon (Attestation)', 'nav' => 'প্রত্যয়ন তৈরি', 'cat' => 'Education', 'icon' => 'a-award', 'url' => 'genaretor/prottoyon/', 'check' => 'genaretor/prottoyon/index.php'],
        ['key' => 'cvmaker',    'name' => 'CV Maker', 'nav' => 'সিভি মেকার', 'cat' => 'Documents', 'icon' => 'a-grid', 'url' => 'genaretor/cvmaker/', 'check' => 'genaretor/cvmaker/index.php'],
        ['key' => 'land',       'name' => 'ভূমি খতিয়ান অনুসন্ধান', 'nav' => 'ভূমি খতিয়ান', 'cat' => 'Land', 'icon' => 'a-file', 'url' => 'tools/land/', 'check' => 'tools/land/index.php'],
        ['key' => 'dwamcbp',    'name' => 'DWAMCBP', 'nav' => 'DWAMCBP', 'cat' => 'Documents', 'icon' => 'a-grid', 'url' => 'tools/dwamcbp/', 'check' => 'tools/dwamcbp/index.php'],
        ['key' => 'showcase',   'name' => 'Template Showcase', 'nav' => 'টেমপ্লেট শোকেস', 'cat' => 'Templates', 'icon' => 'a-layout', 'url' => 'showcase/', 'check' => 'showcase/index.php'],
        ['key' => 'offlinefiles', 'name' => 'PSD / AI / PLP Files', 'nav' => 'PSD / AI / PLP ফাইল', 'cat' => 'Templates', 'icon' => 'a-file', 'url' => 'offlinefiles/', 'check' => 'offlinefiles/index.php'],
        ['key' => 'officefiles', 'name' => 'Word / Excel Files', 'nav' => 'Word / Excel ফাইল', 'cat' => 'Templates', 'icon' => 'a-file', 'url' => 'officefiles/', 'check' => 'officefiles/index.php'],
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

/**
 * Sidebar entries — same shape as the admin panel:
 *   key => [label, icon, url]                  → plain link
 *   key => [label, icon, children => [key => [label, icon, url]]] → collapsible group
 * `url` is site-root relative.
 */
function udc_nav_items(): array
{
    $items = [
        'index.php' => ['label' => 'ড্যাশবোর্ড', 'icon' => 'a-grid', 'url' => 'Entrepreneur/panel/index.php'],
    ];

    if (has_archive_access()) {
        $items['landing.php'] = ['label' => 'ডকুমেন্ট আর্কাইভ', 'icon' => 'a-file', 'url' => 'Entrepreneur/panel/landing.php'];
    }

    $items['prottoyons.php'] = ['label' => 'প্রত্যয়নসমূহ', 'icon' => 'a-award', 'url' => 'Entrepreneur/panel/prottoyons.php'];

    $tools = [];
    foreach (udc_tools() as $t) {
        $tools['tool:' . $t['key']] = ['label' => $t['nav'], 'icon' => $t['icon'], 'url' => $t['url']];
    }
    $items['tools'] = ['label' => 'টুলস', 'icon' => 'a-layout', 'children' => $tools];

    $items['office.php'] = ['label' => 'অফিস সেটিংস', 'icon' => 'a-lock', 'url' => 'genaretor/prottoyon/office.php'];

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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Bengali:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/tokens.css?v=2')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/app.css?v=1')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/admin.css?v=10')) ?>">
    <script>/* Rail state before paint, so the sidebar never flashes wide. */
    try { if (localStorage.getItem('repdoc-udc-rail') === '1') document.documentElement.classList.add('admin-rail'); } catch (e) {}</script>
</head>
<body class="admin-body">
    <svg class="sprite" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <symbol id="a-grid" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></symbol>
            <symbol id="a-file" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></symbol>
            <symbol id="a-id" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><circle cx="8.5" cy="10" r="2"/><path d="M5 16.5a3.6 3.6 0 0 1 7 0"/><line x1="14.5" y1="9.5" x2="19" y2="9.5"/><line x1="14.5" y1="13.5" x2="19" y2="13.5"/></symbol>
            <symbol id="a-award" viewBox="0 0 24 24"><circle cx="12" cy="8" r="6"/><path d="M8.21 13.89 7 23l5-3 5 3-1.21-9.11"/></symbol>
            <symbol id="a-users" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9"/><path d="M16 3.1a4 4 0 0 1 0 7.8"/></symbol>
            <symbol id="a-layout" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></symbol>
            <symbol id="a-wallet" viewBox="0 0 24 24"><path d="M3 7.5A2.5 2.5 0 0 1 5.5 5h11A2.5 2.5 0 0 1 19 7.5"/><rect x="3" y="7.5" width="18" height="12" rx="2"/><circle cx="16.5" cy="13.5" r="1.2"/></symbol>
            <symbol id="a-check" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></symbol>
            <symbol id="a-lock" viewBox="0 0 24 24"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></symbol>
            <symbol id="a-camera" viewBox="0 0 24 24"><path d="M4 8h3l1.5-2h7L17 8h3a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2z"/><circle cx="12" cy="14" r="3.2"/></symbol>
            <symbol id="a-download" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></symbol>
            <symbol id="a-link" viewBox="0 0 24 24"><path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"/><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/></symbol>
            <symbol id="a-log" viewBox="0 0 24 24"><path d="M4 4h11l5 5v11a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1z"/><polyline points="14 4 14 9 19 9"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="13" y2="17"/></symbol>
            <symbol id="a-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></symbol>
            <symbol id="a-sidebar" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="9" y1="3" x2="9" y2="21"/></symbol>
            <symbol id="a-search" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></symbol>
            <symbol id="a-menu" viewBox="0 0 24 24"><line x1="4" y1="7" x2="20" y2="7"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="17" x2="20" y2="17"/></symbol>
            <symbol id="a-x" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></symbol>
            <symbol id="a-login" viewBox="0 0 24 24"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></symbol>
            <symbol id="a-external" viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></symbol>

            <!-- Archive card icons (ids match Entrepreneur/archive_items.json). -->
            <symbol id="i-home" viewBox="0 0 24 24"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><path d="M9 21v-6h6v6"/></symbol>
            <symbol id="i-search" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></symbol>
            <symbol id="i-grid" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></symbol>
            <symbol id="i-list" viewBox="0 0 24 24"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></symbol>
            <symbol id="i-download" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></symbol>
            <symbol id="i-external" viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></symbol>
            <symbol id="i-arrow" viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></symbol>
            <symbol id="i-file" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></symbol>
            <symbol id="i-graduation" viewBox="0 0 24 24"><path d="m22 10-10-5L2 10l10 5 10-5z"/><path d="M6 12v5c3 2 9 2 12 0v-5"/><path d="M22 10v6"/></symbol>
            <symbol id="i-certificate" viewBox="0 0 24 24"><circle cx="12" cy="8" r="6"/><path d="M8.21 13.89 7 23l5-3 5 3-1.21-9.11"/></symbol>
            <symbol id="i-birth" viewBox="0 0 24 24"><circle cx="12" cy="7" r="3"/><path d="M8 21v-4a4 4 0 0 1 8 0v4"/><path d="M4 12h3M17 12h3"/></symbol>
            <symbol id="i-syringe" viewBox="0 0 24 24"><path d="m18 2 4 4"/><path d="m17 7 3-3"/><path d="M19 9 9 19l-5 1 1-5L15 5z"/><path d="m12 8 4 4"/><path d="m8 12 4 4"/></symbol>
            <symbol id="i-marriage" viewBox="0 0 24 24"><circle cx="8" cy="14" r="4"/><circle cx="16" cy="14" r="4"/><path d="M12 3v7"/><path d="M9 6h6"/></symbol>
            <symbol id="i-passport" viewBox="0 0 24 24"><rect x="5" y="2" width="14" height="20" rx="2"/><circle cx="12" cy="9" r="3"/><path d="M8 17h8"/></symbol>
            <symbol id="i-user-check" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><polyline points="16 11 18 13 22 9"/></symbol>
            <symbol id="i-income" viewBox="0 0 24 24"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="3"/><path d="M6 10h.01M18 14h.01"/></symbol>
            <symbol id="i-flag" viewBox="0 0 24 24"><path d="M4 22V3"/><path d="M4 4h12l-2 4 2 4H4"/></symbol>
            <symbol id="i-scroll" viewBox="0 0 24 24"><path d="M6 3h12a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"/><line x1="8" y1="7" x2="16" y2="7"/><line x1="8" y1="11" x2="16" y2="11"/><line x1="8" y1="15" x2="13" y2="15"/></symbol>
            <symbol id="i-cap" viewBox="0 0 24 24"><path d="m22 10-10-5L2 10l10 5 10-5z"/><path d="M6 12v5c3 2 9 2 12 0v-5"/></symbol>
        </defs>
    </svg>

    <div class="admin-shell">
        <div class="admin-scrim" id="adminScrim" hidden></div>

        <aside class="admin-sidebar" id="adminSidebar" aria-label="UDC panel">
            <a class="admin-brand" href="<?= e(repdoc_url('Entrepreneur/panel/index.php')) ?>" aria-label="UDC Panel — Dashboard">
                <img src="<?= e(repdoc_url('src/assets/image/logo.png')) ?>" alt="Repdoc" height="36">
            </a>

            <p class="admin-side-label">UDC Panel</p>
            <nav aria-label="Panel navigation">
                <ul class="admin-links">
                    <?php foreach (udc_nav_items() as $key => $item): ?>
                        <?php if (!empty($item['children']) && is_array($item['children'])): ?>
                            <?php
                            $childActive = false;
                            foreach ($item['children'] as $childKey => $child) {
                                if ($childKey === $active) $childActive = true;
                            }
                            $groupId = 'navgroup-' . preg_replace('/[^a-z0-9_-]/i', '', (string) $key);
                            ?>
                            <li class="admin-navgroup<?= $childActive ? ' is-current' : '' ?>">
                                <button type="button"
                                        class="admin-link admin-group-toggle<?= $childActive ? ' is-open' : '' ?>"
                                        title="<?= e($item['label']) ?>"
                                        id="<?= e($groupId) ?>-btn"
                                        aria-expanded="<?= $childActive ? 'true' : 'false' ?>"
                                        aria-controls="<?= e($groupId) ?>">
                                    <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#<?= e($item['icon']) ?>"/></svg>
                                    <span class="admin-link-label"><?= e($item['label']) ?></span>
                                    <svg class="admin-chevron" viewBox="0 0 24 24" aria-hidden="true"><use href="#a-chevron"/></svg>
                                </button>
                                <ul class="admin-sublinks" id="<?= e($groupId) ?>"<?= $childActive ? '' : ' hidden' ?>>
                                    <?php foreach ($item['children'] as $childKey => $child): ?>
                                        <?php $childIsActive = $childKey === $active; ?>
                                        <li<?= $childIsActive ? ' class="is-active"' : '' ?>>
                                            <a class="admin-sublink<?= $childIsActive ? ' is-active' : '' ?>"
                                               href="<?= e(repdoc_url($child['url'])) ?>"<?= $childIsActive ? ' aria-current="page"' : '' ?>>
                                                <svg class="icon-sub" viewBox="0 0 24 24" aria-hidden="true"><use href="#<?= e($child['icon']) ?>"/></svg>
                                                <span><?= e($child['label']) ?></span>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </li>
                        <?php else: ?>
                            <?php $isActive = $key === $active; ?>
                            <li<?= $isActive ? ' class="is-active"' : '' ?>>
                                <a class="admin-link<?= $isActive ? ' is-active' : '' ?>"
                                   href="<?= e(repdoc_url($item['url'])) ?>" title="<?= e($item['label']) ?>"<?= $isActive ? ' aria-current="page"' : '' ?>>
                                    <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#<?= e($item['icon']) ?>"/></svg>
                                    <span class="admin-link-label"><?= e($item['label']) ?></span>
                                </a>
                            </li>
                        <?php endif; ?>
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
                <button type="button" class="icon-btn admin-rail-toggle" id="adminRailBtn"
                        aria-label="Hide navigation" title="Show / hide the sidebar" aria-pressed="false">
                    <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#a-sidebar"/></svg>
                </button>
                <a class="admin-topbar-brand" href="<?= e(repdoc_url('Entrepreneur/panel/index.php')) ?>" aria-label="UDC Panel — Dashboard">
                    <img src="<?= e(repdoc_url('src/assets/image/logo.png')) ?>" alt="UDC Panel" height="30">
                </a>
                <p class="admin-crumb">UDC <span aria-hidden="true">/</span> <?= e($title) ?></p>
                <div class="admin-topbar-right">
                    <span class="admin-topbar-user" title="<?= e($me['email']) ?>">
                        <span class="admin-avatar" aria-hidden="true"><?= e($initial !== '' ? $initial : 'U') ?></span>
                        <span class="who"><strong><?= e($name) ?></strong></span>
                    </span>
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

    <div class="admin-toasts" id="adminToasts" aria-live="polite"></div>

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
            btn.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
        }

        btn.addEventListener('click', function () {
            setOpen(!sidebar.classList.contains('is-open'));
        });
        if (scrim) scrim.addEventListener('click', function () { setOpen(false); });
        document.addEventListener('keydown', function (ev) {
            if (ev.key === 'Escape') setOpen(false);
        });

        // Collapsible nav groups. The group holding the current page starts open.
        document.querySelectorAll('.admin-group-toggle').forEach(function (toggle) {
            var list = document.getElementById(toggle.getAttribute('aria-controls'));
            if (!list) return;
            toggle.addEventListener('click', function () {
                var open = toggle.getAttribute('aria-expanded') === 'true';
                toggle.setAttribute('aria-expanded', open ? 'false' : 'true');
                toggle.classList.toggle('is-open', !open);
                list.hidden = open;
            });
        });
        window.addEventListener('resize', function () {
            if (window.innerWidth >= 1024) setOpen(false);
        });
    })();

    /* Desktop: collapse the sidebar to an icon rail (or bring it back).
       Remembered per browser, and applied before paint in <head>. */
    (function () {
        var btn = document.getElementById('adminRailBtn');
        var root = document.documentElement;
        if (!btn) return;

        function save(rail) {
            try { localStorage.setItem('repdoc-udc-rail', rail ? '1' : '0'); } catch (e) {}
        }

        function paint() {
            var rail = root.classList.contains('admin-rail');
            btn.setAttribute('aria-pressed', rail ? 'true' : 'false');
            btn.setAttribute('aria-label', rail ? 'Show navigation' : 'Hide navigation');
        }

        function setRail(rail) {
            root.classList.toggle('admin-rail', rail);
            save(rail);
            paint();
        }

        paint();
        btn.addEventListener('click', function () {
            setRail(!root.classList.contains('admin-rail'));
        });

        /* A collapsed group is unusable inside an icon rail: clicking one
           brings the labels back and opens it. */
        document.addEventListener('click', function (ev) {
            var toggle = ev.target.closest ? ev.target.closest('.admin-group-toggle') : null;
            if (!toggle || !root.classList.contains('admin-rail')) return;
            setRail(false);
            var list = document.getElementById(toggle.getAttribute('aria-controls'));
            if (list) {
                list.hidden = false;
                toggle.setAttribute('aria-expanded', 'true');
                toggle.classList.add('is-open');
            }
        });
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
