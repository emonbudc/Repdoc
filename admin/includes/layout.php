<?php
declare(strict_types=1);

/**
 * Repdoc — admin panel layout.
 * Shared header / navigation / footer so every admin page stays consistent.
 */

require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
require_admin();

/** Navigation entries: file => [label, icon, optional pending-count key]. */
function admin_nav_items(): array
{
    return [
        'index.php'        => ['label' => 'Dashboard',         'icon' => 'a-grid'],
        'users.php'        => ['label' => 'Users & Balance',   'icon' => 'a-users', 'badge' => 'users'],
        'entrepreneur.php' => ['label' => 'Entrepreneur Apps', 'icon' => 'a-id',    'badge' => 'entrepreneur'],
        '../Entrepreneur/panel/landing.php' => ['label' => 'Document Archive', 'icon' => 'a-file'],
        'tools.php'        => ['label' => 'Tools',             'icon' => 'a-layout'],
        '../tools/passport/' => ['label' => 'Passport Photo', 'icon' => 'a-id'],
        'files.php'        => ['label' => 'File Packs',        'icon' => 'a-file'],
        'documents.php'    => ['label' => 'Document Logs',   'icon' => 'a-id'],
        'prottoyon.php'    => ['label' => 'Prottoyon Logs',  'icon' => 'a-file'],
        'transactions.php' => ['label' => 'Transactions',      'icon' => 'a-wallet'],
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Bengali:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../src/assets/css/tokens.css?v=1">
    <link rel="stylesheet" href="../src/assets/css/app.css?v=1">
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
                        <?php $isActive = $file === $active; ?>
                        <li>
                            <a class="admin-link<?= $isActive ? ' is-active' : '' ?>"
                               href="<?= e($file) ?>"<?= $isActive ? ' aria-current="page"' : '' ?>>
                                <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#<?= e($item['icon']) ?>"/></svg>
                                <span class="admin-link-label"><?= e($item['label']) ?></span>
                                <?php if (!empty($item['badge']) && ($badges[$item['badge']] ?? 0) > 0): ?>
                                    <span class="admin-badge"><?= (int) $badges[$item['badge']] ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
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
                <a class="admin-topbar-brand" href="index.php" aria-label="Repdoc Admin — Dashboard">
                    <img src="assets/image/logo.png" alt="Repdoc Admin" height="30">
                </a>
                <p class="admin-crumb">Admin <span aria-hidden="true">/</span> <?= e($title) ?></p>
                <div class="admin-topbar-right">
                    <?php if (($badges['users'] + $badges['entrepreneur']) > 0): ?>
                        <a class="pending-pill" href="users.php?status=pending"
                           title="<?= (int) ($badges['users'] + $badges['entrepreneur']) ?> items awaiting review">
                            <span class="admin-badge"><?= (int) ($badges['users'] + $badges['entrepreneur']) ?></span>
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
        window.addEventListener('resize', function () {
            if (window.innerWidth >= 1024) setOpen(false);
        });
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
