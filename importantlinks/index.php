<?php
declare(strict_types=1);

/**
 * Repdoc — file showcase: Microsoft Office / PSD / AI / PLP.
 *
 * Public, indexable landing page for downloadable template packs.
 * Files are auto-listed from officefiles/{word,excel,ppt}/ and
 * offlinefiles/{psd,ai,plp}/; each card shows the admin-written
 * description (admin/files.php) and falls back to a format note when
 * none is set. Listing is public; the bytes stay gated behind an
 * approved login via each hub's download.php.
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';
require_once dirname(__DIR__) . '/src/seo.php';
require_once dirname(__DIR__) . '/src/filemeta.php';

$SEO_TITLE = 'গুরুত্বপূর্ণ লিংক — Repdoc';
$SEO_DESC  = 'Repdoc ফাইল শোকেস: Microsoft Word, Excel ও PowerPoint টেমপ্লেট — প্রতিটি ফাইলের বিবরণসহ। লগ ইন করে ডাউনলোড করুন।';
$CANON     = repdoc_canonical('officefiles/');
$HOME      = repdoc_url('/');
$linksFile = dirname(__DIR__) . '/love/important_links.json';
$links = [];
if (is_file($linksFile)) {
    $dec = json_decode((string) @file_get_contents($linksFile), true);
    if (is_array($dec)) $links = $dec;
}
$LOGIN_URL = repdoc_url('public/login/');
$APPLY_URL = repdoc_url('Entrepreneur/apply/');
$OFFLINE_URL = repdoc_url('offlinefiles/');
$SHOWCASE_URL = repdoc_url('showcase/');
$PROTTOYON_URL = repdoc_url('genaretor/prottoyon/');
$HERO_IMG  = repdoc_url('src/assets/image/officefiles-hero.png');

$currentUser = current_user();
$dashUrl = $currentUser !== null ? repdoc_panel_home() : $LOGIN_URL;

/**
 * One entry per format folder.
 * hub = the public directory that owns the download endpoint.
 */
$FORMATS = [
    'word' => [
        'label' => 'MS Word',
        'full'  => 'Microsoft Word',
        'hub'   => 'officefiles',
        'group' => 'office',
        'brand' => '#2B579A',
        'soft'  => '#EEF3FB',
        'exts'  => ['doc', 'docx', 'rtf', 'odt', 'zip'],
        'note'  => 'চিঠি, আবেদন, নোটিশ ও অফিসের দৈনন্দিন কাজের জন্য রেডি Microsoft Word টেমপ্লেট — নাম, ঠিকানা ও তারিখ বদলে সরাসরি ব্যবহার করুন।',
    ],
    'excel' => [
        'label' => 'MS Excel',
        'full'  => 'Microsoft Excel',
        'hub'   => 'officefiles',
        'group' => 'office',
        'brand' => '#217346',
        'soft'  => '#EDF6F1',
        'exts'  => ['xls', 'xlsx', 'csv', 'zip'],
        'note'  => 'হিসাব, তালিকা, বাজেট ও রিপোর্টের জন্য ফর্মুলা-সহ Microsoft Excel স্প্রেডশিট — নিজের ডেটা বসিয়ে সাথে সাথে ব্যবহারযোগ্য।',
    ],
    'ppt' => [
        'label' => 'PowerPoint',
        'full'  => 'Microsoft PowerPoint',
        'hub'   => 'officefiles',
        'group' => 'office',
        'brand' => '#C43E1C',
        'soft'  => '#FBEEE9',
        'exts'  => ['ppt', 'pptx', 'zip'],
        'note'  => 'মিটিং, প্রেজেন্টেশন ও অফিসিয়াল প্রেজেন্টেশনের জন্য প্রস্তুত Microsoft PowerPoint স্লাইড — নিজের তথ্য বসিয়ে ব্যবহার করুন।',
    ],
];

$GROUPS = [
    'office' => ['label' => 'MS Office', 'sub' => 'Word, Excel, PowerPoint'],
];

/** Official software logos used by the category tiles and preview art. */
function officefiles_logo_url(string $type): string
{
    static $files = [
        'office' => 'microsoft-office.svg',
        'word'   => 'word.svg',
        'excel'  => 'excel.svg',
        'ppt'    => 'powerpoint.svg',
        'psd'    => 'photoshop.svg',
        'ai'     => 'illustrator.svg',
        'plp'    => 'pixellab.png',
    ];
    $file = $files[$type] ?? $files['office'];
    return repdoc_url('src/assets/image/file-logos/' . $file);
}

function officefiles_logo_icon(string $type, string $class = ''): string
{
    $class = trim('of-format-icon ' . $class);
    return '<span class="' . e($class) . '" aria-hidden="true">'
        . '<img src="' . e(officefiles_logo_url($type)) . '" alt="" width="48" height="48" loading="lazy" decoding="async">'
        . '</span>';
}

function officefiles_human_size(int $bytes): string
{
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1024 * 1024) return number_format($bytes / 1024, 1) . ' KB';
    if ($bytes < 1024 * 1024 * 1024) return number_format($bytes / (1024 * 1024), 1) . ' MB';
    return number_format($bytes / (1024 * 1024 * 1024), 2) . ' GB';
}

function officefiles_bn_digits(string $value): string
{
    return strtr($value, ['0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪', '5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯']);
}

function officefiles_date(int $timestamp): string
{
    if ($timestamp <= 0) return '—';
    $months = [1 => 'জানু', 2 => 'ফেব', 3 => 'মার্চ', 4 => 'এপ্রিল', 5 => 'মে', 6 => 'জুন', 7 => 'জুলাই', 8 => 'আগস্ট', 9 => 'সেপ্ট', 10 => 'অক্টো', 11 => 'নভে', 12 => 'ডিসে'];
    return officefiles_bn_digits(date('d', $timestamp)) . ' ' . $months[(int) date('n', $timestamp)] . ' ' . officefiles_bn_digits(date('Y', $timestamp));
}

function officefiles_title(string $name): string
{
    $stem = (string) pathinfo($name, PATHINFO_FILENAME);
    $stem = str_replace(['-', '_'], ' ', $stem);
    $stem = (string) preg_replace('/\s+/u', ' ', trim($stem));
    if ($stem !== '' && preg_match('/^[\x20-\x7E]+$/', $stem)) {
        $words = explode(' ', $stem);
        foreach ($words as $i => $word) {
            if ($word !== '' && !preg_match('/^[A-Z0-9]{2,}$/', $word)) {
                $words[$i] = mb_convert_case($word, MB_CASE_TITLE, 'UTF-8');
            }
        }
        $stem = implode(' ', $words);
    }
    return $stem !== '' ? $stem : $name;
}

function officefiles_search_index(array $f, array $formats, array $groups): string
{
    $meta = $formats[$f['type']];
    $group = $groups[$meta['group']]['label'] ?? '';
    return mb_strtolower(
        $f['name'] . ' ' . officefiles_title($f['name']) . ' ' . $f['type'] . ' ' . $f['ext'] . ' '
        . $meta['label'] . ' ' . $meta['full'] . ' ' . $group . ' ' . strip_tags($f['description']),
        'UTF-8'
    );
}

