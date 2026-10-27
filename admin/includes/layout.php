<?php
declare(strict_types=1);

/**
 * Repdoc — admin panel layout.
 * Shared header / navigation / footer so every admin page stays consistent.
 */

require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
require_admin();

/**
 * Navigation entries.
 *
 * file => [label, icon, optional badge key] — a parent item may instead carry
 * a `children` map (file => [label, icon]) and renders as a collapsible group
 * with sub-items (the Logs group).
 */
function admin_nav_items(): array
{
    return [
        'index.php'        => ['label' => 'Dashboard',         'icon' => 'a-grid'],
        'seo-health.php'   => ['label' => 'SEO & Health',      'icon' => 'a-search'],
        'user-management'  => [
            'label'    => 'User Management',
            'icon'     => 'a-users',
            'children' => [
                'users.php'        => ['label' => 'Users & Balance',   'icon' => 'a-users', 'badge' => 'users'],
                'entrepreneur.php' => ['label' => 'Entrepreneur Apps', 'icon' => 'a-id',    'badge' => 'entrepreneur'],
            ],
        ],
        'document-archive.php' => ['label' => 'Document Archive', 'icon' => 'a-file'],
        // Tools opens admin/tools.php on click; the caret shows every tool.
        'tools.php'        => [
            'label'    => 'Tools',
            'icon'     => 'a-layout',
            'href'     => 'tools.php',
            'children' => admin_nav_tool_items(),
        ],
        'passport.php'     => ['label' => 'Passport Photo',    'icon' => 'a-camera'],
        'files.php'        => ['label' => 'File Packs',        'icon' => 'a-file'],
        'maps.php'         => ['label' => 'Mouza Maps',        'icon' => 'a-grid'],
        'software.php'     => ['label' => 'Software Downloads', 'icon' => 'a-download'],
        'important-links.php' => ['label' => 'Important Links', 'icon' => 'a-link'],
        'logs'             => [
            'label'    => 'Logs',
            'icon'     => 'a-log',
            'children' => [
                'documents.php'    => ['label' => 'Document Log',   'icon' => 'a-id'],
                'prottoyon.php'    => ['label' => 'Prottoyon Log',  'icon' => 'a-file'],
                'transactions.php' => ['label' => 'Transactions',   'icon' => 'a-wallet'],
            ],
        ],
        'pricing.php'      => ['label' => 'Tool Prices',       'icon' => 'a-tag'],
    ];
}

/** Pending-approval counts for nav badges. Never throws — degrades to zeros. */
function admin_pending_counts(): array
{
    $out = ['users' => 0, 'entrepreneur' => 0];
    try {
        $db = repdoc_db();
        if ($db instanceof PDO) {
            $out['users'] = (int) $db->query("SELECT COUNT(*) FROM users WHERE status = 'pending'")->fetchColumn();
            $out['entrepreneur'] = (int) $db->query("SELECT COUNT(*) FROM entrepreneur_apps WHERE status = 'pending'")->fetchColumn();
        }
    } catch (Throwable $e) {}
    return $out;
}

/**
 * Every tool the panel knows about: name, category, url (relative to admin/)
 * and the entry file used to check that it really exists on disk.
 * admin/tools.php lists exactly this registry.
 */
