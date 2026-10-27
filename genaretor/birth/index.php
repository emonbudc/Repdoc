<?php
// Central auth + wallet (replaces the old per-tool auth/config.php).
require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
require_once dirname(__DIR__, 2) . '/src/nav.php';
require_login();

// index.php — Repdoc birth certificate form.
// Presentation lives in src/assets/css/{tokens,shell,tool}.css and js/form.js;
// the certificate itself is rendered by output.php.
$repdocUser    = current_user();
$repdocBalance = user_balance($repdocUser['id']);
$repdocPrice   = tool_price('birth_generator');
$repdocIsAdmin = $repdocUser['role'] === 'admin';
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>জন্ম নিবন্ধন সনদ তৈরি করুন — Repdoc</title>
    <meta name="robots" content="noindex">
    <meta name="description" content="নিবন্ধন তথ্য দিয়ে প্রিন্ট-রেডি জন্ম নিবন্ধন সনদ তৈরি করুন — বাল্ক পেস্ট, PDF ও যাচাই করে অটো-ফিল সহ।">
    <meta name="theme-color" content="#F8FAFC">
    <link rel="icon" type="image/png" href="<?= e(repdoc_url('public/favicon.png')) ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Bengali:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/tokens.css?v=2')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/shell.css?v=2')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/tool.css?v=1')) ?>">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';</script>
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
</head>
<body class="tool-body">
    <a class="skip-link" href="#birthForm">ফর্মে যান (Skip to form)</a>

    <!-- Inline SVG sprite: outline icons only, no icon font, no emoji. -->
    <svg class="sprite" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <g id="i-phone"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.2a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/></g>
            <g id="i-sun"><circle cx="12" cy="12" r="4"/><line x1="12" y1="1.5" x2="12" y2="4"/><line x1="12" y1="20" x2="12" y2="22.5"/><line x1="3.5" y1="12" x2="6" y2="12"/><line x1="18" y1="12" x2="20.5" y2="12"/><line x1="5.6" y1="5.6" x2="7.4" y2="7.4"/><line x1="16.6" y1="16.6" x2="18.4" y2="18.4"/><line x1="5.6" y1="18.4" x2="7.4" y2="16.6"/><line x1="16.6" y1="7.4" x2="18.4" y2="5.6"/></g>
            <g id="i-moon"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></g>
            <g id="i-settings"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-2.9 1.2V21a2 2 0 1 1-4 0v-.1A1.7 1.7 0 0 0 6.1 19l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1A1.7 1.7 0 0 0 3 13.3H3a2 2 0 1 1 0-4h.1A1.7 1.7 0 0 0 4.6 6.1l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1A1.7 1.7 0 0 0 10 3.6V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 2.9 1.2l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0 1.2 2.9H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/></g>
            <g id="i-logout"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></g>
            <g id="i-user-circle"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="10" r="3"/><path d="M6.2 18.9a6 6 0 0 1 11.6 0"/></g>
            <g id="i-menu"><line x1="4" y1="7" x2="20" y2="7"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="17" x2="20" y2="17"/></g>
            <g id="i-x"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></g>
            <g id="i-chevron-up"><polyline points="18 15 12 9 6 15"/></g>
            <g id="i-info"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></g>
            <g id="i-layers"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></g>
            <g id="i-paste"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/></g>
            <g id="i-file-pdf"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><path d="M9 13h1.5a1.5 1.5 0 0 1 0 3H9z"/><line x1="14" y1="16" x2="16" y2="16"/></g>
            <g id="i-cloud-download"><polyline points="8 17 12 21 16 17"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.9 18.4A5 5 0 0 0 18 9h-1.3A8 8 0 1 0 4 16.2"/></g>
            <g id="i-cloud-upload"><polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/><path d="M20.9 18.4A5 5 0 0 0 18 9h-1.3A8 8 0 1 0 4 16.2"/></g>
            <g id="i-wand"><path d="m3 21 9-9"/><path d="M15 4V2"/><path d="M15 16v-2"/><path d="M8 9h2"/><path d="M20 9h2"/><path d="M17.8 11.8 19 13"/><path d="M17.8 6.2 19 5"/><path d="M12.2 6.2 11 5"/></g>
            <g id="i-file-text"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></g>
            <g id="i-refresh"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.5 9a9 9 0 0 1 14.9-3.4L23 10M1 14l4.6 4.4A9 9 0 0 0 20.5 15"/></g>
            <g id="i-certificate"><circle cx="12" cy="8" r="5"/><path d="M8.2 12.9 7 22l5-3 5 3-1.2-9.1"/></g>
            <g id="i-calendar"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></g>
            <g id="i-calendar-check"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><polyline points="9 16 11 18 15 14"/></g>
            <g id="i-cake"><path d="M4 21h16v-6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2z"/><path d="M4 16c1.5 0 1.5-1 3-1s1.5 1 3 1 1.5-1 3-1 1.5 1 3 1 1.5-1 3-1"/><line x1="12" y1="9" x2="12" y2="5"/><circle cx="12" cy="4" r="1"/></g>
            <g id="i-building"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="9" y1="7" x2="9" y2="7.01"/><line x1="15" y1="7" x2="15" y2="7.01"/><line x1="9" y1="12" x2="9" y2="12.01"/><line x1="15" y1="12" x2="15" y2="12.01"/><path d="M10 22v-4h4v4"/></g>
            <g id="i-map-pin"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></g>
            <g id="i-id-card"><rect x="2" y="4" width="20" height="16" rx="2"/><circle cx="8.5" cy="10" r="2"/><path d="M5 16.5a3.6 3.6 0 0 1 7 0"/><line x1="14.5" y1="9.5" x2="19" y2="9.5"/><line x1="14.5" y1="13.5" x2="19" y2="13.5"/></g>
            <g id="i-user"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></g>
            <g id="i-users"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9"/><path d="M16 3.1a4 4 0 0 1 0 7.8"/></g>
            <g id="i-female"><circle cx="12" cy="9" r="5"/><line x1="12" y1="14" x2="12" y2="22"/><line x1="9" y1="18" x2="15" y2="18"/></g>
            <g id="i-male"><circle cx="10" cy="14" r="5"/><line x1="13.5" y1="10.5" x2="20" y2="4"/><polyline points="15 4 20 4 20 9"/></g>
            <g id="i-home"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/></g>
            <g id="i-copy"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></g>
            <g id="i-pencil"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/></g>
            <g id="i-undo"><path d="M3 7v6h6"/><path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6.4 2.6L3 13"/></g>
            <g id="i-check-circle"><path d="M22 11.1V12a10 10 0 1 1-5.9-9.1"/><polyline points="22 4 12 14.01 9 11.01"/></g>
            <g id="i-eye"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></g>
            <g id="i-trash"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></g>
            <g id="i-circle"><circle cx="12" cy="12" r="9"/></g>
            <g id="i-lock"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></g>
            <g id="i-wallet"><path d="M3 7.5A2.5 2.5 0 0 1 5.5 5h11A2.5 2.5 0 0 1 19 7.5"/><rect x="3" y="7.5" width="18" height="12" rx="2"/><circle cx="16.5" cy="13.5" r="1.2"/></g>
            <g id="i-spinner"><circle cx="12" cy="12" r="9" opacity=".25"/><path d="M21 12a9 9 0 0 0-9-9"/></g>
        </defs>
    </svg>

    <!-- ─── Header ─── -->
    <header class="site-header">
        <div class="container header-inner">
            <a class="brand" href="<?= e(repdoc_url('/')) ?>" aria-label="Repdoc — হোম">
                <img class="brand-logo" src="<?= e(repdoc_url('src/assets/image/logo.png')) ?>" alt="" width="34" height="34">
                <span class="brand-text">
                    <strong>Repdoc</strong>
                    <small>জন্ম নিবন্ধন সনদ</small>
                </span>
            </a>

            <?php repdoc_render_site_nav('birth'); ?>

            <div class="header-actions">
                <button type="button" class="icon-btn theme-toggle" id="themeToggle" onclick="toggleTheme()"
                        aria-label="থিম পরিবর্তন" title="থিম পরিবর্তন">
                    <svg class="icon icon-sun" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-sun"/></svg>
                    <svg class="icon icon-moon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-moon"/></svg>
                    <span class="sr-only" id="themeToggleLabel">Dark</span>
                </button>

                <span class="user-chip" title="ক্রেডিট ব্যালেন্স">
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

    <main class="tool-main" id="mainContent">
        <div class="container">
                <div class="tool-workspace">

                    <!-- Verify (permanent entry point — no hide, no box) -->
                    <section class="verify-card" aria-label="যাচাই">
                        <div class="verify-grid">
                            <div class="verify-field">
                                <label for="fetchUbrn">জন্ম নিবন্ধন নম্বর (BRN)</label>
                                <input type="text" id="fetchUbrn" inputmode="numeric" pattern="[0-9]{17}" maxlength="17" placeholder="১৭ অংকের নিবন্ধন নম্বর লিখুন">
                            </div>
                            <div class="verify-field">
                                <label for="fetchDob">জন্ম তারিখ</label>
                                <input type="date" id="fetchDob">
                            </div>
                        </div>
                                <div class="fetch-row">
                                    <div class="captcha-wrap">
                                        <img id="fetchCaptchaImg" src="" alt="ক্যাপচা ছবি">
                                        <button class="captcha-refresh-btn" type="button" onclick="refreshCaptcha()">
                                            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-refresh"/></svg>
                                            Refresh
                                        </button>
                                    </div>
                                    <label class="sr-only" for="fetchCaptchaInput">ক্যাপচার উত্তর</label>
                                    <input type="text" id="fetchCaptchaInput" placeholder="উপরের ছবির উত্তর লিখুন" autocomplete="off">
                                    <input type="hidden" id="fetchCaptchaToken">
                                </div>
                                <div class="bulk-actions">
                                    <button class="btn-blue" type="button" onclick="doDirectFetch()">
                                        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-cloud-download"/></svg>
                                        Verify and Fill
                                    </button>
                                    <button class="btn-ghost" type="button" onclick="clearDirectFetch()">
                                        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
                                        Clear
                                    </button>
                                </div>
                                <div class="bulk-status" id="fetchStatus" role="status" aria-live="polite"></div>
                    </section>

                    <!-- Form card (hidden until verify-and-fill succeeds) -->
                    <div class="form-card" id="formCard" hidden>
                        <div class="progress-track" role="progressbar" aria-label="ফর্ম পূরণের অগ্রগতি" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-fill" id="formProgress"></div>
                        </div>

                        <div class="form-stepper" id="formStepper">
                            <div class="step-item active" data-section="0" onclick="scrollToSection(0)"><span class="step-num">১</span> সাধারণ</div>
                            <div class="step-item" data-section="1" onclick="scrollToSection(1)"><span class="step-num">২</span> জন্ম</div>
                            <div class="step-item" data-section="2" onclick="scrollToSection(2)"><span class="step-num">৩</span> ব্যক্তি</div>
                            <div class="step-item" data-section="3" onclick="scrollToSection(3)"><span class="step-num">৪</span> পিতা-মাতা</div>
                            <div class="step-item" data-section="4" onclick="scrollToSection(4)"><span class="step-num">৫</span> ঠিকানা</div>
                        </div>

                        <form action="output.php" method="post" id="birthForm" novalidate>
                            <div class="lock-note" id="formLockNote" role="status">
                                <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-lock"/></svg>
                                <span>আগে উপরে “যাচাই করে ফিল করুন” সম্পন্ন করুন — যাচাই ছাড়া ফর্মে কিছু লেখা বা জমা দেওয়া যাবে না।</span>
                            </div>
                            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:16px">
                                <span class="save-indicator" id="saveIndicator">
                                    <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-circle"/></svg>
                                    স্বয়ংক্রিয় সংরক্ষণ প্রস্তুত
                                </span>
                                <button type="button" class="preview-toggle" id="clearDraftBtn" onclick="clearAutoSaveDraft()">
                                    <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-trash"/></svg>
                                    খসড়া মুছুন
                                </button>
                            </div>

                            <!-- ১ · সাধারণ -->
                            <div class="form-section" data-section="0">
                                <p class="form-section-title"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-certificate"/></svg> সাধারণ তথ্য</p>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="issuedate"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-calendar"/></svg> সনদ ইস্যুর তারিখ</label>
                                        <input type="date" id="issuedate" name="issuedate" required>
                                        <span class="field-hint">ইস্যুর তারিখ নির্বাচন করুন</span>
                                    </div>
                                    <div class="form-group">
                                        <label for="givendate"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-calendar-check"/></svg> সনদ প্রদানের তারিখ</label>
                                        <input type="date" id="givendate" name="givendate" required>
                                        <span class="field-hint">প্রদানের তারিখ নির্বাচন করুন</span>
                                    </div>
                                    <div class="form-group">
                                        <label for="nibkar"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-building"/></svg> নিবন্ধন কার্যালয়</label>
                                        <input type="text" id="nibkar" name="nibkar" required placeholder="নিবন্ধন কার্যালয়ের নাম লিখুন">
                                        <span class="field-hint">যে অফিসে নিবন্ধন করা হয়েছে</span>
                                    </div>
                                    <div class="form-group">
                                        <label for="thanaandzilla"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-map-pin"/></svg> থানা ও জেলা</label>
                                        <input type="text" id="thanaandzilla" name="thanaandzilla" required placeholder="থানা ও জেলা লিখুন">
                                        <span class="field-hint">যেমন: সোনাতলা, বগুড়া</span>
                                    </div>
                                    <div class="form-group">
                                        <label for="birth_registration_number"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-id-card"/></svg> জন্ম নিবন্ধন নং</label>
                                        <input type="text" id="birth_registration_number" name="birth_registration_number" required placeholder="১৭ সংখ্যার নিবন্ধন নম্বর লিখুন" maxlength="17">
                                        <span class="field-hint">১৭ অংকের নিবন্ধন নম্বর</span>
                                    </div>
                                    <div class="form-group">
                                        <label for="gender"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-user"/></svg> লিঙ্গ</label>
                                        <select id="gender" name="gender" required>
                                            <option value="">-- নির্বাচন করুন --</option>
                                            <option value="Male">পুরুষ</option>
                                            <option value="Female">মহিলা</option>
                                        </select>
                                        <span class="field-hint">লিঙ্গ নির্বাচন করুন</span>
                                    </div>
                                </div>
                            </div>

                            <!-- ২ · জন্ম -->
                            <div class="form-section" data-section="1">
                                <p class="form-section-title"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-cake"/></svg> জন্ম তথ্য</p>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="dob"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-calendar"/></svg> জন্ম তারিখ</label>
                                        <input type="date" id="dob" name="dob" required onchange="updateDobText()">
                                        <span class="field-hint">জন্ম তারিখ নির্বাচন করুন</span>
                                    </div>
                                    <div class="form-group">
                                        <label for="dobt"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-file-text"/></svg> জন্ম তারিখ (কথায়)</label>
                                        <input type="text" id="dobt" name="dobt" required readonly placeholder="উপরের তারিখ থেকে স্বয়ংক্রিয়ভাবে পূরণ হবে">
                                        <span class="field-hint">তারিখ থেকে স্বয়ংক্রিয়ভাবে পূরণ হয়</span>
                                    </div>
                                    <div class="form-group col-span-2">
                                        <label for="bp_bn"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-map-pin"/></svg> জন্মস্থান (বাংলায়)</label>
                                        <input type="text" id="bp_bn" name="bp_bn" required placeholder="জন্মস্থান বাংলায়">
                                        <span class="field-hint">যেমন: ঢাকা, বাংলাদেশ</span>
                                    </div>
                                    <div class="form-group col-span-2">
                                        <label for="bp_en"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-map-pin"/></svg> জন্মস্থান (ইংরেজিতে)</label>
                                        <input type="text" id="bp_en" name="bp_en" required placeholder="ইংরেজিতে জন্মস্থান লিখুন">
                                        <span class="field-hint">ইংরেজিতে জন্মস্থান লিখুন</span>
                                    </div>
                                </div>
                            </div>

                            <!-- ৩ · ব্যক্তি -->
                            <div class="form-section" data-section="2">
                                <p class="form-section-title"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-user"/></svg> নিবন্ধিত ব্যক্তির নাম</p>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="name_bn"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-user"/></svg> নাম (বাংলায়)</label>
                                        <input type="text" id="name_bn" name="name_bn" required placeholder="বাংলায় নাম লিখুন">
                                        <span class="field-hint">পূর্ণ নাম বাংলায়</span>
                                    </div>
                                    <div class="form-group">
                                        <label for="name_en"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-user"/></svg> নাম (ইংরেজিতে)</label>
                                        <input type="text" id="name_en" name="name_en" required placeholder="ইংরেজিতে পূর্ণ নাম লিখুন">
                                        <span class="field-hint">ইংরেজিতে বড় হরফে পূর্ণ নাম লিখুন</span>
                                    </div>
                                </div>
                            </div>

                            <!-- ৪ · পিতা-মাতা -->
                            <div class="form-section" data-section="3">
                                <p class="form-section-title"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-users"/></svg> পিতা-মাতার তথ্য</p>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="mother_name_bn"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-female"/></svg> মাতার নাম (বাংলায়)</label>
                                        <input type="text" id="mother_name_bn" name="mother_name_bn" required placeholder="মাতার নাম বাংলায়">
                                    </div>
                                    <div class="form-group">
                                        <label for="mother_name_en"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-female"/></svg> মাতার নাম (ইংরেজিতে)</label>
                                        <input type="text" id="mother_name_en" name="mother_name_en" required placeholder="ইংরেজিতে মাতার নাম লিখুন">
                                    </div>
                                    <div class="form-group">
                                        <label for="father_name_bn"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-male"/></svg> পিতার নাম (বাংলায়)</label>
                                        <input type="text" id="father_name_bn" name="father_name_bn" required placeholder="পিতার নাম বাংলায়">
                                    </div>
                                    <div class="form-group">
                                        <label for="father_name_en"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-male"/></svg> পিতার নাম (ইংরেজিতে)</label>
                                        <input type="text" id="father_name_en" name="father_name_en" required placeholder="ইংরেজিতে পিতার নাম লিখুন">
                                    </div>
                                </div>
                            </div>

                            <!-- ৫ · ঠিকানা -->
                            <div class="form-section" data-section="4">
                                <p class="form-section-title"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-home"/></svg> স্থায়ী ঠিকানা</p>
                                <div class="form-grid">
                                    <div class="form-group col-span-2">
                                        <label for="addressx"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-home"/></svg> স্থায়ী ঠিকানা (বাংলায়)</label>
                                        <input type="text" id="addressx" name="addressx" required placeholder="ঠিকানা বাংলায়">
                                        <span class="field-hint">গ্রাম/শহর, থানা, জেলা, দেশ</span>
                                    </div>
                                    <div class="form-group col-span-2">
                                        <label for="address"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-home"/></svg> স্থায়ী ঠিকানা (ইংরেজিতে)</label>
                                        <input type="text" id="address" name="address" required placeholder="ইংরেজিতে ঠিকানা লিখুন">
                                        <span class="field-hint">গ্রাম/শহর, থানা, জেলা, দেশ ইংরেজিতে লিখুন</span>
                                    </div>
                                </div>
                            </div>

                            <div class="checkbox-row">
                                <div class="checkbox-group">
                                    <input type="checkbox" id="is_duplicate" name="is_duplicate">
                                    <label for="is_duplicate"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-copy"/></svg> প্রতিলিপি</label>
                                </div>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="is_corrected" name="is_corrected">
                                    <label for="is_corrected"><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-pencil"/></svg> সংশোধিত</label>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="button" id="resetBtn" class="btn-reset" disabled>
                                    <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-undo"/></svg>
                                    রিসেট করুন
                                </button>
                                <button type="submit" id="submitBtn" class="btn-submit" disabled>
                                    <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-check-circle"/></svg>
                                    যাচাই ও ডাউনলোড
                                </button>
                            </div>
                        </form>
                    </div><!-- /form-card -->
                </div>
        </div>
    </main>

    <!-- ─── Footer ─── -->
    <footer class="site-footer">
        <div class="container footer-grid">
            <div class="footer-brand">
                <a class="brand" href="<?= e(repdoc_url('/')) ?>">
                    <img class="brand-logo" src="<?= e(repdoc_url('src/assets/image/logo.png')) ?>" alt="" width="32" height="32" loading="lazy">
                    <span class="brand-text"><strong>Repdoc</strong><small>repdoc.freedev.app</small></span>
                </a>
                <p>ডকুমেন্ট তৈরি, রেকর্ড অনুসন্ধান ও ডাউনলোডের অনলাইন টুলস।</p>
            </div>
        </div>
        <div class="container footer-bottom">
            <p>&copy; <?= date('Y') ?> Repdoc. স্বাধীন টুল সাইট — সরকারি পোর্টাল নয়।</p>
        </div>
    </footer>

    <!-- Loading overlay -->
    <div class="loading-overlay" id="loadingOverlay" role="status" aria-live="polite">
        <div class="loading-spinner" aria-hidden="true"></div>
        <div class="loading-text">সনদ তৈরি হচ্ছে…</div>
    </div>

    <script src="js/form.js?v=3" defer></script>
</body>
</html>
