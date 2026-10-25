<?php
declare(strict_types=1);

/**
 * Repdoc — UDC document archive landing page.
 *
 * Uses the homepage Counter-Glass theme (tokens.css + shell.css + home.css)
 * and the real blank templates in Entrepreneur/data/. Access is restricted
 * to admins and UDC users explicitly granted archive access in the admin
 * panel (entrepreneur_apps.archive_access).
 */

require_once dirname(__DIR__, 2) . '/src/bootstrap.php';

require_login();
if (!is_udc() && !is_admin()) {
    redirect(repdoc_url('Entrepreneur/apply/'));
}
if (!has_archive_access()) {
    flash_set('error', 'ডকুমেন্ট আর্কাইভ ব্যবহারের জন্য অ্যাডমিনের অনুমোদন প্রয়োজন।');
    redirect(repdoc_url('Entrepreneur/panel/'));
}
repdoc_security_headers();

$panelUrl       = repdoc_panel_home();
$downloadUrl    = repdoc_url('Entrepreneur/panel/download.php');
$generatorUrl   = repdoc_url('genaretor/prottoyon/');
$certificateUrl = repdoc_url('genaretor/certificate/');

/** Human-readable size for a file in Entrepreneur/data/. */
function archive_file_meta(string $file): array
{
    $path = REPDOC_ROOT . '/Entrepreneur/data/' . $file;
    if (!is_file($path)) {
        return ['ok' => false, 'label' => 'ফাইল নেই'];
    }

    $bytes = (int) @filesize($path);
    if ($bytes < 1024) {
        $size = $bytes . ' B';
    } elseif ($bytes < 1024 * 1024) {
        $size = number_format($bytes / 1024, 1) . ' KB';
    } else {
        $size = number_format($bytes / (1024 * 1024), 1) . ' MB';
    }

    $ext = strtoupper((string) pathinfo($file, PATHINFO_EXTENSION));
    return ['ok' => true, 'label' => $ext . ' · ' . $size];
}

/** Bengali digits for counters. */
function archive_bn(int|string $value): string
{
    return strtr((string) $value, [
        '0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪',
        '5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯',
    ]);
}