function admin_tool_registry(): array
{
    return [
        ['name' => 'Birth Certificate Generator', 'cat' => 'Birth', 'slug' => 'birth-certificate', 'url' => '../genaretor/birth/index.php', 'check' => '../genaretor/birth/index.php', 'keys' => ['birth_generator']],
        ['name' => 'Record Search (alt copy)', 'cat' => 'Birth', 'slug' => 'birth-search', 'url' => '../tools/birthsearch/index.php', 'check' => '../tools/birthsearch/index.php', 'keys' => ['birth_search', 'birth_export', 'birth_detail']],
        ['name' => 'National ID Card (NID)', 'cat' => 'Identity', 'slug' => 'nid', 'url' => '../genaretor/nid/', 'check' => '../genaretor/nid/index.php', 'keys' => ['nid']],
        ['name' => 'Smart NID', 'cat' => 'Identity', 'slug' => 'smart-nid', 'url' => '../genaretor/smartnid/', 'check' => '../genaretor/smartnid/index.php', 'keys' => ['smartnid']],
        ['name' => 'Passport Size Photo', 'cat' => 'Identity', 'slug' => 'passport-photo', 'url' => '../tools/passport/', 'check' => '../tools/passport/index.php', 'keys' => ['passport_photo']],
        ['name' => 'Education Certificates', 'cat' => 'Education', 'slug' => 'education-certificates', 'url' => '../genaretor/certificate/', 'check' => '../genaretor/certificate/index.php', 'keys' => ['certificate']],
        ['name' => 'Prottoyon (Attestation)', 'cat' => 'Education', 'slug' => 'prottoyon', 'url' => '../genaretor/prottoyon/', 'check' => '../genaretor/prottoyon/index.php', 'keys' => [], 'key_prefix' => 'prottoyon_'],
        ['name' => 'CV Maker', 'cat' => 'Documents', 'slug' => 'cv-maker', 'url' => '../genaretor/cvmaker/', 'check' => '../genaretor/cvmaker/index.php', 'keys' => ['cvmaker']],
        ['name' => 'Land Record Search (Khatiyan)', 'cat' => 'Land', 'slug' => 'land-records', 'url' => '../tools/land/', 'check' => '../tools/land/index.php', 'keys' => []],
        ['name' => 'DWAMCBP', 'cat' => 'Documents', 'slug' => 'dwamcbp', 'url' => '../tools/dwamcbp/', 'check' => '../tools/dwamcbp/index.php', 'keys' => []],
        ['name' => 'Template Showcase', 'cat' => 'Templates', 'slug' => 'template-showcase', 'url' => '../showcase/', 'check' => '../showcase/index.php', 'keys' => []],
        ['name' => 'PSD / AI / PLP Files', 'cat' => 'Templates', 'slug' => 'psd-ai-files', 'url' => '../offlinefiles/', 'check' => '../offlinefiles/index.php', 'keys' => []],
        ['name' => 'Word / Excel Files', 'cat' => 'Templates', 'slug' => 'word-excel-files', 'url' => '../officefiles/', 'check' => '../officefiles/index.php', 'keys' => []],
        ['name' => 'Mouza Map Browser (admin)', 'cat' => 'Land', 'slug' => 'mouza-maps', 'url' => 'maps.php', 'check' => 'maps.php', 'keys' => []],
        ['name' => 'Important Links Directory', 'cat' => 'Public pages', 'slug' => 'important-links', 'url' => '../importantlinks/', 'check' => '../importantlinks/index.php', 'keys' => []],
        ['name' => 'File Packs (admin upload)', 'cat' => 'Admin', 'slug' => 'file-packs', 'url' => 'files.php', 'check' => 'files.php', 'keys' => []],
        ['name' => 'Important Links (admin editor)', 'cat' => 'Admin', 'slug' => 'important-links-editor', 'url' => 'important-links.php', 'check' => 'important-links.php', 'keys' => []],
        ['name' => 'Entrepreneur Application Form', 'cat' => 'Applications', 'slug' => 'entrepreneur-apply', 'url' => '../Entrepreneur/apply/', 'check' => '../Entrepreneur/apply/index.php', 'keys' => []],
    ];
}

/** One registry entry by slug, or null. */
function admin_tool_by_slug(string $slug): ?array
{
    foreach (admin_tool_registry() as $tool) {
        if ((string) $tool['slug'] === $slug) {
            $tool['live'] = is_file(__DIR__ . '/' . $tool['check']);
            return $tool;
        }
    }
    return null;
}

/** Admin control page for a tool (relative to admin/). */
function admin_tool_page(array $tool): string
{
    return 'tool-' . $tool['slug'] . '.php';
}

/**
 * Price keys a tool is charged under: its own keys plus every price key that
 * matches its prefix (Prottoyon spans ~50 keys).
 */
function admin_tool_price_keys(array $tool): array
{
    $keys = array_values(array_filter(array_map('strval', (array) ($tool['keys'] ?? []))));
    $prefix = (string) ($tool['key_prefix'] ?? '');
    if ($prefix !== '') {
        foreach (array_keys(repdoc_default_tools()) as $key) {
            if (str_starts_with((string) $key, $prefix)) $keys[] = (string) $key;
        }
    }
    return array_values(array_unique($keys));
}

