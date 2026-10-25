<?php
declare(strict_types=1);

// Central auth + wallet — same as genaretor/nid (replaces the old
// per-tool auth: ../config/database.php + ../customer/index.php no longer exist).
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
require_login();

$repdocUser    = current_user();
$repdocBalance = user_balance($repdocUser['id']);
$repdocPrice   = tool_price('smartnid');
$repdocIsAdmin = $repdocUser['role'] === 'admin';
?>
<!DOCTYPE html>
<html lang="bn">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="স্মার্ট জাতীয় পরিচয়পত্র (Smart NID) ফর্ম — তথ্য দিন, প্রিন্ট-রেডি কার্ড তৈরি করুন।">
    <meta name="theme-color" content="#F8FAFC">
    <title>Smart NID — Repdoc</title>
    <meta name="robots" content="noindex">
    <link rel="icon" type="image/png" href="<?= e(repdoc_url('public/favicon.png')) ?>">
    <link rel="apple-touch-icon" href="<?= e(repdoc_url('public/favicon.png')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Bengali:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/tokens.css?v=1')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/shell.css?v=1')) ?>">
    <!-- Apply the saved theme before paint so there is no flash of the wrong theme. -->
    <script>
        (function () {
            try {
                if (localStorage.getItem('siteTheme') === 'dark') {
                    document.documentElement.setAttribute('data-theme', 'dark');
                }
            } catch (e) {}
        })();
    </script>
    <noscript><style>.nid-card { opacity: 1 !important; transform: none !important; }</style></noscript>
    <style>
        /* ── Repdoc NID form — page extras on top of the shared
           tokens.css + shell.css (same header as genaretor/birth).
           MASTER: design-system/repdoc/MASTER.md. */
        :root {
            --on-primary: #FFFFFF;
            --ring: #0369A1;
            --success-bg: #ECFDF5;
            --success-border: #6EE7B7;
            --success-text: #065F46;
            --error-bg: #FEF2F2;
            --error-border: #FCA5A5;
            --error-text: #991B1B;
            --shadow: 0 1px 2px rgba(15, 23, 42, .06);
            --shadow-hover: 0 8px 24px rgba(15, 23, 42, .10);
        }
        [data-theme="dark"] {
            --on-primary: #0B0F19;
            --ring: #38BDF8;
            --success-bg: rgba(52, 211, 153, .12);
            --success-border: #065F46;
            --success-text: #A7F3D0;
            --error-bg: rgba(251, 113, 133, .10);
            --error-border: #7F1D1D;
            --error-text: #FECDD3;
            --shadow: 0 1px 2px rgba(0, 0, 0, .4);
            --shadow-hover: 0 8px 24px rgba(0, 0, 0, .5);
        }

        *, *::before, *::after { box-sizing: border-box; }

        body.nid-page {
            margin: 0;
            font-family: var(--font-sans);
            font-size: 16px;
            line-height: 1.6;
            color: var(--text-primary);
            background: var(--bg-base);
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

        /* Visible keyboard focus everywhere (shared :focus-visible lives in tokens.css) */
        .nid-page :focus-visible {
            outline: 3px solid var(--ring);
            outline-offset: 2px;
            border-radius: 6px;
        }

        .nid-wrap {
            max-width: 960px;
            margin: 0 auto;
            padding: 32px 20px 64px;
        }
        .nid-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 44px;
            padding: 10px 18px;
            border-radius: var(--radius-sm);
            font: inherit;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            border: 1.5px solid var(--border-color);
            background: var(--bg-surface);
            color: var(--text-primary);
            transition: background var(--transition-fast) ease, border-color var(--transition-fast) ease, transform var(--transition-fast) ease;
        }
        .nid-btn:hover { border-color: var(--primary); }
        .nid-btn svg { width: 18px; height: 18px; flex: none; }
        .nid-btn-primary {
            background: var(--primary);
            border-color: var(--primary);
            color: var(--on-primary);
        }
        .nid-btn-primary:hover { background: var(--primary-hover); border-color: var(--primary-hover); }

        /* ── Error ── */
        .nid-error {
            background: var(--error-bg);
            border: 1px solid var(--error-border);
            color: var(--error-text);
            border-radius: var(--radius-md);
            padding: 14px 16px;
            margin: 0 0 24px;
            font-size: 15px;
            font-weight: 500;
        }
        .nid-error a { color: inherit; font-weight: 700; }

        /* ── Cards / sections ── */
        .nid-card {
            background: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            padding: 24px;
            margin-bottom: 24px;
            scroll-margin-top: 84px;
            opacity: 0;
            transform: translateY(16px);
            transition: opacity var(--transition-normal) ease, transform var(--transition-normal) ease, box-shadow var(--transition-normal) ease;
        }
        .nid-card.is-visible { opacity: 1; transform: none; }
        .nid-card:hover { box-shadow: var(--shadow-hover); }
        .nid-card-head {
            margin-bottom: 4px;
        }
        .nid-card h2 { font-size: 19px; font-weight: 700; margin: 0; letter-spacing: -.01em; }
        .nid-card-sub { color: var(--text-muted); font-size: 14px; margin: 4px 0 20px; }

        /* ── Fields ── */
        .nid-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .nid-grid-3 { grid-template-columns: repeat(3, 1fr); }
        .nid-field { display: flex; flex-direction: column; min-width: 0; }
        .nid-field-full { grid-column: 1 / -1; }
        .nid-field label, .nid-field legend {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-primary);
        }
        .nid-field fieldset { border: 0; padding: 0; margin: 0; min-width: 0; }
        .nid-req { color: var(--destructive); font-weight: 700; }
        .nid-field input[type="text"] {
            font: inherit;
            font-size: 16px;
            color: var(--text-primary);
            background: var(--bg-base);
            border: 1.5px solid var(--border-color);
            border-radius: var(--radius-sm);
            padding: 12px 14px;
            min-height: 48px;
            width: 100%;
            transition: border-color var(--transition-fast) ease, box-shadow var(--transition-fast) ease;
        }
        .nid-field input::placeholder { color: var(--text-muted); opacity: .75; }
        .nid-field input:hover { border-color: var(--text-muted); }
        .nid-field input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-light);
        }
        .nid-hint { font-size: 13px; color: var(--text-muted); margin-top: 6px; }

        /* Segmented radios */
        .nid-seg { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .nid-seg-opt span {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 52px;
            padding: 12px;
            border: 1.5px solid var(--border-color);
            border-radius: var(--radius-sm);
            background: var(--bg-base);
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all var(--transition-fast) ease;
        }
        .nid-seg-opt span:hover { border-color: var(--primary); }
        .nid-seg-opt input:checked + span {
            border-color: var(--primary);
            background: var(--primary);
            color: var(--on-primary);
            box-shadow: var(--shadow);
        }
        .nid-seg-opt input:focus-visible + span {
            outline: 3px solid var(--ring);
            outline-offset: 2px;
        }

        /* ── Upload cards ── */
        .nid-upload-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .nid-upload {
            border: 2px dashed var(--border-color);
            border-radius: var(--radius-md);
            background: var(--bg-base);
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            min-height: 250px;
            transition: border-color var(--transition-fast) ease, box-shadow var(--transition-fast) ease;
        }
        .nid-upload:hover { border-color: var(--primary); }
        .nid-upload.is-drag { border-color: var(--primary); background: var(--primary-light); }
        .nid-upload.has-file { border-style: solid; border-color: var(--primary); }
        .nid-upload-preview {
            flex: 1;
            min-height: 120px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            color: var(--text-muted);
            font-size: 14px;
            overflow: hidden;
            position: relative;
        }
        .nid-upload-preview svg { width: 36px; height: 36px; }
        .nid-upload-preview img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
            background: var(--bg-surface);
        }
        .nid-upload-row { display: flex; gap: 8px; align-items: stretch; }
        .nid-upload-btn {
            flex: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 44px;
            padding: 10px 14px;
            border-radius: var(--radius-sm);
            background: var(--primary);
            color: var(--on-primary);
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: background var(--transition-fast) ease;
            text-align: center;
        }
        .nid-upload-btn:hover { background: var(--primary-hover); }
        .nid-upload-btn svg { width: 18px; height: 18px; flex: none; }
        .nid-upload-clear {
            flex: none;
            width: 44px;
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-sm);
            border: 1.5px solid var(--border-color);
            background: var(--bg-surface);
            color: var(--text-muted);
            cursor: pointer;
        }
        .nid-upload-clear:hover { color: var(--destructive); border-color: var(--destructive); }
        .nid-upload-clear svg { width: 18px; height: 18px; }
        .nid-upload-name {
            margin: 0;
            font-size: 13px;
            color: var(--text-muted);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* ── Submit ── */
        .nid-submit-card { text-align: center; }
        .nid-price-note { color: var(--text-muted); font-size: 15px; margin: 0 0 16px; }
        .nid-price-note strong { color: var(--text-primary); }
        .nid-submit {
            width: 100%;
            min-height: 52px;
            font-size: 17px;
            font-weight: 700;
        }
        .nid-submit[disabled] { opacity: .7; cursor: wait; }

        /* ── Footer (tools/birthsearch standard, Repdoc token mapped) ── */
        .app-footer {
            background: var(--bg-surface);
            border-top: 1px solid var(--border-color);
            padding: 14px 16px;
            margin-top: auto;
        }
        .app-footer-inner { max-width: 960px; margin: 0 auto; }
        .app-footer p { font-size: 13px; color: var(--text-muted); text-align: center; margin: 0; }
        .app-footer a { color: var(--primary); font-weight: 600; }

        .nid-visually-hidden {
            position: absolute !important;
            width: 1px; height: 1px;
            margin: -1px; padding: 0; border: 0;
            clip: rect(0 0 0 0); clip-path: inset(50%);
            overflow: hidden; white-space: nowrap;
        }
        /* Keep radio focusable for keyboard + screen readers */
        .nid-seg-opt input.nid-visually-hidden:focus-visible + span {
            outline: 3px solid var(--ring);
            outline-offset: 2px;
        }

        @media (max-width: 640px) {
            .nid-grid, .nid-grid-3, .nid-upload-grid { grid-template-columns: 1fr; }
            .nid-card { padding: 18px; }
            .nid-wrap { padding: 24px 16px 48px; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation: none !important;
                transition: none !important;
                scroll-behavior: auto !important;
            }
            .nid-card { opacity: 1; transform: none; }
            html { scroll-behavior: auto !important; }
        }
        .nid-field select {
            font: inherit;
            font-size: 16px;
            color: var(--text-primary);
            background: var(--bg-base);
            border: 1.5px solid var(--border-color);
            border-radius: var(--radius-sm);
            padding: 12px 14px;
            min-height: 48px;
            width: 100%;
            transition: border-color var(--transition-fast) ease, box-shadow var(--transition-fast) ease;
        }
        .nid-field select:hover { border-color: var(--text-muted); }
        .nid-field select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-light);
        }
    </style>
