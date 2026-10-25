<?php
declare(strict_types=1);

// Central auth + wallet — same as genaretor/nid.
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
require_login();

$repdocUser    = current_user();
$repdocBalance = user_balance($repdocUser['id']);
$repdocPrice   = tool_price('cvmaker');
$repdocIsAdmin = $repdocUser['role'] === 'admin';

// Original template gallery (own designs covering the popular categories).
$CV_TEMPLATES = [
    'modern'    => 'Modern',
    'classic'   => 'Classic',
    'minimal'   => 'Minimal',
    'bangla'    => 'Bangla',
    'executive' => 'Executive',
    'creative'  => 'Creative',
    'corporate' => 'Corporate',
    'portfolio' => 'Portfolio',
    'slate'     => 'Slate',
    'elite'     => 'Elite',
    'bold'      => 'Bold',
    'standard'  => 'Standard',
    'dynamic'   => 'Dynamic',
    'word'      => 'Word Style',
    'insight'   => 'Insight',
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Create a professional CV — fill in your details, download a print-ready resume.">
    <meta name="theme-color" content="#F8FAFC">
    <title>CV Maker — Repdoc</title>
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
    <noscript><style>.nid-card { opacity: 1 !important; transform: none !important; } [data-wpane][hidden] { display: block !important; } .nid-wizard-nav, #tplChange { display: none !important; }</style></noscript>
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
        .nid-upload-single { max-width: 460px; }
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
            .nid-tpl { grid-template-columns: 1fr; }
            .nid-live { height: 400px; }
            .nid-live iframe { transform: scale(.4); }
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
        .nid-field textarea {
            font: inherit;
            font-size: 16px;
            color: var(--text-primary);
            background: var(--bg-base);
            border: 1.5px solid var(--border-color);
            border-radius: var(--radius-sm);
            padding: 12px 14px;
            min-height: 110px;
            width: 100%;
            resize: vertical;
            transition: border-color var(--transition-fast) ease, box-shadow var(--transition-fast) ease;
        }
        .nid-field textarea:hover { border-color: var(--text-muted); }
        .nid-field textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-light);
        }
        .nid-tpl { display: grid; gap: 12px; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); }
        .nid-tpl-opt, .nid-tpl-opt > span, .nid-live { min-width: 0; }
        .nid-tpl-opt > span {
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 12px;
            border: 1.5px solid var(--border-color);
            border-radius: var(--radius-sm);
            background: var(--bg-base);
            cursor: pointer;
            transition: all var(--transition-fast) ease;
        }
        .nid-tpl-opt > span strong { font-size: 16px; text-align: center; }
        /* Live template previews (real output rendered with mock data) */
        .nid-live {
            display: block;
            width: 100%;
            height: 420px;
            border-radius: 6px;
            background: #fff;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            position: relative;
        }
        .nid-live iframe {
            width: 794px;
            height: 1123px;
            border: 0;
            transform: scale(.375);
            transform-origin: top left;
            pointer-events: none;
            background: #fff;
            overflow: hidden;
        }
        /* Wizard steps */
        .nid-wizard {
            list-style: none; margin: 0 0 24px; padding: 0;
            display: flex; gap: 8px; flex-wrap: wrap;
        }
        .nid-wizard li {
            flex: 1; min-width: 150px;
            display: flex; align-items: center; gap: 10px;
            background: var(--bg-surface);
            border: 1.5px solid var(--border-color);
            border-radius: var(--radius-sm);
            padding: 10px 14px;
            font-size: 14px; font-weight: 600; color: var(--text-muted);
        }
        .nid-wizard li b {
            width: 28px; height: 28px; flex: none;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 50%; background: var(--bg-muted); font-size: 14px;
        }
        .nid-wizard li[aria-current="step"] { border-color: var(--primary); color: var(--text-primary); }
        .nid-wizard li[aria-current="step"] b { background: var(--primary); color: var(--on-primary); }
        .nid-wizard li.is-done b { background: var(--primary-light); color: var(--primary); }
        .nid-wizard-nav { display: flex; gap: 12px; margin-top: 4px; }
        .nid-sel-chip {
            display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
            background: var(--primary-light);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            padding: 10px 14px; margin-bottom: 20px; font-size: 15px;
        }
        /* ── Split builder: form left, sticky live preview right ── */
        .nid-builder { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 20px; align-items: start; }
        .nid-bform { min-width: 0; }
        .nid-bprev { min-width: 0; position: sticky; top: 84px; }
        .nid-live-big { height: min(78vh, 900px); }
        .nid-live-status { font-size: 13px; color: var(--text-muted); margin-top: 8px; text-align: center; }
        @media (max-width: 1024px) {
            .nid-builder { grid-template-columns: minmax(0, 1fr); }
            .nid-bprev { position: static; order: -1; }
            .nid-live-big { height: 62vh; }
        }
        .nid-tpl-opt span:hover { border-color: var(--primary); }
        .nid-tpl-opt input:checked + span {
            border-color: var(--primary);
            background: var(--primary-light);
            box-shadow: var(--shadow);
        }
        .nid-tpl-opt input:checked + span small { color: var(--text-muted); }
        .nid-tpl-opt input:focus-visible + span {
            outline: 3px solid var(--ring);
            outline-offset: 2px;
        }
        .nid-dyn-row + .nid-dyn-row {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px dashed var(--border-color);
        }
        .nid-row-remove { margin-top: 4px; }
    </style>