/** Icons per tool category (used by the sidebar list and the tool pages). */
function admin_tool_icon(string $category): string
{
    $icons = [
        'Birth' => 'a-id', 'Identity' => 'a-id', 'Education' => 'a-file', 'Documents' => 'a-file',
        'Land' => 'a-grid', 'Templates' => 'a-layout', 'Public pages' => 'a-link',
        'Admin' => 'a-lock', 'Applications' => 'a-users',
    ];
    return $icons[$category] ?? 'a-grid';
}

/** Nav sub-items for the Tools group: href => [label, icon] (admin control pages). */
function admin_nav_tool_items(): array
{
    $items = [];
    foreach (admin_tool_registry() as $tool) {
        $items[admin_tool_page($tool)] = [
            'label' => (string) $tool['name'],
            'icon'  => admin_tool_icon((string) $tool['cat']),
        ];
    }
    return $items;
}

/**
 * Live actions — the panel's forms post in the background and the page
 * updates in place; only switching sections reloads the whole page.
 */
function admin_live_request(): bool
{
    return (($_SERVER['HTTP_X_REPDOC_LIVE'] ?? '') === '1') || ((string) ($_POST['_live'] ?? '') === '1');
}

/** Finish a live action with JSON: no redirect, no page render. */
function admin_live_json(array $payload): never
{
    if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Shared counters: `stats` feeds every [data-live-stat] node, `badges` the
 * sidebar pills, `pending` the topbar review pill.
 */
function admin_live_counters(?PDO $db): array
{
    $raw = [
        'users' => 0, 'users_pending' => 0, 'users_active' => 0, 'users_balance' => 0,
        'apps' => 0, 'apps_pending' => 0, 'apps_approved' => 0, 'apps_archive' => 0,
    ];

    if ($db instanceof PDO) {
        try {
            $raw['users']         = (int) $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
            $raw['users_pending'] = (int) $db->query("SELECT COUNT(*) FROM users WHERE status = 'pending'")->fetchColumn();
            $raw['users_active']  = (int) $db->query("SELECT COUNT(*) FROM users WHERE status = 'approved'")->fetchColumn();
            $raw['users_balance'] = (int) $db->query('SELECT COALESCE(SUM(balance), 0) FROM users')->fetchColumn();
            $raw['apps']          = (int) $db->query('SELECT COUNT(*) FROM entrepreneur_apps')->fetchColumn();
            $raw['apps_pending']  = (int) $db->query("SELECT COUNT(*) FROM entrepreneur_apps WHERE status = 'pending'")->fetchColumn();
            $raw['apps_approved'] = (int) $db->query("SELECT COUNT(*) FROM entrepreneur_apps WHERE status = 'approved'")->fetchColumn();
            $raw['apps_archive']  = (int) $db->query('SELECT COUNT(*) FROM entrepreneur_apps WHERE archive_access = 1')->fetchColumn();
        } catch (Throwable $e) {}
    }

    $pending = $raw['users_pending'] + $raw['apps_pending'];
    $stats   = [];
    foreach ($raw as $key => $value) $stats[$key] = number_format($value);

    return [
        'stats'   => $stats,
        'badges'  => ['users' => $raw['users_pending'], 'entrepreneur' => $raw['apps_pending'], 'all' => $pending],
        'pending' => $pending,
    ];
}

function admin_page_start(string $title, string $active): void
{
    repdoc_security_headers();
    $me = current_user();
    $badges = admin_pending_counts();
    $initial = strtoupper((string) mb_substr($me['fullname'] !== '' ? $me['fullname'] : $me['email'], 0, 1, 'UTF-8'));
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> — Repdoc Admin</title>
    <meta name="robots" content="noindex">
    <link rel="icon" type="image/png" href="assets/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&amp;family=Noto+Sans+Bengali:wght@400;500;600;700&amp;family=JetBrains+Mono:wght@400;500&amp;display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../src/assets/css/tokens.css?v=2">
    <link rel="stylesheet" href="../src/assets/css/app.css?v=1">
    <link rel="stylesheet" href="../src/assets/css/admin.css?v=10">
    <script>/* Rail state before paint, so the sidebar never flashes wide. */
    try { if (localStorage.getItem('repdoc-admin-rail') === '1') document.documentElement.classList.add('admin-rail'); } catch (e) {}</script>
</head>
<body class="admin-body">
    <!-- Inline SVG sprite: outline icons only, decorative here. -->
    <svg class="sprite" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <symbol id="a-grid" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></symbol>
            <symbol id="a-file" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></symbol>
            <symbol id="a-users" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9"/><path d="M16 3.1a4 4 0 0 1 0 7.8"/></symbol>
            <symbol id="a-id" viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="16" rx="2"/><circle cx="8.5" cy="10" r="2"/><path d="M5 16.5a3.6 3.6 0 0 1 7 0"/><line x1="14.5" y1="9.5" x2="19" y2="9.5"/><line x1="14.5" y1="13.5" x2="19" y2="13.5"/></symbol>
            <symbol id="a-layout" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></symbol>
            <symbol id="a-wallet" viewBox="0 0 24 24"><path d="M3 7.5A2.5 2.5 0 0 1 5.5 5h11A2.5 2.5 0 0 1 19 7.5"/><rect x="3" y="7.5" width="18" height="12" rx="2"/><circle cx="16.5" cy="13.5" r="1.2"/></symbol>
            <symbol id="a-tag" viewBox="0 0 24 24"><path d="M20.59 13.41 11 3H4v7l9.59 9.59a2 2 0 0 0 2.82 0l4.18-4.18a2 2 0 0 0 0-2.82z"/><circle cx="7.5" cy="7.5" r=".8"/></symbol>
            <symbol id="a-check" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></symbol>
            <symbol id="a-trash" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></symbol>
            <symbol id="a-lock" viewBox="0 0 24 24"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></symbol>
            <symbol id="a-dots" viewBox="0 0 24 24"><circle cx="12" cy="5" r="1.4" fill="currentColor" stroke="none"/><circle cx="12" cy="12" r="1.4" fill="currentColor" stroke="none"/><circle cx="12" cy="19" r="1.4" fill="currentColor" stroke="none"/></symbol>
            <symbol id="a-menu" viewBox="0 0 24 24"><line x1="4" y1="7" x2="20" y2="7"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="17" x2="20" y2="17"/></symbol>
            <symbol id="a-x" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></symbol>
            <symbol id="a-login" viewBox="0 0 24 24"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></symbol>
            <symbol id="a-external" viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></symbol>
            <symbol id="a-link" viewBox="0 0 24 24"><path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"/><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/></symbol>
            <symbol id="a-download" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></symbol>
            <symbol id="a-camera" viewBox="0 0 24 24"><path d="M4 8h3l1.5-2h7L17 8h3a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2z"/><circle cx="12" cy="14" r="3.2"/></symbol>
            <symbol id="a-log" viewBox="0 0 24 24"><path d="M4 4h11l5 5v11a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1z"/><polyline points="14 4 14 9 19 9"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="13" y2="17"/></symbol>
            <symbol id="a-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></symbol>
            <symbol id="a-sidebar" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="9" y1="3" x2="9" y2="21"/></symbol>
            <symbol id="a-search" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></symbol>
            <symbol id="a-pulse" viewBox="0 0 24 24"><polyline points="3 12 7 12 10 5 14 19 17 12 21 12"/></symbol>
        </defs>
    </svg>

    <div class="admin-shell">
        <div class="admin-scrim" id="adminScrim" hidden></div>

        <aside class="admin-sidebar" id="adminSidebar" aria-label="Admin panel">
            <a class="admin-brand" href="index.php" aria-label="Repdoc Admin — Dashboard">
                <img src="assets/image/logo.png" alt="Repdoc Admin" height="36">
            </a>

            <p class="admin-side-label">Manage</p>
            <nav aria-label="Admin navigation">
                <ul class="admin-links">
                    <?php foreach (admin_nav_items() as $file => $item): ?>
                        <?php if (!empty($item['children']) && is_array($item['children'])): ?>
                            <?php
                            $childActive = false;
                            $badgeSum = 0;
                            foreach ($item['children'] as $childFile => $child) {
                                if ($childFile === $active) $childActive = true;
                                if (!empty($child['badge'])) $badgeSum += (int) ($badges[$child['badge']] ?? 0);
                            }
                            $groupId = 'navgroup-' . preg_replace('/[^a-z0-9_-]/i', '', (string) $file);
                            $href    = (string) ($item['href'] ?? '');
                            ?>
                            <li class="admin-navgroup<?= $childActive ? ' is-current' : '' ?><?= ($href !== '' && $href === $active) ? ' is-active' : '' ?>">
                                <?php if ($href !== ''): ?>
                                    <div class="admin-link-row">
                                        <a class="admin-link" href="<?= e($href) ?>" title="<?= e($item['label']) ?>"<?= $href === $active ? ' aria-current="page"' : '' ?>>
                                            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#<?= e($item['icon']) ?>"/></svg>
                                            <span class="admin-link-label"><?= e($item['label']) ?></span>
                                        </a>
                                        <button type="button"
                                                class="admin-group-toggle admin-caret<?= $childActive ? ' is-open' : '' ?>"
                                                id="<?= e($groupId) ?>-btn"
                                                aria-expanded="<?= $childActive ? 'true' : 'false' ?>"
                                                aria-controls="<?= e($groupId) ?>"
                                                aria-label="<?= e($item['label']) ?> — list">
                                            <svg class="admin-chevron" viewBox="0 0 24 24" aria-hidden="true"><use href="#a-chevron"/></svg>
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <button type="button"
                                            class="admin-link admin-group-toggle<?= $childActive ? ' is-open' : '' ?>"
                                            title="<?= e($item['label']) ?>"
                                            id="<?= e($groupId) ?>-btn"
                                            aria-expanded="<?= $childActive ? 'true' : 'false' ?>"
                                            aria-controls="<?= e($groupId) ?>">
                                        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#<?= e($item['icon']) ?>"/></svg>
                                        <span class="admin-link-label"><?= e($item['label']) ?></span>
                                        <span class="admin-badge" data-live-badge="all"<?= $badgeSum > 0 ? '' : ' hidden' ?>><?= $badgeSum ?></span>
                                        <svg class="admin-chevron" viewBox="0 0 24 24" aria-hidden="true"><use href="#a-chevron"/></svg>
                                    </button>
                                <?php endif; ?>
                                <ul class="admin-sublinks" id="<?= e($groupId) ?>"<?= $childActive ? '' : ' hidden' ?>>
                                    <?php foreach ($item['children'] as $childFile => $child): ?>
                                        <?php $childIsActive = $childFile === $active; ?>
                                        <li<?= $childIsActive ? ' class="is-active"' : '' ?>>
                                            <a class="admin-sublink<?= $childIsActive ? ' is-active' : '' ?>"
                                               href="<?= e($childFile) ?>"<?= $childIsActive ? ' aria-current="page"' : '' ?>>
                                                <svg class="icon-sub" viewBox="0 0 24 24" aria-hidden="true"><use href="#<?= e($child['icon']) ?>"/></svg>
                                                <span><?= e($child['label']) ?></span>
                                                <?php if (!empty($child['badge'])): $childCount = (int) ($badges[$child['badge']] ?? 0); ?>
                                                    <span class="admin-badge" data-live-badge="<?= e($child['badge']) ?>"<?= $childCount > 0 ? '' : ' hidden' ?>><?= $childCount ?></span>
                                                <?php endif; ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </li>
                        <?php else: ?>
                            <?php $isActive = $file === $active; ?>
                            <li<?= $isActive ? ' class="is-active"' : '' ?>>
                                <a class="admin-link<?= $isActive ? ' is-active' : '' ?>"
                                   href="<?= e($file) ?>" title="<?= e($item['label']) ?>"<?= $isActive ? ' aria-current="page"' : '' ?>>
                                    <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#<?= e($item['icon']) ?>"/></svg>
                                    <span class="admin-link-label"><?= e($item['label']) ?></span>
                                    <?php if (!empty($item['badge'])): $itemCount = (int) ($badges[$item['badge']] ?? 0); ?>
                                        <span class="admin-badge" data-live-badge="<?= e($item['badge']) ?>"<?= $itemCount > 0 ? '' : ' hidden' ?>><?= $itemCount ?></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </nav>

            <div class="admin-side-user">
                <span class="admin-avatar" aria-hidden="true"><?= e($initial !== '' ? $initial : 'A') ?></span>
                <span class="who">
                    <strong><?= e($me['fullname'] !== '' ? $me['fullname'] : $me['email']) ?></strong>
                    <span><?= e($me['email']) ?></span>
                </span>
                <span class="admin-side-actions">
                    <a class="icon-btn" href="../index.php" aria-label="View site" title="View site">
                        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#a-external"/></svg>
                    </a>
                    <a class="icon-btn" href="../public/logout.php" aria-label="Logout" title="Logout">
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
                <a class="admin-topbar-brand" href="index.php" aria-label="Repdoc Admin — Dashboard">
                    <img src="assets/image/logo.png" alt="Repdoc Admin" height="30">
                </a>
                <p class="admin-crumb">Admin <span aria-hidden="true">/</span> <?= e($title) ?></p>
                <div class="admin-topbar-right">
                    <?php if (($badges['users'] + $badges['entrepreneur']) > 0): ?>
                        <a class="pending-pill" href="users.php?status=pending" data-live-pending-pill
                           title="<?= (int) ($badges['users'] + $badges['entrepreneur']) ?> items awaiting review">
                            <span class="admin-badge" data-live-badge="all"><?= (int) ($badges['users'] + $badges['entrepreneur']) ?></span>
                            <span>pending</span>
                        </a>
                    <?php endif; ?>
                    <span class="admin-topbar-user" title="<?= e($me['email']) ?>">
                        <span class="admin-avatar" aria-hidden="true"><?= e($initial !== '' ? $initial : 'A') ?></span>
                        <span class="who"><strong><?= e($me['fullname'] !== '' ? $me['fullname'] : $me['email']) ?></strong></span>
                    </span>
                    <a class="icon-btn" href="../public/logout.php" aria-label="Logout" title="Logout">
                        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#a-login"/></svg>
                    </a>
                </div>
            </header>

            <main class="admin-main">
                <div class="container">
    <?php
}

function admin_page_end(): void
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

        // Collapsible nav groups (the Logs group). The group holding the
        // current page starts open; toggling only changes this panel.
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
            try { localStorage.setItem('repdoc-admin-rail', rail ? '1' : '0'); } catch (e) {}
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

    <script>
    /* Persistent panel actions: a form marked data-live posts in the background
       and the row / counters / badges update in place, with a toast for the
       result. The page fully reloads only when you switch sections. */
    (function () {
        var toasts = document.getElementById('adminToasts');

        function toast(message, isError) {
            if (!toasts || !message) return;
            var el = document.createElement('div');
            el.className = 'admin-toast' + (isError ? ' is-error' : '');
            el.setAttribute('role', isError ? 'alert' : 'status');
            el.textContent = message;
            toasts.appendChild(el);
            setTimeout(function () { el.classList.add('is-out'); }, 3400);
            setTimeout(function () { el.remove(); }, 4000);
        }

        function closeMenus() {
            document.querySelectorAll('.action-menu:not([hidden])').forEach(function (card) {
                card.hidden = true;
                var owner = document.querySelector('[data-action-menu][aria-controls="' + card.id + '"]');
                if (owner) owner.setAttribute('aria-expanded', 'false');
            });
        }

        function apply(res) {
            if (res.remove) document.querySelectorAll(res.remove).forEach(function (el) { el.remove(); });

            if (res.replace && res.replace.selector) {
                var host = document.querySelector(res.replace.selector);
                if (host) host.outerHTML = res.replace.html;
            }

            if (res.prepend && res.prepend.selector) {
                var list = document.querySelector(res.prepend.selector);
                if (list) list.insertAdjacentHTML('afterbegin', res.prepend.html);
            }

            if (res.reset) {
                var form = document.querySelector(res.reset);
                if (form) form.reset();
            }

            if (res.stats) {
                Object.keys(res.stats).forEach(function (key) {
                    document.querySelectorAll('[data-live-stat="' + key + '"]').forEach(function (node) {
                        node.textContent = res.stats[key];
                    });
                });
            }

            if (res.badges) {
                Object.keys(res.badges).forEach(function (key) {
                    var value = parseInt(res.badges[key], 10) || 0;
                    document.querySelectorAll('[data-live-badge="' + key + '"]').forEach(function (badge) {
                        badge.textContent = value;
                        badge.hidden = value <= 0;
                    });
                });
            }

            if (typeof res.pending === 'number') {
                document.querySelectorAll('[data-live-pending-pill]').forEach(function (pill) {
                    pill.hidden = res.pending <= 0;
                });
            }

            closeMenus();
        }

        document.addEventListener('submit', function (ev) {
            var form = ev.target;
            if (!form.matches || !form.matches('form[data-live]') || ev.defaultPrevented) return;
            ev.preventDefault();

            var data = new FormData(form);
            var submitter = ev.submitter || null;
            if (submitter && submitter.name) data.set(submitter.name, submitter.value);

            var btn = submitter || form.querySelector('[type="submit"]');
            if (btn) btn.disabled = true;
            form.classList.add('is-busy');

            fetch(form.getAttribute('action') || location.pathname, {
                method: 'POST',
                body: data,
                credentials: 'same-origin',
                headers: { 'X-Repdoc-Live': '1', 'Accept': 'application/json' }
            })
            .then(function (response) {
                return response.text().then(function (text) {
                    var json = null;
                    try { json = JSON.parse(text); } catch (err) { json = null; }
                    return json;
                });
            })
            .then(function (json) {
                if (!json) {
                    toast('Unexpected reply from the server — reloading the page.', true);
                    setTimeout(function () { location.reload(); }, 1200);
                    return;
                }
                apply(json);
                toast(json.message || 'Saved.', json.ok === false);
            })
            .catch(function () {
                toast('Connection problem — nothing was changed.', true);
            })
            .finally(function () {
                if (btn) btn.disabled = false;
                form.classList.remove('is-busy');
            });
        });

        /* Full-row action menu: one Actions button opens a popup with every
           action for that row. Delegated, so rows swapped in by a live action
           keep working. */
        function placeMenu(card, owner) {
            var rect = owner.getBoundingClientRect();
            var width = Math.min(300, window.innerWidth - 16);
            card.style.width = width + 'px';
            card.style.left = Math.max(8, Math.min(rect.left, window.innerWidth - width - 8)) + 'px';
            var top = rect.bottom + 6;
            var height = card.offsetHeight || 300;
            if (top + height > window.innerHeight - 8) top = Math.max(8, rect.top - height - 6);
            card.style.top = top + 'px';
        }

        function openMenu() {
            var card = document.querySelector('.action-menu:not([hidden])');
            if (!card) return null;
            var owner = document.querySelector('[data-action-menu][aria-controls="' + card.id + '"]');
            return owner ? { card: card, owner: owner } : null;
        }

        function repositionMenus() {
            var open = openMenu();
            if (open) placeMenu(open.card, open.owner);
        }

        document.addEventListener('click', function (ev) {
            var trigger = ev.target.closest ? ev.target.closest('[data-action-menu]') : null;
            if (trigger) {
                ev.preventDefault();
                var card = document.getElementById(trigger.getAttribute('aria-controls'));
                if (!card) return;
                var willOpen = card.hidden;
                closeMenus();
                card.hidden = !willOpen;
                trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                if (willOpen) {
                    placeMenu(card, trigger);
                    var first = card.querySelector('input');
                    if (first) first.focus();
                }
                return;
            }
            if (!ev.target.closest || !ev.target.closest('.action-menu')) closeMenus();
        });

        document.addEventListener('keydown', function (ev) {
            if (ev.key === 'Escape') closeMenus();
        });

        document.addEventListener('focusin', function (ev) {
            var open = openMenu();
            if (open && !open.card.contains(ev.target)
                && !(ev.target.closest && ev.target.closest('[data-action-menu]'))) {
                closeMenus();
            }
        });

        window.addEventListener('scroll', repositionMenus, { capture: true, passive: true });
        window.addEventListener('resize', repositionMenus);
    })();
    </script>
</body>
</html>
    <?php
}

/** Render a flash/notice alert once. */
function admin_notice(): void
{
    $flash = flash_get();
    if (!$flash) return;

    $map = ['ok' => 'alert-success', 'error' => 'alert-error', 'warn' => 'alert-warning'];
    $class = $map[$flash['type']] ?? 'alert-info';
    echo '<div class="alert ' . $class . '" role="status">' . e((string) $flash['message']) . '</div>';
}
