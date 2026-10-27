<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/src/bootstrap.php';
require_once dirname(__DIR__, 3) . '/src/nav.php';

function prottoyon_flash_html(): string
{
    $flash = flash_get();
    if (!$flash) return '';
    $type = ($flash['type'] ?? '') === 'ok' ? 'success' : 'error';
    return '<div class="pp-alert pp-alert-' . $type . '" role="status">' . e((string) ($flash['message'] ?? '')) . '</div>';
}

function prottoyon_page_start(string $title, string $active = ''): void
{
    $me = current_user();
    $balance = $me ? user_balance((int) $me['id']) : 0;
    $isAdmin = $me && ($me['role'] ?? '') === 'admin';
    $canOffice = $me && (is_udc() || $isAdmin);
    ?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FFFEFB">
    <meta name="robots" content="noindex">
    <title><?= e($title) ?> — Repdoc</title>
    <link rel="icon" type="image/png" href="<?= e(repdoc_url('public/favicon.png')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Bengali:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/tokens.css?v=2')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/shell.css?v=2')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/home.css?v=4')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('genaretor/prottoyon/assets/css/prottoyon.css?v=2')) ?>">
</head>
<body class="pp-page">
    <a class="skip-link" href="#ppMain">মূল কন্টেন্টে যান</a>
    <svg class="sprite" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <g id="pp-i-menu"><line x1="4" y1="7" x2="20" y2="7"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="17" x2="20" y2="17"/></g>
            <g id="pp-i-x"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></g>
            <g id="pp-i-wallet"><path d="M3 7.5A2.5 2.5 0 0 1 5.5 5h11A2.5 2.5 0 0 1 19 7.5"/><rect x="3" y="7.5" width="18" height="12" rx="2"/><circle cx="16.5" cy="13.5" r="1.2"/></g>
            <g id="pp-i-settings"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-2.9 1.2V21a2 2 0 1 1-4 0v-.1A1.7 1.7 0 0 0 6.1 19l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1A1.7 1.7 0 0 0 3 13.3H3a2 2 0 1 1 0-4h.1A1.7 1.7 0 0 0 4.6 6.1l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1A1.7 1.7 0 0 0 10 3.6V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 2.9 1.2l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0 1.2 2.9H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/></g>
            <g id="pp-i-logout"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></g>
        </defs>
    </svg>

    <header class="site-header">
        <div class="container header-inner">
            <a class="brand" href="<?= e(repdoc_url('/')) ?>" aria-label="Repdoc — হোম">
                <img class="brand-logo" src="<?= e(repdoc_url('src/assets/image/logo.png')) ?>" alt="" width="38" height="38">
                <span class="brand-text"><strong>Repdoc</strong><small>প্রত্যয়ন</small></span>
            </a>
            <?php repdoc_render_site_nav('prottoyon'); ?>
            <div class="header-actions">
                <span class="user-chip" title="ক্রেডিট ব্যালেন্স">
                    <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#pp-i-wallet"/></svg>
                    <span class="balance"><?= prottoyon_bn_digits((string) $balance) ?></span>
                </span>
                <?php if ($isAdmin): ?>
                    <a class="btn btn-secondary btn-login" href="<?= e(repdoc_url('admin/')) ?>">
                        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#pp-i-settings"/></svg><span>অ্যাডমিন</span>
                    </a>
                <?php endif; ?>
                <a class="btn btn-primary btn-login" href="<?= e(repdoc_url('public/logout.php')) ?>" aria-label="লগআউট">
                    <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#pp-i-logout"/></svg><span>লগআউট</span>
                </a>
                <button type="button" class="icon-btn nav-toggle" id="navToggle" aria-label="মেনু খুলুন" aria-expanded="false" aria-controls="siteNav">
                    <svg class="icon nav-toggle-open" viewBox="0 0 24 24" aria-hidden="true"><use href="#pp-i-menu"/></svg>
                    <svg class="icon nav-toggle-close" viewBox="0 0 24 24" aria-hidden="true"><use href="#pp-i-x"/></svg>
                </button>
            </div>
        </div>
    </header>

    <nav class="pp-subnav" aria-label="প্রত্যয়ন মেনু">
        <div class="container pp-subnav-inner">
            <a class="pp-nav-link<?= $active === 'home' ? ' is-active' : '' ?>" href="<?= e(repdoc_url('genaretor/prottoyon/')) ?>">সনদ বাছাই</a>
            <a class="pp-nav-link<?= $active === 'applications' ? ' is-active' : '' ?>" href="<?= e(repdoc_url('genaretor/prottoyon/applications.php')) ?>">আমার আবেদন</a>
            <a class="pp-nav-link<?= $active === 'verify' ? ' is-active' : '' ?>" href="<?= e(repdoc_url('verify/')) ?>">যাচাই</a>
            <?php if ($canOffice): ?>
                <a class="pp-nav-link<?= $active === 'office' ? ' is-active' : '' ?>" href="<?= e(repdoc_url('genaretor/prottoyon/office.php')) ?>">অফিস সেটিংস</a>
            <?php endif; ?>
            <a class="pp-nav-link pp-nav-cta<?= $active === 'form' ? ' is-active' : '' ?>" href="<?= e(repdoc_url('genaretor/prottoyon/')) ?>">+ নতুন আবেদন</a>
        </div>
    </nav>

    <main class="pp-main container" id="ppMain">
        <?= prottoyon_flash_html() ?>
<?php
}

function prottoyon_page_end(): void
{
    ?>
    </main>

    <footer class="site-footer">
        <div class="container footer-bottom">
            <span>Repdoc — প্রত্যয়ন পত্র ব্যবস্থাপনা</span>
            <span><a class="back-top" href="<?= e(repdoc_url('/')) ?>">হোমে ফিরুন ↑</a></span>
        </div>
    </footer>
    <script src="<?= e(repdoc_url('src/assets/js/home.js?v=1')) ?>"></script>
    <script src="<?= e(repdoc_url('genaretor/prottoyon/assets/js/prottoyon.js?v=2')) ?>"></script>
</body>
</html>
<?php
}
