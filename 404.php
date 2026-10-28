<?php
declare(strict_types=1);

/**
 * Repdoc — 404 page.
 *
 * Served by Apache via .htaccess (ErrorDocument 404) and safe to open
 * directly. Works without a database: role links degrade gracefully.
 */

http_response_code(404);

require_once __DIR__ . '/src/bootstrap.php';

$home = repdoc_url('/');
$dashUrl = is_logged_in() ? repdoc_panel_home() : repdoc_url('public/login/');
$dashLabel = is_logged_in() ? 'ড্যাশবোর্ড' : 'লগইন';
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>পাতা পাওয়া যায়নি (404) — Repdoc</title>
    <meta name="robots" content="noindex">
    <link rel="icon" type="image/png" href="<?= e(repdoc_url('public/favicon.png')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Bengali:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/tokens.css?v=2')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/app.css?v=1')) ?>">
</head>
<body class="auth-body">
    <main class="auth-card" style="text-align:center">
        <a class="auth-brand" href="<?= e($home) ?>" style="justify-content:center">
            <img src="<?= e(repdoc_url('src/assets/image/logo.png')) ?>" alt="" width="96" height="32">
            <span><strong>Repdoc</strong><small>repdoc.rf.gd · union toolkit</small></span>
        </a>
        <p class="auth-kicker" style="font-family:var(--font-mono)">REG / 404</p>
        <h1>পাতাটি পাওয়া যায়নি</h1>
        <p class="auth-sub">The page you're looking for doesn't exist or was moved.</p>
        <div class="form-actions" style="justify-content:center">
            <a class="btn btn-primary" href="<?= e($home) ?>">হোমপেজ</a>
            <a class="btn btn-secondary" href="<?= e($dashUrl) ?>"><?= e($dashLabel) ?></a>
        </div>
    </main>

    <p class="auth-sub" style="text-align:center">
        Independent tools site — not a government portal.
    </p>
</body>
</html>
