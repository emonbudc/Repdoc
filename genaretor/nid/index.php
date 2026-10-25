<?php
declare(strict_types=1);

// Central auth + wallet — same pattern as genaretor/birth (replaces the old
// per-tool auth: ../config/database.php + ../customer/index.php no longer exist).
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
require_login();
repdoc_security_headers();

$repdocUser    = current_user();
$repdocBalance = user_balance($repdocUser['id']);
$repdocPrice   = tool_price('nid');
$repdocIsAdmin = $repdocUser['role'] === 'admin';

$json = null;
$showFrom = true;
$userPhotoPath = '';
$userSignaturePath = '';
$formError = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    // PHP throws away $_POST and $_FILES entirely when the request body is
    // bigger than post_max_size, which used to look like "submit did nothing".
    // Detect it from CONTENT_LENGTH so the user gets a real message instead.
    $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($contentLength > 0 && $_POST === [] && $_FILES === []) {
        $formError = 'আপলোড অনেক বড় — সার্ভার সর্বোচ্চ ' . ini_get('post_max_size')
            . ' পর্যন্ত নেয়। ছবি ও স্বাক্ষর ২MB-এর নিচে রেখে আবার চেষ্টা করুন।';
    }

    // Handle photo upload (server-side validated: ext + finfo MIME + getimagesize + ≤2MB).
    $photoUploadError = $_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($formError === '' && $photoUploadError !== UPLOAD_ERR_NO_FILE) {
        if ($photoUploadError === UPLOAD_ERR_OK) {
            $safe = repdoc_validate_image_upload($_FILES['photo'], 2 * 1024 * 1024);
            if ($safe !== '') {
                $userPhotoPath = 'uploads/' . $safe;
                if (!@move_uploaded_file($_FILES['photo']['tmp_name'], __DIR__ . '/' . $userPhotoPath)) {
                    $userPhotoPath = '';
                    $formError = "Could not save photo. Try again.";
                }
            } else {
                $formError = "ছবি সঠিক নয় — শুধু JPG/PNG, সর্বোচ্চ ২MB।";
            }
        } elseif ($photoUploadError === UPLOAD_ERR_INI_SIZE || $photoUploadError === UPLOAD_ERR_FORM_SIZE) {
            $formError = 'ছবি সার্ভারের সীমার চেয়ে বড় (max ' . ini_get('upload_max_filesize') . ')। ছবি ২MB-এর নিচে করুন।';
        } elseif ($photoUploadError !== UPLOAD_ERR_NO_FILE) {
            $formError = 'ছবি আপলোড ব্যর্থ হয়েছে। আবার চেষ্টা করুন।';
        }
    }

    // Handle signature upload
    $signatureUploadError = $_FILES['signature']['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($formError === '' && $signatureUploadError !== UPLOAD_ERR_NO_FILE) {
        if ($signatureUploadError === UPLOAD_ERR_OK) {
            $safe = repdoc_validate_image_upload($_FILES['signature'], 2 * 1024 * 1024);
            if ($safe !== '') {
                $userSignaturePath = 'uploads/' . $safe;
                if (!@move_uploaded_file($_FILES['signature']['tmp_name'], __DIR__ . '/' . $userSignaturePath)) {
                    $userSignaturePath = '';
                    $formError = "Could not save signature. Try again.";
                }
            } else {
                $formError = "স্বাক্ষর সঠিক নয় — শুধু JPG/PNG, সর্বোচ্চ ২MB।";
            }
        } elseif ($signatureUploadError === UPLOAD_ERR_INI_SIZE || $signatureUploadError === UPLOAD_ERR_FORM_SIZE) {
            $formError = 'স্বাক্ষর সার্ভারের সীমার চেয়ে বড় (max ' . ini_get('upload_max_filesize') . ')। স্বাক্ষর ২MB-এর নিচে করুন।';
        } elseif ($signatureUploadError !== UPLOAD_ERR_NO_FILE) {
            $formError = 'স্বাক্ষর আপলোড ব্যর্থ হয়েছে। আবার চেষ্টা করুন।';
        }
    }

    // Billing: one charge per generated NID (price 0 = free, admins free).
    // Validated BEFORE charging so a bad upload never costs credits.
    if ($formError === '') {
        $charge = charge_tool('nid');
        if (!$charge['ok']) {
            $formError = $charge['message'];
        } else {
            $repdocBalance = $charge['balance'];
            // Full submission goes to the database (admin → Document Logs).
            log_document('nid', (string) ($_POST['nid'] ?? ''), (string) ($_POST['dob'] ?? ''),
                $userPhotoPath !== '' ? 'genaretor/nid/' . $userPhotoPath : '',
                $userSignaturePath !== '' ? 'genaretor/nid/' . $userSignaturePath : '',
                $_POST);
            $showFrom = false;
        }
    }
}
?>