</head>
<body class="nid-page">
    <a class="skip-link" href="#nidForm">Skip to form</a>

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

    <!-- ─── Header (same as genaretor/birth page) ─── -->
    <header class="site-header">
        <div class="container header-inner">
            <a class="brand" href="<?= e(repdoc_url('/')) ?>" aria-label="Repdoc — Home">
                <img class="brand-logo" src="<?= e(repdoc_url('src/assets/image/logo.png')) ?>" alt="" width="34" height="34">
                <span class="brand-text">
                    <strong>Repdoc</strong>
                    <small>CV Maker</small>
                </span>
            </a>

            <nav class="site-nav" id="siteNav" aria-label="Primary navigation">
                <a class="nav-link" href="<?= e(repdoc_url('/')) ?>">Home</a>
                <a class="nav-link" href="<?= e(repdoc_url('/')) ?>#tools">All Tools</a>
            </nav>

            <div class="header-actions">
                <button type="button" class="icon-btn theme-toggle" id="themeToggle" onclick="toggleTheme()"
                        aria-label="Toggle theme" title="Toggle theme">
                    <svg class="icon icon-sun" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-sun"/></svg>
                    <svg class="icon icon-moon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-moon"/></svg>
                    <span class="sr-only" id="themeToggleLabel">Dark</span>
                </button>

                <span class="user-chip" title="Credit balance — <?= e($repdocUser['fullname'] !== '' ? $repdocUser['fullname'] : $repdocUser['email']) ?>">
                    <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-wallet"/></svg>
                    <span class="balance"><?= (int) $repdocBalance ?></span>
                </span>

                <?php if ($repdocIsAdmin): ?>
                    <a class="btn btn-secondary btn-login" href="<?= e(repdoc_url('admin/')) ?>">
                        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-settings"/></svg>
                        <span>Admin</span>
                    </a>
                <?php endif; ?>

                <a class="btn btn-primary btn-login" href="<?= e(repdoc_url('public/logout.php')) ?>" aria-label="Log out">
                    <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-logout"/></svg>
                    <span>Log out</span>
                </a>

                <button type="button" class="icon-btn nav-toggle" onclick="toggleNav()"
                        aria-label="Open menu" aria-expanded="false" aria-controls="siteNav">
                    <svg class="icon nav-toggle-open" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-menu"/></svg>
                    <svg class="icon nav-toggle-close" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
                </button>
            </div>
        </div>
    </header>
    <div class="nid-wrap">
        <ol class="nid-wizard" aria-label="Steps">
            <li data-wstep="1" aria-current="step"><b aria-hidden="true">1</b> Choose Template</li>
            <li data-wstep="2"><b aria-hidden="true">2</b> Fill in the Form</li>
            <li data-wstep="3"><b aria-hidden="true">3</b> Get Output</li>
        </ol>
        <main>
            <form action="output.php" method="POST" enctype="multipart/form-data" id="nidForm">
            <div data-wpane="1">
                <section class="nid-card" aria-labelledby="t-tpl" data-reveal>
                    <div class="nid-card-head"><h2 id="t-tpl">Choose a Template</h2></div>
                    <p class="nid-card-sub">See the real output rendered with sample data — click the one you like.</p>
                    <div class="nid-tpl" role="radiogroup" aria-label="Template">
                        <?php foreach ($CV_TEMPLATES as $tkey => $tlabel): ?>
                        <label class="nid-tpl-opt">
                            <input class="nid-visually-hidden" type="radio" name="template" value="<?= e($tkey) ?>" required<?= $tkey === 'modern' ? ' checked' : '' ?>>
                            <span><span class="nid-live" aria-hidden="true"><iframe src="output.php?preview=<?= e($tkey) ?>" title="<?= e($tlabel) ?> template preview" loading="lazy" tabindex="-1" scrolling="no"></iframe></span><strong><?= e($tlabel) ?></strong></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="nid-wizard-nav">
                        <button type="button" class="nid-btn nid-btn-primary" id="tplContinue">Fill in the form with this template →</button>
                    </div>
                </section>
            </div>
            <div data-wpane="2" hidden>
                <p class="nid-sel-chip">Template: <strong id="tplName">Modern</strong> <button type="button" class="nid-btn" id="tplChange">Change</button></p>
                <div class="nid-builder">
                <div class="nid-bform">
                <section class="nid-card" id="sec-photo" aria-labelledby="t-photo" data-reveal>
                    <div class="nid-card-head"><h2 id="t-photo">Photo</h2></div>
                    <p class="nid-card-sub">Add a professional photo (optional).</p>
                    <div class="nid-upload-single">
                        <div class="nid-upload" data-upload>
                            <div class="nid-upload-preview" id="photoPreview">
                                <svg aria-hidden="true"><use href="#nid-i-photo"/></svg>
                                <span>Photo preview</span>
                            </div>
                            <div class="nid-upload-row">
                                <label class="nid-upload-btn" for="photoInput">
                                    <svg aria-hidden="true"><use href="#nid-i-photo"/></svg>
                                    <span>Choose photo</span>
                                </label>
                                <button type="button" class="nid-upload-clear" id="photoClearBtn" aria-label="Remove photo" hidden>
                                    <svg aria-hidden="true"><use href="#nid-i-x"/></svg>
                                </button>
                            </div>
                            <input class="nid-visually-hidden" type="file" name="photo" id="photoInput" accept="image/jpeg,image/png,image/jpg">
                            <p class="nid-upload-name" id="photoName" aria-live="polite">No file chosen</p>
                            <p class="nid-hint">Optional · JPG/PNG · max 2MB</p>
                        </div>
                    </div>
                </section>

                <section class="nid-card" id="sec-personal" aria-labelledby="t-personal" data-reveal>
                    <div class="nid-card-head"><h2 id="t-personal">Personal Information</h2></div>
                    <p class="nid-card-sub">This is what employers will see first.</p>
                    <div class="nid-grid">
                        <div class="nid-field">
                            <label for="f-name">Full Name <span class="nid-req" aria-hidden="true">*</span></label>
                            <input type="text" id="f-name" name="full_name" placeholder="e.g. Abdullah Al Emon" required autocomplete="name">
                        </div>
                        <div class="nid-field">
                            <label for="f-title">Job Title / Position <span class="nid-req" aria-hidden="true">*</span></label>
                            <input type="text" id="f-title" name="job_title" placeholder="e.g. Graphic Designer" required autocomplete="organization-title">
                        </div>
                        <div class="nid-field">
                            <label for="f-email">Email <span class="nid-req" aria-hidden="true">*</span></label>
                            <input type="text" id="f-email" name="email" placeholder="you@example.com" required inputmode="email" autocomplete="email">
                        </div>
                        <div class="nid-field">
                            <label for="f-phone">Mobile <span class="nid-req" aria-hidden="true">*</span></label>
                            <input type="text" id="f-phone" name="phone" placeholder="01XXXXXXXXX" required inputmode="tel" autocomplete="tel">
                        </div>
                        <div class="nid-field">
                            <label for="f-father">Father's Name</label>
                            <input type="text" id="f-father" name="father_name" placeholder="Father's name" autocomplete="off">
                        </div>
                        <div class="nid-field">
                            <label for="f-mother">Mother's Name</label>
                            <input type="text" id="f-mother" name="mother_name" placeholder="Mother's name" autocomplete="off">
                        </div>
                        <div class="nid-field">
                            <label for="f-present">Present Address</label>
                            <input type="text" id="f-present" name="present_address" placeholder="Village, Upazila, District" autocomplete="street-address">
                        </div>
                        <div class="nid-field">
                            <label for="f-permanent">Permanent Address</label>
                            <input type="text" id="f-permanent" name="permanent_address" placeholder="Village, Upazila, District" autocomplete="off">
                        </div>
                        <div class="nid-field">
                            <label for="f-dob">Date of Birth</label>
                            <input type="text" id="f-dob" name="dob" placeholder="01 Jan 2000" autocomplete="bday">
                        </div>
                        <div class="nid-field">
                            <label for="f-namebn">Name (in Bengali)</label>
                            <input type="text" id="f-namebn" name="name_bn" placeholder="Full name in Bengali" autocomplete="off">
                        </div>
                        <div class="nid-field">
                            <label for="f-religion">Religion</label>
                            <select id="f-religion" name="religion">
                                <option value="">Select</option>
                                <option value="ইসলাম">Islam</option>
                                <option value="হিন্দু">Hindu</option>
                                <option value="খ্রিস্টান">Christian</option>
                                <option value="বৌদ্ধ">Buddhist</option>
                            </select>
                        </div>
                        <div class="nid-field">
                            <label for="f-gender">Gender</label>
                            <select id="f-gender" name="gender">
                                <option value="">Select</option>
                                <option value="পুরুষ">Male</option>
                                <option value="মহিলা">Female</option>
                            </select>
                        </div>
                        <div class="nid-field">
                            <label for="f-blood">Blood Group</label>
                            <select id="f-blood" name="blood_group">
                                <option value="">Select</option>
                                <option value="A+">A+</option>
                                <option value="A-">A-</option>
                                <option value="B+">B+</option>
                                <option value="B-">B-</option>
                                <option value="O+">O+</option>
                                <option value="O-">O-</option>
                                <option value="AB+">AB+</option>
                                <option value="AB-">AB-</option>
                            </select>
                        </div>
                        <div class="nid-field">
                            <label for="f-nationality">Nationality</label>
                            <input type="text" id="f-nationality" name="nationality" placeholder="e.g. Bangladeshi" autocomplete="off">
                        </div>
                        <div class="nid-field">
                            <label for="f-nid">NID Number</label>
                            <input type="text" id="f-nid" name="nid" placeholder="NID number" inputmode="numeric" autocomplete="off">
                        </div>
                        <div class="nid-field nid-field-full">
                            <label for="f-summary">Career Summary</label>
                            <textarea id="f-summary" name="summary" rows="4" placeholder="Write your skills and goals in 2-3 lines…"></textarea>
                        </div>
                    </div>
                </section>

                <section class="nid-card" id="sec-ref" aria-labelledby="t-ref" data-reveal>
                    <div class="nid-card-head"><h2 id="t-ref">Reference &amp; Declaration</h2></div>
                    <p class="nid-card-sub">Optional — shown on the Bangla template.</p>
                    <div class="nid-grid">
                        <div class="nid-field">
                            <label for="f-refname">Reference Name</label>
                            <input type="text" id="f-refname" name="ref_name" placeholder="Reference person's name" autocomplete="off">
                        </div>
                        <div class="nid-field">
                            <label for="f-reforg">Reference Org. &amp; Title</label>
                            <input type="text" id="f-reforg" name="ref_org" placeholder="Organization & designation" autocomplete="off">
                        </div>
                        <div class="nid-field">
                            <label for="f-refphone">Reference Mobile</label>
                            <input type="text" id="f-refphone" name="ref_phone" placeholder="Mobile number" inputmode="tel" autocomplete="off">
                        </div>
                        <div class="nid-field">
                            <label for="f-decl">Declaration</label>
                            <select id="f-decl" name="declaration">
                                <option value="1">Add declaration statement</option>
                                <option value="">No declaration</option>
                            </select>
                        </div>
                    </div>
                </section>

                <section class="nid-card" id="sec-edu" aria-labelledby="t-edu" data-reveal>
                    <div class="nid-card-head"><h2 id="t-edu">Education</h2></div>
                    <p class="nid-card-sub">Add more rows with "Add more" if needed.</p>
                    <div id="eduRows">
                        <div class="nid-grid nid-dyn-row">
                            <div class="nid-field">
                                <label>Degree <span class="nid-req" aria-hidden="true">*</span></label>
                                <input type="text" name="edu_degree[]" placeholder="e.g. B.Sc in CSE" required>
                            </div>
                            <div class="nid-field">
                                <label>Institute <span class="nid-req" aria-hidden="true">*</span></label>
                                <input type="text" name="edu_institute[]" placeholder="University / College" required>
                            </div>
                            <div class="nid-field">
                                <label>Year</label>
                                <input type="text" name="edu_year[]" placeholder="2020" inputmode="numeric">
                            </div>
                            <div class="nid-field">
                                <label>Result</label>
                                <input type="text" name="edu_result[]" placeholder="CGPA 3.50">
                            </div>
                        </div>
                    </div>
                    <button type="button" class="nid-btn" id="eduAdd">+ Add more education</button>
                </section>

                <section class="nid-card" id="sec-exp" aria-labelledby="t-exp" data-reveal>
                    <div class="nid-card-head"><h2 id="t-exp">Work Experience</h2></div>
                    <p class="nid-card-sub">List your most recent job first.</p>
                    <div id="expRows">
                        <div class="nid-grid nid-dyn-row">
                            <div class="nid-field">
                                <label>Job Title</label>
                                <input type="text" name="exp_title[]" placeholder="e.g. Junior Designer">
                            </div>
                            <div class="nid-field">
                                <label>Company</label>
                                <input type="text" name="exp_company[]" placeholder="Company name">
                            </div>
                            <div class="nid-field">
                                <label>Period</label>
                                <input type="text" name="exp_period[]" placeholder="2022 — Present">
                            </div>
                            <div class="nid-field">
                                <label>Location</label>
                                <input type="text" name="exp_location[]" placeholder="e.g. Dhaka, Bangladesh">
                            </div>
                            <div class="nid-field nid-field-full">
                                <label>Duties (brief)</label>
                                <input type="text" name="exp_detail[]" placeholder="Main duties in one line">
                            </div>
                        </div>
                    </div>
                    <button type="button" class="nid-btn" id="expAdd">+ Add more experience</button>
                </section>

                <section class="nid-card" id="sec-skills" aria-labelledby="t-skills" data-reveal>
                    <div class="nid-card-head"><h2 id="t-skills">Skills</h2></div>
                    <p class="nid-card-sub">Separate with commas.</p>
                    <div class="nid-grid">
                        <div class="nid-field nid-field-full">
                            <label for="f-skills">Skills</label>
                            <input type="text" id="f-skills" name="skills" placeholder="MS Word, Photoshop, Communication">
                        </div>
                        <div class="nid-field nid-field-full">
                            <label for="f-lang">Languages</label>
                            <input type="text" id="f-lang" name="languages" placeholder="Bangla, English">
                        </div>
                        <div class="nid-field nid-field-full">
                            <label for="f-interests">Interests</label>
                            <input type="text" id="f-interests" name="interests" placeholder="Travel, Photography, Reading">
                        </div>
                    </div>
                </section>

                <section class="nid-card" id="sec-cert" aria-labelledby="t-cert" data-reveal>
                    <div class="nid-card-head"><h2 id="t-cert">Certifications</h2></div>
                    <p class="nid-card-sub">Courses, certificates and training (optional).</p>
                    <div id="certRows">
                        <div class="nid-grid nid-dyn-row">
                            <div class="nid-field">
                                <label>Certificate / Course</label>
                                <input type="text" name="cert_name[]" placeholder="e.g. Google UX Design">
                            </div>
                            <div class="nid-field">
                                <label>Year</label>
                                <input type="text" name="cert_year[]" placeholder="2023" inputmode="numeric">
                            </div>
                        </div>
                    </div>
                    <button type="button" class="nid-btn" id="certAdd">+ Add more certificates</button>
                </section>
                </div><!-- /nid-bform -->
                <div class="nid-bprev">
                    <div class="nid-live nid-live-big" aria-hidden="true"><iframe id="cvLive" src="output.php?preview=modern" title="Live CV preview" loading="lazy" tabindex="-1" scrolling="no"></iframe></div>
                    <p class="nid-live-status" id="cvLiveStatus" role="status">Live preview — updates as you type.</p>
                </div>
                </div><!-- /nid-builder -->

                <section class="nid-card nid-submit-card" aria-labelledby="t-submit" data-reveal>
                    <h2 id="t-submit" class="nid-visually-hidden">Submit</h2>
                    <p class="nid-price-note">
                        <?php if ($repdocPrice > 0 && !$repdocIsAdmin): ?>
                        Each submission costs <strong><?= (int) $repdocPrice ?> credits</strong> (Balance: <?= (int) $repdocBalance ?>).
                        <?php else: ?>
                        Submitting the form is <strong>completely free</strong>.
                        <?php endif; ?>
                    </p>
                    <button type="submit" class="nid-btn nid-btn-primary nid-submit" id="nidSubmit">Generate CV</button>
                </section>
            </div>
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

            /* ── Wizard: 1 choose template → 2 fill form (3 = output page) ── */
            var panes = {
                1: document.querySelector('[data-wpane="1"]'),
                2: document.querySelector('[data-wpane="2"]')
            };
            var steps = Array.prototype.slice.call(document.querySelectorAll('.nid-wizard li'));
            var tplName = document.getElementById('tplName');
            function goStep(n) {
                if (panes[1]) panes[1].hidden = (n !== 1);
                if (panes[2]) panes[2].hidden = (n !== 2);
                steps.forEach(function (li) {
                    var s = parseInt(li.getAttribute('data-wstep'), 10);
                    if (s === n) li.setAttribute('aria-current', 'step');
                    else li.removeAttribute('aria-current');
                    li.classList.toggle('is-done', s < n);
                });
                var wrap = document.querySelector('.nid-wrap');
                if (wrap) wrap.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' });
                if (n === 2) {
                    try { refreshLive(); } catch (e) {}
                    try { fitBigPreview(); } catch (e2) {}
                }
            }
            function syncTplName() {
                var checked = document.querySelector('input[name="template"]:checked');
                if (tplName && checked) {
                    var label = checked.closest('label');
                    var strong = label ? label.querySelector('strong') : null;
                    tplName.textContent = strong ? strong.textContent : checked.value;
                }
            }
            var tplContinue = document.getElementById('tplContinue');
            var tplChange = document.getElementById('tplChange');
            if (tplContinue) tplContinue.addEventListener('click', function () { goStep(2); });
            if (tplChange) tplChange.addEventListener('click', function () { goStep(1); });
            document.querySelectorAll('input[name="template"]').forEach(function (r) {
                r.addEventListener('change', syncTplName);
            });
            syncTplName();

            /* ── Mobile nav + theme (same as genaretor/birth/js/form.js) ── */
            function toggleNav() {
                var nav = document.getElementById('siteNav');
                if (!nav) return;
                var open = nav.classList.toggle('is-open');
                var btn = document.querySelector('.nav-toggle');
                if (btn) {
                    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
                    btn.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
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

            /* ── Dynamic education / experience rows ── */
            function dynRow(boxId, btnId) {
                var box = document.getElementById(boxId);
                var btn = document.getElementById(btnId);
                if (!box || !btn) return;
                btn.addEventListener('click', function () {
                    var first = box.querySelector('.nid-dyn-row');
                    if (!first) return;
                    var clone = first.cloneNode(true);
                    clone.querySelectorAll('input, textarea').forEach(function (el) {
                        el.value = '';
                        el.removeAttribute('required');
                    });
                    var rm = document.createElement('button');
                    rm.type = 'button';
                    rm.className = 'nid-btn nid-row-remove';
                    rm.textContent = '\u2212 Remove this row';
                    rm.addEventListener('click', function () { clone.remove(); });
                    clone.appendChild(rm);
                    box.appendChild(clone);
                });
            }
            dynRow('eduRows', 'eduAdd');
            dynRow('expRows', 'expAdd');
            dynRow('certRows', 'certAdd');

            /* ── File uploads: preview + drag/drop + clear, no alert() ── */
            function note(card, msg) {
                var name = card.querySelector('.nid-upload-name');
                if (name) {
                    name.textContent = msg;
                    name.style.color = 'var(--destructive)';
                    setTimeout(function () { name.style.color = ''; }, 3000);
                }
            }
            function setupUpload(inputId, previewId, nameId, clearId, emptyText) {
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
                        note(card, 'Only JPG or PNG images please');
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
            setupUpload('photoInput', 'photoPreview', 'photoName', 'photoClearBtn', 'No file chosen');

            /* ── Submit feedback: loading state ── */
            var nidForm = document.getElementById('nidForm');
            var submitBtn = document.getElementById('nidSubmit');
            if (nidForm && submitBtn) {
                nidForm.addEventListener('submit', function () {
                    if (!nidForm.checkValidity()) return; // let native validation speak
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Please wait…';
                });
            }

            /* ── Mobile: fit chooser previews exactly to card width (not the big builder preview) ── */
            var mqMobile = window.matchMedia('(max-width: 640px)');
            function fitLivePreviews() {
                var boxes = document.querySelectorAll('.nid-live:not(.nid-live-big)');
                boxes.forEach(function (box) {
                    var f = box.querySelector('iframe');
                    if (!f) return;
                    if (!mqMobile.matches) {
                        f.style.transform = '';
                        box.style.height = '';
                        return;
                    }
                    var w = box.clientWidth;
                    if (w <= 0) return;
                    var s = w / 794;
                    f.style.transform = 'scale(' + s + ')';
                    box.style.height = Math.round(620 * s) + 'px';
                });
            }
            var fitT = null;
            window.addEventListener('resize', function () {
                clearTimeout(fitT);
                fitT = setTimeout(fitLivePreviews, 150);
            });
            window.addEventListener('load', fitLivePreviews);
            fitLivePreviews();

            /* ── Split builder: live A4 preview that updates as you type ── */
            var liveFrame = document.getElementById('cvLive');
            var liveStatus = document.getElementById('cvLiveStatus');
            var liveT = null;
            function fitBigPreview() {
                if (!liveFrame) return;
                var box = liveFrame.closest('.nid-live');
                if (!box) return;
                var w = box.clientWidth;
                if (w <= 0) return;
                liveFrame.style.transform = 'scale(' + (w / 794) + ')';
            }
            function refreshLive() {
                if (!liveFrame || !panes[2] || panes[2].hidden) return;
                var form = document.getElementById('nidForm');
                if (!form) return;
                var fd = new FormData(form);
                fd.delete('photo'); // photo appears on the final output only
                fd.append('live_preview', '1');
                if (liveStatus) liveStatus.textContent = 'Updating preview…';
                fetch('output.php', { method: 'POST', body: fd, credentials: 'same-origin' })
                    .then(function (res) { return res.text(); })
                    .then(function (html) {
                        liveFrame.srcdoc = html;
                        fitBigPreview();
                        if (liveStatus) liveStatus.textContent = 'Live preview — updates as you type.';
                    })
                    .catch(function () {
                        if (liveStatus) liveStatus.textContent = 'Preview paused — check your connection.';
                    });
            }
            function queueLiveRefresh() {
                clearTimeout(liveT);
                liveT = setTimeout(refreshLive, 700);
            }
            var liveForm = document.getElementById('nidForm');
            if (liveForm) {
                liveForm.addEventListener('input', queueLiveRefresh);
                liveForm.addEventListener('change', queueLiveRefresh);
            }
            window.addEventListener('resize', function () { fitBigPreview(); });
            window.addEventListener('load', function () { fitBigPreview(); refreshLive(); });

            /* ── Move focus to server-side error summary ── */
            var errBox = document.getElementById('nidError');
            if (errBox) errBox.focus({ preventScroll: false });
        })();
    </script>
</body>

</html>