$cards = [
    [
        'title'  => 'High School Old',
        'desc'   => 'OLD High School Certificate blank template',
        'cat'    => 'certificate',
        'tag'    => 'Certificate',
        'icon'   => 'i-graduation',
        'tint'   => 'tint-blue',
        'kind'   => 'download',
        'file'   => 'oldblank.PNG',
        'action' => 'ডাউনলোড',
    ],
    [
        'title'  => 'Girls Old',
        'desc'   => 'Certificate template for grade 8 girls',
        'cat'    => 'certificate',
        'tag'    => 'Certificate',
        'icon'   => 'i-certificate',
        'tint'   => 'tint-green',
        'kind'   => 'download',
        'file'   => '8mgirls.jpg',
        'action' => 'ডাউনলোড',
    ],
    [
        'title'  => 'OLD Birth Certificate',
        'desc'   => 'Old Birth Certificate blank template',
        'cat'    => 'certificate',
        'tag'    => 'Birth Cert',
        'icon'   => 'i-birth',
        'tint'   => 'tint-amber',
        'kind'   => 'download',
        'file'   => 'oldbc.jpg',
        'action' => 'ডাউনলোড',
    ],
    [
        'title'  => 'PSC Certificate',
        'desc'   => 'PSC Certificate blank form',
        'cat'    => 'certificate',
        'tag'    => 'Certificate',
        'icon'   => 'i-file',
        'tint'   => 'tint-red',
        'kind'   => 'download',
        'file'   => 'PSC.jpg',
        'action' => 'ডাউনলোড',
    ],
    [
        'title'  => 'Oxford KG Prottoyon',
        'desc'   => 'Prottoyon blank for birth proof (Oxford Kindergarten)',
        'cat'    => 'prottoyon',
        'tag'    => 'Prottoyon',
        'icon'   => 'i-file',
        'tint'   => 'tint-violet',
        'kind'   => 'download',
        'file'   => 'Oxford_Prottoyon.JPG',
        'action' => 'ডাউনলোড',
    ],
    [
        'title'  => 'Gobarpara Primary Prottoyon',
        'desc'   => 'Gobarpara Primary School prottoyon blank for birth proof',
        'cat'    => 'prottoyon',
        'tag'    => 'Prottoyon',
        'icon'   => 'i-file',
        'tint'   => 'tint-violet',
        'kind'   => 'download',
        'file'   => 'Gobarpara_Primary.jpg',
        'action' => 'ডাউনলোড',
    ],
    [
        'title'  => 'Tika Card (EPI)',
        'desc'   => 'EPI Vaccination Card blank — Black & White',
        'cat'    => 'certificate',
        'tag'    => 'Health Card',
        'icon'   => 'i-syringe',
        'tint'   => 'tint-green',
        'kind'   => 'external',
        'url'    => 'https://docs.google.com/document/d/1v9rU5lP_wjHwexDzvcwfhhBAyNRzfYoN/edit?usp=sharing&ouid=108834765163028255543&rtpof=true&sd=true',
        'meta'   => 'Google Docs',
        'action' => 'খুলুন',
    ],
    [
        'title'  => 'Kabin Nama / Nikahnama',
        'desc'   => 'Marriage Certificate (Kabin/Nikahnama) PSD template',
        'cat'    => 'certificate',
        'tag'    => 'PSD File',
        'icon'   => 'i-marriage',
        'tint'   => 'tint-amber',
        'kind'   => 'external',
        'url'    => 'https://drive.google.com/file/d/1NWN_NM5OAmeSymwHGOQl5AMv5U4vyNgW/view?usp=sharing',
        'meta'   => 'PSD · Google Drive',
        'action' => 'খুলুন',
    ],
    [
        'title'  => 'Birth Registration Prottoyon',
        'desc'   => 'Official Birth Registration Certificate (Prottoyon Form)',
        'cat'    => 'prottoyon',
        'tag'    => 'প্রত্যয়ন',
        'icon'   => 'i-passport',
        'tint'   => 'tint-blue',
        'kind'   => 'generator',
        'type'   => 'birth',
        'meta'   => 'Word · PDF · লাইভ জেনারেটর',
        'action' => 'তৈরি করুন',
    ],
    [
        'title'  => 'Character Certificate',
        'desc'   => 'School Character Certificate / Prottoyon for students',
        'cat'    => 'prottoyon',
        'tag'    => 'Prottoyon',
        'icon'   => 'i-user-check',
        'tint'   => 'tint-green',
        'kind'   => 'generator',
        'type'   => 'character',
        'meta'   => 'Word · PDF · লাইভ জেনারেটর',
        'action' => 'তৈরি করুন',
    ],
    [
        'title'  => 'Income Certificate',
        'desc'   => 'Union Parishad Income Certificate / Prottoyon format',
        'cat'    => 'prottoyon',
        'tag'    => 'প্রত্যয়নপত্র',
        'icon'   => 'i-income',
        'tint'   => 'tint-amber',
        'kind'   => 'generator',
        'type'   => 'annual-income',
        'meta'   => 'Word · PDF · লাইভ জেনারেটর',
        'action' => 'তৈরি করুন',
    ],
    [
        'title'  => 'Nationality Certificate',
        'desc'   => 'Bangladesh Nationality Certificate template (প্রত্যয়নপত্র)',
        'cat'    => 'certificate',
        'tag'    => 'Certificate',
        'icon'   => 'i-flag',
        'tint'   => 'tint-red',
        'kind'   => 'generator',
        'type'   => 'nationality',
        'meta'   => 'Word · PDF · লাইভ জেনারেটর',
        'action' => 'তৈরি করুন',
    ],
    [
        'title'  => 'JSC Certificate',
        'desc'   => 'Junior School Certificate (JSC) blank template',
        'cat'    => 'certificate',
        'tag'    => 'Certificate',
        'icon'   => 'i-scroll',
        'tint'   => 'tint-blue',
        'kind'   => 'page',
        'url'    => $certificateUrl,
        'meta'   => 'সনদ তালিকা',
        'action' => 'খুলুন',
    ],
    [
        'title'  => 'SSC Certificate',
        'desc'   => 'Secondary School Certificate (SSC) blank template',
        'cat'    => 'certificate',
        'tag'    => 'Certificate',
        'icon'   => 'i-cap',
        'tint'   => 'tint-green',
        'kind'   => 'page',
        'url'    => $certificateUrl,
        'meta'   => 'সনদ তালিকা',
        'action' => 'খুলুন',
    ],
];