<?php if ($showFrom) { ?>

<!DOCTYPE html>
<html lang="bn">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="জাতীয় পরিচয়পত্র (NID) ফর্ম — তথ্য দিন, প্রিন্ট-রেডি কার্ড তৈরি করুন।">
    <meta name="theme-color" content="#F8FAFC">
    <meta name="robots" content="noindex">
    <title>NID — Repdoc</title>
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
                    <small>জাতীয় পরিচয়পত্র</small>
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
        <?php if ($formError !== '') { ?>
        <div class="nid-error" role="alert" tabindex="-1" id="nidError">
            <?= e($formError) ?> <a href="#nidForm">ফর্মে ফিরে যান</a>
        </div>
        <?php } ?>

        <main>
            <form action="" method="POST" enctype="multipart/form-data" id="nidForm" target="_blank">
                <section class="nid-card" id="sec-photo" aria-labelledby="t-photo" data-reveal>
                    <div class="nid-card-head"><h2 id="t-photo">ছবি ও স্বাক্ষর</h2></div>
                    <p class="nid-card-sub">JPG বা PNG ছবি দিন (প্রতিটি সর্বোচ্চ ২MB)। কার্ডে যেমন দেখাবে, প্রিভিউতে তেমনই দেখুন।</p>
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
                            <input class="nid-visually-hidden" type="file" name="photo" id="photoInput" accept="image/jpeg,image/png,image/jpg">
                            <p class="nid-upload-name" id="photoName" aria-live="polite">কোনো ফাইল বাছা হয়নি</p>
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
                            <input class="nid-visually-hidden" type="file" name="signature" id="signatureInput" accept="image/jpeg,image/png,image/jpg">
                            <p class="nid-upload-name" id="signatureName" aria-live="polite">কোনো ফাইল বাছা হয়নি</p>
                        </div>
                    </div>
                </section>

                <section class="nid-card" id="sec-personal" aria-labelledby="t-personal" data-reveal>
                    <div class="nid-card-head"><h2 id="t-personal">ব্যক্তিগত তথ্য</h2></div>
                    <p class="nid-card-sub">NID কার্ডে ঠিক যেমন ছাপা হবে, তেমন করে লিখুন।</p>
                    <div class="nid-grid">
                        <div class="nid-field">
                            <label for="f-name-bn">নাম (বাংলায়) <span class="nid-req" aria-hidden="true">*</span></label>
                            <input type="text" id="f-name-bn" name="name_bn" placeholder="নাম (বাংলায়)" required autocomplete="name">
                        </div>
                        <div class="nid-field">
                            <label for="f-name-en">Name (In English) <span class="nid-req" aria-hidden="true">*</span></label>
                            <input type="text" id="f-name-en" name="name_en" placeholder="Name (In English)" required autocomplete="name">
                        </div>
                        <div class="nid-field">
                            <label for="f-nid">NID Number <span class="nid-req" aria-hidden="true">*</span></label>
                            <input type="text" id="f-nid" name="nid" placeholder="NID Number" required inputmode="numeric" autocomplete="off">
                        </div>
                        <div class="nid-field">
                            <label for="f-pin">Pin Number <span class="nid-req" aria-hidden="true">*</span></label>
                            <input type="text" id="f-pin" name="pin" placeholder="Pin Number" required inputmode="numeric" autocomplete="off">
                        </div>
                        <div class="nid-field">
                            <label for="f-dob">Date of Birth <span class="nid-req" aria-hidden="true">*</span></label>
                            <input type="text" id="f-dob" name="dob" placeholder="01 Jan 2001" required autocomplete="bday">
                            <span class="nid-hint">উদাহরণ: 01 Jan 2001</span>
                        </div>
                        <div class="nid-field">
                            <label for="f-blood">Blood Group</label>
                            <input type="text" id="f-blood" name="blood" placeholder="যেমন: B+" autocomplete="off">
                        </div>
                    </div>
                </section>

                <section class="nid-card" id="sec-family" aria-labelledby="t-family" data-reveal>
                    <div class="nid-card-head"><h2 id="t-family">পারিবারিক তথ্য</h2></div>
                    <p class="nid-card-sub">পিতা অথবা স্বামী — যেকোনো একটি বেছে নিন।</p>
                    <div class="nid-grid">
                        <div class="nid-field nid-field-full">
                            <fieldset>
                                <legend>সম্পর্ক <span class="nid-req" aria-hidden="true">*</span></legend>
                                <div class="nid-seg" id="relationSeg">
                                    <label class="nid-seg-opt">
                                        <input class="nid-visually-hidden" type="radio" name="relation" value="father" required>
                                        <span>পিতা</span>
                                    </label>
                                    <label class="nid-seg-opt">
                                        <input class="nid-visually-hidden" type="radio" name="relation" value="husband" required>
                                        <span>স্বামী</span>
                                    </label>
                                </div>
                            </fieldset>
                        </div>
                        <div class="nid-field">
                            <label for="relationNameInput">পিতার / স্বামীর নাম <span class="nid-req" aria-hidden="true">*</span></label>
                            <input type="text" id="relationNameInput" name="relation_name" placeholder="পিতার / স্বামীর নাম" required autocomplete="off">
                        </div>
                        <div class="nid-field">
                            <label for="f-mother">মাতার নাম <span class="nid-req" aria-hidden="true">*</span></label>
                            <input type="text" id="f-mother" name="mother" placeholder="মাতার নাম" required autocomplete="off">
                        </div>
                    </div>
                </section>

                <section class="nid-card" id="sec-extra" aria-labelledby="t-extra" data-reveal>
                    <div class="nid-card-head"><h2 id="t-extra">অতিরিক্ত তথ্য</h2></div>
                    <p class="nid-card-sub">কার্ডের পেছনের অংশের তথ্য।</p>
                    <div class="nid-grid nid-grid-3">
                        <div class="nid-field">
                            <label for="f-birth">জন্মস্থান <span class="nid-req" aria-hidden="true">*</span></label>
                            <input type="text" id="f-birth" name="birth" placeholder="জন্মস্থান" required autocomplete="off">
                        </div>
                        <div class="nid-field">
                            <label for="f-card-date">প্রদানের তারিখ <span class="nid-req" aria-hidden="true">*</span></label>
                            <input type="text" id="f-card-date" name="card_date" placeholder="০১/১২/২০২৯" required autocomplete="off">
                        </div>
                        <div class="nid-field">
                            <label for="f-address">ভোটার ঠিকানা <span class="nid-req" aria-hidden="true">*</span></label>
                            <input type="text" id="f-address" name="address" placeholder="ভোটার ঠিকানা" required autocomplete="street-address">
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
                    <button type="submit" name="submit" value="Submit" class="nid-btn nid-btn-primary nid-submit" id="nidSubmit" data-default-label="কার্ড তৈরি করুন">
                        কার্ড তৈরি করুন
                    </button>
                </section>
            </form>
        </main>
    </div>

    <!-- ─── Footer (tools/birthsearch এর standard footer — সব tool এ এটাই) ─── -->
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

            /* ── Relation placeholder sync ── */
            var relInput = document.getElementById('relationNameInput');
            var seg = document.getElementById('relationSeg');
            if (seg && relInput) {
                seg.addEventListener('change', function (ev) {
                    var v = ev.target && ev.target.value;
                    if (v === 'husband') relInput.placeholder = 'স্বামীর নাম';
                    else if (v === 'father') relInput.placeholder = 'পিতার নাম';
                });
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
            function setupUpload(inputId, previewId, nameId, clearId, emptyText) {
                var input = document.getElementById(inputId);
                var preview = document.getElementById(previewId);
                var nameEl = document.getElementById(nameId);
                var clearBtn = document.getElementById(clearId);
                if (!input || !preview || !nameEl || !clearBtn) return;
                var card = input.closest('[data-upload]');
                var placeholder = preview.innerHTML;

                function showFile(file, displayName) {
                    nameEl.textContent = displayName;
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
                function resetFile() {
                    input.value = '';
                    preview.innerHTML = placeholder;
                    nameEl.textContent = emptyText;
                    card.classList.remove('has-file');
                    clearBtn.hidden = true;
                }
                function compressFile(file, done) {
                    var url = URL.createObjectURL(file);
                    var img = new Image();
                    img.onload = function () {
                        var max = 1600;
                        var w = img.naturalWidth || img.width;
                        var h = img.naturalHeight || img.height;
                        var scale = Math.min(1, max / Math.max(w, h));
                        w = Math.max(1, Math.round(w * scale));
                        h = Math.max(1, Math.round(h * scale));
                        var canvas = document.createElement('canvas');
                        canvas.width = w;
                        canvas.height = h;
                        var ctx = canvas.getContext('2d');
                        ctx.fillStyle = '#ffffff';
                        ctx.fillRect(0, 0, w, h);
                        ctx.drawImage(img, 0, 0, w, h);
                        URL.revokeObjectURL(url);
                        canvas.toBlob(function (blob) {
                            if (!blob) { done(null); return; }
                            var name = file.name.replace(/\.[^.]+$/, '') + '.jpg';
                            try {
                                done(new File([blob], name, { type: 'image/jpeg' }));
                            } catch (err) {
                                done(null);
                            }
                        }, 'image/jpeg', 0.85);
                    };
                    img.onerror = function () {
                        URL.revokeObjectURL(url);
                        done(null);
                    };
                    img.src = url;
                }
                function setFile(file) {
                    if (!file) return;
                    if (!/^image\/(jpeg|png|jpg)$/i.test(file.type || '')) {
                        resetFile();
                        note(card, 'শুধু JPG বা PNG ছবি দিন');
                        return;
                    }
                    if (file.size <= 2 * 1024 * 1024) {
                        showFile(file, file.name);
                        return;
                    }
                    // Phone photos are often 3–10MB. Shrink locally so the POST
                    // stays under the server limit instead of silently failing.
                    compressFile(file, function (small) {
                        if (small && small.size <= 2 * 1024 * 1024) {
                            try {
                                var dt = new DataTransfer();
                                dt.items.add(small);
                                input.files = dt.files;
                            } catch (err) {
                                resetFile();
                                note(card, 'ছবিটি ২MB-এর বেশি — ছোট ছবি বেছে নিন');
                                return;
                            }
                            showFile(small, file.name);
                        } else {
                            resetFile();
                            note(card, 'ছবিটি ২MB-এর বেশি — ছোট ছবি বেছে নিন');
                        }
                    });
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
                    resetFile();
                    input.focus();
                });
            }
            setupUpload('photoInput', 'photoPreview', 'photoName', 'photoClearBtn', 'কোনো ফাইল বাছা হয়নি');
            setupUpload('signatureInput', 'signaturePreview', 'signatureName', 'signatureClearBtn', 'কোনো ফাইল বাছা হয়নি');

            /* ── Submit feedback: loading state ── */
            var nidForm = document.getElementById('nidForm');
            var submitBtn = document.getElementById('nidSubmit');
            if (nidForm && submitBtn) {
                nidForm.addEventListener('submit', function () {
                    if (!nidForm.checkValidity()) return; // let native validation speak
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'অপেক্ষা করুন…';
                    // The form opens in a new tab, so this tab stays put:
                    // unlock it again after the new tab has had time to open.
                    setTimeout(function () {
                        submitBtn.disabled = false;
                        submitBtn.textContent = submitBtn.getAttribute('data-default-label') || 'কার্ড তৈরি করুন';
                    }, 4000);
                });
            }

            /* ── Move focus to server-side error summary ── */
            var errBox = document.getElementById('nidError');
            if (errBox) errBox.focus({ preventScroll: false });
        })();
    </script>
