<?php
require_once __DIR__ . '/auth/config.php';
requireLogin();
// index.php — RepDoc Birth Certificate Form (BDRIS-inspired Professional Dynamic)
// Domain: repdoc.xyz
// Session check performed — user is authenticated
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>জন্ম নিবন্ধন সনদ ফর্ম — Birth Certificate Registry</title>
    <meta name="robots" content="noindex">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+Bengali:wght@400;600;700&family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';</script>
    <!-- Apply saved theme before paint to avoid flash of wrong theme -->
    <script>
        (function () {
            try {
                var t = localStorage.getItem('siteTheme');
                if (t === 'light') document.documentElement.setAttribute('data-theme', 'light');
            } catch (e) {}
        })();
    </script>

    <style>
        /* ═══════════════════════════════
           DESIGN TOKENS — PREMIUM DARK DASHBOARD
        ═══════════════════════════════ */
        :root {
            --blue:       #3b82f6;
            --blue-dark:  #2563eb;
            --blue-darker:#1d4ed8;
            --blue-light: rgba(56,189,248,.16);
            --blue-mid:   #38bdf8;
            --blue-subtle:rgba(56,189,248,.08);
            --blue-glow:  rgba(56,189,248,.35);
            --cyan:       #22d3ee;

            --bg:         #070b16;
            --bg2:        #0d1322;
            --surface:    rgba(20,28,48,.72);
            --surface-solid:#121a2e;
            --surface2:   rgba(30,41,66,.6);
            --border:     rgba(148,163,184,.14);
            --border-hi:  rgba(148,163,184,.32);
            --text:       #e6edf7;
            --text-dim:   #9fb0c8;
            --text-muted: #64748b;
            --label:      #cbd9f0;
            --input-bg:   rgba(8,13,26,.6);
            --input-border:rgba(148,163,184,.22);
            --shadow:     rgba(0,0,0,.4);
            --shadow-lg:  rgba(0,0,0,.55);
            --danger:     #f87171;
            --warn:       #fbbf24;
            --success:    #34d399;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }

        body {
            font-family: 'Noto Serif Bengali', 'Inter', sans-serif;
            background:
                radial-gradient(1100px 600px at 12% -8%, rgba(56,189,248,.10), transparent 60%),
                radial-gradient(900px 500px at 100% 0%, rgba(99,102,241,.12), transparent 55%),
                radial-gradient(800px 600px at 50% 120%, rgba(34,211,238,.06), transparent 60%),
                var(--bg);
            background-attachment: fixed;
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        a { text-decoration: none; color: var(--blue); }
        a:hover { text-decoration: underline; }
        ul { list-style: none; }

        .container {
            width: 100%;
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* ── Scrollbar ── */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: var(--bg); }
        ::-webkit-scrollbar-thumb { background: linear-gradient(var(--blue),var(--cyan)); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--cyan); }

        /* ═══════════════════════════════
           TOP BAR
        ═══════════════════════════════ */
        .top-bar {
            background: rgba(6,10,20,.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            color: rgba(230,237,247,.78);
            font-size: .8rem;
            font-family: 'Inter', sans-serif;
        }
        .top-bar .container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 34px;
        }
        .top-bar-left { display: flex; align-items: center; gap: 8px; }
        .top-bar-left i { font-size: .72rem; }
        .top-bar-right { display: flex; align-items: center; gap: 4px; }
        .top-bar-right a {
            color: rgba(230,237,247,.82);
            padding: 3px 10px;
            border-radius: 6px;
            font-size: .76rem;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: background .2s, color .2s;
        }
        .top-bar-right a:hover { background: var(--blue-light); color: var(--cyan); text-decoration: none; }
        .top-bar-right .lang-btn { border-left: 1px solid var(--border); border-radius: 0; }

        /* ═══════════════════════════════
           HEADER
        ═══════════════════════════════ */
        .main-header {
            background: rgba(13,19,34,.75);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border);
            box-shadow: 0 8px 32px rgba(0,0,0,.45), 0 1px 0 rgba(56,189,248,.25);
            position: sticky;
            top: 0;
            z-index: 200;
        }
        .main-header::after {
            content: '';
            position: absolute;
            left: 0; right: 0; bottom: -1px;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--blue), var(--cyan), transparent);
            opacity: .8;
        }
        .header-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 6px 0;
            flex-wrap: wrap;
        }
        .logo-area {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }
        .logo-area img {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            border: 1px solid var(--border-hi);
            background: #fff;
            object-fit: contain;
            padding: 2px;
            box-shadow: 0 0 0 4px rgba(56,189,248,.12), 0 0 18px rgba(56,189,248,.25);
        }
        .logo-text { display: flex; flex-direction: column; }
        .logo-text .logo-title {
            font-size: .9rem;
            font-weight: 700;
            color: #fff;
            line-height: 1.2;
        }
        .logo-text .logo-subtitle {
            font-size: .64rem;
            color: var(--text-dim);
            font-family: 'Inter', sans-serif;
            line-height: 1.3;
        }

        .main-nav ul { display: flex; gap: 4px; flex-wrap: wrap; }
        .main-nav > ul > li > a {
            color: var(--text-dim);
            padding: 9px 14px;
            font-size: .8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 4px;
            border-radius: 8px;
            transition: background .2s, color .2s, box-shadow .2s;
        }
        .main-nav > ul > li > a:hover,
        .main-nav > ul > li > a.active {
            background: linear-gradient(135deg, var(--blue), var(--cyan));
            color: #04111f;
            text-decoration: none;
            box-shadow: 0 4px 16px rgba(56,189,248,.35);
        }
        .main-nav > ul > li > a i.fa-chevron-down { font-size: .55rem; margin-top: 2px; }
        .main-nav .dropdown { position: relative; }
        .main-nav .dropdown-menu {
            display: none;
            position: absolute;
            top: calc(100% + 6px);
            left: 0;
            background: rgba(17,26,46,.96);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            min-width: 250px;
            border-radius: 12px;
            box-shadow: 0 20px 48px rgba(0,0,0,.6);
            z-index: 300;
            padding: 6px 0;
            border: 1px solid var(--border);
            border-top: 2px solid var(--cyan);
            overflow: hidden;
        }
        .main-nav .dropdown:hover .dropdown-menu { display: block; animation: fadeDown .2s ease; }
        @keyframes fadeDown { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: translateY(0); } }
        .main-nav .dropdown-menu li a {
            display: block;
            padding: 9px 18px;
            font-size: .78rem;
            color: var(--text-dim);
            border-bottom: 1px solid var(--border);
            transition: background .15s, color .15s, padding-left .2s;
        }
        .main-nav .dropdown-menu li:last-child a { border-bottom: none; }
        .main-nav .dropdown-menu li a:hover { background: var(--blue-light); color: var(--cyan); text-decoration: none; padding-left: 24px; }

        .nav-toggle {
            display: none;
            background: none;
            border: 1px solid var(--border-hi);
            color: var(--text);
            padding: 7px 12px;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
        }

        /* ═══════════════════════════════
           MAIN LAYOUT
        ═══════════════════════════════ */
        .main-content { flex: 1; padding: 24px 0; }
        .content-row { display: flex; gap: 24px; }

        /* Sidebar */
        .left-sidebar {
            width: 240px;
            flex-shrink: 0;
            background: var(--surface);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            align-self: flex-start;
            position: sticky;
            top: 100px;
            box-shadow: 0 12px 40px var(--shadow);
        }
        .left-sidebar .sidebar-title {
            background: linear-gradient(135deg, rgba(56,189,248,.18), rgba(99,102,241,.18));
            color: #fff;
            padding: 13px 16px;
            font-size: .8rem;
            font-weight: 700;
            border-bottom: 1px solid var(--border);
        }
        .left-sidebar ul li a {
            display: block;
            padding: 10px 16px;
            font-size: .78rem;
            color: var(--text-dim);
            border-bottom: 1px solid var(--border);
            transition: background .15s, color .15s, padding-left .2s;
            line-height: 1.4;
        }
        .left-sidebar ul li:last-child a { border-bottom: none; }
        .left-sidebar ul li a:hover {
            background: var(--blue-light);
            color: var(--cyan);
            padding-left: 22px;
            text-decoration: none;
        }

        .main-area { flex: 1; min-width: 0; }

        /* ═══════════════════════════════
           NOTICE BAR
        ═══════════════════════════════ */
        .notice-bar {
            background: rgba(251,191,36,.08);
            border: 1px solid rgba(251,191,36,.25);
            border-radius: 12px;
            padding: 10px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            overflow: hidden;
            margin-bottom: 16px;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }
        .notice-bar i { color: var(--warn); font-size: .9rem; flex-shrink: 0; }
        .notice-marquee { overflow: hidden; flex: 1; }
        .notice-marquee-inner {
            display: inline-block;
            white-space: nowrap;
            font-size: .78rem;
            color: #fcd34d;
            animation: marquee 30s linear infinite;
        }
        @keyframes marquee {
            from { transform: translateX(100%); }
            to   { transform: translateX(-100%); }
        }

        /* ═══════════════════════════════
           BULK PANEL
        ═══════════════════════════════ */
        .bulk-panel {
            background: var(--surface);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 20px;
            box-shadow: 0 12px 40px var(--shadow);
        }
        .bulk-panel-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 13px 18px;
            background: rgba(255,255,255,.02);
            border-bottom: 1px solid var(--border);
            cursor: pointer;
            user-select: none;
            transition: background .2s;
        }
        .bulk-panel-header:hover { background: var(--blue-light); }
        .bulk-panel-header i.icon { color: var(--cyan); }
        .bulk-panel-title {
            font-weight: 600;
            font-size: .84rem;
            flex: 1;
            color: var(--label);
        }
        .bulk-toggle-btn {
            background: var(--surface2);
            border: 1px solid var(--border);
            color: var(--text-dim);
            padding: 4px 12px;
            border-radius: 8px;
            font-size: .74rem;
            cursor: pointer;
            display: flex; align-items: center; gap: 5px;
            transition: all .2s;
            font-family: 'Inter', sans-serif;
        }
        .bulk-toggle-btn:hover { background: var(--blue); color: #04111f; border-color: var(--blue); }

        .bulk-body { display: block; padding: 0; }
        .bulk-tabs {
            display: flex;
            gap: 0;
            padding: 0;
            border-bottom: 1px solid var(--border);
            background: rgba(0,0,0,.18);
        }
        .bulk-tab {
            padding: 10px 20px;
            font-size: .79rem;
            font-weight: 600;
            background: transparent;
            border: none;
            border-bottom: 2px solid transparent;
            color: var(--text-dim);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all .2s;
            font-family: 'Noto Serif Bengali', sans-serif;
        }
        .bulk-tab:hover { color: var(--cyan); background: var(--blue-subtle); }
        .bulk-tab.active {
            color: var(--cyan);
            background: rgba(255,255,255,.03);
            border-bottom-color: var(--cyan);
        }
        .bulk-tab-content { display: none; padding: 18px; }
        .bulk-tab-content.active { display: block; animation: fadeIn .25s ease; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        .fetch-row { margin-bottom: 14px; }
        .fetch-row:last-child { margin-bottom: 0; }
        .fetch-row label { font-size: .78rem; font-weight: 600; color: var(--label); display: block; margin-bottom: 4px; }
        .fetch-row label i { margin-right: 5px; }

        .fetch-row input[type="text"],
        .fetch-row input[type="date"],
        .fetch-row input[type="number"] {
            width: 100%;
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 8px;
            color: var(--text);
            font-family: 'Inter', sans-serif;
            font-size: .86rem;
            padding: 9px 13px;
            transition: border-color .2s, box-shadow .2s;
        }
        .fetch-row input::placeholder { color: var(--text-muted); }
        .fetch-row input[type="date"]::-webkit-calendar-picker-indicator { filter: invert(.8); cursor: pointer; }
        .fetch-row input:focus {
            outline: none;
            border-color: var(--cyan);
            box-shadow: 0 0 0 3px var(--blue-glow);
        }

        .captcha-wrap { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 10px; }
        .captcha-wrap img {
            border: 1px solid var(--input-border);
            border-radius: 6px;
            background: var(--input-bg);
            max-width: 150px;
            height: auto;
        }
        .captcha-refresh-btn {
            background: var(--surface2);
            border: 1px solid var(--border);
            color: var(--text-dim);
            padding: 6px 12px;
            border-radius: 6px;
            font-size: .76rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all .2s;
            font-family: 'Inter', sans-serif;
        }
        .captcha-refresh-btn:hover { background: var(--blue); color: #04111f; border-color: var(--blue); }

        .bulk-textarea {
            width: 100%;
            height: 100px;
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 8px;
            color: var(--text);
            font-size: .84rem;
            padding: 10px 12px;
            resize: vertical;
            transition: border-color .2s, box-shadow .2s;
        }
        .bulk-textarea::placeholder { color: var(--text-muted); }
        .bulk-textarea:focus {
            outline: none;
            border-color: var(--cyan);
            box-shadow: 0 0 0 3px var(--blue-glow);
        }

        .bulk-actions { display: flex; gap: 8px; margin-top: 8px; }

        .btn-blue {
            background: linear-gradient(135deg, var(--blue), var(--cyan));
            color: #04111f;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: .8rem;
            font-weight: 700;
            cursor: pointer;
            display: flex; align-items: center; gap: 6px;
            transition: all .2s;
            font-family: 'Inter', sans-serif;
        }
        .btn-blue:hover { transform: translateY(-1px); box-shadow: 0 8px 22px var(--blue-glow); filter: brightness(1.08); }

        .btn-ghost {
            background: var(--surface2);
            color: var(--text-dim);
            border: 1px solid var(--border);
            padding: 8px 14px;
            border-radius: 6px;
            font-size: .8rem;
            cursor: pointer;
            display: flex; align-items: center; gap: 6px;
            transition: all .2s;
            font-family: 'Inter', sans-serif;
        }
        .btn-ghost:hover { background: var(--surface-solid); border-color: var(--border-hi); }

        .pdf-dropzone {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
            height: 100px;
            border: 2px dashed var(--input-border);
            border-radius: 8px;
            background: var(--input-bg);
            cursor: pointer;
            text-align: center;
            padding: 10px;
            transition: all .2s;
        }
        .pdf-dropzone:hover, .pdf-dropzone.drag-over {
            border-color: var(--cyan);
            background: var(--blue-subtle);
        }
        .pdf-dropzone i { font-size: 1.3rem; color: var(--cyan); }
        .pdf-dropzone span { font-size: .76rem; color: var(--text-dim); }
        .pdf-filename { font-size: .7rem; color: var(--cyan); word-break: break-all; }

        .bulk-status {
            margin-top: 8px;
            font-size: .8rem;
            display: none;
            padding: 6px 10px;
            border-radius: 8px;
            background: var(--blue-subtle);
        }

        /* ═══════════════════════════════
           FORM SECTION STEPPER (DYNAMIC)
        ═══════════════════════════════ */
        .form-stepper {
            display: flex;
            gap: 0;
            margin-bottom: 24px;
            padding: 0;
            background: rgba(0,0,0,.2);
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--border);
        }
        .step-item {
            flex: 1;
            padding: 12px 10px;
            text-align: center;
            font-size: .75rem;
            font-weight: 600;
            color: var(--text-muted);
            cursor: pointer;
            transition: all .3s;
            border-right: 1px solid var(--border);
            position: relative;
            font-family: 'Inter', sans-serif;
        }
        .step-item:last-child { border-right: none; }
        .step-item:hover { background: var(--blue-subtle); }
        .step-item.active {
            background: linear-gradient(135deg, var(--blue), var(--cyan));
            color: #04111f;
        }
        .step-item.completed {
            background: rgba(52,211,153,.14);
            color: var(--success);
        }
        .step-item .step-num {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            font-size: .7rem;
            font-weight: 700;
            margin-right: 6px;
            background: rgba(255,255,255,.08);
        }
        .step-item.active .step-num { background: rgba(4,17,31,.35); color: #04111f; }
        .step-item.completed .step-num { background: var(--success); color: #04111f; }

        /* ═══════════════════════════════
           MAIN FORM CARD
        ═══════════════════════════════ */
        .form-card {
            position: relative;
            background: var(--surface);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: clamp(20px, 3vw, 32px);
            box-shadow: 0 20px 60px var(--shadow-lg), inset 0 1px 0 rgba(255,255,255,.04);
        }
        .form-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: url('img/gov_logo.png');
            background-size: 180px;
            background-position: center;
            background-repeat: no-repeat;
            opacity: .04;
            z-index: 0;
            pointer-events: none;
        }
        .form-card > * { position: relative; z-index: 1; }

        /* Progress */
        .progress-track {
            width: 100%;
            height: 6px;
            background: rgba(255,255,255,.06);
            border-radius: 6px;
            margin-bottom: 22px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, var(--blue), var(--cyan));
            border-radius: 6px;
            box-shadow: 0 0 12px var(--blue-glow);
            transition: width .5s ease;
        }

        .form-section-title {
            font-size: .73rem;
            font-weight: 700;
            color: var(--text-muted);
            letter-spacing: .4px;
            text-transform: uppercase;
            margin: 0 0 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .form-section-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
            margin-bottom: 22px;
        }
        @media(max-width: 640px) {
            .form-grid { grid-template-columns: 1fr; gap: 14px; }
        }

        .form-group { display: flex; flex-direction: column; gap: 5px; }
        .form-group.col-span-2 { grid-column: span 2; }
        @media(max-width: 640px) { .form-group.col-span-2 { grid-column: span 1; } }

        .form-group label {
            font-size: .78rem;
            font-weight: 600;
            color: var(--label);
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .form-group label i { font-size: .72rem; opacity: .65; }
        .form-group .field-hint {
            font-size: .68rem;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .form-group input[type="text"],
        .form-group input[type="date"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 9px;
            color: var(--text);
            font-size: .86rem;
            padding: 10px 13px;
            transition: border-color .2s, box-shadow .2s, background .2s;
            appearance: none;
            -webkit-appearance: none;
        }
        .form-group input::placeholder, .form-group textarea::placeholder { color: var(--text-muted); }
        .form-group input[type="date"]::-webkit-calendar-picker-indicator { filter: invert(.8); cursor: pointer; }
        .form-group select option { background: var(--surface-solid); color: var(--text); }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--cyan);
            box-shadow: 0 0 0 3px var(--blue-glow);
            background: rgba(8,13,26,.85);
        }
        .form-group input:hover,
        .form-group select:hover {
            border-color: var(--blue-mid);
        }
        .form-group input[readonly] {
            opacity: .65;
            cursor: not-allowed;
            background: rgba(255,255,255,.03);
        }
        .form-group input.invalid,
        .form-group select.invalid {
            border-color: var(--danger) !important;
            box-shadow: 0 0 0 3px rgba(248,113,113,.18) !important;
        }
        .form-group input.valid {
            border-color: var(--success) !important;
            box-shadow: 0 0 0 3px rgba(52,211,153,.16) !important;
        }

        .form-group select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%2322d3ee' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 13px center;
            padding-right: 36px;
            cursor: pointer;
        }

        .checkbox-row {
            display: flex;
            gap: 24px;
            flex-wrap: wrap;
            padding: 12px 0 4px;
            border-top: 1px solid var(--border);
            margin-top: 4px;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 9px;
            cursor: pointer;
        }
        .checkbox-group input[type="checkbox"] {
            width: 18px; height: 18px;
            appearance: none;
            -webkit-appearance: none;
            background: var(--input-bg);
            border: 1.5px solid var(--input-border);
            border-radius: 5px;
            cursor: pointer;
            flex-shrink: 0;
            transition: all .2s;
            position: relative;
        }
        .checkbox-group input[type="checkbox"]:checked {
            background: linear-gradient(135deg, var(--blue), var(--cyan));
            border-color: var(--cyan);
        }
        .checkbox-group input[type="checkbox"]:checked::after {
            content: '✓';
            position: absolute;
            color: #04111f;
            font-size: 11px;
            font-weight: 700;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
        }

        .form-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
        }
        .btn-submit {
            flex: 1;
            min-width: 180px;
            padding: 13px 24px;
            background: linear-gradient(135deg, var(--blue), var(--cyan));
            color: #04111f;
            font-size: .9rem;
            font-weight: 800;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            transition: all .25s;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 24px var(--blue-glow);
        }
        .btn-submit::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,.15), transparent);
            transform: translateX(-100%);
            transition: transform .5s;
        }
        .btn-submit:hover::after { transform: translateX(100%); }
        .btn-submit:hover {
            transform: translateY(-2px);
            filter: brightness(1.08);
            box-shadow: 0 12px 34px var(--blue-glow);
        }
        .btn-reset {
            padding: 12px 20px;
            background: var(--surface2);
            color: var(--text-dim);
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: .86rem;
            font-weight: 500;
            cursor: pointer;
            display: flex; align-items: center; gap: 7px;
            transition: all .2s;
            font-family: 'Inter', sans-serif;
        }
        .btn-reset:hover {
            background: var(--surface-solid);
            border-color: var(--blue-mid);
            transform: translateY(-1px);
        }

        /* ── Offline/autosave indicator ── */
        .save-indicator {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: .7rem;
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            transition: all .3s;
        }
        .save-indicator.saving { background: var(--blue-light); color: var(--cyan); }
        .save-indicator.saved { background: rgba(52,211,153,.14); color: var(--success); }
        .save-indicator .fa { font-size: .65rem; }

        /* ═══════════════════════════════
           CERTIFICATE PREVIEW PANEL (DYNAMIC)
        ═══════════════════════════════ */
        .preview-toggle {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            background: var(--surface2);
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: .76rem;
            color: var(--text-dim);
            cursor: pointer;
            transition: all .2s;
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            margin-left: auto;
        }
        .preview-toggle:hover { background: var(--blue-light); color: var(--cyan); border-color: var(--blue-mid); }
        .preview-toggle.active { background: linear-gradient(135deg, var(--blue), var(--cyan)); color: #04111f; border-color: var(--cyan); }
        #clearDraftBtn:hover { background: rgba(248,113,113,.14); color: var(--danger); border-color: rgba(248,113,113,.4); }

        .cert-preview-panel {
            display: none;
            margin-top: 20px;
            border: 1px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
            background: rgba(0,0,0,.25);
            animation: fadeIn .3s ease;
        }
        .cert-preview-panel.visible { display: block; }

        .cert-preview-header {
            background: linear-gradient(135deg, rgba(56,189,248,.2), rgba(99,102,241,.2));
            color: #fff;
            padding: 12px 18px;
            font-size: .78rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
            border-bottom: 1px solid var(--border);
        }
        .cert-preview-body {
            padding: 20px 24px;
            font-size: .82rem;
            line-height: 1.8;
        }
        .cert-preview-body .preview-row {
            display: flex;
            border-bottom: 1px dashed var(--border);
            padding: 5px 0;
        }
        .cert-preview-body .preview-row:last-child { border-bottom: none; }
        .cert-preview-body .preview-label {
            width: 180px;
            flex-shrink: 0;
            font-weight: 600;
            color: var(--text-muted);
            font-size: .76rem;
        }
        .cert-preview-body .preview-value {
            flex: 1;
            color: var(--text);
            font-weight: 500;
        }
        .cert-preview-body .preview-value.empty {
            color: var(--text-muted);
            font-style: italic;
            font-weight: 400;
        }
        .cert-preview-watermark {
            text-align: center;
            padding: 12px;
            font-size: .65rem;
            color: var(--text-muted);
            border-top: 1px solid var(--border);
            opacity: .7;
        }

        /* ═══════════════════════════════
           LOADING OVERLAY
        ═══════════════════════════════ */
        .loading-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(4,8,18,.75);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 14px;
            backdrop-filter: blur(6px);
        }
        .loading-spinner {
            width: 44px; height: 44px;
            border: 3px solid rgba(255,255,255,.12);
            border-top-color: var(--cyan);
            border-radius: 50%;
            box-shadow: 0 0 20px var(--blue-glow);
            animation: spin 1s linear infinite;
        }
        .loading-text { font-size: .85rem; color: #fff; font-family: 'Inter', sans-serif; }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ═══════════════════════════════
           FOOTER
        ═══════════════════════════════ */
        .site-footer {
            background: rgba(6,10,20,.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            color: var(--text-dim);
            padding: 28px 0 0;
            margin-top: auto;
            border-top: 1px solid var(--border);
            box-shadow: 0 -1px 0 rgba(56,189,248,.2);
        }
        .footer-inner {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 2fr;
            gap: 28px;
            padding-bottom: 22px;
        }
        .footer-col h5 {
            color: #fff;
            font-size: .83rem;
            font-weight: 700;
            margin-bottom: 10px;
            padding-bottom: 7px;
            border-bottom: 1px solid var(--border);
        }
        .footer-col p, .footer-col li {
            font-size: .76rem;
            line-height: 1.7;
            color: var(--text-dim);
        }
        .footer-col ul li { margin-bottom: 3px; }
        .footer-col ul li a { color: var(--text-dim); font-size: .76rem; }
        .footer-col ul li a:hover { color: var(--cyan); }
        .footer-social { display: flex; gap: 8px; margin-top: 8px; }
        .footer-social a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 34px; height: 34px;
            border-radius: 50%;
            background: rgba(255,255,255,.06);
            color: var(--text);
            font-size: .95rem;
            transition: all .2s;
        }
        .footer-social a:hover { background: linear-gradient(135deg, var(--blue), var(--cyan)); color: #04111f; text-decoration: none; transform: translateY(-2px); }
        .footer-bottom {
            background: rgba(4,8,18,.6);
            border-top: 1px solid var(--border);
            padding: 14px 0;
            text-align: center;
            font-size: .73rem;
            color: var(--text-muted);
        }

        /* ═══════════════════════════════
           RESPONSIVE
        ═══════════════════════════════ */
        @media(max-width: 992px) {
            .content-row { flex-direction: column; }
            .left-sidebar {
                width: 100%;
                position: static;
                margin-bottom: 0;
            }
            .left-sidebar ul { display: flex; flex-wrap: wrap; }
            .left-sidebar ul li { flex: 1 1 50%; }
            .footer-inner { grid-template-columns: 1fr 1fr; }
        }
        @media(max-width: 768px) {
            .header-inner { flex-direction: column; align-items: flex-start; gap: 8px; }
            .main-nav { width: 100%; }
            .main-nav ul { flex-direction: column; width: 100%; }
            .main-nav > ul > li > a { border-radius: 5px; }
            .main-nav .dropdown-menu {
                position: static;
                box-shadow: none;
                border: none;
                border-left: 3px solid var(--cyan);
                border-radius: 0;
                background: rgba(255,255,255,.03);
            }
            .main-nav .dropdown-menu li a { color: var(--text-dim); }
            .main-nav .dropdown-menu li a:hover { background: rgba(255,255,255,.06); }
            .footer-inner { grid-template-columns: 1fr; gap: 18px; }
            .left-sidebar ul li { flex: 1 1 100%; }
            .form-actions { flex-direction: column; }
            .btn-submit, .btn-reset { width: 100%; }
            .captcha-wrap { flex-direction: column; }
            .form-stepper { flex-wrap: wrap; }
            .step-item { flex: 1 1 50%; border-bottom: 1px solid var(--border); }
        }
        @media(max-width: 480px) {
            .top-bar .container { flex-direction: column; gap: 4px; padding: 4px 15px; }
            .logo-area img { width: 38px; height: 38px; }
            .logo-text .logo-title { font-size: .78rem; }
            .logo-text .logo-subtitle { font-size: .58rem; }
            .bulk-tab { padding: 8px 12px; font-size: .73rem; }
            .step-item { flex: 1 1 100%; }
            .cert-preview-body .preview-row { flex-direction: column; gap: 2px; }
            .cert-preview-body .preview-label { width: 100%; }
        }

        /* ── Animations ── */
        .form-group {
            opacity: 0;
            transform: translateY(10px);
            transition: opacity .45s ease, transform .45s ease;
        }
        .form-group.revealed {
            opacity: 1;
            transform: translateY(0);
        }

        .pulse-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--blue);
            animation: pulse 1.5s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: .5; transform: scale(.85); }
        }

        .section-collapse {
            overflow: hidden;
            max-height: 0;
            transition: max-height .45s ease, opacity .3s ease, margin .3s ease;
            opacity: 0;
        }
        .section-collapse.open {
            max-height: 2000px;
            opacity: 1;
        }

        .floating-tooltip {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: rgba(17,26,46,.95);
            backdrop-filter: blur(12px);
            border: 1px solid var(--border);
            color: #fff;
            padding: 10px 18px;
            border-radius: 12px;
            font-size: .78rem;
            font-weight: 500;
            box-shadow: 0 12px 32px rgba(0,0,0,.5);
            z-index: 100;
            display: none;
            align-items: center;
            gap: 8px;
            animation: slideUp .35s ease;
            font-family: 'Inter', sans-serif;
        }
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        /* ═══════════════════════════════
           THEME TOGGLE BUTTON
        ═══════════════════════════════ */
        .theme-toggle {
            background: none;
            border: none;
            color: inherit;
            padding: 3px 10px;
            border-radius: 6px;
            font-size: .76rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
            font-family: 'Inter', sans-serif;
            transition: background .2s, color .2s;
        }
        .theme-toggle:hover { background: var(--blue-light); color: var(--cyan); }

        /* ═══════════════════════════════
           LIGHT THEME OVERRIDES
        ═══════════════════════════════ */
        html[data-theme="light"] {
            --blue:       #2563eb;
            --blue-dark:  #1e3a6b;
            --blue-darker:#0f2547;
            --blue-light: #dbeafe;
            --blue-mid:   #3b82f6;
            --blue-subtle:rgba(37,99,235,.07);
            --blue-glow:  rgba(37,99,235,.18);
            --cyan:       #0ea5e9;

            --bg:         #f8fafc;
            --bg2:        #f1f5f9;
            --surface:    #ffffff;
            --surface-solid:#ffffff;
            --surface2:   #f0f3f8;
            --border:     #e2e8f0;
            --border-hi:  #94a3b8;
            --text:       #0f172a;
            --text-dim:   #475569;
            --text-muted: #94a3b8;
            --label:      #1e3a6b;
            --input-bg:   #fafbfc;
            --input-border:#cbd5e1;
            --shadow:     rgba(0,0,0,.06);
            --shadow-lg:  rgba(0,0,0,.1);
            --danger:     #ef4444;
            --warn:       #f59e0b;
            --success:    #10b981;
        }

        html[data-theme="light"] body {
            background:
                radial-gradient(1100px 600px at 12% -8%, rgba(37,99,235,.05), transparent 60%),
                radial-gradient(900px 500px at 100% 0%, rgba(99,102,241,.05), transparent 55%),
                var(--bg);
        }
        html[data-theme="light"] .top-bar {
            background: var(--blue-darker);
            color: rgba(255,255,255,.85);
            border-bottom: none;
        }
        html[data-theme="light"] .top-bar-right a { color: rgba(255,255,255,.85); }
        html[data-theme="light"] .top-bar-right a:hover { background: rgba(255,255,255,.12); color: #fff; }
        html[data-theme="light"] .top-bar-right .lang-btn { border-left: 1px solid rgba(255,255,255,.2); }
        html[data-theme="light"] .theme-toggle:hover { background: rgba(255,255,255,.12); color: #fff; }

        html[data-theme="light"] .main-header {
            background: var(--blue-dark);
            border-bottom: 3px solid var(--blue);
            box-shadow: 0 2px 12px rgba(0,0,0,.15);
        }
        html[data-theme="light"] .main-header::after { display: none; }
        html[data-theme="light"] .logo-area img { box-shadow: none; border: 2px solid rgba(255,255,255,.3); }
        html[data-theme="light"] .logo-text .logo-subtitle { color: rgba(255,255,255,.65); }
        html[data-theme="light"] .main-nav > ul > li > a { color: rgba(255,255,255,.82); }
        html[data-theme="light"] .main-nav > ul > li > a:hover,
        html[data-theme="light"] .main-nav > ul > li > a.active {
            background: var(--blue);
            color: #fff;
            box-shadow: none;
        }
        html[data-theme="light"] .main-nav .dropdown-menu {
            background: #fff;
            box-shadow: 0 12px 32px rgba(0,0,0,.18);
            border-top: 2px solid var(--blue);
        }
        html[data-theme="light"] .main-nav .dropdown-menu li a { color: var(--text); }
        html[data-theme="light"] .main-nav .dropdown-menu li a:hover { background: var(--blue-light); color: var(--blue-dark); }

        html[data-theme="light"] .sidebar-title { background: var(--blue-dark); }
        html[data-theme="light"] .left-sidebar ul li a:hover { color: var(--blue-dark); }
        html[data-theme="light"] .left-sidebar ul li a,
        html[data-theme="light"] .main-nav .dropdown-menu li a { border-bottom: 1px solid #f1f5f9; }

        html[data-theme="light"] .notice-bar { background: #fffbeb; border: 1px solid #fde68a; }
        html[data-theme="light"] .notice-marquee-inner { color: #92400e; }

        html[data-theme="light"] .bulk-panel-header { background: #f8fafc; }
        html[data-theme="light"] .bulk-panel-header i.icon { color: var(--blue); }
        html[data-theme="light"] .bulk-tabs { background: var(--bg2); }
        html[data-theme="light"] .bulk-tab:hover { color: var(--blue); }
        html[data-theme="light"] .bulk-tab.active { color: var(--blue); background: var(--surface); border-bottom-color: var(--blue); }
        html[data-theme="light"] .bulk-tab-content,
        html[data-theme="light"] .form-stepper,
        html[data-theme="light"] .cert-preview-panel { background: var(--surface); }

        html[data-theme="light"] .btn-blue,
        html[data-theme="light"] .btn-submit,
        html[data-theme="light"] .step-item.active,
        html[data-theme="light"] .preview-toggle.active,
        html[data-theme="light"] .footer-social a:hover,
        html[data-theme="light"] .checkbox-group input[type="checkbox"]:checked {
            background: var(--blue);
            color: #fff;
            border-color: var(--blue);
        }
        html[data-theme="light"] .step-item.active .step-num { background: rgba(255,255,255,.25); color: #fff; }
        html[data-theme="light"] .step-item.completed { background: #d1fae5; }
        html[data-theme="light"] .step-item.completed .step-num { background: var(--success); color: #fff; }
        html[data-theme="light"] .step-item .step-num { background: rgba(0,0,0,.08); }
        html[data-theme="light"] .checkbox-group input[type="checkbox"]:checked::after { color: #fff; }
        html[data-theme="light"] .pdf-dropzone i,
        html[data-theme="light"] .pdf-filename { color: var(--blue); }
        html[data-theme="light"] .btn-blue:hover { filter: none; background: var(--blue-dark); }
        html[data-theme="light"] .btn-submit:hover { filter: none; background: var(--blue-dark); }

        html[data-theme="light"] .form-stepper,
        html[data-theme="light"] .bulk-tabs { border-radius: 8px; }
        html[data-theme="light"] .progress-track { background: #e2e8f0; }
        html[data-theme="light"] .progress-fill { box-shadow: none; }
        html[data-theme="light"] .form-card { box-shadow: 0 1px 4px var(--shadow); }
        html[data-theme="light"] .form-group input:focus,
        html[data-theme="light"] .form-group select:focus,
        html[data-theme="light"] .form-group textarea:focus { background: #fff; border-color: var(--blue); }
        html[data-theme="light"] .form-group input[readonly] { background: #f1f5f9; }
        html[data-theme="light"] .form-group input[type="date"]::-webkit-calendar-picker-indicator,
        html[data-theme="light"] .fetch-row input[type="date"]::-webkit-calendar-picker-indicator { filter: none; }
        html[data-theme="light"] .form-group select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%232563eb' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
        }
        html[data-theme="light"] .form-group select option { background: #fff; color: var(--text); }

        html[data-theme="light"] .cert-preview-header { background: var(--blue-dark); }
        html[data-theme="light"] .save-indicator.saved { background: #d1fae5; }
        html[data-theme="light"] .save-indicator.saving { background: #dbeafe; color: var(--blue); }
        html[data-theme="light"] .loading-overlay { background: rgba(0,0,0,.55); }
        html[data-theme="light"] .loading-spinner { border-top-color: var(--blue); box-shadow: none; }

        html[data-theme="light"] .site-footer {
            background: var(--blue-darker);
            color: rgba(255,255,255,.78);
            border-top: 3px solid var(--blue);
            box-shadow: none;
        }
        html[data-theme="light"] .footer-col p,
        html[data-theme="light"] .footer-col li,
        html[data-theme="light"] .footer-col ul li a { color: rgba(255,255,255,.65); }
        html[data-theme="light"] .footer-col ul li a:hover { color: #fff; }
        html[data-theme="light"] .footer-col h5 { border-bottom: 1px solid rgba(255,255,255,.12); }
        html[data-theme="light"] .footer-social a { background: rgba(255,255,255,.08); color: #fff; }
        html[data-theme="light"] .footer-bottom { background: var(--blue-darker); border-top: 1px solid rgba(255,255,255,.08); color: rgba(255,255,255,.5); }
    </style>
</head>
<body>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner"></div>
    <div class="loading-text">Processing certificate…</div>
</div>

<!-- ─── TOP BAR ─── -->
<div class="top-bar">
    <div class="container">
        <div class="top-bar-left">
            <i class="fa fa-phone"></i> হটলাইন: ১৬১৫২
        </div>
        <div class="top-bar-right">
            <button type="button" class="theme-toggle" id="themeToggle" onclick="toggleTheme()" title="Toggle light/dark theme">
                <i class="fa fa-moon-o" id="themeToggleIcon"></i> <span id="themeToggleLabel">Dark</span>
            </button>
            <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="auth/admin.php"><i class="fa fa-cogs"></i> Admin</a>
            <?php endif; ?>
            <a href="#" class="lang-btn">English</a>
            <a href="auth/logout.php" style="color:#fca5a5;"><i class="fa fa-sign-out"></i> Logout</a>
            <span style="font-size:.75rem;color:rgba(255,255,255,.6);padding:0 6px;">
                <i class="fa fa-user-circle"></i> <?= htmlspecialchars($_SESSION['fullname']) ?>
            </span>
        </div>
    </div>
</div>

<!-- ─── HEADER ─── -->
<header class="main-header">
    <div class="container">
        <div class="header-inner">
            <div class="logo-area">
                <img src="img/gov_logo.png" alt="Government Logo">
                <div class="logo-text">
                    <span class="logo-title">জন্ম ও মৃত্যু নিবন্ধন তথ্য সিস্টেম</span>
                    <span class="logo-subtitle">Birth &amp; Death Registration Information System</span>
                </div>
            </div>
            <button class="nav-toggle" onclick="toggleNav()"><i class="fa fa-bars"></i></button>
            <nav class="main-nav" id="mainNav">
                <ul>
                    <li><a href="#" class="active">হোম</a></li>
                    <li class="dropdown">
                        <a href="#">জন্ম নিবন্ধন <i class="fa fa-chevron-down"></i></a>
                        <ul class="dropdown-menu">
                            <li><a href="#">জন্ম নিবন্ধন আবেদন</a></li>
                            <li><a href="#">আবেদনের বর্তমান অবস্থা</a></li>
                            <li><a href="#">সনদ পুনঃ মুদ্রন</a></li>
                            <li><a href="#">তথ্য সংশোধন আবেদন</a></li>
                            <li><a href="#">সার্টিফিকেট বাতিলের আবেদন</a></li>
                            <li><a href="https://everify.bdris.gov.bd">তথ্য অনুসন্ধান</a></li>
                        </ul>
                    </li>
                    <li class="dropdown">
                        <a href="#">মৃত্যু নিবন্ধন <i class="fa fa-chevron-down"></i></a>
                        <ul class="dropdown-menu">
                            <li><a href="#">মৃত্যু নিবন্ধন আবেদন</a></li>
                            <li><a href="#">আবেদনের বর্তমান অবস্থা</a></li>
                            <li><a href="#">সনদ পুনঃ মুদ্রন</a></li>
                            <li><a href="#">তথ্য সংশোধন আবেদন</a></li>
                            <li><a href="#">সার্টিফিকেট বাতিলের আবেদন</a></li>
                            <li><a href="https://everify.bdris.gov.bd/UDRNVerification">তথ্য অনুসন্ধান</a></li>
                        </ul>
                    </li>
                    <li><a href="#">ব্যবহারকারী সংযোজন</a></li>
                </ul>
            </nav>
        </div>
    </div>
</header>

<!-- ─── MAIN CONTENT ─── -->
<div class="main-content">
    <div class="container">
        <div class="content-row">

            <!-- Sidebar -->
            <aside class="left-sidebar">
                <div class="sidebar-title">গুরুত্বপূর্ণ লিংক</div>
                <ul>
                    <li><a href="http://bdlaws.minlaw.gov.bd/act-details-921.html">জন্ম ও মৃত্যু নিবন্ধন আইন, ২০০৪</a></li>
                    <li><a href="#">জন্ম ও মৃত্যু নিবন্ধন বিধিমালা, ২০১৮</a></li>
                    <li><a href="#">জন্ম ও মৃত্যু নিবন্ধন নির্দেশিকা ২০২১</a></li>
                    <li><a href="#">প্রায়শই জিজ্ঞাসিত প্রশ্ন FAQ</a></li>
                    <li><a href="#">আবেদন প্রক্রিয়া</a></li>
                    <li><a href="#">জন্ম ও মৃত্যু নিবন্ধন ফি</a></li>
                    <li><a href="#">জন্ম ও মৃত্যু নিবন্ধনের তথ্য</a></li>
                </ul>
            </aside>

            <main class="main-area">

                <!-- ─── NOTICE ─── -->
                <div class="notice-bar">
                    <i class="fa fa-info-circle"></i>
                    <div class="notice-marquee">
                        <span class="notice-marquee-inner">
                            <strong>Important:</strong> Please ensure all information is accurate. The certificate will automatically open the print dialog after submission. This is an official government document — providing false information is punishable by law. &nbsp;&nbsp;&nbsp; <strong>গুরুত্বপূর্ণ:</strong> অনুগ্রহ করে সমস্ত তথ্য সঠিক কিনা নিশ্চিত করুন।
                        </span>
                    </div>
                </div>

                <!-- ─── BULK PANEL ─── -->
                <div class="bulk-panel" id="bulkPanel">
                    <div class="bulk-panel-header" onclick="toggleBulkPanel()">
                        <i class="fa fa-layer-group icon"></i>
                        <span class="bulk-panel-title">ধরন নির্বাচন করুন &nbsp;<span style="color:var(--text-muted);font-weight:400;font-size:.78em;">— বাল্ক পেস্ট, Pdf বা যাচাই করে ফরম ফিল করুন</span></span>
                        <button class="bulk-toggle-btn" id="bulkToggleBtn" onclick="event.stopPropagation(); toggleBulkPanel()">
                            <i class="fa fa-chevron-up" id="bulkChevron"></i> Hide
                        </button>
                    </div>

                    <div class="bulk-body" id="bulkBody">
                        <div class="bulk-tabs">
                            <button class="bulk-tab active" data-tab="paste" onclick="switchBulkTab('paste')">
                                <i class="fa fa-paste"></i> টেক্সট পেস্ট করুন
                            </button>
                            <button class="bulk-tab" data-tab="pdf" onclick="switchBulkTab('pdf')">
                                <i class="fa fa-file-pdf-o"></i> ভেরিফাই কপি আপলোড করুন
                            </button>
                            <button class="bulk-tab" data-tab="fetch" onclick="switchBulkTab('fetch')">
                                <i class="fa fa-cloud-download"></i> যাচাই করে ফিল করুন
                            </button>
                        </div>

                        <div class="bulk-tab-content active" data-tab="paste">
                            <textarea class="bulk-textarea" id="bulkDataInput" placeholder="এখানে ডাটা পেস্ট করুন…"></textarea>
                            <div class="bulk-actions">
                                <button class="btn-blue" type="button" onclick="fillFormFromBulk()">
                                    <i class="fa fa-magic"></i> Fill Now
                                </button>
                                <button class="btn-ghost" type="button" onclick="document.getElementById('bulkDataInput').value=''">
                                    <i class="fa fa-times"></i> Clear
                                </button>
                            </div>
                            <div class="bulk-status" id="bulkFillStatus"></div>
                        </div>

                        <div class="bulk-tab-content" data-tab="pdf">
                            <label class="pdf-dropzone" id="pdfDropZone" for="pdfFileInput">
                                <i class="fa fa-cloud-upload"></i>
                                <span>PDF ড্রপ করুন অথবা ক্লিক করুন</span>
                                <span class="pdf-filename" id="pdfFileName"></span>
                                <input type="file" id="pdfFileInput" accept="application/pdf" style="display:none;" onchange="handlePdfSelect(this)">
                            </label>
                            <div class="bulk-actions" style="margin-top:8px;">
                                <button class="btn-blue" type="button" id="pdfFillBtn" onclick="fillFormFromPdf()" style="display:none; width:100%; justify-content:center;">
                                    <i class="fa fa-file-text"></i> Fill From Pdf
                                </button>
                            </div>
                            <div class="bulk-status" id="pdfStatus"></div>
                        </div>

                        <div class="bulk-tab-content" data-tab="fetch">
                            <div class="fetch-row">
                                <input type="text" id="fetchUbrn" inputmode="numeric" pattern="[0-9]{17}" maxlength="17" placeholder="১৭ অংকের নিবন্ধন নম্বর লিখুন">
                            </div>
                            <div class="fetch-row">
                                <input type="date" id="fetchDob">
                            </div>
                            <div class="fetch-row">
                                <div class="captcha-wrap">
                                    <img id="fetchCaptchaImg" src="" alt="CAPTCHA">
                                    <button class="captcha-refresh-btn" type="button" onclick="refreshCaptcha()">
                                        <i class="fa fa-refresh"></i> Refresh
                                    </button>
                                </div>
                                <input type="text" id="fetchCaptchaInput" placeholder="উপরের ছবির উত্তর লিখুন" autocomplete="off">
                                <input type="hidden" id="fetchCaptchaToken">
                            </div>
                            <div class="bulk-actions">
                                <button class="btn-blue" type="button" onclick="doDirectFetch()" style="justify-content:center;">
                                    <i class="fa fa-cloud-download"></i> Verify and Fill
                                </button>
                                <button class="btn-ghost" type="button" onclick="clearDirectFetch()">
                                    <i class="fa fa-times"></i> Clear
                                </button>
                            </div>
                            <div class="bulk-status" id="fetchStatus"></div>
                        </div>
                    </div>
                </div>

                <!-- ─── FORM CARD ─── -->
                <div class="form-card">
                    <!-- Progress -->
                    <div class="progress-track">
                        <div class="progress-fill" id="formProgress"></div>
                    </div>

                    <!-- Section Stepper -->
                    <div class="form-stepper" id="formStepper">
                        <div class="step-item active" data-section="0" onclick="scrollToSection(0)">
                            <span class="step-num">1</span> General
                        </div>
                        <div class="step-item" data-section="1" onclick="scrollToSection(1)">
                            <span class="step-num">2</span> Birth
                        </div>
                        <div class="step-item" data-section="2" onclick="scrollToSection(2)">
                            <span class="step-num">3</span> Person
                        </div>
                        <div class="step-item" data-section="3" onclick="scrollToSection(3)">
                            <span class="step-num">4</span> Parents
                        </div>
                        <div class="step-item" data-section="4" onclick="scrollToSection(4)">
                            <span class="step-num">5</span> Address
                        </div>
                    </div>

                    <form action="output.php" method="post" id="birthForm" novalidate>
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;">
                            <span class="save-indicator" id="saveIndicator">
                                <i class="fa fa-circle-o"></i> Auto-save ready
                            </span>
                            <button type="button" class="preview-toggle" id="clearDraftBtn" onclick="clearAutoSaveDraft()">
                                <i class="fa fa-trash"></i> Clear Draft
                            </button>
                            <button type="button" class="preview-toggle" id="previewToggle" onclick="togglePreview()">
                                <i class="fa fa-eye"></i> Live Preview
                            </button>
                        </div>

                        <!-- Section 1: Certificate Info -->
                        <div class="form-section" data-section="0">
                            <p class="form-section-title"><i class="fa fa-certificate"></i> সাধারন তথ্য — General Details</p>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="issuedate"><i class="fa fa-calendar"></i> সনদ ইস্যুর তারিখ</label>
                                    <input type="date" id="issuedate" name="issuedate" required>
                                    <span class="field-hint">Certificate issue date</span>
                                </div>
                                <div class="form-group">
                                    <label for="givendate"><i class="fa fa-calendar-check-o"></i> সনদ প্রদানের তারিখ</label>
                                    <input type="date" id="givendate" name="givendate" required>
                                    <span class="field-hint">Certificate delivery date</span>
                                </div>
                                <div class="form-group">
                                    <label for="nibkar"><i class="fa fa-building"></i> নিবন্ধন কার্যালয়</label>
                                    <input type="text" id="nibkar" name="nibkar" required placeholder="Registration office name">
                                    <span class="field-hint">যে অফিসে নিবন্ধন করা হয়েছে</span>
                                </div>
                                <div class="form-group">
                                    <label for="thanaandzilla"><i class="fa fa-map-marker"></i> থানা ও জেলা</label>
                                    <input type="text" id="thanaandzilla" name="thanaandzilla" required placeholder="Thana & District">
                                    <span class="field-hint">যেমন: সোনাতলা, বগুড়া</span>
                                </div>
                                <div class="form-group">
                                    <label for="birth_registration_number"><i class="fa fa-id-card"></i> জন্ম নিবন্ধন নং</label>
                                    <input type="text" id="birth_registration_number" name="birth_registration_number" required placeholder="17-digit registration number" maxlength="17">
                                    <span class="field-hint">১৭ অংকের নিবন্ধন নম্বর</span>
                                </div>
                                <div class="form-group">
                                    <label for="gender"><i class="fa fa-venus-mars"></i> লিঙ্গ</label>
                                    <select id="gender" name="gender" required>
                                        <option value="">-- নির্বাচন করুন --</option>
                                        <option value="Male">পুরুষ (Male)</option>
                                        <option value="Female">মহিলা (Female)</option>
                                    </select>
                                    <span class="field-hint">Select gender</span>
                                </div>
                            </div>
                        </div>

                        <!-- Section 2: Birth Info -->
                        <div class="form-section" data-section="1">
                            <p class="form-section-title"><i class="fa fa-baby"></i> জন্ম তথ্য — Birth Information</p>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="dob"><i class="fa fa-birthday-cake"></i> জন্ম তারিখ</label>
                                    <input type="date" id="dob" name="dob" required onchange="updateDobText()">
                                    <span class="field-hint">Date of birth</span>
                                </div>
                                <div class="form-group">
                                    <label for="dobt"><i class="fa fa-font"></i> জন্ম তারিখ (কথায়)</label>
                                    <input type="text" id="dobt" name="dobt" required readonly placeholder="Auto-filled from date above">
                                    <span class="field-hint">Auto-filled in English words</span>
                                </div>
                                <div class="form-group col-span-2">
                                    <label for="bp_bn"><i class="fa fa-map-marker"></i> জন্মস্থান (বাংলায়)</label>
                                    <input type="text" id="bp_bn" name="bp_bn" required placeholder="জন্মস্থান বাংলায়">
                                    <span class="field-hint">যেমন: ঢাকা, বাংলাদেশ</span>
                                </div>
                                <div class="form-group col-span-2">
                                    <label for="bp_en"><i class="fa fa-map-marker"></i> জন্মস্থান (ইংরেজিতে)</label>
                                    <input type="text" id="bp_en" name="bp_en" required placeholder="Place of birth in English">
                                    <span class="field-hint">e.g. Dhaka, Bangladesh</span>
                                </div>
                            </div>
                        </div>

                        <!-- Section 3: Person Name -->
                        <div class="form-section" data-section="2">
                            <p class="form-section-title"><i class="fa fa-user"></i> নিবন্ধিত ব্যক্তির নাম — Registered Person</p>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="name_bn"><i class="fa fa-user"></i> নাম (বাংলায়)</label>
                                    <input type="text" id="name_bn" name="name_bn" required placeholder="বাংলায় নাম লিখুন">
                                    <span class="field-hint">পূর্ণ নাম বাংলায়</span>
                                </div>
                                <div class="form-group">
                                    <label for="name_en"><i class="fa fa-user"></i> Name (English)</label>
                                    <input type="text" id="name_en" name="name_en" required placeholder="Full name in English">
                                    <span class="field-hint">Full name in block letters</span>
                                </div>
                            </div>
                        </div>

                        <!-- Section 4: Parents -->
                        <div class="form-section" data-section="3">
                            <p class="form-section-title"><i class="fa fa-users"></i> পিতামাতার তথ্য — Parents Information</p>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="mother_name_bn"><i class="fa fa-female"></i> মাতার নাম (বাংলায়)</label>
                                    <input type="text" id="mother_name_bn" name="mother_name_bn" required placeholder="মাতার নাম বাংলায়">
                                </div>
                                <div class="form-group">
                                    <label for="mother_name_en"><i class="fa fa-female"></i> Mother's Name (English)</label>
                                    <input type="text" id="mother_name_en" name="mother_name_en" required placeholder="Mother's name in English">
                                </div>
                                <div class="form-group">
                                    <label for="father_name_bn"><i class="fa fa-male"></i> পিতার নাম (বাংলায়)</label>
                                    <input type="text" id="father_name_bn" name="father_name_bn" required placeholder="পিতার নাম বাংলায়">
                                </div>
                                <div class="form-group">
                                    <label for="father_name_en"><i class="fa fa-male"></i> Father's Name (English)</label>
                                    <input type="text" id="father_name_en" name="father_name_en" required placeholder="Father's name in English">
                                </div>
                            </div>
                        </div>

                        <!-- Section 5: Address -->
                        <div class="form-section" data-section="4">
                            <p class="form-section-title"><i class="fa fa-home"></i> স্থায়ী ঠিকানা — Permanent Address</p>
                            <div class="form-grid">
                                <div class="form-group col-span-2">
                                    <label for="addressx"><i class="fa fa-home"></i> স্থায়ী ঠিকানা (বাংলায়)</label>
                                    <input type="text" id="addressx" name="addressx" required placeholder="ঠিকানা বাংলায়">
                                    <span class="field-hint">গ্রাম/শহর, থানা, জেলা, দেশ</span>
                                </div>
                                <div class="form-group col-span-2">
                                    <label for="address"><i class="fa fa-home"></i> Permanent Address (English)</label>
                                    <input type="text" id="address" name="address" required placeholder="Address in English">
                                    <span class="field-hint">Village/City, Thana, District, Country</span>
                                </div>
                            </div>
                        </div>

                        <!-- Checkboxes -->
                        <div class="checkbox-row">
                            <div class="checkbox-group">
                                <input type="checkbox" id="is_duplicate" name="is_duplicate">
                                <label for="is_duplicate"><i class="fa fa-copy"></i> প্রতিলিপি / Duplicate</label>
                            </div>
                            <div class="checkbox-group">
                                <input type="checkbox" id="is_corrected" name="is_corrected">
                                <label for="is_corrected"><i class="fa fa-pencil"></i> সংশোধিত / Corrected</label>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="form-actions">
                            <button type="button" id="resetBtn" class="btn-reset">
                                <i class="fa fa-undo"></i> রিসেট করুন
                            </button>
                            <button type="submit" class="btn-submit">
                                <i class="fa fa-check-circle"></i> যাচাই ও ডাউনলোড
                            </button>
                        </div>
                    </form>

                    <!-- ─── CERTIFICATE PREVIEW ─── -->
                    <div class="cert-preview-panel" id="certPreview">
                        <div class="cert-preview-header">
                            <i class="fa fa-file-text-o"></i>
                            Certificate Live Preview
                            <span style="margin-left:auto;font-weight:400;font-size:.72rem;opacity:.75;" id="previewUpdated">just now</span>
                        </div>
                        <div class="cert-preview-body" id="previewBody">
                            <div class="preview-row">
                                <span class="preview-label">Issue Date</span>
                                <span class="preview-value empty" id="pv_issuedate">—</span>
                            </div>
                            <div class="preview-row">
                                <span class="preview-label">Given Date</span>
                                <span class="preview-value empty" id="pv_givendate">—</span>
                            </div>
                            <div class="preview-row">
                                <span class="preview-label">Registration No</span>
                                <span class="preview-value empty" id="pv_brn">—</span>
                            </div>
                            <div class="preview-row">
                                <span class="preview-label">Gender</span>
                                <span class="preview-value empty" id="pv_gender">—</span>
                            </div>
                            <div class="preview-row">
                                <span class="preview-label">Name (Bengali)</span>
                                <span class="preview-value empty" id="pv_name_bn">—</span>
                            </div>
                            <div class="preview-row">
                                <span class="preview-label">Name (English)</span>
                                <span class="preview-value empty" id="pv_name_en">—</span>
                            </div>
                            <div class="preview-row">
                                <span class="preview-label">Date of Birth</span>
                                <span class="preview-value empty" id="pv_dob">—</span>
                            </div>
                            <div class="preview-row">
                                <span class="preview-label">Place of Birth</span>
                                <span class="preview-value empty" id="pv_bp">—</span>
                            </div>
                            <div class="preview-row">
                                <span class="preview-label">Mother's Name</span>
                                <span class="preview-value empty" id="pv_mother">—</span>
                            </div>
                            <div class="preview-row">
                                <span class="preview-label">Father's Name</span>
                                <span class="preview-value empty" id="pv_father">—</span>
                            </div>
                            <div class="preview-row">
                                <span class="preview-label">Address</span>
                                <span class="preview-value empty" id="pv_address">—</span>
                            </div>
                            <div class="cert-preview-watermark">
                                <i class="fa fa-lock" style="margin-right:5px;"></i>
                                This is a live preview — data is only saved upon submission
                            </div>
                        </div>
                    </div>

                </div><!-- /form-card -->
            </main>
        </div>
    </div>
</div>

<!-- ─── FOOTER ─── -->
<footer class="site-footer">
    <div class="container">
        <div class="footer-inner">
            <div class="footer-col">
                <h5>পরিকল্পনা ও বাস্তবায়নে</h5>
                <p>Office of the Registrar General</p>
                <p>Birth and Death Registration</p>
                <p>Local Government Division</p>
                <p>Paribahan Pool, 9th floor, Secretariat, Dhaka</p>
                <p>Email: programmer_rg@lgd.gov.bd</p>
            </div>
            <div class="footer-col">
                <h5>গুরুত্বপূর্ণ লিংক</h5>
                <ul>
                    <li><a href="#">রাষ্ট্রপতির কার্যালয়</a></li>
                    <li><a href="#">প্রধানমন্ত্রীর কার্যালয়</a></li>
                    <li><a href="#">জনপ্রশাসন মন্ত্রণালয়</a></li>
                    <li><a href="#">ই-তথ্য কোষ</a></li>
                    <li><a href="#">এ টু আই প্রোগ্রাম</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h5>সামাজিক যোগাযোগ</h5>
                <div class="footer-social">
                    <a href="#"><i class="fa fa-facebook"></i></a>
                    <a href="#"><i class="fa fa-twitter"></i></a>
                    <a href="#"><i class="fa fa-youtube-play"></i></a>
                </div>
            </div>
            <div class="footer-col">
                <h5>হোম</h5>
                <ul>
                    <li><a href="#">লগইন</a></li>
                    <li><a href="#">ব্যবহারের শর্তাবলী</a></li>
                    <li><a href="#">সহযোগীতায়</a></li>
                    <li><a href="#">যোগাযোগ</a></li>
                    <li><a href="#">সাইট ম্যাপ</a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container">
            Copyright &copy; <?php echo date('Y'); ?>. All Rights Reserved Government of the People's Republic of Bangladesh
        </div>
    </div>
</footer>

<!-- ════════════════════════════════════
     JAVASCRIPT
════════════════════════════════════ -->
<script>
/* ── Mobile nav toggle ── */
function toggleNav() {
    const nav = document.getElementById('mainNav');
    nav.style.display = nav.style.display === 'block' ? '' : 'block';
}

/* ── Theme toggle (light / dark) ── */
function applyThemeUI(theme) {
    const icon  = document.getElementById('themeToggleIcon');
    const label = document.getElementById('themeToggleLabel');
    if (theme === 'light') {
        if (icon)  icon.className = 'fa fa-sun-o';
        if (label) label.textContent = 'Light';
    } else {
        if (icon)  icon.className = 'fa fa-moon-o';
        if (label) label.textContent = 'Dark';
    }
}
function toggleTheme() {
    const isLight = document.documentElement.getAttribute('data-theme') === 'light';
    const next = isLight ? 'dark' : 'light';
    if (next === 'light') {
        document.documentElement.setAttribute('data-theme', 'light');
    } else {
        document.documentElement.removeAttribute('data-theme');
    }
    try { localStorage.setItem('siteTheme', next); } catch (e) {}
    applyThemeUI(next);
}
// Sync the toggle button UI with the theme applied pre-paint
applyThemeUI(document.documentElement.getAttribute('data-theme') === 'light' ? 'light' : 'dark');

/* ── Form sections ── */
const SECTION_IDS = ['issuedate','dob','name_bn','mother_name_bn','addressx'];

function scrollToSection(index) {
    const sections = document.querySelectorAll('.form-section');
    if (sections[index]) {
        sections[index].scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

function updateStepper() {
    const steps = document.querySelectorAll('.step-item');
    steps.forEach((step, i) => {
        const fields = document.querySelectorAll(`.form-section[data-section="${i}"] input[required], .form-section[data-section="${i}"] select[required]`);
        let allFilled = true;
        fields.forEach(f => { if (!f.value) allFilled = false; });
        step.classList.toggle('completed', allFilled);
        step.classList.remove('active');
    });
    // Set active based on scroll position
    const scrollPos = window.scrollY + 200;
    let activeIdx = 0;
    document.querySelectorAll('.form-section').forEach((sec, i) => {
        const top = sec.offsetTop;
        if (scrollPos >= top) activeIdx = i;
    });
    steps.forEach((s, i) => s.classList.toggle('active', i === activeIdx));
}

/* ── Progress bar ── */
function updateProgressBar() {
    const fields = document.querySelectorAll('#birthForm input[required], #birthForm select[required]');
    let filled = 0;
    fields.forEach(f => { if (f.value) filled++; });
    const pct = fields.length > 0 ? Math.round((filled / fields.length) * 100) : 0;
    const bar = document.getElementById('formProgress');
    bar.style.width = pct + '%';
    bar.style.background = pct < 30 ? 'var(--danger)' : pct < 70 ? 'var(--warn)' : 'var(--success)';
}

/* ── Progress + stepper + preview update ── */
function refreshAll() {
    updateProgressBar();
    updatePreview();
    updateStepper();
}

/* ── Field validation + update all ── */
document.querySelectorAll('#birthForm input[required], #birthForm select[required]').forEach(el => {
    el.addEventListener('blur', () => {
        el.classList.remove('valid');
        el.classList.toggle('invalid', !el.value);
        if (el.value) el.classList.add('valid');
        refreshAll();
        updatePreview();
        updateStepper();
    });
    el.addEventListener('input', () => {
        if (el.value) {
            el.classList.remove('invalid');
            el.classList.add('valid');
        } else {
            el.classList.remove('valid');
        }
        refreshAll();
        updatePreview();
        updateStepper();
    });
    el.addEventListener('change', () => {
        refreshAll();
        updatePreview();
        updateStepper();
    });
});

/* ── Stagger reveal ── */
document.addEventListener('DOMContentLoaded', () => {
    const groups = document.querySelectorAll('.form-group');
    groups.forEach((g, i) => {
        setTimeout(() => g.classList.add('revealed'), 60 + i * 35);
    });

    const today = new Date().toISOString().split('T')[0];
    if (!document.getElementById('issuedate').value) document.getElementById('issuedate').value = today;
    if (!document.getElementById('givendate').value) document.getElementById('givendate').value = today;

    refreshAll();
    updatePreview();
    updateStepper();

    // Restore draft
    restoreDraft();

    // Auto-save every 3s
    setInterval(autoSaveDraft, 3000);
});

/* ── Real-time Certificate Preview ── */
function togglePreview() {
    const panel = document.getElementById('certPreview');
    const btn = document.getElementById('previewToggle');
    panel.classList.toggle('visible');
    btn.classList.toggle('active');
    btn.innerHTML = panel.classList.contains('visible')
        ? '<i class="fa fa-eye-slash"></i> Hide Preview'
        : '<i class="fa fa-eye"></i> Live Preview';
}

function updatePreview() {
    const map = {
        'issuedate': 'pv_issuedate',
        'givendate': 'pv_givendate',
        'birth_registration_number': 'pv_brn',
        'gender': 'pv_gender',
        'name_bn': 'pv_name_bn',
        'name_en': 'pv_name_en',
        'dob': 'pv_dob',
        'bp_bn': 'pv_bp',
        'mother_name_bn': 'pv_mother',
        'father_name_bn': 'pv_father',
        'addressx': 'pv_address'
    };
    for (const [srcId, destId] of Object.entries(map)) {
        const src = document.getElementById(srcId);
        const dest = document.getElementById(destId);
        if (src && dest) {
            const val = src.value || '';
            dest.textContent = val || '—';
            dest.className = 'preview-value' + (val ? '' : ' empty');
        }
    }
    document.getElementById('previewUpdated').textContent = 'updated ' + new Date().toLocaleTimeString();
}

/* ── Auto-save Draft (localStorage) ── */
function autoSaveDraft() {
    const fields = document.querySelectorAll('#birthForm input, #birthForm select');
    const data = {};
    fields.forEach(f => { data[f.id || f.name] = f.value; });
    localStorage.setItem('birthCertDraft', JSON.stringify(data));
    const indicator = document.getElementById('saveIndicator');
    indicator.className = 'save-indicator saved';
    indicator.innerHTML = '<i class="fa fa-check-circle"></i> Draft saved ' + new Date().toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'});
    clearTimeout(indicator._timeout);
    indicator._timeout = setTimeout(() => {
        indicator.className = 'save-indicator';
        indicator.innerHTML = '<i class="fa fa-circle-o"></i> Auto-save ready';
    }, 3000);
}

function restoreDraft() {
    const saved = localStorage.getItem('birthCertDraft');
    if (!saved) return;
    try {
        const data = JSON.parse(saved);
        let restored = 0;
        for (const [id, val] of Object.entries(data)) {
            if (!val) continue;
            const el = document.getElementById(id);
            if (el) {
                el.value = val;
                el.dispatchEvent(new Event('input'));
                restored++;
            }
        }
        if (restored > 0) {
            const indicator = document.getElementById('saveIndicator');
            indicator.className = 'save-indicator saved';
            indicator.innerHTML = '<i class="fa fa-refresh"></i> Draft restored (' + restored + ' fields)';
            setTimeout(() => {
                indicator.className = 'save-indicator';
                indicator.innerHTML = '<i class="fa fa-circle-o"></i> Auto-save ready';
            }, 4000);
            updatePreview();
            refreshAll();
            updateStepper();
            if (document.getElementById('dob').value) updateDobText();
        }
    } catch(e) {}
}

/* ── Reset ── */
document.getElementById('resetBtn').addEventListener('click', () => {
    if (confirm('আপনি কি নিশ্চিত যে আপনি ফর্মটি রিসেট করতে চান?')) {
        document.getElementById('birthForm').reset();
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('issuedate').value = today;
        document.getElementById('givendate').value = today;
        document.querySelectorAll('input, select').forEach(i => { i.classList.remove('invalid'); i.classList.remove('valid'); });
        localStorage.removeItem('birthCertDraft');
        refreshAll();
        updatePreview();
        updateStepper();
        updateDobText();
    }
});

/* ── Clear Draft ── */
function clearAutoSaveDraft() {
    if (confirm('Are you sure you want to clear the saved draft?')) {
        localStorage.removeItem('birthCertDraft');
        document.getElementById('birthForm').reset();
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('issuedate').value = today;
        document.getElementById('givendate').value = today;
        document.querySelectorAll('input, select').forEach(i => { i.classList.remove('invalid'); i.classList.remove('valid'); });
        const indicator = document.getElementById('saveIndicator');
        indicator.className = 'save-indicator saved';
        indicator.innerHTML = '<i class="fa fa-trash"></i> Draft cleared';
        setTimeout(() => {
            indicator.className = 'save-indicator';
            indicator.innerHTML = '<i class="fa fa-circle-o"></i> Auto-save ready';
        }, 3000);
        refreshAll();
        updatePreview();
        updateStepper();
        updateDobText();
    }
}

/* ── Submit ── */
document.getElementById('birthForm').addEventListener('submit', function(e) {
    const fields = document.querySelectorAll('input[required], select[required]');
    let ok = true;
    fields.forEach(f => {
        if (!f.value) { f.classList.add('invalid'); f.classList.remove('valid'); ok = false; }
    });
    if (!ok) {
        e.preventDefault();
        alert('অনুগ্রহ করে সমস্ত প্রয়োজনীয় ক্ষেত্র পূরণ করুন।');
        return;
    }
    localStorage.removeItem('birthCertDraft');
    document.getElementById('loadingOverlay').style.display = 'flex';
});

/* ── Bulk panel ── */
function toggleBulkPanel() {
    const body    = document.getElementById('bulkBody');
    const chevron = document.getElementById('bulkChevron');
    const btnEl   = document.getElementById('bulkToggleBtn');
    const open    = body.style.display !== 'none';
    body.style.display  = open ? 'none' : 'block';
    chevron.className   = open ? 'fa fa-chevron-down' : 'fa fa-chevron-up';
    btnEl.innerHTML     = (open ? '<i class="fa fa-chevron-down"></i> Show' : '<i class="fa fa-chevron-up"></i> Hide');
}

/* ── Bulk fill ── */
function fillFormFromBulk() {
    const raw = document.getElementById('bulkDataInput').value;
    if (!raw.trim()) { showStatus('bulkFillStatus', '⚠️ কোনো ডাটা পাওয়া যায়নি।', 'warn'); return; }

    const mapping = {
        'জন্ম নিবন্ধন নম্বর':  { id: 'birth_registration_number' },
        'জন্ম তারিখ':           { id: 'dob',      type: 'date' },
        'নিবন্ধনের তারিখ':      { id: 'issuedate', type: 'date' },
        'লিঙ্গ':                { id: 'gender',    type: 'select' },
        'পুরো নাম':             { id: 'name_bn' },
        'পুরো নাম ইংরেজিতে':    { id: 'name_en' },
        'মাতার নাম':            { id: 'mother_name_bn' },
        'মাতার নাম ইংরেজিতে':   { id: 'mother_name_en' },
        'পিতার নাম':            { id: 'father_name_bn' },
        'পিতার নাম ইংরেজিতে':   { id: 'father_name_en' },
        'জন্মস্থান':            { id: 'bp_bn',  type: 'birthplace_bn' },
        'জন্মস্থান (ইংরেজি)':   { id: 'bp_en',  type: 'birthplace_en' },
        'জন্মস্থান (ইংরেজিতে)': { id: 'bp_en',  type: 'birthplace_en' },
        'স্থায়ী ঠিকানা':       { id: 'addressx' },
        'স্থায়ী ঠিকানা (ইংরেজি)':   { id: 'address' },
        'স্থায়ী ঠিকানা (ইংরেজিতে)': { id: 'address' },
    };

    const lines = raw.split('\n').map(l => l.trim()).filter(l => l);
    let filled = 0;
    for (let i = 0; i < lines.length; i++) {
        const label = lines[i].replace(/\s+/g, ' ').trim();
        const value = lines[i + 1] ? lines[i + 1].trim() : '';
        if (!value) continue;
        const fi = mapping[label];
        if (!fi) continue;
        const el = document.getElementById(fi.id);
        if (!el) continue;

        if (fi.type === 'date') {
            const parts = value.split('/');
            if (parts.length === 3) {
                el.value = `${parts[2]}-${parts[1].padStart(2,'0')}-${parts[0].padStart(2,'0')}`;
                el.dispatchEvent(new Event('change'));
                filled++;
            }
        } else if (fi.type === 'select') {
            const v = value.toUpperCase();
            el.value = (v === 'MALE' || v === 'পুরুষ') ? 'Male' : (v === 'FEMALE' || v === 'মহিলা') ? 'Female' : '';
            el.dispatchEvent(new Event('change'));
            filled++;
        } else if (fi.type === 'birthplace_bn' || fi.type === 'birthplace_en') {
            const parts = value.split(',').map(p => p.trim()).filter(p => p);
            const country  = parts[parts.length - 1];
            const district = parts[parts.length - 3];
            el.value = (district && country) ? `${district}, ${country}` : value;
            el.dispatchEvent(new Event('input'));
            filled++;
        } else {
            el.value = value;
            el.dispatchEvent(new Event('input'));
            filled++;
        }
        i++;
    }

    const iss = document.getElementById('issuedate');
    const giv = document.getElementById('givendate');
    if (iss.value && !giv.value) giv.value = iss.value;

    refreshAll();
    updatePreview();
    updateStepper();
    filled > 0
        ? showStatus('bulkFillStatus', `✅ ${filled}টি ফিল্ড সফলভাবে পূরণ হয়েছে!`, 'ok')
        : showStatus('bulkFillStatus', '❌ কোনো ফিল্ড মেলেনি। ডাটা ফরম্যাট চেক করুন।', 'err');
}

/* ── PDF ── */
function handlePdfSelect(input) {
    const file = input.files[0];
    if (!file) return;
    document.getElementById('pdfFileName').textContent = file.name;
    document.getElementById('pdfFillBtn').style.display = 'flex';
}

const dropZone = document.getElementById('pdfDropZone');
if (dropZone) {
    dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
    dropZone.addEventListener('dragleave', ()=> dropZone.classList.remove('drag-over'));
    dropZone.addEventListener('drop', e => {
        e.preventDefault(); dropZone.classList.remove('drag-over');
        const file = e.dataTransfer.files[0];
        if (file && file.type === 'application/pdf') {
            document.getElementById('pdfFileInput').files = e.dataTransfer.files;
            document.getElementById('pdfFileName').textContent = file.name;
            document.getElementById('pdfFillBtn').style.display = 'flex';
        }
    });
}

async function fillFormFromPdf() {
    const input = document.getElementById('pdfFileInput');
    if (!input.files[0]) { showStatus('pdfStatus','⚠️ কোনো PDF সিলেক্ট করা হয়নি।','warn'); return; }
    showStatus('pdfStatus','<i class="fa fa-spinner fa-spin"></i> PDF পড়া হচ্ছে…','ok',true);

    try {
        const arrayBuffer = await input.files[0].arrayBuffer();
        const pdf = await pdfjsLib.getDocument({ data: arrayBuffer }).promise;
        let fullText = '';
        for (let i = 1; i <= pdf.numPages; i++) {
            const page = await pdf.getPage(i);
            const tc   = await page.getTextContent();
            fullText  += tc.items.map(it => it.str).join(' ') + '\n';
        }

        function fixBrokenBangla(str) {
            return str
                .replace(/\u09be\s+/g,'\u09be').replace(/\u09bf\s+/g,'\u09bf')
                .replace(/\u09c0\s+/g,'\u09c0').replace(/\u09c1\s+/g,'\u09c1')
                .replace(/\u09c2\s+/g,'\u09c2').replace(/\u09c7\s+/g,'\u09c7')
                .replace(/\u09cb\s+/g,'\u09cb').replace(/\u09cd\s+/g,'\u09cd')
                .replace(/\u09bc\s+/g,'\u09bc')
                .replace(/([\u0980-\u09ff])\s+([\u0980-\u09ff])/g,'$1$2')
                .replace(/([\u0980-\u09ff])\s+([\u0980-\u09ff])/g,'$1$2')
                .trim();
        }

        const monthMap = {JANUARY:1,FEBRUARY:2,MARCH:3,APRIL:4,MAY:5,JUNE:6,JULY:7,AUGUST:8,SEPTEMBER:9,OCTOBER:10,NOVEMBER:11,DECEMBER:12};
        function parseEngDate(str) {
            const m = str.match(/(\d{1,2})\s+([A-Z]+)\s+(\d{4})/);
            if (!m) return '';
            const mon = monthMap[m[2]];
            return mon ? `${m[3]}-${String(mon).padStart(2,'0')}-${m[1].padStart(2,'0')}` : '';
        }

        const t = fullText;
        const regNoMatch   = t.match(/\b(\d{17})\b/);
        const genderMatch  = t.match(/\b(MALE|FEMALE)\b/i);
        const dateMatches  = [...t.matchAll(/(\d{1,2})\s+(JANUARY|FEBRUARY|MARCH|APRIL|MAY|JUNE|JULY|AUGUST|SEPTEMBER|OCTOBER|NOVEMBER|DECEMBER)\s+(\d{4})/gi)];
        const nameEnMatch  = t.match(/REGISTERED PERSON\s+NAME\s+([A-Z][A-Z\s]+?)(?=\s{2,}|\n|জন্ম)/i);
        const motherEnMatch= t.match(/MOTHER'S NAME\s+([A-Z][A-Z\s]+?)(?=\s{2,}|\n|MOTHER'S\s+NAT)/i);
        const fatherEnMatch= t.match(/FATHER'S NAME\s+([A-Z][A-Z\s]+?)(?=\s{2,}|\n|FATHER'S\s+NAT)/i);
        const pobMatch     = t.match(/PLACE OF BIRTH\s+([A-Z]+)/i);
        const nameBnMatch  = t.match(/নি[\s]*বন্ধি[\s]*ত[\s]*ব্য[\s\S]{0,10}না[\s]*ম\s+([\u0980-\u09ff\s]+?)(?=REGISTERED|\n.*REGISTERED)/i);
        const motherBnMatch= t.match(/মা[\s]*তা[\s]*র[\s]+না[\s]*ম\s+([\u0980-\u09ff\s]+?)(?=MOTHER|\n.*MOTHER)/i);
        const fatherBnMatch= t.match(/পি[\s]*তা[\s]*র[\s]+না[\s]*ম\s+([\u0980-\u09ff\s]+?)(?=FATHER|\n.*FATHER)/i);
        const pobBnMatch   = t.match(/জন্ম[\s]*স্থা[\s]*ন\s+([\u0980-\u09ff]+)/i);

        const districtEn  = pobMatch    ? pobMatch[1].trim() : '';
        const districtBn  = pobBnMatch  ? fixBrokenBangla(pobBnMatch[1]) : '';

        let filled = 0;
        function setVal(id, val) {
            if (!val) return;
            const el = document.getElementById(id);
            if (!el) return;
            el.value = val;
            el.dispatchEvent(new Event('input'));
            el.dispatchEvent(new Event('change'));
            filled++;
        }

        setVal('birth_registration_number', regNoMatch ? regNoMatch[1] : '');
        if (dateMatches[0]) { setVal('issuedate', parseEngDate(dateMatches[0][0])); }
        if (dateMatches[1]) { setVal('givendate', parseEngDate(dateMatches[1][0])); }
        if (dateMatches[2]) {
            const el = document.getElementById('dob');
            const v  = parseEngDate(dateMatches[2][0]);
            if (v) { el.value = v; el.dispatchEvent(new Event('change')); filled++; }
        }
        if (genderMatch) {
            const el = document.getElementById('gender');
            el.value = genderMatch[1].toUpperCase() === 'MALE' ? 'Male' : 'Female';
            el.dispatchEvent(new Event('change'));
            filled++;
        }
        setVal('name_bn',        nameBnMatch   ? fixBrokenBangla(nameBnMatch[1])   : '');
        setVal('name_en',        nameEnMatch   ? nameEnMatch[1].trim()             : '');
        setVal('mother_name_bn', motherBnMatch ? fixBrokenBangla(motherBnMatch[1]) : '');
        setVal('mother_name_en', motherEnMatch ? motherEnMatch[1].trim()           : '');
        setVal('father_name_bn', fatherBnMatch ? fixBrokenBangla(fatherBnMatch[1]) : '');
        setVal('father_name_en', fatherEnMatch ? fatherEnMatch[1].trim()           : '');
        if (districtBn) { setVal('bp_bn',    `${districtBn}, বাংলাদেশ`);  setVal('addressx', `${districtBn}, বাংলাদেশ`); }
        if (districtEn) { setVal('bp_en',    `${districtEn}, BANGLADESH`); setVal('address',  `${districtEn}, BANGLADESH`); }

        refreshAll();
        updatePreview();
        updateStepper();
        showStatus('pdfStatus', `✅ ${filled}টি ফিল্ড পূরণ হয়েছে!`, 'ok');
    } catch(err) {
        showStatus('pdfStatus', '❌ ত্রুটি: ' + err.message, 'err');
    }
}

/* ── Status helper ── */
function showStatus(id, msg, type, persist) {
    const el = document.getElementById(id);
    el.style.display = 'block';
    el.style.color   = type === 'ok' ? 'var(--success)' : type === 'warn' ? 'var(--warn)' : 'var(--danger)';
    el.innerHTML     = msg;
    if (!persist) setTimeout(() => { el.style.display = 'none'; }, 5000);
}

/* ── DOB → text ── */
function updateDobText() {
    const dobEl  = document.getElementById('dob');
    const dobtEl = document.getElementById('dobt');
    if (!dobEl || !dobtEl) return;
    if (!dobEl.value) { dobtEl.value = ''; return; }
    const [y, m, d] = dobEl.value.split('-').map(Number);
    const ordinals = ['First','Second','Third','Fourth','Fifth','Sixth','Seventh','Eighth','Ninth','Tenth',
        'Eleventh','Twelfth','Thirteenth','Fourteenth','Fifteenth','Sixteenth','Seventeenth',
        'Eighteenth','Nineteenth','Twentieth','Twenty First','Twenty Second','Twenty Third',
        'Twenty Fourth','Twenty Fifth','Twenty Sixth','Twenty Seventh','Twenty Eighth',
        'Twenty Ninth','Thirtieth','Thirty First'];
    const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    dobtEl.value = `${ordinals[d-1]} of ${months[m-1]} ${convertYearToText(y)}`;
    refreshAll();
}

/* ── Bulk tab switching ── */
function switchBulkTab(tabName) {
    document.querySelectorAll('.bulk-tab').forEach(t => {
        t.classList.toggle('active', t.dataset.tab === tabName);
    });
    document.querySelectorAll('.bulk-tab-content').forEach(p => {
        p.classList.toggle('active', p.dataset.tab === tabName);
    });
    if (tabName === 'fetch') {
        initDirectFetch();
    }
}

/* ── Direct Fetch ── */
let fetchSessionActive = false;

async function initDirectFetch() {
    if (fetchSessionActive) return;
    const statusEl = document.getElementById('fetchStatus');
    statusEl.style.display = 'block';
    statusEl.innerHTML = '<i class="fa fa-spinner fa-spin"></i> CAPTCHA লোড হচ্ছে…';
    statusEl.style.color = 'var(--text-dim)';

    try {
        const resp = await fetch('api/fetch-birth.php?action=init');
        const data = await resp.json();
        if (!data.ok) {
            statusEl.innerHTML = '❌ ' + (data.error || 'Failed to load captcha');
            statusEl.style.color = 'var(--danger)';
            return;
        }
        document.getElementById('fetchCaptchaImg').src = 'data:' + (data.captcha_mime || 'image/png') + ';base64,' + data.captcha_base64;
        document.getElementById('fetchCaptchaToken').value = data.captcha_token;
        fetchSessionActive = true;
        statusEl.style.display = 'none';
    } catch (err) {
        statusEl.innerHTML = '❌ Network error: ' + err.message;
        statusEl.style.color = 'var(--danger)';
    }
}

async function refreshCaptcha() {
    fetchSessionActive = false;
    const statusEl = document.getElementById('fetchStatus');
    statusEl.style.display = 'block';
    statusEl.innerHTML = '<i class="fa fa-spinner fa-spin"></i> CAPTCHA Refresh হচ্ছে…';
    statusEl.style.color = 'var(--text-dim)';
    document.getElementById('fetchCaptchaInput').value = '';
    await initDirectFetch();
}

function clearDirectFetch() {
    document.getElementById('fetchUbrn').value = '';
    document.getElementById('fetchDob').value = '';
    document.getElementById('fetchCaptchaInput').value = '';
    document.getElementById('fetchStatus').style.display = 'none';
}

async function doDirectFetch() {
    const ubrn = document.getElementById('fetchUbrn').value.trim();
    const dob = document.getElementById('fetchDob').value.trim();
    const captcha = document.getElementById('fetchCaptchaInput').value.trim();
    const statusEl = document.getElementById('fetchStatus');

    if (!ubrn || !dob || !captcha) {
        statusEl.style.display = 'block';
        statusEl.innerHTML = '⚠️ অনুগ্রহ করে সব ফিল্ড পূরণ করুন।';
        statusEl.style.color = 'var(--warn)';
        return;
    }

    if (!/^\d{17}$/.test(ubrn)) {
        statusEl.style.display = 'block';
        statusEl.innerHTML = '⚠️ জন্ম নিবন্ধন নম্বর ১৭ অংকের হতে হবে।';
        statusEl.style.color = 'var(--warn)';
        return;
    }

    statusEl.style.display = 'block';
    statusEl.innerHTML = '<i class="fa fa-spinner fa-spin"></i> BDRIS থেকে ডাটা ফেচ করা হচ্ছে…';
    statusEl.style.color = 'var(--text-dim)';

    const formData = new FormData();
    formData.append('ubrn', ubrn);
    formData.append('birth_date', dob);
    formData.append('captcha_answer', captcha);
    formData.append('captcha_token', document.getElementById('fetchCaptchaToken').value);

    try {
        const resp = await fetch('api/fetch-birth.php', { method: 'POST', body: formData });
        const data = await resp.json();

        if (!data.ok) {
            const errMsg = data.errors ? data.errors.join('; ') : (data.error || 'Unknown error');
            statusEl.innerHTML = '❌ ' + errMsg;
            statusEl.style.color = 'var(--danger)';
            if (data.captcha_expired) {
                setTimeout(refreshCaptcha, 1500);
            }
            return;
        }

        const r = data.record;
        let filled = 0;

        function setVal(id, val) {
            if (!val) return;
            const el = document.getElementById(id);
            if (!el) return;
            el.value = val;
            el.dispatchEvent(new Event('input'));
            el.dispatchEvent(new Event('change'));
            filled++;
        }

        function parseDDMonthYYYY(str) {
            if (!str) return '';
            const mm = {
                'January':1,'February':2,'March':3,'April':4,'May':5,'June':6,
                'July':7,'August':8,'September':9,'October':10,'November':11,'December':12
            };
            const mt = str.match(/(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})/);
            if (!mt) return '';
            const d = mt[1].padStart(2,'0'), mon = mm[mt[2]], y = mt[3];
            return mon ? `${y}-${String(mon).padStart(2,'0')}-${d}` : '';
        }

        setVal('birth_registration_number', r.brn || '');
        setVal('name_bn', r.name_bn || '');
        setVal('name_en', r.name_en || '');
        setVal('mother_name_bn', r.mother_name_bn || '');
        setVal('mother_name_en', r.mother_name_en || '');
        setVal('father_name_bn', r.father_name_bn || '');
        setVal('father_name_en', r.father_name_en || '');

        const dobParsed = parseDDMonthYYYY(r.dob);
        if (dobParsed) {
            const el = document.getElementById('dob');
            el.value = dobParsed;
            el.dispatchEvent(new Event('change'));
            filled++;
        }

        if (r.sex) {
            const el = document.getElementById('gender');
            const v = r.sex.toUpperCase();
            el.value = (v === 'MALE' || v === 'পুরুষ') ? 'Male' : (v === 'FEMALE' || v === 'মহিলা') ? 'Female' : '';
            el.dispatchEvent(new Event('change'));
            filled++;
        }

        const pobBn = r.place_of_birth_bn || '';
        const pobEn = r.place_of_birth_en || '';
        const appendCountry = (val, suffix) => val && !val.includes(suffix) ? val + suffix : val;
        if (pobBn) {
            const bnVal = appendCountry(pobBn, ', বাংলাদেশ');
            setVal('bp_bn', bnVal);
            setVal('addressx', bnVal);
        }
        if (pobEn) {
            const enVal = appendCountry(pobEn, ', Bangladesh');
            setVal('bp_en', enVal);
            setVal('address', enVal);
        }

        const regDateParsed = parseDDMonthYYYY(r.reg_date);
        const issDateParsed = parseDDMonthYYYY(r.issuance_date);
        if (regDateParsed) setVal('givendate', regDateParsed);
        if (issDateParsed) setVal('issuedate', issDateParsed);
        const toTitleCase = (str) => str ? str.replace(/\w\S*/g, w => w[0].toUpperCase() + w.slice(1).toLowerCase()) : '';
        if (r.reg_office) {
            setVal('nibkar', toTitleCase(r.reg_office));
        }

        const taz = document.getElementById('thanaandzilla');
        if (taz && !taz.value) {
            taz.value = 'Sonatola, Bogura';
            taz.dispatchEvent(new Event('input'));
            filled++;
        }

        const iss = document.getElementById('issuedate');
        const giv = document.getElementById('givendate');
        if (iss.value && !giv.value) giv.value = iss.value;
        if (giv.value && !iss.value) iss.value = giv.value;

        refreshAll();
        updatePreview();
        updateStepper();
        statusEl.innerHTML = '<i class="fa fa-check-circle"></i> ' + filled + 'টি ফিল্ড সফলভাবে পূরণ হয়েছে!';
        statusEl.style.color = 'var(--success)';

        setTimeout(() => {
            switchBulkTab('paste');
        }, 2000);
    } catch (err) {
        statusEl.innerHTML = '❌ Network error: ' + err.message;
        statusEl.style.color = 'var(--danger)';
    }
}

function convertYearToText(year) {
    if (year >= 2000 && year < 3000) {
        const rem = year % 1000;
        if (rem === 0) return 'Two Thousand';
        if (rem < 100) return `Two Thousand ${numToText(rem)}`;
        return `Two Thousand ${numToText(rem)}`;
    }
    if (year >= 1900 && year < 2000) {
        const tens = year % 100;
        return tens === 0 ? 'Nineteen Hundred' : `Nineteen Hundred ${numToText(tens)}`;
    }
    const th = Math.floor(year / 1000), rem = year % 1000;
    return rem === 0 ? `${numToText(th)} Thousand` : `${numToText(th)} Thousand ${numToText(rem)}`;
}
function numToText(n) {
    const units = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine','Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen','Seventeen','Eighteen','Nineteen'];
    const tens2 = ['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
    if (n === 0) return 'Zero';
    if (n < 20)  return units[n];
    if (n < 100) { const t=Math.floor(n/10),u=n%10; return u===0?tens2[t]:`${tens2[t]} ${units[u]}`; }
    if (n < 1000){ const h=Math.floor(n/100),r=n%100; return r===0?`${units[h]} Hundred`:`${units[h]} Hundred ${numToText(r)}`; }
    const th=Math.floor(n/1000),r=n%1000;
    return r===0?`${numToText(th)} Thousand`:`${numToText(th)} Thousand ${numToText(r)}`;
}
</script>

</body>
</html>