function officefiles_preview_art(string $type): string
{
    $logo = e(officefiles_logo_url($type));
    $chip = '<img class="oc-chip oc-chip-logo" src="' . $logo . '" alt="" width="24" height="24" loading="lazy" decoding="async">';
    $stamp = '<img class="oc-brand-stamp" src="' . $logo . '" alt="" width="38" height="38" loading="lazy" decoding="async">';

    switch ($type) {
        case 'word':
            return '<span class="oc-art art-word">'
                . '<span class="oc-paper paper-word">'
                . $chip
                . '<span class="oc-line" style="width:88%"></span>'
                . '<span class="oc-line" style="width:66%"></span>'
                . '<span class="oc-line" style="width:78%"></span>'
                . '<span class="oc-line" style="width:72%"></span>'
                . '<span class="oc-line" style="width:44%"></span>'
                . '</span></span>';
        case 'excel':
            return '<span class="oc-art art-excel">'
                . '<span class="oc-paper paper-excel">'
                . $chip
                . '<span class="oc-sheet-grid">'
                . str_repeat('<i></i>', 15)
                . '</span></span></span>';
        case 'ppt':
            return '<span class="oc-art art-ppt">'
                . '<span class="oc-paper paper-ppt">'
                . $chip
                . '<span class="oc-slide"><span class="oc-slide-title"></span><span class="oc-slide-line"></span><span class="oc-slide-line short"></span></span>'
                . '<span class="oc-slide-dots"><i></i><i></i><i></i></span>'
                . '</span></span>';
        case 'psd':
            return '<span class="oc-art art-psd">'
                . $stamp
                . '<span class="oc-visit visit-back"></span>'
                . '<span class="oc-visit visit-front"><strong>BUSINESS<br>CARD</strong><small>YOUR BRAND</small></span>'
                . '</span>';
        case 'ai':
            return '<span class="oc-art art-ai">'
                . $stamp
                . '<span class="oc-brandsheet"><span class="oc-orbit"></span><strong>BRAND<br>LOGO</strong><small>DESIGN AGENCY</small></span>'
                . '</span>';
        default:
            return '<span class="oc-art art-plp">'
                . $stamp
                . '<span class="oc-poster"><span class="oc-poster-kicker">পোস্টার</span><strong>নবাবর্ষ</strong></span>'
                . '</span>';
    }
}

/** Scan one format folder; returns newest-first file rows. */
function officefiles_scan(string $dir, string $type, array $exts): array
{
    $out = [];
    if (!is_dir($dir)) return $out;
    foreach (@scandir($dir) ?: [] as $name) {
        if ($name === '.' || $name === '..') continue;
        if (str_starts_with($name, '.')) continue;
        $ext = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, $exts, true)) continue;
        $path = $dir . DIRECTORY_SEPARATOR . $name;
        if (!is_file($path)) continue;
        $out[] = [
            'name'  => $name,
            'type'  => $type,
            'ext'   => $ext,
            'size'  => (int) @filesize($path),
            'mtime' => (int) @filemtime($path),
        ];
    }
    usort($out, static fn(array $a, array $b): int => $b['mtime'] <=> $a['mtime']);
    return $out;
}

$root = dirname(__DIR__);
$files = [];
foreach ($FORMATS as $type => $meta) {
    $dir = $root . DIRECTORY_SEPARATOR . $meta['hub'] . DIRECTORY_SEPARATOR . $type;
    foreach (officefiles_scan($dir, $type, $meta['exts']) as $row) {
        $files[] = $row;
    }
}
usort($files, static fn(array $a, array $b): int => $b['mtime'] <=> $a['mtime']);
$files = [];

$counts = ['all' => count($files), 'office' => 0];
$typeCounts = array_fill_keys(array_keys($FORMATS), 0);
$descriptions = repdoc_file_descriptions_all();
$described = 0;

foreach ($files as $i => $f) {
    $meta = $FORMATS[$f['type']];
    $custom = $descriptions[repdoc_file_description_key($f['type'], $f['name'])] ?? '';
    $files[$i]['description'] = $custom !== '' ? $custom : $meta['note'];
    $files[$i]['custom_description'] = $custom !== '';
    $files[$i]['title'] = officefiles_title($f['name']);
    $files[$i]['thumb'] = repdoc_file_thumb_url($f['type'], $f['name']);
    $files[$i]['search'] = officefiles_search_index($files[$i], $FORMATS, $GROUPS);
    if ($custom !== '') $described++;
    $counts[$meta['group']] = ($counts[$meta['group']] ?? 0) + 1;
    $typeCounts[$f['type']] = ($typeCounts[$f['type']] ?? 0) + 1;
}