</body>

</html>
<?php } else { ?>
    <html lang="en">
    <head>
        <meta name="robots" content="noindex">
        <title>nid-<?= e(isset($_POST['nid']) ? (string) $_POST['nid'] : '') ?></title>
        <link href="https://sonnetdp.github.io/nikosh/css/nikosh.css" rel="stylesheet" type="text/css">
        <link href="https://fonts.maateen.me/kalpurush/font.css" rel="stylesheet">
        <link href="assets/css/bootstrap.min.css" rel="stylesheet">
        <script src="assets/JavaScript/popper.min.js"></script>
        <script src="assets/JavaScript/bootstrap.min.js"></script>
        <script src="assets/JavaScript/jquery-1.11.1.min.js"></script>
        <link rel="stylesheet" href="assets/css/nidxnx.css" data-n-g="" />
        <style>
        
            @media print {
                @page {
                    margin: 0;
                }
            }
        </style>
        <script>
            window.addEventListener('afterprint', function () {
                if (window.opener) {
                    window.close();
                }
            });

            window.onload = function() {
                <?php $nid_hub3 = '<pin>' . (isset($_POST['pin']) ? (string) $_POST['pin'] : '') . '</pin><n>' . (isset($_POST['name_en']) ? (string) $_POST['name_en'] : '') . '</n><DOB>' . (isset($_POST['dob']) ? (string) $_POST['dob'] : '') . '</DOB><FP></FP><F>Right Index</F><TYPE>A</TYPE><V>2.0</V><ds>302c0214103fc01240542ed736c0b48858c1c03d80006215021416e73728de9618fedcd368c88d8f3a2e72096d</ds>'; ?>
                var hub3_code = <?php echo json_encode($nid_hub3, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;

                console.log(hub3_code);

                PDF417.init(hub3_code);

                var barcode = PDF417.getBarcodeArray();

                // block sizes (width and height) in pixels
                var bw = 2;
                var bh = 2;

                // create canvas element based on number of columns and rows in barcode
                var canvas = document.createElement('canvas');
                canvas.width = bw * barcode['num_cols'];
                canvas.height = bh * barcode['num_rows'];
                
                // Set the canvas display style properties for better mobile support
                canvas.style.width = '100%';
                canvas.style.height = 'auto';
                canvas.style.maxHeight = '39px';
                canvas.style.display = 'block';
                
                // Clear the barcode container before appending
                var barcodeContainer = document.getElementById('barcode');
                while (barcodeContainer.firstChild) {
                    barcodeContainer.removeChild(barcodeContainer.firstChild);
                }
                
                barcodeContainer.appendChild(canvas);

                var ctx = canvas.getContext('2d');
                // Make sure the rendering context is properly scaled
                ctx.clearRect(0, 0, canvas.width, canvas.height);

                // graph barcode elements
                var y = 0;
                // for each row
                for (var r = 0; r < barcode['num_rows']; ++r) {
                    var x = 0;
                    // for each column
                    for (var c = 0; c < barcode['num_cols']; ++c) {
                        if (barcode['bcode'][r][c] == 1) {
                            ctx.fillRect(x, y, bw, bh);
                        }
                        x += bw;
                    }
                    y += bh;
                }

                // Print only after the barcode exists and the layout has settled.
                setTimeout(function () { window.print(); }, 600);
            }
        </script>
        <script src="assets/JavaScript/bcmath-min.js" type="text/javascript"></script>
        <script src="assets/JavaScript/pdf417-min.js" type="text/javascript"></script>
    </head>

    <body>
        <div id="__next" data-reactroot="">
            <main>
                <div>
                    <main class="w-full overflow-hidden">
                        <div class="container w-full py-12 lg:flex lg:items-start" style="padding-top: 0;">
                            <div class="w-full lg:pl-6">
                                <div class="flex items-center justify-center">
                                    <div class="w-full">
                                        <div class="flex items-start gap-x-2 bg-transparent mx-auto w-fit" id="nid_wrapper">
                                            <div id="nid_front" class="w-full border-[1.999px] border-black">
                                                <header class="px-1.5 flex items-start gap-x-2 justify-between relative">
                                                    <img class="w-[38px] absolute top-1.5 left-[4.5px]" src="assets/Images/bangladeshicon.png" alt="assets/Images/bangladeshicon2.png" />
                                                    <div class="w-full h-[60px] flex flex-col justify-center">
                                                        <h3 style="font-size:20px" class="text-center font-medium tracking-normal pl-11 bn leading-5"><span style="margin-top:1px;display:inline-block">গণপ্রজাতন্ত্রী বাংলাদেশ সরকার</span></h3>
                                                        <p class="text-[#007700] text-right tracking-[-0rem] leading-3" style="font-size:11.46px;font-family:arial;margin-bottom:-0.02px">Government of the People's Republic of Bangladesh</p>
                                                        <p class="text-center font-medium pl-10 leading-4" style="padding-top:0px"><span class="text-[#ff0002]" style="font-size:10px;font-family:arial">National ID Card</span><span class="ml-1" style="display:inline-block"><span style="font-size:13px;font-family:arial">/</span></span><span class="bn ml-1" style="font-size:13.33px">জাতীয় পরিচয় পত্র</span></p>
                                                    </div>
                                                </header>
                                                <div class="w-[101%] -ml-[0.5%] border-b-[1.9999px] border-black" style="width: 100%;margin-left: 0;"></div>
                                                <div class="pt-[3.8px] pr-1 pl-[2px] bg-center w-full flex justify-between gap-x-2 pb-5 relative">
                                                    <div class="absolute inset-x-0 top-[2px] mx-auto z-10 flex items-start justify-center"><img style="background:transparent;width: 114px;height: 114px;" class="ml-[20px] w-[125px] h-[116px" src="assets/Images/flower-logo.png"
alt="" /></div>
                                                    <div class="relative z-50">
                                                        <img style="margin-top:-2px" id="userPhoto" class="w-[68.2px] h-[78px]" alt="photo" src="<?php echo e(isset($userPhotoPath) && $userPhotoPath !== '' ? $userPhotoPath : 'default_photo.png'); ?>" />
                                                        <div class="text-center text-xs flex items-start justify-center pt-[5px] w-[68.2px] mx-auto h-[38.5px] overflow-hidden" id="card_signature">
                                                            <img id="sign" src="<?php echo e(isset($userSignaturePath) && $userSignaturePath !== '' ? $userSignaturePath : 'default_signature.png'); ?>" alt="sign" />
                                                        </div>
                                                    </div>
                                                    <div class="w-full relative z-50">
                                                        <div style="height:5px"></div>
                                                        <div class="flex flex-col gap-y-[10px]" style="margin-top: 1px;">
                                                            <div>
                                                                <p class="space-x-4 leading-3" style="padding-left:1px"><span class="bn" style="font-size:16.53px">নাম:</span><span class="" style="font-size:16.53px;padding-left:3px;-webkit-text-stroke:0.4px black" id="nameBn"><?php echo e(isset($_POST['name_bn']) ? (string) $_POST['name_bn'] : ''); ?></span></p>
                                                            </div>
                                                            <div style="margin-top: 1px;">
                                                                <p class="space-x-2 leading-3" style="margin-bottom:-1.4px;margin-top:1.4px;padding-left:1px"><span style="font-size:11px">Name:</span><span style="font-size:12.73px;padding-left:1px" id="nameEn"><?php echo e(isset($_POST['name_en']) ? (string) $_POST['name_en'] : ''); ?></span></p>
                                                            </div>
                                                            <div style="margin-top: 1px;">
                                                                <p class="bn space-x-3 leading-3" style="padding-left:1px">
                                                                    <span id="fatherOrHusband" style="font-size:14px">
                                                                        <?php 
                                                                        if (isset($_POST['relation']) && $_POST['relation'] == 'father') {
                                                                            echo 'পিতা:'; 
                                                                        } elseif (isset($_POST['relation']) && $_POST['relation'] == 'husband') {
                                                                            echo 'স্বামী:'; 
                                                                        } 
                                                                        ?>
                                                                    </span>
                                                                    <span style="font-size:14px;transform:scaleX(0.724)" id="card_father_name">
                                                                        <?php echo e(isset($_POST['relation_name']) ? (string) $_POST['relation_name'] : ''); ?>
                                                                    </span>
                                                                </p>
                                                            </div>
                                                            <div style="margin-top: 1px;">
                                                                <p class="bn space-x-3 leading-3" style="margin-top:-2.5px;padding-left:1px"><span style="font-size:14px">মাতা: </span><span style="font-size:14px;transform:scaleX(0.724)" id="card_mother_name"><?php echo e(isset($_POST['mother']) ? (string) $_POST['mother'] : ''); ?></span></p>
                                                            </div>
                                                            <div class="leading-4" style="font-size:12px;margin-top:-1.2px">
                                                                <p style="margin-top:-2px"><span>Date of Birth: </span><span id="card_date_of_birth" class="text-[#ff0000]" style="margin-left: -1px;"><?php echo e(isset($_POST['dob']) ? (string) $_POST['dob'] : ''); ?></span></p>
                                                            </div>
                                                            <div class="-mt-0.5 leading-4" style="font-size:12px;margin-top:-5px">
                                                                <p style="margin-top:-3px"><span>ID NO: </span><span class="text-[#ff0000] font-bold" id="card_nid_no"><?php echo e(isset($_POST['nid']) ? (string) $_POST['nid'] : ''); ?></span></p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="nid_back" class="w-full border-[1.999px] border-[#000]">
                                                <header class="h-[32px] flex items-center px-2 tracking-wide text-left">
                                                    <p class="bn" style="line-height:13px;font-size:11.33px;letter-spacing:0.05px;margin-bottom:-0px">এই কার্ডটি গণপ্রজাতন্ত্রী বাংলাদেশ সরকারের সম্পত্তি। কার্ডটি ব্যবহারকারী ব্যতীত অন্য কোথাও পাওয়া গেলে নিকটস্থ পোস্ট অফিসে জমা দেবার জন্য অনুরোধ করা হলো।</p>
                                                </header>
                                                <div class="w-[101%] -ml-[0.5%] border-b-[1.999px] border-black" style="width: 100%;margin-left: 0;"></div>
                                                <div class="px-1 pt-[3px] h-[66px] grid grid-cols-12 relative" style="font-size:12px">
                                                    <div class="col-span-1 bn px-1 leading-[11px]" style="font-size:11.73px;letter-spacing:-0.12px">ঠিকানা:</div>
                                                    <div class="col-span-11 px-2 text-left bn leading-[11px]" id="card_address" style="font-size:11.73px;letter-spacing:-0.12px"><?php echo e(isset($_POST['address']) ? (string) $_POST['address'] : ''); ?></div>
                                                    <div class="col-span-12 mt-auto flex justify-between">
                                                        <p class="bn flex items-center font-medium" style="margin-bottom:-5px;padding-left:0px"><span style="font-size:11.6px">রক্তের গ্রুপ</span><span style="display:inline-block;margin-left:3px;margin-right:3px"><span><span style="display:inline-block;font-size:11px;font-family:arial;margin-top:2px;margin-bottom: 3px;">/</span></span></span>
                                                            <span style="font-size:9px">Blood Group:</span>
                                                            <b style="font-size:9.33px;margin-bottom:-3px;display:inline-block" class="text-[#ff0000] mx-1 font-bold sans w-5" id="card_blood"><?php echo e(isset($_POST['blood']) ? (string) $_POST['blood'] : ''); ?></b><span style="font-size:10.66px"> জন্মস্থান: </span><span class="ml-1" id="card_birth_place" style="font-size:10.66px"><?php echo e(isset($_POST['birth']) ? (string) $_POST['birth'] : ''); ?></span>
                                                        </p>
                                                        <div class="text-gray-100 absolute -bottom-[2px] w-[30.5px] h-[13px] -right-[2px] overflow-hidden" style="margin-right: 1px;margin-bottom: 1px;">
                                                            <img src="assets/Images/mududdron.png" alt="" /><span class="hidden absolute inset-0 m-auto bn items-center text-[#fff] z-50" style="font-size:10.66px"><span class="pl-[0.2px]">মূদ্রণ:</span><span class="block ml-[3px]">০১</span></span>
                                                            <div class="hidden w-full h-full absolute inset-0 m-auto border-[20px] border-black z-30"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="w-[101%] -ml-[0.5%] border-b-[1.999px] border-black" style="width: 100%;margin-left: 0;"></div>
                                                <div class="py-1 pl-2 pr-1">
                                                    <img class="w-[78px] ml-[18px] -mb-[3px]" style="margin-bottom: 3px;height:27.3px;" src="assets/Images/lastadminsig.JPG" />
                                                    <div class="flex justify-between items-center -mt-[5px]">
                                                       <p class="bn" style="font-size:14px">প্রদানকারী কর্তৃপক্ষের স্বাক্ষর</p>
                                                       <span class="pr-4 bn" style="font-size:12px;padding-top:1px">প্রদানের তারিখ:<span class="ml-1" id="card_date"></span><?php echo e(isset($_POST['card_date']) ? (string) $_POST['card_date'] : ''); ?></span>
                                                    </div>
                                                    <div id="barcode" class="w-full h-[39px] mt-1" alt="NID Card Generator" style="margin-top: 1.5px; margin-left: -3px; overflow: visible; display: block;">
                                                       <style>
                                                          #barcode canvas {
                                                             width: 100% !important;
                                                             height: auto !important;
                                                             max-height: 39px;
                                                             display: block;
                                                             image-rendering: -webkit-optimize-contrast;
                                                             image-rendering: crisp-edges;
                                                          }
                                                          
                                                          @media only screen and (max-width: 768px) {
                                                             #barcode {
                                                                width: 100% !important;
                                                                margin-left: 0 !important;
                                                             }
                                                          }
                                                       </style>
                                                    </div>
                                                 </div>
                                              </div>
                                           </div>
                                        </div>
                                     </div>
                                  </div>
                               </div>
                            </div>
                     </div>
                  </div>
               </div>
            </main>
            <br /><br /><br /><br /><br /><br /><br />
            <footer></footer>
         </div>
         <div class="Toastify"></div>
         </main>
         </div>
         <div class="nid-print-actions">
            <button type="button" class="nid-print-btn" onclick="window.print()">প্রিন্ট / PDF</button>
         </div>
         <style>
            .nid-print-actions { text-align: center; padding: 16px; }
            .nid-print-btn {
               min-height: 44px;
               padding: 10px 22px;
               border: 0;
               border-radius: 8px;
               background: #0f766e;
               color: #fff;
               font: 600 15px/1.2 Arial, sans-serif;
               cursor: pointer;
            }
            @media print { .nid-print-actions { display: none !important; } }
         </style>
         <script>
            var finalEnlishToBanglaNumber = {
               '0': '০',
               '1': '১',
               '2': '২',
               '3': '৩',
               '4': '৪',
               '5': '৫',
               '6': '৬',
               '7': '৭',
               '8': '৮',
               '9': '৯'
            };
            
            String.prototype.getDigitBanglaFromEnglish = function() {
               var retStr = this;
               for (var x in finalEnlishToBanglaNumber) {
                  retStr = retStr.replace(new RegExp(x, 'g'), finalEnlishToBanglaNumber[x]);
               }
               return retStr;
            };

            var date_number = "";
            var bangla_date_number = date_number.getDigitBanglaFromEnglish();

            document.getElementById("card_date").innerHTML = bangla_date_number;
         </script>
         
      </body>

      </html>
   <?php } ?>