$counts = ['all' => count($cards), 'prottoyon' => 0, 'certificate' => 0];

// Extra Bangla search terms: the card titles stay close to the source files.
$bnKeywords = [
    'High School Old'              => 'মাধ্যমিক সনদ স্কুল সার্টিফিকেট',
    'Girls Old'                    => 'বালিকা সনদ অষ্টম শ্রেণি',
    'OLD Birth Certificate'        => 'জন্ম সনদ জন্মনিবন্ধন',
    'PSC Certificate'              => 'পিএসসি প্রাথমিক সমাপনী সনদ',
    'Oxford KG Prottoyon'          => 'অক্সফোর্ড কেজি প্রত্যয়ন জন্ম',
    'Gobarpara Primary Prottoyon'  => 'গোবরপাড়া প্রাথমিক প্রত্যয়ন জন্ম',
    'Tika Card (EPI)'              => 'টিকা কার্ড ইপিআই স্বাস্থ্য',
    'Kabin Nama / Nikahnama'       => 'কবিন নামা নিকাহনামা বিবাহ',
    'Birth Registration Prottoyon' => 'জন্ম নিবন্ধন প্রত্যয়ন',
    'Character Certificate'        => 'চারিত্রিক সনদ',
    'Income Certificate'           => 'আয় সনদ প্রত্যয়ন',
    'Nationality Certificate'      => 'জাতীয়তা সনদ',
    'JSC Certificate'              => 'জেএসসি সনদ',
    'SSC Certificate'              => 'এসএসসি সনদ',
];