$totalBytes = array_sum(array_column($files, 'size'));
$attestationCount = count(glob($root . '/genaretor/prottoyon/template/*.php') ?: []);
$showAllCount = $counts['all'];
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($SEO_TITLE) ?></title>
    <meta name="description" content="<?= e($SEO_DESC) ?>">
    <meta name="theme-color" content="#0B6B4A">
    <?php
    repdoc_seo_head([
        'title'       => $SEO_TITLE,
        'description' => $SEO_DESC,
        'canonical'   => $CANON,
        'type'        => 'website',
        'image'       => repdoc_canonical('src/assets/image/officefiles-hero.png'),
        'imageWidth'  => 600,
        'imageHeight' => 378,
        'imageAlt'    => 'Repdoc file showcase',
        'locale'      => 'bn_BD',
    ]);
    repdoc_jsonld([
        '@type'         => 'WebPage',
        '@id'           => $CANON . '#page',
        'name'          => $SEO_TITLE,
        'url'           => $CANON,
        'description'   => $SEO_DESC,
        'inLanguage'    => 'bn',
        'isPartOf'      => ['@id' => repdoc_canonical() . '#website'],
        'breadcrumb'    => ['@id' => $CANON . '#breadcrumb'],
    ]);
    repdoc_jsonld([
        '@type'           => 'BreadcrumbList',
        '@id'             => $CANON . '#breadcrumb',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => repdoc_canonical()],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'ফাইল শোকেস', 'item' => $CANON],
        ],
    ]);
    if ($files !== []) {
        $items = [];
        foreach ($files as $i => $f) {
            $items[] = [
                '@type'       => 'ListItem',
                'position'    => $i + 1,
                'name'        => $FORMATS[$f['type']]['full'] . ' — ' . $f['name'],
                'description' => $f['description'],
            ];
        }
        repdoc_jsonld([
            '@type'           => 'ItemList',
            '@id'             => $CANON . '#files',
            'name'            => 'Repdoc file showcase',
            'numberOfItems'   => count($files),
            'itemListElement' => $items,
        ]);
    }
    ?>
    <link rel="icon" type="image/png" href="<?= e(repdoc_url('public/favicon.png')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Bengali:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/tokens.css?v=1')) ?>">
    <style>
        :root {
            --of-green: #0B6B4A;
            --of-green-dark: #064E3B;
            --of-green-bright: #12A56F;
            --of-green-soft: #E8F5EE;
            --of-ink: #11261E;
            --of-muted: #5B6D64;
            --of-line: #DDE8E1;
            --of-line-strong: #C6D9CE;
            --of-bg: #F7FAF8;
            --of-card: #FFFFFF;
            --of-gold: #F2B705;
            --of-shadow: 0 14px 34px rgba(7, 54, 38, .10);
        }

        .officefiles-page {
            --primary: #0B6B4A;
            --primary-hover: #064E3B;
            --primary-light: #E8F5EE;
            --border-color: #DDE8E1;
            --border-strong: #C6D9CE;
            --border-focus: #0B6B4A;
            background: var(--of-bg);
            color: var(--of-ink);
        }

        .of-container { width: 100%; max-width: 1600px; margin-inline: auto; padding-inline: 22px; }
        .of-sprite { position: absolute; width: 0; height: 0; overflow: hidden; }
        .of-icon { width: 20px; height: 20px; fill: none; stroke: currentColor; stroke-width: 1.9; stroke-linecap: round; stroke-linejoin: round; flex: none; }

        /* Header */
        .of-header { position: sticky; top: 0; z-index: 100; background: rgba(255, 255, 255, .96); border-bottom: 1px solid var(--of-line); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); }
        .of-header-inner { min-height: 74px; display: flex; align-items: center; gap: 24px; }
        .of-brand { display: inline-flex; align-items: center; flex: none; }
        .of-brand img { height: 42px; width: auto; }
        .of-nav { display: flex; align-items: center; gap: 2px; margin-inline-start: 8px; }
        .of-nav-link, .of-dropdown > summary {
            position: relative; display: inline-flex; align-items: center; gap: 6px;
            padding: 12px 12px; border-radius: 8px;
            color: #3D5449; font-size: .94rem; font-weight: 600; white-space: nowrap;
            cursor: pointer; list-style: none;
            transition: color .16s ease, background .16s ease;
        }
        .of-nav-link:hover, .of-dropdown > summary:hover { color: var(--of-green); background: var(--of-green-soft); }
        .of-nav-link.is-active, .of-dropdown > summary.is-active { color: var(--of-green); }
        .of-nav-link.is-active::after, .of-dropdown > summary.is-active::after {
            content: ""; position: absolute; left: 10px; right: 10px; bottom: 3px; height: 2px; border-radius: 2px; background: var(--of-green);
        }
        .of-dropdown { position: relative; }
        .of-dropdown > summary::-webkit-details-marker { display: none; }
        .of-dropdown > summary svg { width: 13px; height: 13px; transition: transform .18s ease; }
        .of-dropdown[open] > summary svg { transform: rotate(180deg); }
        .of-dropdown-menu {
            position: absolute; top: calc(100% + 10px); inset-inline-start: 0; z-index: 120;
            display: grid; gap: 2px; min-width: 238px; padding: 9px;
            background: #fff; border: 1px solid var(--of-line); border-radius: 12px;
            box-shadow: 0 20px 44px rgba(7, 54, 38, .16);
        }
        .of-dropdown-menu a { display: flex; align-items: center; gap: 9px; padding: 10px 12px; border-radius: 8px; color: #3D5449; font-size: .89rem; font-weight: 600; }
        .of-dropdown-menu a:hover { background: var(--of-green-soft); color: var(--of-green); }
        .of-dropdown-menu a .of-count { margin-inline-start: auto; background: #EDF7F1; color: var(--of-green); }
        .of-dropdown-sep { height: 1px; margin: 5px 4px; background: var(--of-line); }

        .of-actions { display: flex; align-items: center; gap: 8px; margin-inline-start: auto; }
        .of-icon-btn { width: 42px; height: 42px; display: inline-flex; align-items: center; justify-content: center; border: 0; border-radius: 50%; background: transparent; color: #344A40; cursor: pointer; transition: background .16s ease, color .16s ease; }
        .of-icon-btn:hover { background: var(--of-green-soft); color: var(--of-green); }
        .of-btn { display: inline-flex; align-items: center; justify-content: center; gap: 7px; min-height: 42px; padding: 9px 20px; border: 1.5px solid transparent; border-radius: 8px; font-size: .9rem; font-weight: 700; white-space: nowrap; cursor: pointer; transition: background .16s ease, color .16s ease, border-color .16s ease, transform .16s ease; }
        .of-btn:active { transform: translateY(1px); }
        .of-btn-ghost { color: var(--of-green); border-color: var(--of-green); background: #fff; }
        .of-btn-ghost:hover { background: var(--of-green-soft); }
        .of-btn-solid { color: #fff; background: var(--of-green); border-color: var(--of-green); box-shadow: 0 8px 18px rgba(11, 107, 74, .20); }
        .of-btn-solid:hover { background: var(--of-green-dark); border-color: var(--of-green-dark); }
        .of-user { max-width: 160px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #3D5449; font-size: .86rem; font-weight: 600; padding: 0 6px; }
        .of-menu-btn { display: none; }

        /* Hero */
        .of-hero { position: relative; overflow: hidden; color: #fff; background: linear-gradient(118deg, #052F22 0%, #07533B 48%, #0A6847 100%); }
        .of-hero::before { content: ""; position: absolute; inset: 0; background: radial-gradient(46% 72% at 84% 60%, rgba(55, 214, 144, .18), transparent 70%), radial-gradient(28% 50% at 68% 18%, rgba(255, 255, 255, .08), transparent 75%); pointer-events: none; }
        .of-hero::after { content: ""; position: absolute; width: 520px; height: 520px; right: -140px; bottom: -280px; border-radius: 50%; border: 1px solid rgba(121, 232, 178, .16); box-shadow: 0 0 0 70px rgba(121, 232, 178, .045), 0 0 0 150px rgba(121, 232, 178, .03); pointer-events: none; }
        .of-hero-inner { position: relative; z-index: 1; display: grid; grid-template-columns: minmax(0, 1.04fr) minmax(320px, .96fr); align-items: center; gap: 34px; padding-block: 48px 52px; }
        .of-kicker { display: inline-flex; align-items: center; gap: 8px; padding: 7px 14px; border: 1px solid rgba(173, 240, 205, .28); border-radius: 7px; background: rgba(255, 255, 255, .09); color: #CFF3DF; font-size: .8rem; font-weight: 600; }
        .of-kicker i { width: 7px; height: 7px; border-radius: 50%; background: #42E39A; box-shadow: 0 0 0 4px rgba(66, 227, 154, .16); }
        .of-hero h1 { margin: 17px 0 14px; color: #fff; font-size: clamp(2rem, 3.6vw, 3.1rem); font-weight: 800; line-height: 1.28; letter-spacing: -.025em; }
        .of-hero h1 span { display: block; color: #54E7A5; }
        .of-lead { max-width: 580px; color: #D4E8DD; font-size: .98rem; line-height: 1.8; }
        .of-search { display: flex; align-items: center; gap: 6px; max-width: 600px; margin-top: 24px; padding: 5px; border-radius: 10px; background: #fff; box-shadow: 0 18px 38px rgba(1, 25, 15, .28); }
        .of-search > svg { margin-inline-start: 13px; color: #697D73; }
        .of-search input { flex: 1; min-width: 0; padding: 12px 6px; border: 0; outline: 0; background: transparent; color: #12211D; font: inherit; font-size: .94rem; }
        .of-search input::placeholder { color: #7C8F86; }
        .of-search button { min-height: 44px; padding: 10px 25px; border: 0; border-radius: 7px; background: var(--of-green); color: #fff; font-weight: 700; font-size: .9rem; cursor: pointer; }
        .of-search button:hover { background: var(--of-green-dark); }
        .of-tags { display: flex; align-items: center; flex-wrap: wrap; gap: 7px; margin-top: 17px; }
        .of-tags > span { color: #BCE0CD; font-size: .78rem; font-weight: 600; margin-inline-end: 2px; }
        .of-tag { padding: 5px 12px; border: 1px solid rgba(172, 238, 204, .28); border-radius: 999px; background: rgba(255, 255, 255, .10); color: #F0FBF5; font-size: .77rem; font-weight: 600; cursor: pointer; transition: background .16s ease, border-color .16s ease; }
        .of-tag:hover { background: rgba(255, 255, 255, .19); border-color: rgba(172, 238, 204, .50); }
        .of-hero-art { position: relative; display: flex; align-items: center; justify-content: center; min-height: 300px; }
        .of-hero-art img { position: relative; z-index: 1; width: min(100%, 500px); height: auto; filter: drop-shadow(0 24px 38px rgba(0, 0, 0, .32)); }
        .of-hero-note { position: absolute; top: -8px; right: 0; z-index: 2; max-width: 154px; color: #fff; font-size: .82rem; font-weight: 600; line-height: 1.45; text-align: center; transform: rotate(-3deg); }
        .of-hero-note svg { display: block; width: 48px; height: 42px; margin: 7px auto 0; opacity: .9; transform: rotate(8deg); }

        /* Category strip */
        .of-categories { padding-block: 20px 4px; background: var(--of-bg); }
        .of-cat-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)) minmax(220px, 1.14fr); gap: 12px; }
        .of-cat { display: flex; align-items: center; gap: 12px; min-width: 0; padding: 12px 13px; border: 1px solid var(--of-line); border-radius: 10px; background: #fff; color: var(--of-ink); text-align: start; cursor: pointer; transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease; }
        .of-cat:hover { border-color: #A9CDBA; box-shadow: 0 10px 22px rgba(7, 54, 38, .07); transform: translateY(-1px); }
        .of-cat.is-active { border-color: var(--of-green); box-shadow: 0 0 0 2px var(--of-green-soft); }
        .of-cat-text { flex: 1; min-width: 0; }
        .of-cat-text strong { display: block; font-size: .93rem; font-weight: 700; }
        .of-cat-text small { display: block; margin-top: 2px; color: var(--of-muted); font-size: .71rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .of-chev { width: 15px; height: 15px; color: #A4B3AB; flex: none; }
        .of-format-icon { position: relative; display: grid; place-items: center; flex: none; width: 48px; height: 48px; overflow: hidden; border-radius: 11px; background: #fff; box-shadow: inset 0 0 0 1px rgba(7, 54, 38, .08), 0 7px 14px rgba(7, 54, 38, .15); }
        .of-format-icon img { display: block; width: 100%; height: 100%; object-fit: contain; }
        .fi-office { padding: 8px; background: #FFF6EC; }
        .fi-psd, .fi-ai { background: #fff; }
        .fi-plp { padding: 5px; background: #EAF6FF; }
        .fi-file { background: linear-gradient(145deg, #129465, #0A5C40); color: #fff; }
        .of-cat-promo { display: flex; align-items: center; gap: 12px; min-width: 0; padding: 12px 14px; border: 1px solid #B6DFC9; border-radius: 10px; background: linear-gradient(135deg, #E7F8EE, #C9EDD9); color: #084C34; transition: box-shadow .18s ease, transform .18s ease; }
        .of-cat-promo:hover { box-shadow: 0 10px 22px rgba(7, 54, 38, .08); transform: translateY(-1px); }
        .of-promo-icon { display: grid; place-items: center; flex: none; width: 48px; height: 48px; border-radius: 11px; background: var(--of-green); color: #fff; box-shadow: 0 7px 14px rgba(7, 54, 38, .18); }
        .of-cat-promo strong { display: block; font-size: .84rem; font-weight: 700; line-height: 1.45; }
        .of-cat-promo small { display: block; margin-top: 2px; color: #37735B; font-size: .7rem; font-weight: 600; }

        /* Layout */
        .of-layout { display: grid; grid-template-columns: minmax(0, 1fr) 286px; align-items: start; gap: 24px; padding-block: 34px 10px; }
        .of-main { min-width: 0; }
        .of-section-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 18px; margin-bottom: 15px; }
        .of-section-title { display: flex; align-items: center; gap: 10px; font-size: 1.42rem; font-weight: 800; letter-spacing: -.02em; color: #0D2A20; }
        .of-title-icon { display: grid; place-items: center; flex: none; width: 33px; height: 33px; border-radius: 8px; background: var(--of-green); color: #fff; }
        .of-section-title .of-icon { width: 17px; height: 17px; }
        .of-section-sub { margin-top: 5px; color: var(--of-muted); font-size: .85rem; }
        .of-section-sub strong { color: var(--of-ink); }
        .of-show-all { display: inline-flex; align-items: center; gap: 5px; margin-top: 8px; color: var(--of-green); font-size: .84rem; font-weight: 700; white-space: nowrap; }
        .of-show-all:hover { color: var(--of-green-dark); }
        .of-filters { display: flex; flex-wrap: wrap; gap: 7px; margin-bottom: 16px; }
        .of-filter { padding: 6px 13px; border: 1px solid var(--of-line); border-radius: 999px; background: #fff; color: var(--of-muted); font-size: .78rem; font-weight: 600; cursor: pointer; transition: background .16s ease, color .16s ease, border-color .16s ease; }
        .of-filter:hover { border-color: var(--of-line-strong); color: var(--of-ink); }
        .of-filter.is-active { background: var(--of-green); border-color: var(--of-green); color: #fff; }

        /* File cards */
        .of-file-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 13px; }
        .of-file-card { display: flex; flex-direction: column; min-width: 0; overflow: hidden; border: 1px solid var(--of-line); border-radius: 11px; background: var(--of-card); transition: border-color .20s ease, box-shadow .20s ease, transform .20s ease; }
        .of-file-card:hover { border-color: #B7D5C4; box-shadow: 0 16px 30px rgba(7, 54, 38, .09); transform: translateY(-3px); }
        .of-file-card[hidden] { display: none; }
        .of-file-thumb { position: relative; aspect-ratio: 4 / 3; overflow: hidden; background: #F1F7F3; border-bottom: 1px solid #EAF1EC; }
        .of-thumb-img { width: 100%; height: 100%; object-fit: cover; transition: transform .35s ease; }
        .of-file-card:hover .of-thumb-img { transform: scale(1.035); }
        .of-file-badge { position: absolute; top: 9px; inset-inline-start: 9px; z-index: 2; padding: 4px 9px; border-radius: 5px; color: #fff; font-size: .62rem; font-weight: 700; letter-spacing: .02em; box-shadow: 0 4px 10px rgba(0, 0, 0, .16); }
        .badge-office { background: #0B6B4A; }
        .badge-psd { background: #1D4ED8; }
        .badge-ai { background: #D97706; }
        .badge-plp { background: #7C3AED; }
        .of-new { position: absolute; top: 9px; inset-inline-end: 9px; z-index: 2; padding: 3px 7px; border-radius: 5px; background: var(--of-gold); color: #3A2A00; font-size: .58rem; font-weight: 800; }
        .of-file-body { display: flex; flex: 1; flex-direction: column; padding: 11px 12px 0; }
        .of-file-name { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; min-height: 2.7em; margin: 0; color: #10261C; font-size: .89rem; font-weight: 700; line-height: 1.36; overflow-wrap: anywhere; }
        .of-file-desc { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; margin: 5px 0 0; color: var(--of-muted); font-size: .755rem; line-height: 1.58; overflow-wrap: anywhere; }
        .of-file-meta { display: flex; flex-wrap: wrap; align-items: center; gap: 4px 7px; margin-top: auto; padding: 9px 0 10px; color: #75877D; font-size: .67rem; }
        .of-ext { padding: 2px 5px; border: 1px solid #D7E4DB; border-radius: 4px; color: #365648; font-family: var(--font-mono); font-size: .6rem; font-weight: 700; text-transform: uppercase; }
        .of-meta-dot { color: #B4C3BA; }
        .of-file-foot { padding: 0 12px 12px; }
        .of-download { display: flex; align-items: center; justify-content: center; gap: 6px; width: 100%; min-height: 38px; padding: 8px 10px; border: 1px solid var(--of-green); border-radius: 6px; background: var(--of-green); color: #fff; font-size: .78rem; font-weight: 700; transition: background .16s ease, border-color .16s ease, color .16s ease; }
        .of-download:hover { background: var(--of-green-dark); border-color: var(--of-green-dark); }
        .of-download.is-locked { background: #fff; color: var(--of-green); }
        .of-download.is-locked:hover { background: var(--of-green-soft); }
        .of-download .of-icon { width: 15px; height: 15px; }

        /* Generated previews */
        .of-art { position: absolute; inset: 0; display: grid; place-items: center; padding: 18px; }
        .of-art::before { content: ""; position: absolute; inset: 0; opacity: .6; background-image: radial-gradient(#B6D1C2 1px, transparent 1px); background-size: 12px 12px; }
        .art-word { background: linear-gradient(145deg, #EEF4FE, #DCE9FA); }
        .art-excel { background: linear-gradient(145deg, #EDF8F2, #D9EEE2); }
        .art-ppt { background: linear-gradient(145deg, #FDF0EB, #F7DFD6); }
        .art-psd { background: linear-gradient(145deg, #EDF3FB, #DCE7F6); }
        .art-ai { background: linear-gradient(145deg, #FBF4E8, #F3E4CB); }
        .art-plp { background: linear-gradient(145deg, #F5F0FE, #E9DEFB); }
        .oc-paper { position: relative; z-index: 1; width: 54%; aspect-ratio: 3 / 4; padding: 12% 9%; border-radius: 4px; background: #fff; box-shadow: 0 10px 20px rgba(22, 48, 36, .16); transform: rotate(-4deg); border-top: 4px solid var(--paper-accent, #2B579A); }
        .paper-word { --paper-accent: #2B579A; }
        .paper-excel { --paper-accent: #217346; padding: 13% 8%; }
        .paper-ppt { --paper-accent: #C43E1C; padding: 12% 8%; }
        .oc-chip { position: absolute; top: -8px; right: -8px; z-index: 2; width: 24px; height: 24px; padding: 1px; border-radius: 5px; background: #fff; object-fit: contain; box-shadow: 0 3px 7px rgba(0, 0, 0, .18); }
        .oc-brand-stamp { position: absolute; top: 12px; right: 12px; z-index: 2; width: 38px; height: 38px; padding: 3px; border-radius: 9px; background: #fff; object-fit: contain; box-shadow: 0 6px 14px rgba(7, 54, 38, .18); }
        .oc-line { display: block; height: 3px; margin-bottom: 5px; border-radius: 99px; background: #DCE6E1; }
        .oc-line:nth-of-type(even) { background: #E9F0EC; }
        .oc-sheet-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 2px; margin-top: 6px; }
        .oc-sheet-grid i { display: block; aspect-ratio: 1.55; border-radius: 1px; background: #D8EBDF; }
        .oc-sheet-grid i:nth-child(3n+2) { background: #EAF4EE; }
        .oc-slide { display: flex; flex-direction: column; gap: 4px; margin-top: 5px; padding: 7px 5px; border: 1px solid #F0D1C6; border-radius: 2px; background: #FDF6F3; }
        .oc-slide-title { width: 55%; height: 4px; border-radius: 99px; background: #D26A4C; }
        .oc-slide-line { width: 88%; height: 2px; border-radius: 99px; background: #E5B7A7; }
        .oc-slide-line.short { width: 62%; }
        .oc-slide-dots { display: flex; gap: 3px; justify-content: center; margin-top: 5px; }
        .oc-slide-dots i { width: 3px; height: 3px; border-radius: 50%; background: #E1B5A6; }
        .oc-visit { position: absolute; z-index: 1; width: 44%; aspect-ratio: 1.72; border-radius: 3px; background: linear-gradient(135deg, #1B3B78, #0E224D); box-shadow: 0 9px 18px rgba(15, 38, 82, .20); }
        .visit-back { top: 27%; left: 19%; transform: rotate(-18deg); background: linear-gradient(135deg, #2B5AA5, #102E66); }
        .visit-front { right: 15%; bottom: 22%; display: flex; flex-direction: column; justify-content: center; padding: 8% 8%; transform: rotate(7deg); color: #fff; }
        .visit-front strong { font-size: .44rem; letter-spacing: .06em; line-height: 1.4; }
        .visit-front small { margin-top: 5px; color: #AFC8EA; font-size: .27rem; letter-spacing: .10em; }
        .oc-brandsheet { position: relative; z-index: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; width: 66%; aspect-ratio: 1.5; padding: 9%; border-radius: 3px; background: #fff; box-shadow: 0 10px 20px rgba(69, 46, 10, .14); }
        .oc-orbit { width: 28px; height: 28px; margin-bottom: 5px; border: 2px solid #1B2B24; border-radius: 50%; position: relative; }
        .oc-orbit::after { content: ""; position: absolute; inset: 4px; border-radius: 50%; background: #1B2B24; }
        .oc-brandsheet strong { color: #183028; font-size: .56rem; line-height: 1.25; letter-spacing: .05em; text-align: center; }
        .oc-brandsheet small { margin-top: 4px; color: #8B9C93; font-size: .27rem; letter-spacing: .16em; }
        .oc-poster { position: relative; z-index: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; width: 58%; aspect-ratio: 3 / 4; padding: 8%; border-radius: 3px; background: linear-gradient(160deg, #0C6F50, #07503C 62%, #E6425E 62%, #B41E39); box-shadow: 0 10px 20px rgba(54, 18, 101, .20); transform: rotate(3deg); }
        .oc-poster-kicker { color: #CFF3DF; font-size: .43rem; font-weight: 700; letter-spacing: .08em; }
        .oc-poster strong { margin-top: 5px; color: #fff; font-size: .74rem; line-height: 1.25; text-align: center; }

        /* Sidebar */
        .of-sidebar { display: flex; flex-direction: column; gap: 14px; }
        .of-side-card { padding: 17px 16px; border: 1px solid var(--of-line); border-radius: 11px; background: #fff; }
        .of-side-title { margin: 0 0 8px; color: #163A2B; font-size: .95rem; font-weight: 800; }
        .of-checks { display: grid; gap: 8px; }
        .of-checks li { display: flex; align-items: flex-start; gap: 8px; color: #3D5549; font-size: .79rem; line-height: 1.55; }
        .of-check { display: grid; place-items: center; flex: none; width: 18px; height: 18px; margin-top: 1px; border-radius: 50%; background: var(--of-green); color: #fff; }
        .of-check .of-icon { width: 10px; height: 10px; stroke-width: 3; }
        .of-side-promo { padding: 24px 18px; border-radius: 11px; background: linear-gradient(155deg, #0D7350 0%, #074E39 100%); color: #fff; text-align: center; box-shadow: 0 12px 24px rgba(7, 54, 38, .14); }
        .of-promo-bolt { display: grid; place-items: center; width: 54px; height: 54px; margin: 0 auto 13px; border: 1px solid rgba(255, 255, 255, .22); border-radius: 50%; background: rgba(255, 255, 255, .12); }
        .of-promo-bolt .of-icon { width: 25px; height: 25px; }
        .of-side-promo h3 { margin: 0 0 15px; color: #fff; font-size: .98rem; font-weight: 700; line-height: 1.6; }
        .of-side-promo a { display: inline-flex; align-items: center; justify-content: center; gap: 7px; min-height: 38px; padding: 8px 18px; border-radius: 6px; background: #fff; color: #084C34; font-size: .8rem; font-weight: 700; }
        .of-side-promo a:hover { background: #EAF7F0; }
        .of-sidecat-list { display: flex; flex-direction: column; }
        .of-sidecat-item { display: flex; align-items: center; gap: 9px; width: 100%; padding: 8px 0; border: 0; border-bottom: 1px solid #EDF2EF; background: transparent; color: #2A4137; font: inherit; font-size: .8rem; font-weight: 600; text-align: start; cursor: pointer; }
        .of-sidecat-item:last-child { border-bottom: 0; padding-bottom: 0; }
        .of-sidecat-item:hover, .of-sidecat-item.is-active { color: var(--of-green); }
        .of-sidecat-item.is-active .of-count { background: var(--of-green); color: #fff; }
        .of-mini { width: 30px; height: 30px; border-radius: 7px; box-shadow: inset 0 0 0 1px rgba(7, 54, 38, .08), 0 3px 7px rgba(7, 54, 38, .12); }
        .of-mini.fi-office { padding: 5px; }
        .of-mini.fi-plp { padding: 2px; }
        .of-count { display: inline-grid; place-items: center; min-width: 28px; height: 21px; margin-inline-start: auto; padding: 0 7px; border-radius: 999px; background: #EAF7F0; color: var(--of-green); font-size: .66rem; font-weight: 700; }
        .of-sidecat-link { margin-inline-start: auto; color: #9BAFA3; }
        .of-sidecat-link .of-icon { width: 14px; height: 14px; }

        /* Empty states */
        .of-empty, .of-noresult { padding: 44px 24px; border: 1px dashed var(--of-line-strong); border-radius: 12px; background: #fff; text-align: center; }
        .of-empty-art { display: flex; justify-content: center; gap: 7px; margin-bottom: 14px; }
        .of-empty-art .of-format-icon { width: 40px; height: 40px; border-radius: 9px; font-size: .72rem; }
        .of-empty h3, .of-noresult strong { display: block; margin: 0 0 5px; color: #163A2B; font-size: 1rem; font-weight: 800; }
        .of-empty p, .of-noresult p { margin: 0; color: var(--of-muted); font-size: .84rem; line-height: 1.7; }
        .of-empty-actions { display: flex; flex-wrap: wrap; justify-content: center; gap: 8px; margin-top: 16px; }

        /* Guide */
        .of-guide { margin-top: 38px; padding-block: 34px 8px; border-top: 1px solid var(--of-line); }
        .of-guide-head { margin-bottom: 16px; }
        .of-guide-kicker { color: var(--of-green); font-size: .72rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
        .of-guide h2 { margin-top: 3px; color: #0D2A20; font-size: 1.32rem; font-weight: 800; }
        .of-steps { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
        .of-step { padding: 18px 17px; border: 1px solid var(--of-line); border-radius: 10px; background: #fff; }
        .of-step-num { display: grid; place-items: center; width: 28px; height: 28px; border-radius: 50%; background: var(--of-green-soft); color: var(--of-green); font-size: .76rem; font-weight: 800; }
        .of-step h3 { margin: 10px 0 5px; color: #163A2B; font-size: .9rem; font-weight: 700; }
        .of-step p { color: var(--of-muted); font-size: .79rem; line-height: 1.65; }
        .of-step a { color: var(--of-green); font-weight: 700; }

        /* Footer */
        .of-footer { margin-top: 34px; padding-top: 34px; background: #064E3B; color: #D6EAE0; }
        .of-footer-main { display: flex; align-items: center; justify-content: space-between; gap: 22px; flex-wrap: wrap; padding-bottom: 26px; }
        .of-footer-brand { display: flex; flex-direction: column; gap: 7px; }
        .of-footer-brand img { height: 42px; width: auto; filter: brightness(0) invert(1); }
        .of-footer-brand span { color: #A9CFBC; font-size: .76rem; }
        .of-footer-nav { display: flex; align-items: center; flex-wrap: wrap; gap: 4px 18px; }
        .of-footer-nav a { color: #D6EAE0; font-size: .8rem; font-weight: 500; }
        .of-footer-nav a:hover { color: #fff; }
        .of-footer-social { display: flex; gap: 8px; }
        .of-footer-social a { display: grid; place-items: center; width: 34px; height: 34px; border-radius: 50%; background: rgba(255, 255, 255, .12); color: #fff; }
        .of-footer-social a:hover { background: rgba(255, 255, 255, .22); }
        .of-footer-social .of-icon { width: 15px; height: 15px; }
        .of-footer-bottom { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; padding-block: 15px; border-top: 1px solid rgba(255, 255, 255, .14); color: #A9CFBC; font-size: .74rem; }

        @media (max-width: 1120px) {
            .of-cat-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
            .of-cat-promo { grid-column: 1 / -1; }
            .of-file-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        }
        @media (max-width: 980px) {
            .of-nav { display: none; position: absolute; inset-inline: 0; top: 100%; z-index: 110; flex-direction: column; align-items: stretch; gap: 2px; margin: 0; padding: 10px 22px 18px; background: #fff; border-bottom: 1px solid var(--of-line); box-shadow: 0 18px 30px rgba(7, 54, 38, .12); }
            .of-nav.is-open { display: flex; }
            .of-nav-link, .of-dropdown > summary { padding: 13px 12px; border-radius: 7px; }
            .of-nav-link.is-active::after, .of-dropdown > summary.is-active::after { display: none; }
            .of-dropdown { width: 100%; }
            .of-dropdown > summary { justify-content: space-between; }
            .of-dropdown-menu { position: static; min-width: 0; margin: 2px 0 7px; padding: 4px 0 4px 12px; border: 0; border-inline-start: 2px solid var(--of-line); border-radius: 0; box-shadow: none; }
            .of-menu-btn { display: inline-flex; }
            .of-hero-inner { grid-template-columns: 1fr; gap: 18px; padding-block: 38px 42px; }
            .of-hero-art { min-height: 220px; justify-content: flex-start; padding-inline-start: 18px; }
            .of-hero-art img { width: min(78%, 420px); }
            .of-hero-note { top: 4px; right: 12%; }
            .of-layout { grid-template-columns: 1fr; }
            .of-sidebar { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .of-side-promo { grid-row: span 2; }
        }
        @media (max-width: 760px) {
            .of-cat-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .of-cat-promo { grid-column: 1 / -1; }
            .of-file-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .of-steps { grid-template-columns: 1fr; }
        }
        @media (max-width: 620px) {
            .of-container { padding-inline: 16px; }
            .of-header-inner { min-height: 64px; gap: 10px; }
            .of-brand img { height: 36px; }
            .of-actions { gap: 4px; }
            .of-btn { min-height: 38px; padding: 8px 13px; font-size: .82rem; }
            .of-icon-btn { width: 38px; height: 38px; }
            .of-user { display: none; }
            .of-hero h1 { font-size: 1.85rem; }
            .of-lead { font-size: .91rem; }
            .of-search { margin-top: 20px; }
            .of-search button { padding-inline: 16px; }
            .of-hero-art { justify-content: center; padding-inline: 0; }
            .of-hero-note { display: none; }
            .of-tags > span { flex-basis: 100%; }
            .of-layout { padding-top: 26px; }
            .of-section-head { align-items: flex-start; }
            .of-section-title { font-size: 1.18rem; }
            .of-file-grid { grid-template-columns: 1fr; }
            .of-file-name { min-height: 0; }
            .of-file-desc { -webkit-line-clamp: 4; }
            .of-sidebar { grid-template-columns: 1fr; }
            .of-side-promo { grid-row: auto; }
            .of-empty, .of-noresult { padding: 32px 18px; }
            .of-footer-main { flex-direction: column; align-items: flex-start; }
        }
        @media (max-width: 420px) {
            .of-cat-grid { grid-template-columns: 1fr; }
            .of-cat-promo { grid-column: auto; }
            .of-signup { display: none; }
        }
        @media (prefers-reduced-motion: reduce) {
            .of-cat, .of-file-card, .of-thumb-img { transition: none; }
            .of-file-card:hover { transform: none; }
        }
    </style>
</head>
<body class="officefiles-page">
    <svg class="of-sprite" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <g id="of-i-search"><circle cx="11" cy="11" r="7"/><line x1="20" y1="20" x2="16.65" y2="16.65"/></g>
            <g id="of-i-chevron"><polyline points="6 9 12 15 18 9"/></g>
            <g id="of-i-arrow"><line x1="4" y1="12" x2="20" y2="12"/><polyline points="14 6 20 12 14 18"/></g>
            <g id="of-i-download"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></g>
            <g id="of-i-lock"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></g>
            <g id="of-i-check"><polyline points="20 6 9 17 4 12"/></g>
            <g id="of-i-file"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="14" y2="17"/></g>
            <g id="of-i-grid"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></g>
            <g id="of-i-menu"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></g>
            <g id="of-i-close"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></g>
            <g id="of-i-bolt"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></g>
            <g id="of-i-facebook"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></g>
            <g id="of-i-youtube"><path d="M22.5 7.2a2.8 2.8 0 0 0-2-2C18.7 4.7 12 4.7 12 4.7s-6.7 0-8.5.5a2.8 2.8 0 0 0-2 2A29 29 0 0 0 1 12a29 29 0 0 0 .5 4.8 2.8 2.8 0 0 0 2 2c1.8.5 8.5.5 8.5.5s6.7 0 8.5-.5a2.8 2.8 0 0 0 2-2A29 29 0 0 0 23 12a29 29 0 0 0-.5-4.8z"/><polygon points="10 15 15 12 10 9"/></g>
            <g id="of-i-send"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></g>
            <g id="of-i-arrow-curve"><path d="M4 4c12 1 18 9 15 22"/><polyline points="13 20 19 27 25 21"/></g>
        </defs>
    </svg>

    <header class="of-header">
        <div class="of-container of-header-inner">
            <a class="of-brand" href="<?= e($HOME) ?>" aria-label="Repdoc — হোম">
                <img src="<?= e(repdoc_url('src/assets/image/logo.png')) ?>" alt="Repdoc" width="126" height="42" decoding="async">
            </a>

            <nav class="of-nav" id="ofNav" aria-label="প্রধান নেভিগেশন">
                <a class="of-nav-link" href="<?= e($HOME) ?>">হোম</a>
                <details class="of-dropdown">
                    <summary class="is-active">সকল ফাইল <svg class="of-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#of-i-chevron"/></svg></summary>
                    <div class="of-dropdown-menu">
                        <a href="#files" data-filter="office">MS Office <span class="of-count"><?= e(officefiles_bn_digits((string) $counts['office'])) ?></span></a>
                        <div class="of-dropdown-sep"></div>
                        <a href="<?= e($OFFLINE_URL) ?>">অফলাইন ফাইল হাব</a>
                        <a href="<?= e($SHOWCASE_URL) ?>">সব টেমপ্লেট শোকেস</a>
                    </div>
                </details>
                <a class="of-nav-link" href="<?= e($HOME) ?>#tools">সার্ভিস সমূহ</a>
                <a class="of-nav-link" href="#guide">ডাউনলোড গাইড</a>
                <a class="of-nav-link" href="<?= e($HOME) ?>#about">আমাদের সম্পর্কে</a>
            </nav>

            <div class="of-actions">
                <button type="button" class="of-icon-btn" id="ofHeaderSearch" aria-label="ফাইল খুঁজুন">
                    <svg class="of-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#of-i-search"/></svg>
                </button>
                <?php if ($currentUser === null): ?>
                    <a class="of-btn of-btn-ghost" href="<?= e($LOGIN_URL) ?>">লগইন</a>
                    <a class="of-btn of-btn-solid of-signup" href="<?= e($APPLY_URL) ?>">সাইন আপ</a>
                <?php else: ?>
                    <span class="of-user"><?= e((string) ($currentUser['fullname'] ?? 'অ্যাকাউন্ট')) ?></span>
                    <a class="of-btn of-btn-solid" href="<?= e($dashUrl) ?>">ড্যাশবোর্ড</a>
                <?php endif; ?>
                <button type="button" class="of-icon-btn of-menu-btn" id="ofMenuToggle" aria-label="মেনু খুলুন" aria-controls="ofNav" aria-expanded="false">
                    <svg class="of-icon of-menu-open" viewBox="0 0 24 24" aria-hidden="true"><use href="#of-i-menu"/></svg>
                    <svg class="of-icon of-menu-close" viewBox="0 0 24 24" aria-hidden="true" style="display:none"><use href="#of-i-close"/></svg>
                </button>
            </div>
        </div>
    </header>

    <main>
        <section class="of-hero" aria-labelledby="ofHeroTitle">
            <div class="of-container of-hero-inner">
                <div>
                    <p class="of-kicker"><i aria-hidden="true"></i> ডকুমেন্ট ও ডিজাইন টেমপ্লেট কালেকশন</p>
                    <h1 id="ofHeroTitle">গুরুত্বপূর্ণ লিংক <span>এক জায়গায়</span></h1>
                    <p class="of-lead">দরকারি ওয়েবসাইট ও সেবার লিংক এক জায়গায় — ক্লিক করে সরাসরি খুলুন।</p>

                    <form class="of-search" id="ofSearchForm" role="search">
                        <svg class="of-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#of-i-search"/></svg>
                        <label class="sr-only" for="ofFileSearch">ফাইলের নাম, ক্যাটাগরি বা কীওয়ার্ড লিখুন</label>
                        <input id="ofFileSearch" type="search" placeholder="ফাইলের নাম, ক্যাটাগরি বা কীওয়ার্ড লিখুন…" autocomplete="off">
                        <button type="submit">খুঁজুন</button>
                    </form>

                </div>

                <div class="of-hero-art">
                    <img src="<?= e($HERO_IMG) ?>" alt="Word, Photoshop, Illustrator ও PixelLab ফাইল সহ Repdoc ফাইল ব্যাগ" width="600" height="378" decoding="async" fetchpriority="high">
                    <span class="of-hero-note">প্রফেশনাল টেমপ্লেট<br>ডাউনলোড করুন
                        <svg class="of-icon" viewBox="0 0 28 32" aria-hidden="true"><use href="#of-i-arrow-curve"/></svg>
                    </span>
                </div>
            </div>
        </section>

        <section class="of-categories" aria-label="ফাইল ক্যাটাগরি">
            <div class="of-container of-cat-grid">
                <button type="button" class="of-cat" data-filter="office">
                    <?= officefiles_logo_icon('office', 'fi-office') ?>
                    <span class="of-cat-text"><strong>MS Office</strong><small>Word, Excel, PowerPoint</small></span>
                    <svg class="of-icon of-chev" viewBox="0 0 24 24" aria-hidden="true"><use href="#of-i-arrow"/></svg>
                </button>
                <button type="button" class="of-cat" data-filter="psd">
                    <?= officefiles_logo_icon('psd', 'fi-psd') ?>
                    <span class="of-cat-text"><strong>PSD Files</strong><small>Photoshop Design</small></span>
                    <svg class="of-icon of-chev" viewBox="0 0 24 24" aria-hidden="true"><use href="#of-i-arrow"/></svg>
                </button>
            </div>
        </section>

        <div class="of-container of-layout">
            <div class="of-main">
                <div class="of-section-head" id="files">
                    <div>
                        <h2 class="of-section-title"><span class="of-title-icon" aria-hidden="true"><svg class="of-icon" viewBox="0 0 24 24"><use href="#of-i-grid"/></svg></span> জনপ্রিয় ফাইল কালেকশন</h2>
                        <p class="of-section-sub">প্রদর্শিত <strong id="ofShowCount"><?= e(officefiles_bn_digits((string) $counts['all'])) ?></strong> টি ফাইল · প্রতিটি ফাইলের বিবরণসহ · মোট <?= e(officefiles_bn_digits(officefiles_human_size((int) $totalBytes))) ?></p>
                    </div>
                    <a class="of-show-all" href="#files" id="ofShowAll">সব দেখুন <svg class="of-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#of-i-arrow"/></svg></a>
                </div>

                <div class="of-filters" role="group" aria-label="ফাইল ফিল্টার">
                    <button type="button" class="of-filter is-active" data-filter="all">সব ফাইল</button>
                    <button type="button" class="of-filter" data-filter="office">MS Office</button>
                </div>

                <?php if (true): ?>
                    <div class="of-file-grid" id="ofFileGrid">
                        <?php foreach ($links as $l): ?>
                            <article class="of-file-card">
                                <div class="of-file-thumb" style="display:grid;place-items:center;background:#EEF3FB">
                                    <span style="font-size:2rem">🔗</span>
                                </div>
                                <div class="of-file-body">
                                    <h3 class="of-file-name"><?= e($l['title'] ?? '') ?></h3>
                                    <p class="of-file-desc"><?= e($l['desc'] ?? '') ?></p>
                                </div>
                                <div class="of-file-foot">
                                    <a class="of-download" href="<?= e($l['url'] ?? '#') ?>" target="_blank" rel="noopener">খুলুন</a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                        <?php if ($links === []): ?>
                            <div class="of-empty" role="status">
                                <h3>কোনো লিংক যুক্ত হয়নি</h3>
                                <p>অ্যাডমিন প্যানেল থেকে লিংক যুক্ত করুন।</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="of-file-grid" id="ofFileGrid">
                        <?php foreach ($files as $f):
                            $meta = $FORMATS[$f['type']];
                            $group = $meta['group'];
                            $isNew = $f['mtime'] > 0 && $f['mtime'] > time() - 14 * 86400;
                            $dl = repdoc_url($meta['hub'] . '/download.php') . '?type=' . rawurlencode($f['type']) . '&file=' . rawurlencode($f['name']);
                            if ($currentUser === null) {
                                $dl = $LOGIN_URL . '?next=' . rawurlencode('/officefiles/');
                            }
                            $badge = $meta['label'];
                        ?>
                            <article class="of-file-card" data-type="<?= e($f['type']) ?>" data-group="<?= e($group) ?>" data-search="<?= e($f['search']) ?>">
                                <div class="of-file-thumb">
                                    <?php if ($f['thumb'] !== ''): ?>
                                        <img class="of-thumb-img" src="<?= e($f['thumb']) ?>" alt="<?= e($f['title']) ?> — প্রিভিউ" width="480" height="360" loading="lazy" decoding="async">
                                    <?php else: ?>
                                        <?= officefiles_preview_art($f['type']) ?>
                                    <?php endif; ?>
                                    <span class="of-file-badge badge-<?= e($group) ?>"><img src="<?= e(officefiles_logo_url($f['type'])) ?>" alt="" width="14" height="14" loading="lazy" decoding="async"></span>
                                    <?php if ($isNew): ?><span class="of-new">নতুন</span><?php endif; ?>
                                </div>
                                <div class="of-file-body">
                                    <h3 class="of-file-name"><?= e($f['title']) ?></h3>
                                    <p class="of-file-desc" title="<?= e($f['description']) ?>"><?= e($f['description']) ?></p>
                                    <div class="of-file-meta">
                                        <span class="of-ext"><?= e(strtoupper($f['ext'])) ?></span>
                                        <span><?= e(officefiles_bn_digits(officefiles_human_size((int) $f['size']))) ?></span>
                                        <span class="of-meta-dot" aria-hidden="true">•</span>
                                        <span><?= e(officefiles_date((int) $f['mtime'])) ?></span>
                                    </div>
                                </div>
                                <div class="of-file-foot">
                                    <a class="of-download<?= $currentUser === null ? ' is-locked' : '' ?>" href="<?= e($dl) ?>"<?= $currentUser === null ? ' title="ডাউনলোড করতে লগ ইন করুন"' : '' ?>>
                                        <svg class="of-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#<?= $currentUser === null ? 'of-i-lock' : 'of-i-download' ?>"/></svg>
                                        <?= $currentUser === null ? 'ডাউনলোড করতে লগইন' : 'ডাউনলোড করুন' ?>
                                    </a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <div class="of-noresult" id="ofNoResult" role="status" hidden>
                        <strong>কোনো ফাইল পাওয়া যায়নি</strong>
                        <p>অন্য নাম, বিবরণ বা ক্যাটাগরি দিয়ে আবার খুঁজুন।</p>
                    </div>
                <?php endif; ?>

                
            </div>

        </div>
    </main>

    <footer class="of-footer">
        <div class="of-container">
            <div class="of-footer-main">
                <div class="of-footer-brand">
                    <a href="<?= e($HOME) ?>" aria-label="Repdoc — হোম"><img src="<?= e(repdoc_url('src/assets/image/logo.png')) ?>" alt="Repdoc" width="126" height="42" loading="lazy" decoding="async"></a>
                    <span>ডিজিটাল সেবা, সবার জন্য সহজ</span>
                </div>
                <nav class="of-footer-nav" aria-label="ফুটার নেভিগেশন">
                    <a href="<?= e($HOME) ?>">হোম</a>
                    <a href="#files">সকল ফাইল</a>
                    <a href="<?= e($HOME) ?>#tools">সার্ভিস সমূহ</a>
                    <a href="#guide">ডাউনলোড গাইড</a>
                    <a href="<?= e($HOME) ?>#about">আমাদের সম্পর্কে</a>
                </nav>
                <div class="of-footer-social" aria-label="সোশ্যাল মিডিয়া">
                    <a href="https://www.facebook.com/Repdocbdofficial" target="_blank" rel="noopener" aria-label="Facebook"><svg class="of-icon" viewBox="0 0 24 24"><use href="#of-i-facebook"/></svg></a>
                    <a href="https://www.youtube.com/@Repdocbdofficial" target="_blank" rel="noopener" aria-label="YouTube"><svg class="of-icon" viewBox="0 0 24 24"><use href="#of-i-youtube"/></svg></a>
                    <a href="https://t.me/Repdocbdofficial" target="_blank" rel="noopener" aria-label="Telegram"><svg class="of-icon" viewBox="0 0 24 24"><use href="#of-i-send"/></svg></a>
                </div>
            </div>
            <div class="of-footer-bottom">
                <span>© <?= date('Y') ?> Repdoc. সর্বস্বত্ব সংরক্ষিত।</span>
                <span>উন্নয়নের পথে ডিজিটাল বাংলাদেশ</span>
            </div>
        </div>
    </footer>

    <script>
    (function () {
        var BN = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
        var bn = function (value) {
            return String(value).replace(/[0-9]/g, function (d) { return BN[+d]; });
        };

        var nav = document.getElementById('ofNav');
        var menuToggle = document.getElementById('ofMenuToggle');
        var openIcon = menuToggle ? menuToggle.querySelector('.of-menu-open') : null;
        var closeIcon = menuToggle ? menuToggle.querySelector('.of-menu-close') : null;

        if (menuToggle && nav) {
            menuToggle.addEventListener('click', function () {
                var open = nav.classList.toggle('is-open');
                menuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                if (openIcon) openIcon.style.display = open ? 'none' : '';
                if (closeIcon) closeIcon.style.display = open ? '' : 'none';
            });
        }

        var dropdown = document.querySelector('.of-dropdown');
        if (dropdown) {
            document.addEventListener('click', function (event) {
                if (!dropdown.contains(event.target)) dropdown.removeAttribute('open');
            });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') dropdown.removeAttribute('open');
            });
        }

        var grid = document.getElementById('ofFileGrid');
        var cards = grid ? Array.prototype.slice.call(grid.querySelectorAll('.of-file-card')) : [];
        var search = document.getElementById('ofFileSearch');
        var searchForm = document.getElementById('ofSearchForm');
        var filterButtons = Array.prototype.slice.call(document.querySelectorAll('[data-filter]'));
        var showCount = document.getElementById('ofShowCount');
        var noResult = document.getElementById('ofNoResult');
        var showAll = document.getElementById('ofShowAll');
        var activeFilter = 'all';

        function activeStates() {
            filterButtons.forEach(function (button) {
                var match = (button.getAttribute('data-filter') || 'all') === activeFilter;
                if (button.classList.contains('of-filter') || button.classList.contains('of-cat') || button.classList.contains('of-sidecat-item')) {
                    button.classList.toggle('is-active', match);
                }
            });
        }

        function apply(shouldScroll) {
            var query = (search ? search.value : '').trim().toLowerCase();
            var shown = 0;

            cards.forEach(function (card) {
                var group = card.getAttribute('data-group') || '';
                var haystack = (card.getAttribute('data-search') || '').toLowerCase();
                var matchGroup = activeFilter === 'all' || group === activeFilter;
                var matchQuery = query === '' || haystack.indexOf(query) !== -1;
                var show = matchGroup && matchQuery;
                card.hidden = !show;
                if (show) shown++;
            });

            if (showCount) showCount.textContent = bn(shown);
            if (noResult) noResult.hidden = !(cards.length > 0 && shown === 0);
            activeStates();

            if (shouldScroll) {
                var files = document.getElementById('files');
                if (files) files.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        filterButtons.forEach(function (button) {
            button.addEventListener('click', function (event) {
                if (button.tagName === 'A' && button.getAttribute('href') === '#files') {
                    event.preventDefault();
                }
                activeFilter = button.getAttribute('data-filter') || 'all';
                if (dropdown) dropdown.removeAttribute('open');
                if (nav) nav.classList.remove('is-open');
                if (menuToggle) {
                    menuToggle.setAttribute('aria-expanded', 'false');
                    if (openIcon) openIcon.style.display = '';
                    if (closeIcon) closeIcon.style.display = 'none';
                }
                apply(true);
            });
        });

        if (searchForm && search) {
            searchForm.addEventListener('submit', function (event) {
                event.preventDefault();
                apply(true);
            });
        }

        if (search) {
            search.addEventListener('input', function () { apply(false); });
        }

        document.querySelectorAll('.of-tag').forEach(function (tag) {
            tag.addEventListener('click', function () {
                if (!search) return;
                search.value = tag.getAttribute('data-q') || '';
                activeFilter = 'all';
                apply(true);
            });
        });

        if (showAll) {
            showAll.addEventListener('click', function (event) {
                event.preventDefault();
                activeFilter = 'all';
                if (search) search.value = '';
                apply(true);
            });
        }

        var headerSearch = document.getElementById('ofHeaderSearch');
        if (headerSearch && search) {
            headerSearch.addEventListener('click', function () {
                search.scrollIntoView({ behavior: 'smooth', block: 'center' });
                window.setTimeout(function () { search.focus(); }, 220);
            });
        }

        apply(false);
    })();
    </script>
</body>
</html>