</head>
<body class="nid-page">
    <a class="skip-link" href="#nidForm">ফর্মে যান (Skip to form)</a>

    <!-- Inline SVG sprite: birth header icons (i-*) + NID form icons (nid-i-*). -->
    <svg class="sprite" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <g id="i-sun"><circle cx="12" cy="12" r="4"/><line x1="12" y1="1.5" x2="12" y2="4"/><line x1="12" y1="20" x2="12" y2="22.5"/><line x1="3.5" y1="12" x2="6" y2="12"/><line x1="18" y1="12" x2="20.5" y2="12"/><line x1="5.6" y1="5.6" x2="7.4" y2="7.4"/><line x1="16.6" y1="16.6" x2="18.4" y2="18.4"/><line x1="5.6" y1="18.4" x2="7.4" y2="16.6"/><line x1="16.6" y1="7.4" x2="18.4" y2="5.6"/></g>
            <g id="i-moon"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></g>
            <g id="i-settings"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-2.9 1.2V21a2 2 0 1 1-4 0v-.1A1.7 1.7 0 0 0 6.1 19l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1A1.7 1.7 0 0 0 3 13.3H3a2 2 0 1 1 0-4h.1A1.7 1.7 0 0 0 4.6 6.1l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1A1.7 1.7 0 0 0 10 3.6V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 2.9 1.2l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0 1.2 2.9H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/></g>
            <g id="i-logout"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></g>
            <g id="i-menu"><line x1="4" y1="7" x2="20" y2="7"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="17" x2="20" y2="17"/></g>
            <g id="i-x"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></g>
            <g id="i-wallet"><path d="M3 7.5A2.5 2.5 0 0 1 5.5 5h11A2.5 2.5 0 0 1 19 7.5"/><rect x="3" y="7.5" width="18" height="12" rx="2"/><circle cx="16.5" cy="13.5" r="1.2"/></g>
            <symbol id="nid-i-photo" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></symbol>
            <symbol id="nid-i-pen" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.8 2.8 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5z"/></symbol>
            <symbol id="nid-i-x" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></symbol>
            <symbol id="nid-i-check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></symbol>
        </defs>
    </svg>

    <!-- ─── Header (genaretor/birth পেজের same) ─── -->
    <header class="site-header">
        <div class="container header-inner">
            <a class="brand" href="<?= e(repdoc_url('/')) ?>" aria-label="Repdoc — হোম">
                <img class="brand-logo" src="<?= e(repdoc_url('src/assets/image/logo.png')) ?>" alt="" width="34" height="34">
                <span class="brand-text">
                    <strong>Repdoc</strong>
                    <small>স্মার্ট জাতীয় পরিচয়পত্র</small>
                </span>
            </a>

            <nav class="site-nav" id="siteNav" aria-label="প্রধান নেভিগেশন">
                <a class="nav-link" href="<?= e(repdoc_url('/')) ?>">হোম</a>
                <a class="nav-link" href="<?= e(repdoc_url('/')) ?>#tools">সব টুল</a>
            </nav>

            <div class="header-actions">
                <button type="button" class="icon-btn theme-toggle" id="themeToggle" onclick="toggleTheme()"
                        aria-label="থিম পরিবর্তন" title="থিম পরিবর্তন">
                    <svg class="icon icon-sun" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-sun"/></svg>
                    <svg class="icon icon-moon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-moon"/></svg>
                    <span class="sr-only" id="themeToggleLabel">Dark</span>
                </button>

                <span class="user-chip" title="ক্রেডিট ব্যালেন্স — <?= e($repdocUser['fullname'] !== '' ? $repdocUser['fullname'] : $repdocUser['email']) ?>">
                    <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-wallet"/></svg>
                    <span class="balance"><?= (int) $repdocBalance ?></span>
                </span>

                <?php if ($repdocIsAdmin): ?>
                    <a class="btn btn-secondary btn-login" href="<?= e(repdoc_url('admin/')) ?>">
                        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-settings"/></svg>
                        <span>অ্যাডমিন</span>
                    </a>
                <?php endif; ?>

                <a class="btn btn-primary btn-login" href="<?= e(repdoc_url('public/logout.php')) ?>" aria-label="লগআউট">
                    <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-logout"/></svg>
                    <span>লগআউট</span>
                </a>

                <button type="button" class="icon-btn nav-toggle" onclick="toggleNav()"
                        aria-label="মেনু খুলুন" aria-expanded="false" aria-controls="siteNav">
                    <svg class="icon nav-toggle-open" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-menu"/></svg>
                    <svg class="icon nav-toggle-close" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
                </button>
            </div>
        </div>
    </header>
    <div class="nid-wrap">
        <main>
            <form action="output.php" method="POST" enctype="multipart/form-data" id="nidForm">
                <section class="nid-card" id="sec-photo" aria-labelledby="t-photo" data-reveal>
                    <div class="nid-card-head"><h2 id="t-photo">ছবি ও স্বাক্ষর</h2></div>
                    <p class="nid-card-sub">JPG বা PNG ছবি দিন। কার্ডে যেমন দেখাবে, প্রিভিউতে তেমনই দেখুন।</p>
                    <div class="nid-upload-grid">
                        <div class="nid-upload" data-upload>
                            <div class="nid-upload-preview" id="photoPreview">
                                <svg aria-hidden="true"><use href="#nid-i-photo"/></svg>
                                <span>ছবির প্রিভিউ</span>
                            </div>
                            <div class="nid-upload-row">
                                <label class="nid-upload-btn" for="photoInput">
                                    <svg aria-hidden="true"><use href="#nid-i-photo"/></svg>
                                    <span>ছবি বেছে নিন</span>
                                </label>
                                <button type="button" class="nid-upload-clear" id="photoClearBtn" aria-label="ছবি মুছে ফেলুন" hidden>
                                    <svg aria-hidden="true"><use href="#nid-i-x"/></svg>
                                </button>
                            </div>
                            <input class="nid-visually-hidden" type="file" name="photo" id="photoInput" accept="image/jpeg,image/png,image/jpg" required>
                            <p class="nid-upload-name" id="photoName" aria-live="polite">কোনো ফাইল বাছা হয়নি</p>
                            <p class="nid-hint">৩০০×৩০০ পিক্সেল · সর্বোচ্চ ২MB · সাদা/হালকা ব্যাকগ্রাউন্ড</p>
                        </div>
                        <div class="nid-upload" data-upload>
                            <div class="nid-upload-preview" id="signaturePreview">
                                <svg aria-hidden="true"><use href="#nid-i-pen"/></svg>
                                <span>স্বাক্ষরের প্রিভিউ</span>
                            </div>
                            <div class="nid-upload-row">
                                <label class="nid-upload-btn" for="signatureInput">
                                    <svg aria-hidden="true"><use href="#nid-i-pen"/></svg>
                                    <span>স্বাক্ষর বেছে নিন</span>
                                </label>
                                <button type="button" class="nid-upload-clear" id="signatureClearBtn" aria-label="স্বাক্ষর মুছে ফেলুন" hidden>
                                    <svg aria-hidden="true"><use href="#nid-i-x"/></svg>
                                </button>
                            </div>
                            <input class="nid-visually-hidden" type="file" name="signature" id="signatureInput" accept="image/jpeg,image/png,image/jpg" required>
                            <p class="nid-upload-name" id="signatureName" aria-live="polite">কোনো ফাইল বাছা হয়নি</p>
                            <p class="nid-hint">৩০০×৮০ পিক্সেল · সর্বোচ্চ ১MB · সাদা কাগজে কালো স্বাক্ষর</p>
                        </div>
                    </div>
                </section>

                <section class="nid-card" id="sec-personal" aria-labelledby="t-personal" data-reveal>
                    <div class="nid-card-head"><h2 id="t-personal">ব্যক্তিগত তথ্য</h2></div>
                    <p class="nid-card-sub">কার্ডে ঠিক যেমন ছাপা হবে, তেমন করে লিখুন।</p>
                    <div class="nid-grid">
                        <div class="nid-field">
                            <label for="f-name-bn">নাম (বাংলা) <span class="nid-req" aria-hidden="true">*</span></label>
                            <input type="text" id="f-name-bn" name="name_bn" placeholder="নাম বাংলায়" required autocomplete="name">
                        </div>
                        <div class="nid-field">
                            <label for="f-name-en">Name (English) <span class="nid-req" aria-hidden="true">*</span></label>
                            <input type="text" id="f-name-en" name="name_en" placeholder="Name in English (Uppercase Letters Only)" required autocomplete="name">
                        </div>
                        <div class="nid-field">
                            <label for="f-nidno">NID Number <span class="nid-req" aria-hidden="true">*</span></label>
                            <input type="text" id="f-nidno" name="nidno" placeholder="NID নম্বরঃ উদাহরণঃ 737 888 2182" required inputmode="numeric" autocomplete="off">
                        </div>
                        <div class="nid-field">
                            <label for="f-pin">PIN (for barcode) <span class="nid-req" aria-hidden="true">*</span></label>
                            <input type="text" id="f-pin" name="pin" placeholder="পিন নম্বর" required inputmode="numeric" autocomplete="off">
                        </div>
                        <div class="nid-field">
                            <label for="f-dob">Date of Birth <span class="nid-req" aria-hidden="true">*</span></label>
                            <input type="text" id="f-dob" name="dob" placeholder="জন্ম তারিখঃ উদাহরণঃ 18 Jan 2004" required autocomplete="bday">
                        </div>
                        <div class="nid-field">
                            <label for="f-blood">Blood Group <span class="nid-req" aria-hidden="true">*</span></label>
                            <select id="f-blood" name="blood_group" required>
                                <option value="A+">A+</option>
                                <option value="A-">A-</option>
                                <option value="B+">B+</option>
                                <option value="B-">B-</option>
                                <option value="AB+">AB+</option>
                                <option value="AB-">AB-</option>
                                <option value="O+">O+</option>
                                <option value="O-">O-</option>
                            </select>
                        </div>
                        <div class="nid-field">
                            <label for="f-gender">Gender <span class="nid-req" aria-hidden="true">*</span></label>
                            <select id="f-gender" name="gender" required>
                                <option value="M">Male</option>
                                <option value="F">Female</option>
                            </select>
                        </div>
                        <div class="nid-field">
                            <label for="f-pob">Place of Birth <span class="nid-req" aria-hidden="true">*</span></label>
                            <input type="text" id="f-pob" name="place_of_birth" placeholder="জন্মস্থান (ইংরেজিতে বড় হাতের অক্ষর ব্যবহার করুন)" required autocomplete="off">
                        </div>
                    </div>
                </section>

                <section class="nid-card" id="sec-family" aria-labelledby="t-family" data-reveal>
                    <div class="nid-card-head"><h2 id="t-family">পারিবারিক তথ্য</h2></div>
                    <p class="nid-card-sub">পিতা ও মাতার নাম লিখুন।</p>
                    <div class="nid-grid">
                        <div class="nid-field">
                            <label for="f-father">পিতার নাম <span class="nid-req" aria-hidden="true">*</span></label>
                            <input type="text" id="f-father" name="father_name" placeholder="পিতার নাম" required autocomplete="off">
                        </div>
                        <div class="nid-field">
                            <label for="f-mother">মাতার নাম <span class="nid-req" aria-hidden="true">*</span></label>
                            <input type="text" id="f-mother" name="mother_name" placeholder="মাতার নাম" required autocomplete="off">
                        </div>
                    </div>
                </section>

                <section class="nid-card" id="sec-extra" aria-labelledby="t-extra" data-reveal>
                    <div class="nid-card-head"><h2 id="t-extra">ঠিকানা</h2></div>
                    <p class="nid-card-sub">কার্ডের পেছনের অংশের ঠিকানা।</p>
                    <div class="nid-grid">
                        <div class="nid-field">
                            <label for="f-addr1">Address Line 1 <span class="nid-req" aria-hidden="true">*</span></label>
                            <input type="text" id="f-addr1" name="address1" placeholder="গ্রাম/রাস্তাঃ গ্রামের নাম, ডাকঘর - ১১১১" required autocomplete="street-address">
                        </div>
                        <div class="nid-field">
                            <label for="f-addr2">Address Line 2 <span class="nid-req" aria-hidden="true">*</span></label>
                            <input type="text" id="f-addr2" name="address2" placeholder="থানা/জেলা উদাহরণঃ নারায়ণগঞ্জ, ঢাকা" required autocomplete="off">
                        </div>
                    </div>
                </section>

                <section class="nid-card nid-submit-card" aria-labelledby="t-submit" data-reveal>
                    <h2 id="t-submit" class="nid-visually-hidden">জমা দিন</h2>
                    <p class="nid-price-note">
                        <?php if ($repdocPrice > 0 && !$repdocIsAdmin): ?>
                        প্রতিটি জমায় <strong><?= (int) $repdocPrice ?> ক্রেডিট</strong> কাটা হবে (ব্যালেন্স: <?= (int) $repdocBalance ?>)।
                        <?php else: ?>
                        ফর্ম জমা <strong>সম্পূর্ণ ফ্রি</strong>।
                        <?php endif; ?>
                    </p>
                    <button type="submit" class="nid-btn nid-btn-primary nid-submit" id="nidSubmit">সাবমিট</button>
                </section>
            </form>
        </main>
    </div>

    <footer class="app-footer">
        <div class="app-footer-inner">
            <p class="credit">Made with ❤️ by <a href="https://github.com/emonbaluahat" target="_blank" rel="noopener">Abdullah Al Emon</a> | &copy; REPDOC</p>
        </div>
    </footer>
    
    <script>
        (function () {
            'use strict';
            var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            /* ── Mobile nav + theme (same as genaretor/birth/js/form.js) ── */
            function toggleNav() {
                var nav = document.getElementById('siteNav');
                if (!nav) return;
                var open = nav.classList.toggle('is-open');
                var btn = document.querySelector('.nav-toggle');
                if (btn) {
                    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
                    btn.setAttribute('aria-label', open ? 'মেনু বন্ধ করুন' : 'মেনু খুলুন');
                }
            }
            function applyThemeUI(theme) {
                var label = document.getElementById('themeToggleLabel');
                if (label) label.textContent = theme === 'dark' ? 'Light' : 'Dark';
            }
            function toggleTheme() {
                var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
                var next = isDark ? 'light' : 'dark';
                if (next === 'dark') {
                    document.documentElement.setAttribute('data-theme', 'dark');
                } else {
                    document.documentElement.removeAttribute('data-theme');
                }
                var meta = document.querySelector('meta[name="theme-color"]');
                if (meta) meta.setAttribute('content', next === 'dark' ? '#0B0F19' : '#F8FAFC');
                try { localStorage.setItem('siteTheme', next); } catch (e) {}
                applyThemeUI(next);
            }
            applyThemeUI(document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light');

            /* ── Section reveal (skipped under reduced motion) ── */
            var cards = document.querySelectorAll('[data-reveal]');
            if (reduceMotion || !('IntersectionObserver' in window)) {
                cards.forEach(function (c) { c.classList.add('is-visible'); });
            } else {
                var io = new IntersectionObserver(function (entries) {
                    entries.forEach(function (en) {
                        if (en.isIntersecting) {
                            en.target.classList.add('is-visible');
                            io.unobserve(en.target);
                        }
                    });
                }, { threshold: 0.08 });
                cards.forEach(function (c) { io.observe(c); });
            }

            /* ── File uploads: preview + drag/drop + clear, no alert() ── */
            function note(card, msg) {
                var name = card.querySelector('.nid-upload-name');
                if (name) {
                    name.textContent = msg;
                    name.style.color = 'var(--destructive)';
                    setTimeout(function () { name.style.color = ''; }, 3000);
                }
            }
            function setupUpload(inputId, previewId, nameId, clearId, emptyText, maxMB) {
                var input = document.getElementById(inputId);
                var preview = document.getElementById(previewId);
                var nameEl = document.getElementById(nameId);
                var clearBtn = document.getElementById(clearId);
                if (!input || !preview || !nameEl || !clearBtn) return;
                var card = input.closest('[data-upload]');
                var placeholder = preview.innerHTML;

                function setFile(file) {
                    if (!file) return;
                    if (!/^image\/(jpeg|png|jpg)$/i.test(file.type || '')) {
                        note(card, 'শুধু JPG বা PNG ছবি দিন');
                        return;
                    }
                    if (maxMB && file.size > maxMB * 1024 * 1024) {
                        note(card, 'ফাইল সাইজ ' + maxMB + 'MB এর মধ্যে হতে হবে');
                        return;
                    }
                    nameEl.textContent = file.name;
                    card.classList.add('has-file');
                    clearBtn.hidden = false;
                    var reader = new FileReader();
                    reader.onload = function (e) {
                        preview.innerHTML = '';
                        var img = document.createElement('img');
                        img.src = e.target.result;
                        img.alt = 'Selected image preview';
                        preview.appendChild(img);
                    };
                    reader.readAsDataURL(file);
                }
                input.addEventListener('change', function () {
                    if (input.files && input.files[0]) setFile(input.files[0]);
                });
                ['dragenter', 'dragover'].forEach(function (ev) {
                    card.addEventListener(ev, function (e) {
                        e.preventDefault();
                        card.classList.add('is-drag');
                    });
                });
                ['dragleave', 'drop'].forEach(function (ev) {
                    card.addEventListener(ev, function (e) {
                        e.preventDefault();
                        card.classList.remove('is-drag');
                    });
                });
                card.addEventListener('drop', function (e) {
                    var f = e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files[0];
                    if (f) {
                        try { input.files = e.dataTransfer.files; } catch (err) {}
                        setFile(f);
                    }
                });
                clearBtn.addEventListener('click', function () {
                    input.value = '';
                    preview.innerHTML = placeholder;
                    nameEl.textContent = emptyText;
                    card.classList.remove('has-file');
                    clearBtn.hidden = true;
                    input.focus();
                });
            }
            setupUpload('photoInput', 'photoPreview', 'photoName', 'photoClearBtn', 'কোনো ফাইল বাছা হয়নি', 2);
            setupUpload('signatureInput', 'signaturePreview', 'signatureName', 'signatureClearBtn', 'কোনো ফাইল বাছা হয়নি', 1);

            /* ── Submit feedback: loading state ── */
            var nidForm = document.getElementById('nidForm');
            var submitBtn = document.getElementById('nidSubmit');
            if (nidForm && submitBtn) {
                nidForm.addEventListener('submit', function () {
                    if (!nidForm.checkValidity()) return; // let native validation speak
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'অপেক্ষা করুন…';
                });
            }

            /* ── Move focus to server-side error summary ── */
            var errBox = document.getElementById('nidError');
            if (errBox) errBox.focus({ preventScroll: false });
        })();
    </script>
</body>

</html>