foreach ($cards as &$card) {
    $card['meta'] = $card['meta'] ?? '';
    $card['available'] = true;

    if ($card['kind'] === 'download') {
        $file = archive_file_meta((string) $card['file']);
        $card['available'] = $file['ok'];
        $card['meta'] = $file['label'];
    }

    $card['search'] = mb_strtolower(implode(' ', [
        (string) $card['title'],
        (string) $card['desc'],
        (string) $card['tag'],
        (string) $card['meta'],
        (string) $card['cat'],
        (string) ($bnKeywords[(string) $card['title']] ?? ''),
    ]), 'UTF-8');

    $counts[$card['cat']]++;
}
unset($card);
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ডকুমেন্ট আর্কাইভ — UDC প্যানেল — Repdoc</title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#FFFEFB">
    <link rel="icon" type="image/png" href="<?= e(repdoc_url('public/favicon.png')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Bengali:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/tokens.css?v=1')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/shell.css?v=1')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/home.css?v=3')) ?>">
    <style>
        .archive-hero { padding: 36px 0 28px; }
        .archive-hero .reg-hero-main { min-height: 170px; }
        .archive-hero .reg-hero-art { width: min(24%, 250px); }
        .archive-hero .hero-sub { max-width: 680px; font-size: .98rem; }
        .archive-hero .hero-note { margin-top: 14px; }

        .archive-board-wrap { margin-top: -1px; padding: 22px 0 0; }
        .archive-board {
            display: grid; grid-template-columns: repeat(3, minmax(0, 1fr));
            background: var(--bg-surface);
            border: 2px solid var(--text-primary);
            border-radius: 8px;
            box-shadow: 4px 4px 0 var(--text-primary);
            overflow: hidden;
        }
        .archive-counter { padding: 16px 14px; text-align: center; }
        .archive-counter + .archive-counter { border-left: 1px solid var(--border-color); }
        .archive-counter-num {
            display: block;
            font-family: var(--font-heading);
            font-size: 1.9rem; font-weight: 800; line-height: 1;
            color: var(--primary);
        }
        .archive-counter-label {
            display: block; margin-top: 4px;
            font-family: var(--font-mono); font-size: .66rem; font-weight: 600;
            letter-spacing: .12em; text-transform: uppercase; color: var(--text-muted);
        }

        .archive-section { padding: 34px 0 56px; }
        .archive-toolbar {
            display: flex; flex-wrap: wrap; align-items: center; gap: 12px;
            margin-bottom: 14px;
        }
        .archive-search {
            flex: 1 1 280px; display: flex; align-items: center; gap: 9px;
            min-height: 46px; padding: 0 13px;
            background: var(--bg-surface);
            border: 2px solid var(--text-primary);
            border-radius: 6px;
            box-shadow: 3px 3px 0 var(--text-primary);
        }
        .archive-search:focus-within { border-color: var(--primary); outline: 3px solid var(--primary-light); }
        .archive-search .icon { width: 18px; height: 18px; color: var(--primary); flex: 0 0 auto; }
        .archive-search input {
            flex: 1; min-width: 0; border: 0; outline: 0; background: transparent;
            font: inherit; font-size: .9rem; color: var(--text-primary); padding: 10px 0;
        }
        .archive-search input::placeholder { color: var(--text-muted); }

        .archive-view { display: flex; gap: 8px; flex: 0 0 auto; }
        .archive-view-btn {
            display: inline-flex; align-items: center; justify-content: center;
            width: 46px; height: 46px;
            background: var(--bg-surface); color: var(--text-muted);
            border: 2px solid var(--text-primary); border-radius: 6px;
            box-shadow: 3px 3px 0 var(--text-primary); cursor: pointer;
            transition: background var(--transition-fast), color var(--transition-fast), transform var(--transition-fast);
        }
        .archive-view-btn .icon { width: 18px; height: 18px; }
        .archive-view-btn:hover { color: var(--text-primary); background: var(--bg-muted); }
        .archive-view-btn.is-active { background: var(--primary); color: var(--on-accent); }
        .archive-view-btn:active { transform: translate(2px, 2px); box-shadow: 1px 1px 0 var(--text-primary); }

        .archive-chips { margin: 0 0 22px; }

        .archive-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 18px;
        }
        .archive-grid.is-list { grid-template-columns: 1fr; }
        .archive-grid.is-list .archive-card {
            flex-direction: row; align-items: center; gap: 18px;
        }
        .archive-grid.is-list .archive-card-top { flex: 0 0 auto; }
        .archive-grid.is-list .archive-body { flex: 1; }
        .archive-grid.is-list .archive-actions { margin-top: 0; }

        .archive-card {
            display: flex; flex-direction: column; gap: 12px;
            padding: 18px;
            background: var(--bg-surface);
            border: 2px solid var(--text-primary);
            border-radius: 6px;
            box-shadow: 4px 4px 0 var(--text-primary);
            transition: transform var(--transition-fast), box-shadow var(--transition-fast);
        }
        .archive-card:hover, .archive-card:focus-within {
            transform: translate(-2px, -2px);
            box-shadow: 6px 6px 0 var(--text-primary);
        }
        .archive-card[hidden] { display: none; }

        .archive-card-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; }
        .archive-icon {
            display: grid; place-items: center;
            width: 48px; height: 48px; flex: 0 0 auto;
            border: 1.5px solid var(--text-primary);
            border-radius: 6px;
            background: var(--bg-muted);
            color: var(--primary);
        }
        .archive-icon .icon { width: 23px; height: 23px; }
        .archive-icon.tint-blue { background: #E7F0FC; color: #2F5BB7; }
        .archive-icon.tint-green { background: #E9F5EE; color: #0E6B4A; }
        .archive-icon.tint-violet { background: #F1EAFB; color: #6D45C7; }
        .archive-icon.tint-amber { background: #FFF4DC; color: #9A6200; }
        .archive-icon.tint-red { background: #FDECEC; color: #A31B20; }

        .archive-stamp {
            display: inline-block; padding: 3px 8px;
            border: 2px solid var(--accent); border-radius: 4px;
            color: var(--accent); background: transparent;
            font-family: var(--font-mono); font-size: .62rem; font-weight: 700;
            letter-spacing: .06em; text-transform: uppercase;
            transform: rotate(-2deg);
        }
        .archive-stamp.is-green { border-color: var(--primary); color: var(--primary); }

        .archive-title {
            font-size: 1.04rem; font-weight: 800; line-height: 1.35;
            color: var(--text-primary); letter-spacing: 0;
        }
        .archive-desc { margin-top: 5px; color: var(--text-muted); font-size: .85rem; line-height: 1.65; }
        .archive-meta {
            display: flex; align-items: center; gap: 6px; margin-top: 9px;
            font-family: var(--font-mono); font-size: .67rem; font-weight: 600;
            letter-spacing: .04em; color: var(--text-muted);
        }
        .archive-meta .icon { width: 13px; height: 13px; color: var(--primary); }

        .archive-actions { margin-top: auto; }
        .archive-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 7px;
            min-height: 40px; padding: 8px 14px;
            background: var(--primary); color: var(--on-accent);
            border: 2px solid var(--text-primary); border-radius: 6px;
            box-shadow: 3px 3px 0 var(--text-primary);
            font-family: var(--font-sans); font-size: .82rem; font-weight: 700;
            text-decoration: none; cursor: pointer;
            transition: background var(--transition-fast), transform var(--transition-fast), box-shadow var(--transition-fast);
        }
        .archive-btn .icon { width: 15px; height: 15px; }
        .archive-btn:hover { background: var(--primary-hover); color: var(--on-accent); }
        .archive-btn:active { transform: translate(2px, 2px); box-shadow: 1px 1px 0 var(--text-primary); }
        .archive-btn.is-secondary { background: transparent; color: var(--text-primary); }
        .archive-btn.is-secondary:hover { background: var(--bg-muted); color: var(--text-primary); }
        .archive-btn.is-disabled { opacity: .55; cursor: not-allowed; box-shadow: none; transform: none; }

        .archive-empty[hidden] { display: none; }
        .archive-empty {
            border: 2px dashed var(--border-strong); border-radius: 6px;
            padding: 34px 20px; text-align: center; color: var(--text-muted);
            background: var(--bg-surface);
        }
        .archive-empty .icon { width: 34px; height: 34px; margin: 0 auto 10px; color: var(--border-strong); }
        .archive-empty strong { display: block; color: var(--text-primary); margin-bottom: 4px; }

        .archive-footer {
            margin-top: 8px; padding: 22px 0;
            background: #EEF8F1; border-top: 2px solid var(--text-primary);
        }
        .archive-footer-inner {
            display: flex; align-items: center; justify-content: space-between;
            gap: 14px; flex-wrap: wrap;
        }
        .archive-legal { color: var(--text-muted); font-size: .8rem; }

        @media (max-width: 900px) {
            .archive-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 620px) {
            .archive-hero .reg-hero-art { display: none; }
            .archive-board { grid-template-columns: 1fr; }
            .archive-counter + .archive-counter { border-left: 0; border-top: 1px solid var(--border-color); }
            .archive-grid, .archive-grid.is-list { grid-template-columns: 1fr; }
            .archive-grid.is-list .archive-card { flex-direction: column; align-items: stretch; }
            .archive-grid.is-list .archive-actions { margin-top: 0; }
            .archive-footer-inner { justify-content: center; text-align: center; }
        }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: .01ms !important; animation-iteration-count: 1 !important; transition-duration: .01ms !important; }
        }
    </style>
</head>
<body>
    <svg class="sprite" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">
        <defs>
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

    <header class="site-header">
        <div class="container header-inner">
            <a class="brand brand-ledger" href="<?= e($panelUrl) ?>" aria-label="Repdoc — UDC প্যানেল">
                <img class="brand-logo" src="<?= e(repdoc_url('src/assets/image/logo.png')) ?>" alt="" width="120" height="40" decoding="async">
                <span class="brand-word"><strong>Repdoc</strong><small>UDC · ডকুমেন্ট আর্কাইভ</small></span>
            </a>
            <div class="header-actions">
                <a class="btn btn-primary btn-login" href="<?= e($panelUrl) ?>">
                    <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-home"/></svg>
                    <span>ড্যাশবোর্ড</span>
                </a>
            </div>
        </div>
    </header>

    <main>
        <section class="hero hero-ledger archive-hero" aria-labelledby="archiveHeading">
            <div class="container hero-ledger-inner">
                <p class="reg-kicker"><span class="reg-no" aria-hidden="true">UDC</span> ডকুমেন্ট আর্কাইভ</p>
                <div class="reg-hero-main">
                    <div class="reg-hero-title">
                        <h1 class="reg-title" id="archiveHeading">প্রত্যয়ন <span class="reg-title-accent">&amp; সার্টিফিকেট</span></h1>
                        <span class="reg-rule" aria-hidden="true"></span>
                    </div>
                    <figure class="reg-hero-art">
                        <img class="illus hero-photo" src="<?= e(repdoc_url('src/assets/image/hero.webp')) ?>" alt="" width="900" height="600" decoding="async">
                    </figure>
                </div>
                <p class="hero-sub reg-sub">
                    UDC অপারেটরদের জন্য রেডি টেমপ্লেট ও জেনারেটর — ব্ল্যাংক ফাইল ডাউনলোড করুন,
                    অথবা তথ্য দিয়ে প্রত্যয়ন ও সনদ তৈরি করুন। Word ও PDF আকারে।
                </p>
                <p class="hero-note reg-note">
                    <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-file"/></svg>
                    এই আর্কাইভ শুধু অনুমোদিত UDC অপারেটর ও অ্যাডমিনের জন্য।
                </p>
            </div>
        </section>

        <div class="archive-board-wrap">
            <div class="container">
                <div class="archive-board" aria-label="আর্কাইভ সারসংক্ষেপ">
                    <div class="archive-counter">
                        <span class="archive-counter-num" id="total-count" aria-live="polite"><?= archive_bn($counts['all']) ?></span>
                        <span class="archive-counter-label">টেমপ্লেট</span>
                    </div>
                    <div class="archive-counter">
                        <span class="archive-counter-num"><?= archive_bn($counts['prottoyon']) ?></span>
                        <span class="archive-counter-label">প্রত্যয়ন</span>
                    </div>
                    <div class="archive-counter">
                        <span class="archive-counter-num"><?= archive_bn($counts['certificate']) ?></span>
                        <span class="archive-counter-label">সার্টিফিকেট</span>
                    </div>
                </div>
            </div>
        </div>

        <section class="archive-section" aria-labelledby="archiveListHeading">
            <div class="container">
                <div class="archive-toolbar">
                    <div class="archive-search">
                        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-search"/></svg>
                        <input type="search" id="archiveSearch" placeholder="ডকুমেন্ট খুঁজুন… (যেমন: জন্ম, কবিন, সার্টিফিকেট)" aria-label="ডকুমেন্ট খুঁজুন" autocomplete="off">
                    </div>
                    <div class="archive-view" role="group" aria-label="ভিউ">
                        <button type="button" class="archive-view-btn is-active" id="gridViewBtn" aria-label="গ্রিড ভিউ" aria-pressed="true">
                            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-grid"/></svg>
                        </button>
                        <button type="button" class="archive-view-btn" id="listViewBtn" aria-label="লিস্ট ভিউ" aria-pressed="false">
                            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-list"/></svg>
                        </button>
                    </div>
                </div>

                <div class="category-chips archive-chips" role="group" aria-label="ক্যাটাগরি">
                    <button type="button" class="chip chip-cat is-active" data-cat="all" aria-pressed="true">সব (<?= archive_bn($counts['all']) ?>)</button>
                    <button type="button" class="chip chip-cat" data-cat="prottoyon" aria-pressed="false">প্রত্যয়ন (<?= archive_bn($counts['prottoyon']) ?>)</button>
                    <button type="button" class="chip chip-cat" data-cat="certificate" aria-pressed="false">সার্টিফিকেট (<?= archive_bn($counts['certificate']) ?>)</button>
                </div>

                <h2 id="archiveListHeading" class="sr-only">সব টেমপ্লেট</h2>

                <div class="archive-grid" id="archiveGrid">
                    <?php foreach ($cards as $card): ?>
                        <?php
                        $href = '#';
                        $external = false;
                        if ($card['kind'] === 'download' && $card['available']) {
                            $href = $downloadUrl . '?file=' . rawurlencode((string) $card['file']);
                        } elseif ($card['kind'] === 'external') {
                            $href = (string) $card['url'];
                            $external = true;
                        } elseif ($card['kind'] === 'generator') {
                            $href = $generatorUrl . '?type=' . rawurlencode((string) $card['type']);
                        } elseif ($card['kind'] === 'page') {
                            $href = (string) $card['url'];
                        }
                        $actionIcon = $card['kind'] === 'download' ? 'i-download' : ($external ? 'i-external' : 'i-arrow');
                        ?>
                        <article class="archive-card"
                                 data-cat="<?= e($card['cat']) ?>"
                                 data-search="<?= e($card['search']) ?>">
                            <div class="archive-card-top">
                                <span class="archive-icon <?= e($card['tint']) ?>" aria-hidden="true">
                                    <svg class="icon" viewBox="0 0 24 24"><use href="#<?= e($card['icon']) ?>"/></svg>
                                </span>
                                <span class="archive-stamp<?= $card['cat'] === 'prottoyon' ? ' is-green' : '' ?>"><?= e($card['tag']) ?></span>
                            </div>
                            <div class="archive-body">
                                <h3 class="archive-title"><?= e($card['title']) ?></h3>
                                <p class="archive-desc"><?= e($card['desc']) ?></p>
                                <p class="archive-meta">
                                    <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-file"/></svg>
                                    <?= e($card['meta']) ?>
                                </p>
                            </div>
                            <div class="archive-actions">
                                <?php if ($card['kind'] === 'download' && !$card['available']): ?>
                                    <span class="archive-btn is-disabled" aria-disabled="true">ফাইল নেই</span>
                                <?php else: ?>
                                    <a class="archive-btn"
                                       href="<?= e($href) ?>"
                                       <?= $external ? 'target="_blank" rel="noopener noreferrer"' : '' ?>
                                       <?= $card['kind'] === 'download' ? 'download="' . e((string) $card['file']) . '"' : '' ?>>
                                        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#<?= e($actionIcon) ?>"/></svg>
                                        <?= e($card['action']) ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <div class="archive-empty" id="archiveEmpty" role="status" hidden>
                    <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-search"/></svg>
                    <strong>কোনো ডকুমেন্ট পাওয়া যায়নি</strong>
                    <span>অন্য শব্দ বা ক্যাটাগরি চেষ্টা করুন।</span>
                </div>
            </div>
        </section>
    </main>

    <footer class="archive-footer">
        <div class="container archive-footer-inner">
            <p class="archive-legal">স্বতন্ত্র টুলস পেজ — সরকারি পোর্টাল নয়।</p>
            <a class="btn btn-ledger-outline" href="<?= e($panelUrl) ?>">
                <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-home"/></svg>
                ড্যাশবোর্ডে ফিরুন
            </a>
        </div>
    </footer>

    <script>
        (function () {
            'use strict';

            var grid = document.getElementById('archiveGrid');
            var search = document.getElementById('archiveSearch');
            var empty = document.getElementById('archiveEmpty');
            var totalCount = document.getElementById('total-count');
            var cards = Array.prototype.slice.call(grid.querySelectorAll('.archive-card[data-cat]'));
            var chips = Array.prototype.slice.call(document.querySelectorAll('.archive-chips .chip'));
            var gridBtn = document.getElementById('gridViewBtn');
            var listBtn = document.getElementById('listViewBtn');
            var activeCat = 'all';

            function bn(value) {
                return String(value).replace(/[0-9]/g, function (digit) {
                    return '০১২৩৪৫৬৭৮৯'[digit];
                });
            }

            function applyFilter() {
                var query = search.value.toLowerCase().trim();
                var visible = 0;

                cards.forEach(function (card) {
                    var matchesCat = activeCat === 'all' || card.getAttribute('data-cat') === activeCat;
                    var matchesQuery = query === '' || (card.getAttribute('data-search') || '').indexOf(query) !== -1;
                    var show = matchesCat && matchesQuery;
                    card.hidden = !show;
                    if (show) visible++;
                });

                empty.hidden = visible !== 0;
                totalCount.textContent = bn(visible);
            }

            function setView(view) {
                var isList = view === 'list';
                grid.classList.toggle('is-list', isList);
                gridBtn.classList.toggle('is-active', !isList);
                listBtn.classList.toggle('is-active', isList);
                gridBtn.setAttribute('aria-pressed', isList ? 'false' : 'true');
                listBtn.setAttribute('aria-pressed', isList ? 'true' : 'false');
            }

            search.addEventListener('input', applyFilter);

            chips.forEach(function (chip) {
                chip.addEventListener('click', function () {
                    activeCat = chip.getAttribute('data-cat') || 'all';
                    chips.forEach(function (item) {
                        var active = item === chip;
                        item.classList.toggle('is-active', active);
                        item.setAttribute('aria-pressed', active ? 'true' : 'false');
                    });
                    applyFilter();
                });
            });

            gridBtn.addEventListener('click', function () { setView('grid'); });
            listBtn.addEventListener('click', function () { setView('list'); });

            applyFilter();
        })();
    </script>
</body>
</html>
