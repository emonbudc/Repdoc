<?php
declare(strict_types=1);

/**
 * Repdoc — public homepage (repdoc.freedev.app)
 *
 * Why a PHP registry instead of hard-coded HTML: every card maps to a real
 * folder in this repo, so adding a tool later is a one-line array entry and
 * the search/category filters pick it up automatically. No build step, no
 * dependencies — safe for the InfinityFree host.
 *
 * Design system: design-system/repdoc/MASTER.md
 */

// Central auth + wallet (also starts the session). Every tool uses the same include.
require_once __DIR__ . '/src/bootstrap.php';
require_once __DIR__ . '/src/seo.php';

$BRAND      = 'Repdoc';
$BRAND_FULL = 'Repdoc — Free Document Templates';
$DOMAIN     = 'repdoc.freedev.app';
$LOGIN_URL  = repdoc_url('public/login/');
$APPLY_URL  = repdoc_url('Entrepreneur/apply/');
$LOGO       = 'src/assets/image/logo.png';
$FAVICON    = 'public/favicon.png';

$currentUser = current_user();
$userBalance = $currentUser !== null ? user_balance($currentUser['id']) : 0;

/** Category groups. `icon` refers to a symbol in the inline SVG sprite below. */
$categories = [
    'documents'    => ['label' => 'ডকুমেন্টস',      'icon' => 'i-grid'],
    'templates'    => ['label' => 'টেমপ্লেট',       'icon' => 'i-layout'],
    'certificates' => ['label' => 'সার্টিফিকেট',    'icon' => 'i-award'],
    'software'     => ['label' => 'সফটওয়্যার',     'icon' => 'i-download'],
];

/**
 * Tool registry — single source of truth for the homepage.
 * Only legal, template-based tools are listed here: this site is a document
 * generator from templates, offering free templates and forms as Word
 * docs and PDF. Government-associated documents are never listed.
 * url:    relative path from the site root (folder or entry file that exists).
 * desc:   short English description, or '' when the folder is not yet documented.
 * status: 'live' for a working entry point; 'folder' for a real folder whose
 *         entry file is not present in this repo.
 */
$tools = [
    [
        'id' => 'showcase',
        'cat' => 'templates',
        'icon' => 'i-layout',
        'name' => 'টেমপ্লেট শোকেস',
        'desc' => 'ফ্রি টেমপ্লেট ও ফরম — Word ও PDF আকারে।',
        'url' => 'showcase/',
        'featured' => true,
        'status' => 'folder',
        'badge' => '',
        'keywords' => 'template showcase word pdf psd free forms ms word download টেমপ্লেট',
    ],
    [
        'id' => 'cvmaker',
        'cat' => 'documents',
        'icon' => 'i-user',
        'name' => 'সিভি মেকার',
        'desc' => 'ফ্রি টেমপ্লেট থেকে সিভি বানিয়ে ডাউনলোড করুন।',
        'url' => 'genaretor/cvmaker/',
        'featured' => false,
        'status' => 'folder',
        'badge' => '',
        'keywords' => 'CV resume maker template word pdf free form download সিভি',
    ],
    [
        'id' => 'prottoyon',
        'cat' => 'certificates',
        'icon' => 'i-award',
        'name' => 'প্রত্যয়ন টেমপ্লেট',
        'desc' => '২৩ ধরনের প্রত্যয়ন সনদ — তথ্য দিয়ে প্রিন্ট বা PDF সংরক্ষণ করুন।',
        'url' => 'genaretor/prottoyon/',
        'featured' => false,
        'status' => 'folder',
        'badge' => 'জনপ্রিয়',
        'keywords' => 'prottoyon attestation certificate sonod print pdf প্রত্যয়ন সনদ',
    ],
    [
        'id' => 'certificate',
        'cat' => 'certificates',
        'icon' => 'i-file',
        'name' => 'সনদ টেমপ্লেট',
        'desc' => 'সনদ টেমপ্লেট ও ফরম — Word ও PDF আকারে।',
        'url' => 'genaretor/certificate/',
        'featured' => false,
        'status' => 'folder',
        'badge' => 'জনপ্রিয়',
        'keywords' => 'sonod certificate template word pdf free form download সনদ',
    ],
    [
        'id' => 'offlinefiles',
        'cat' => 'templates',
        'icon' => 'i-file',
        'name' => 'PSD / AI / PLP টেমপ্লেট',
        'desc' => 'PSD, AI ও PLP ফাইল — ডাউনলোড করুন।',
        'url' => 'offlinefiles/',
        'featured' => false,
        'status' => 'folder',
        'badge' => 'জনপ্রিয়',
        'keywords' => 'psd ai plp photoshop illustrator pixellab offline files download পিএসডি টেমপ্লেট',
    ],
    [
        'id' => 'officefiles',
        'cat' => 'templates',
        'icon' => 'i-grid',
        'name' => 'Word / Excel টেমপ্লেট',
        'desc' => 'Word ও Excel ফাইল — ডাউনলোড করুন।',
        'url' => 'officefiles/',
        'featured' => false,
        'status' => 'folder',
        'badge' => '',
        'keywords' => 'word excel doc docx xls xlsx office files download ওয়ার্ড এক্সেল টেমপ্লেট ফরম',
    ],
    [
        'id' => 'software',
        'cat' => 'software',
        'icon' => 'i-download',
        'name' => 'সফটওয়্যার ডাউনলোড',
        'desc' => 'Windows, Android, macOS ও Linux-এর সফটওয়্যার — লগ ইন ছাড়াই।',
        'url' => 'software/',
        'featured' => false,
        'status' => 'folder',
        'badge' => 'নতুন',
        'keywords' => 'software download windows android apk macos linux installer সফটওয়্যার ডাউনলোড',
    ],
    [
        'id' => 'importantlinks',
        'cat' => 'tools',
        'icon' => 'i-grid',
        'name' => 'গুরুত্বপূর্ণ লিংক',
        'desc' => 'দরকারি ওয়েবসাইট ও সেবার লিংক এক জায়গায়।',
        'url' => 'importantlinks/',
        'featured' => false,
        'status' => 'folder',
        'badge' => 'নতুন',
        'keywords' => 'important links urls websites services গুরুত্বপূর্ণ লিংক',
    ],
];

/** Trust strip — formats catalog + one account. */
$trust = [
    ['icon' => 'i-check', 'title' => 'সব ফরম্যাট এক জায়গায়', 'text' => 'PDF, Word, Excel, PSD, AI, PLP — রেডি-টু-ইউজ থেকে প্রিন্ট-রেডি, সব টেমপ্লেট।'],
    ['icon' => 'i-file',   'title' => 'এক অ্যাকাউন্টেই সব',   'text' => 'একটি লগইনেই সব টুল, সব টেমপ্লেট, সব ফরম্যাট আনলক।'],
    ['icon' => 'i-mobile', 'title' => 'যেকোনো ডিভাইসে', 'text' => 'ফোন, ট্যাবলেট ও ডেস্কটপ — যেখান থেকে খুশি কাজ করুন।'],
];

$featured = array_values(array_filter($tools, static fn(array $t): bool => !empty($t['featured'])));

/** Lower-cased haystack used by the client-side filter. */
function searchIndex(array $t, array $categories): string
{
    $cat = $categories[$t['cat']]['label'] ?? '';
    return mb_strtolower(trim($t['name'] . ' ' . ($t['desc'] ?? '') . ' ' . $cat . ' ' . ($t['keywords'] ?? '')), 'UTF-8');
}

/** Display digits in Bengali (serials only; balances stay tabular Arabic). */
function bn_digits(string $v): string
{
    return strtr($v, ['0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪', '5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯']);
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($BRAND) ?> — ফ্রি ডকুমেন্ট টেমপ্লেট ও ফরম (Word, Excel, PDF, PSD, AI, PLP)</title>
    <meta name="description" content="<?= e($BRAND) ?> — বাংলাদেশের কম্পিউটার অপারেটরদের জন্য ফ্রি ডকুমেন্ট টুলকিট। ফ্রি টেমপ্লেট ও ফরম Word, Excel, PDF, PSD, AI, PLP আকারে: সিভি (CV), প্রত্যয়ন, অফলাইন ফাইল।">
    <meta name="theme-color" content="#FFFEFB">
    <meta name="google-site-verification" content="WvNaraAzQXGqMwigxVnsnBnU_gDpyansRX9wSDeM4gU">
    <?php
    $SEO_TITLE = $BRAND . ' — ফ্রি ডকুমেন্ট টেমপ্লেট ও ফরম (Word, Excel, PDF, PSD, AI, PLP)';
    $SEO_DESC  = $BRAND . ' — বাংলাদেশের কম্পিউটার অপারেটরদের জন্য ফ্রি ডকুমেন্ট টুলকিট। ফ্রি টেমপ্লেট ও ফরম Word, Excel, PDF, PSD, AI, PLP আকারে: সিভি (CV), প্রত্যয়ন, অফলাইন ফাইল।';
    repdoc_seo_head([
        'title'       => $SEO_TITLE,
        'description' => $SEO_DESC,
        'canonical'   => repdoc_canonical(),
        'type'        => 'website',
        'image'       => repdoc_canonical('src/assets/image/logo.png'),
        'imageWidth'  => 480,
        'imageHeight' => 160,
        'imageAlt'    => 'Repdoc logo',
        'locale'      => 'bn_BD',
    ]);
    ?>
    <link rel="icon" type="image/png" href="<?= e($FAVICON) ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="image" href="src/assets/image/hero.webp" fetchpriority="high">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Bengali:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="src/assets/css/tokens.css?v=2">
    <link rel="stylesheet" href="src/assets/css/shell.css?v=2">
    <link rel="stylesheet" href="src/assets/css/home.css?v=4">

    <!-- Homepage renders one look only (no theme switching here). -->
    <?php
    repdoc_jsonld([
        '@type'        => 'Organization',
        '@id'          => repdoc_canonical() . '#org',
        'name'         => 'Repdoc',
        'url'          => repdoc_canonical(),
        'logo'         => repdoc_canonical('src/assets/image/logo.png'),
        'description'  => 'যেকোনো কম্পিউটার অপারেটরের জন্য ফ্রি ডকুমেন্ট-টেমপ্লেট টুলকিট।',
        'areaServed'   => ['@type' => 'Country', 'name' => 'Bangladesh'],
        'audience'     => ['@type' => 'Audience', 'audienceType' => 'Computer operators with a business or job, Bangladesh'],
        'sameAs'       => [
            'https://github.com/Repdoc-BD',
            'https://t.me/Repdocbdofficial',
            'https://www.youtube.com/@Repdocbdofficial',
            'https://x.com/Repdocbdofficial',
            'https://www.facebook.com/Repdocbdofficial',
        ],
    ]);
    repdoc_jsonld([
        '@type'        => 'WebSite',
        '@id'          => repdoc_canonical() . '#website',
        'name'         => 'Repdoc',
        'url'          => repdoc_canonical(),
        'publisher'    => ['@id' => repdoc_canonical() . '#org'],
        'inLanguage'   => ['bn'],
        'description'  => $SEO_DESC,
    ]);
    repdoc_jsonld([
        '@type'              => 'WebApplication',
        '@id'                => repdoc_canonical() . '#app',
        'name'               => 'Repdoc',
        'url'                => repdoc_canonical(),
        'applicationCategory' => 'BusinessApplication',
        'operatingSystem'    => 'Web',
        'browserRequirements' => 'Requires a web browser',
        'inLanguage'         => 'bn',
        'description'        => $SEO_DESC,
        'publisher'          => ['@id' => repdoc_canonical() . '#org'],
    ]);
    ?>
</head>
<body>
    <a class="skip-link" href="#tools">টুলসে যান</a>

    <!-- Inline SVG sprite: outline icons only, no emoji, no icon-font dependency. -->
    <svg class="sprite" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <g id="i-search"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></g>
            <g id="i-file"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></g>
            <g id="i-id"><rect x="2" y="4" width="20" height="16" rx="2"/><circle cx="8.5" cy="10" r="2"/><path d="M5 16.5a3.6 3.6 0 0 1 7 0"/><line x1="14.5" y1="9.5" x2="19" y2="9.5"/><line x1="14.5" y1="13.5" x2="19" y2="13.5"/></g>
            <g id="i-award"><circle cx="12" cy="8" r="6"/><path d="M8.21 13.89 7 23l5-3 5 3-1.21-9.11"/></g>
            <g id="i-user"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></g>
            <g id="i-grid"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></g>
            <g id="i-layout"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></g>
            <g id="i-download"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></g>
            <g id="i-arrow"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></g>
            <g id="i-check"><polyline points="20 6 9 17 4 12"/></g>
            <g id="i-sun"><circle cx="12" cy="12" r="4"/><line x1="12" y1="1.5" x2="12" y2="4"/><line x1="12" y1="20" x2="12" y2="22.5"/><line x1="3.5" y1="12" x2="6" y2="12"/><line x1="18" y1="12" x2="20.5" y2="12"/><line x1="5.6" y1="5.6" x2="7.4" y2="7.4"/><line x1="16.6" y1="16.6" x2="18.4" y2="18.4"/><line x1="5.6" y1="18.4" x2="7.4" y2="16.6"/><line x1="16.6" y1="7.4" x2="18.4" y2="5.6"/></g>
            <g id="i-moon"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></g>
            <g id="i-menu"><line x1="4" y1="7" x2="20" y2="7"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="17" x2="20" y2="17"/></g>
            <g id="i-x"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></g>
            <g id="i-shield"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></g>
            <g id="i-bolt"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></g>
            <g id="i-mobile"><rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></g>
            <g id="i-login">            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></g>
            <g id="i-wallet"><path d="M3 7.5A2.5 2.5 0 0 1 5.5 5h11A2.5 2.5 0 0 1 19 7.5"/><rect x="3" y="7.5" width="18" height="12" rx="2"/><circle cx="16.5" cy="13.5" r="1.2"/></g>
            <g id="i-external"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></g>
            <g id="i-link"><path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"/><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/></g>
            <g id="i-send"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4z"/></g>
        </defs>
    </svg>

    <header class="site-header" id="top">
        <div class="container header-inner">
            <a class="brand brand-ledger" href="#top" aria-label="Repdoc — হোম">
                <img class="brand-logo" src="<?= e($LOGO) ?>" alt="" width="120" height="40" decoding="async">
                <span class="brand-word"><strong>Repdoc</strong><small><?= e($DOMAIN) ?> · অপারেটর টুলকিট</small></span>
            </a>

            <nav class="site-nav" id="siteNav" aria-label="প্রধান নেভিগেশন">
                <a class="nav-link" href="#tools">টুলস</a>
                <a class="nav-link" href="#featured">জনপ্রিয়</a>
                <a class="nav-link" href="#how">কীভাবে কাজ করে</a>
                <a class="nav-link" href="<?= e(repdoc_url('about/')) ?>">সম্পর্কে</a>
            </nav>

            <div class="header-actions">
                <?php if ($currentUser !== null): ?>
                    <span class="user-chip" title="ব্যালেন্স">
                        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-wallet"/></svg>
                        <span class="balance"><?= (int) $userBalance ?></span>
                        <span class="sr-only">ক্রেডিট ব্যালেন্স</span>
                    </span>
                    <?php $dashUrl = repdoc_panel_home(); ?>
                    <?php if ($dashUrl !== repdoc_url('/')): ?>
                        <a class="btn btn-primary btn-login" href="<?= e($dashUrl) ?>" aria-label="ড্যাশবোর্ডে যান">
                            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-external"/></svg>
                            <span>ড্যাশবোর্ড</span>
                        </a>
                    <?php endif; ?>
                <?php else: ?>
                    <a class="btn btn-primary btn-login" href="<?= e($LOGIN_URL) ?>" aria-label="লগ ইন">
                        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-login"/></svg>
                        <span>লগ ইন</span>
                    </a>
                <?php endif; ?>
                <button type="button" class="icon-btn nav-toggle" id="navToggle"
                        aria-label="মেনু খুলুন" aria-controls="siteNav" aria-expanded="false">
                    <svg class="icon nav-toggle-open" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-menu"/></svg>
                    <svg class="icon nav-toggle-close" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-x"/></svg>
                </button>
            </div>
        </div>
    </header>

    <main>
        <!-- ── Hero: counter glass (Bangla, Comp code-led) ── -->
        <section class="hero hero-ledger" aria-labelledby="heroHeading">
            <div class="container hero-ledger-inner">
                <p class="reg-kicker"><span class="reg-no" aria-hidden="true">Psd</span>ঘষামাজার দিন শেষ!</p>
                <div class="reg-hero-main">
                    <div class="reg-hero-title">
                        <h1 class="reg-title" id="heroHeading">যেকোন ডকুমেন্ট তৈরি করুন
                            <span class="reg-title-accent">ফরম পূরণ করে!</span>
                        </h1>
                        <span class="reg-rule" aria-hidden="true"></span>
                    </div>
                    <figure class="reg-hero-art">
                        <img class="illus hero-photo" src="src/assets/image/hero.webp" alt="" width="900" height="600" decoding="async" fetchpriority="high">
                    </figure>
                </div>
                <div class="hero-cta reg-cta">
                    <a class="btn btn-ledger" href="#tools">
                        <span>টুলস দেখুন</span>
                    </a>
                    <a class="btn btn-ledger-outline" href="<?= e(repdoc_url('Entrepreneur/apply/')) ?>">
                        <span>ফ্রি অ্যাকাউন্টের জন্য আবেদন করুন</span>
                        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-arrow"/></svg>
                    </a>
                </div>
                <p class="hero-sub reg-sub">
                    রেডি টেমপ্লেট থেকে প্রত্যয়ন ও দরকারি সব ডকুমেন্ট,
                    Word, Excel, PDF, PSD, AI ও PLP আকারে।
                </p>
                <?php if ($currentUser === null): ?>
                    <p class="hero-note reg-note">
                        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-shield"/></svg>
                        সব টুল ব্যবহার করতে <a href="<?= e($LOGIN_URL) ?>">লগ ইন</a> করুন।
                    </p>
                <?php endif; ?>
            </div>
        </section>

        <!-- ── Categorized tool cards ── -->
        <section class="section tools-section" id="tools" aria-labelledby="toolsHeading">
            <div class="container">
                <div class="section-head">
                    <div>
                        <p class="kicker">কাজ যেন সহজ, সময় যেন সাশ্রয়</p>
                        <h2 id="toolsHeading">সব টুলস</h2>
                        <p class="section-sub">দৈনন্দিন কাজে প্রয়োজনীয় সব ডিজিটাল টুল এক জায়গায় — ফিল্টার করতে ক্যাটাগরি বেছে নিন।</p>
                    </div>
                </div>

                <div class="category-chips" id="categoryChips" role="group" aria-label="টুল ক্যাটাগরি">
                    <button type="button" class="chip chip-cat is-active" data-cat="all" aria-pressed="true">
                        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-grid"/></svg>
                        সব
                    </button>
                    <?php foreach ($categories as $key => $cat): ?>
                        <button type="button" class="chip chip-cat" data-cat="<?= e($key) ?>" aria-pressed="false">
                            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#<?= e($cat['icon']) ?>"/></svg>
                            <?= e($cat['label']) ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <div class="dir-grid">
                    <div class="dir-main">
                <div class="tool-grid" id="toolGrid" role="list">
                    <?php foreach ($tools as $i => $t):
                        $badge = trim((string) ($t['badge'] ?? ''));
                    ?>
                        <a class="tool-card tool-tile" href="<?= e($t['url']) ?>"
                           role="listitem"
                           data-cat="<?= e($t['cat']) ?>"
                           data-search="<?= e(searchIndex($t, $categories)) ?>">
                            <span class="tool-tile-icon" aria-hidden="true">
                                <svg class="icon" viewBox="0 0 24 24"><use href="#<?= e($t['icon']) ?>"/></svg>
                            </span>
                            <span class="tool-tile-body">
                                <span class="tool-tile-head">
                                    <span class="tool-name"><?= e($t['name']) ?></span>
                                    <?php if ($badge !== ''): ?>
                                        <span class="tool-badge"><?= e($badge) ?></span>
                                    <?php endif; ?>
                                </span>
                                <?php if ($t['desc'] !== ''): ?>
                                    <span class="tool-desc"><?= e($t['desc']) ?></span>
                                <?php endif; ?>
                            </span>
                            <span class="tool-go" aria-hidden="true">
                                <svg class="icon" viewBox="0 0 24 24"><use href="#i-arrow"/></svg>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Empty state (ux:no-results): guidance + reset, never a blank grid. -->
                <div class="empty-state" id="emptyState" role="status" hidden>
                    <img class="illus empty-illus empty-photo" src="src/assets/image/boyfromwhole.png" alt="" width="500" height="500" loading="lazy" decoding="async">
                    <p class="empty-title">কোনো টুল পাওয়া যায়নি</p>
                    <p class="empty-text" id="emptyHint">অন্য ক্যাটাগরি চেষ্টা করুন।</p>
                    <button type="button" class="btn btn-secondary" id="resetSearch">সব টুল দেখুন</button>
                </div>
                    </div><!-- /.dir-main -->

                    <aside class="dir-rail" aria-label="জনপ্রিয় টুলস">
                        <div class="rail-block" id="featured" aria-labelledby="featuredHeading">
                            <p class="featured-eyebrow">
                                <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><polygon points="12 2.6 14.9 8.6 21.4 9.5 16.7 14.1 17.8 20.6 12 17.5 6.2 20.6 7.3 14.1 2.6 9.5 9.1 8.6"/></svg>
                                বিশেষ
                            </p>
                            <h2 class="rail-title" id="featuredHeading">জনপ্রিয় টুলস</h2>
                            <?php foreach ($featured as $i => $t): ?>
                                <article class="featured-card featured-slip">
                                    <p><?= e($t['desc']) ?></p>
                                    <div class="featured-art" aria-hidden="true">
                                        <img src="src/assets/image/tools-featured.webp" alt="" width="385" height="251" loading="lazy" decoding="async">
                                    </div>
                                    <a class="btn btn-primary" href="<?= e($t['url']) ?>" aria-label="<?= e($t['name']) ?> খুলুন">
                                        <span aria-hidden="true">খুলুন</span>
                                        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-arrow"/></svg>
                                    </a>
                                </article>
                            <?php endforeach; ?>
                        </div>

                    </aside>
                </div>
            </div>
        </section>

        <!-- ── Process: illustrated steps ── -->
        <section class="section how-section" id="how" aria-labelledby="howHeading">
            <div class="container">
                <div class="section-head">
                    <div>
                        <p class="kicker">প্রক্রিয়া</p>
                        <h2 id="howHeading">কীভাবে কাজ করে</h2>
                    </div>
                </div>
                <ol class="how-grid">
                    <li class="how-card">
                        <figure class="how-figure">
                            <img src="src/assets/image/step1.webp" alt="ফোন থেকে ডকুমেন্ট টেমপ্লেট বেছে নিচ্ছেন একজন অপারেটর" width="675" height="567" loading="lazy" decoding="async">
                        </figure>
                        <span class="step-num" aria-hidden="true">১</span>
                        <h3>টুল বেছে নিন</h3>
                        <p>দরকারি ডকুমেন্টস টুল খুলুন।</p>
                    </li>
                    <li class="how-card">
                        <figure class="how-figure">
                            <img src="src/assets/image/step2.webp" alt="ফরমে তথ্য পূরণ ও যাচাইয়ের ধাপ" width="760" height="1266" loading="lazy" decoding="async">
                        </figure>
                        <span class="step-num" aria-hidden="true">২</span>
                        <h3>তথ্য দিন</h3>
                        <p>ফরমে আপনার তথ্য পূরণ করুন।</p>
                    </li>
                    <li class="how-card">
                        <figure class="how-figure">
                            <img src="src/assets/image/step3.webp" alt="প্রিন্টার ও ল্যাপটপে Word ও PDF ডকুমেন্ট তৈরি" width="760" height="1026" loading="lazy" decoding="async">
                        </figure>
                        <span class="step-num" aria-hidden="true">৩</span>
                        <h3>ডাউনলোড করুন</h3>
                        <p>Word ও PDF আকারে ফাইল নিন — প্রিন্ট বা ডাউনলোড করুন।</p>
                    </li>
                </ol>
            </div>
        </section>

        <!-- ── Trust strip ── -->
        <section class="section" aria-labelledby="trustHeading">
            <div class="container">
                <div class="section-head">
                    <div>
                        <p class="kicker">আস্থা</p>
                        <h2 id="trustHeading">কেন Repdoc</h2>
                    </div>
                </div>
                <figure class="why-banner">
                    <img src="src/assets/image/why-us.webp" alt="PDF, Word, Excel, PSD, AI ও PLP ফরম্যাট এবং Repdoc-এর মোবাইল ও ডেস্কটপ ইন্টারফেস" width="1264" height="519" loading="lazy" decoding="async">
                </figure>
                <ul class="trust-strip">
                    <?php foreach ($trust as $item): ?>
                        <li class="trust-tile">
                            <span class="trust-icon" aria-hidden="true">
                                <svg class="icon" viewBox="0 0 24 24"><use href="#<?= e($item['icon']) ?>"/></svg>
                            </span>
                            <span><strong><?= e($item['title']) ?></strong><?= e($item['text']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </section>

        <!-- ── About / disclaimer ── -->
        <section class="section section-alt" id="about" aria-labelledby="aboutHeading">
            <div class="container about-grid">
                <div class="about-inner">
                    <p class="kicker">সম্পর্কে</p>
                    <h2 id="aboutHeading"><?= e($BRAND) ?> সম্পর্কে</h2>
                    <p>
                        <?= e($BRAND) ?> যেকোনো কম্পিউটার অপারেটরের জন্য — দোকান বা ব্যবসা থেকে
                        নাগরিকদের সেবা দিন। কাগজের স্তূপ নয়, ফরম পূরণ করেই তৈরি হয়
                        প্রত্যয়ন, সনদ ও দরকারি ডকুমেন্ট — Word ও PDF আকারে।
                    </p>
                    <p class="about-legal">স্বতন্ত্র টুলস সাইট — সরকারি পোর্টাল নয়।</p>
                    <p>
                        <a class="btn btn-secondary" href="<?= e(repdoc_url('about/')) ?>">
                            <span>আমাদের সম্পর্কে আরও জানুন</span>
                            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-arrow"/></svg>
                        </a>
                    </p>
                </div>
                <ul class="about-gallery">
                    <li>
                        <figure>
                            <img src="src/assets/image/about1.webp" alt="এলোমেলো কাগজের স্তূপের পাশে গোছানো ডিজিটাল ডকুমেন্ট" width="760" height="316" loading="lazy" decoding="async">
                            <figcaption>এলোমেলো কাগজ থেকে গোছানো ফাইল</figcaption>
                        </figure>
                    </li>
                    <li>
                        <figure>
                            <img src="src/assets/image/about2.webp" alt="ডকুমেন্ট তৈরির ধাপগুলো একসঙ্গে চলছে এমন একটি কাজের দৃশ্য" width="760" height="316" loading="lazy" decoding="async">
                            <figcaption>একই ফরমে দ্রুত ডকুমেন্ট তৈরি</figcaption>
                        </figure>
                    </li>
                    <li>
                        <figure>
                            <img src="src/assets/image/about3.webp" alt="Repdoc থেকে প্রত্যয়ন, সনদ ও ফরম Word ও PDF আকারে বের হচ্ছে" width="760" height="316" loading="lazy" decoding="async">
                            <figcaption>প্রত্যয়ন, সনদ ও ফরম — Word ও PDF-এ প্রস্তুত</figcaption>
                        </figure>
                    </li>
                </ul>
            </div>
        </section>

        <!-- ── Closing CTA: ready-to-start invitation ── -->
        <section class="section cta-band" aria-labelledby="ctaHeading">
            <div class="container">
                <div class="cta-start">
                    <div class="cta-start-copy">
                        <p class="cta-eyebrow">
                            <span class="cta-eyebrow-mark" aria-hidden="true">
                                <svg class="icon" viewBox="0 0 24 24"><use href="#i-check"/></svg>
                            </span>
                            সহজ · দ্রুত · নির্ভরযোগ্য
                        </p>
                        <h2 id="ctaHeading">শুরু করতে প্রস্তুত?</h2>
                        <p class="cta-lead">অপারেটর অ্যাকাউন্টের জন্য আবেদন করুন, লগ ইন করে যেকোনো টুল খুলুন।</p>
                        <p class="cta-note">এখন বাংলাদেশের যেকোনো কম্পিউটার অপারেটরের আবেদন নেওয়া হচ্ছে — অনুমোদনের পর সব টুল খুলে যাবে।</p>
                        <ul class="cta-perks">
                            <li><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-file"/></svg>রেডি টেমপ্লেট</li>
                            <li><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-shield"/></svg>নিরাপদ লগইন</li>
                            <li><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-mobile"/></svg>সব ডিভাইসে</li>
                            <li><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-bolt"/></svg>দ্রুত ডাউনলোড</li>
                        </ul>
                    </div>
                    <figure class="cta-start-art">
                        <img src="src/assets/image/cta-start.webp" alt="ল্যাপটপে ডকুমেন্ট তৈরি করছেন একজন অপারেটর — চারপাশে আবেদনপত্র, সার্টিফিকেট, প্রতিবেদন ও অন্যান্য টুলসের টেমপ্লেট" width="960" height="480" loading="lazy" decoding="async">
                    </figure>
                    <div class="cta-start-actions">
                        <a class="btn btn-primary" href="<?= e($APPLY_URL) ?>">
                            <span>অপারেটর হিসেবে আবেদন</span>
                            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-arrow"/></svg>
                        </a>
                        <a class="btn btn-secondary" href="<?= e($LOGIN_URL) ?>">লগ ইন</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="site-footer footer-mint">
        <div class="container footer-grid footer-grid--5">
            <div class="footer-brand">
                <a class="brand footer-brandmark" href="#top" aria-label="<?= e($BRAND) ?> — হোম">
                    <img class="brand-logo" src="<?= e($LOGO) ?>" alt="<?= e($BRAND) ?>" width="132" height="44" loading="lazy" decoding="async">
                </a>
                <p class="footer-tagline">বাংলাদেশ · অপারেটর ডকুমেন্ট টুলকিট</p>
                <p class="footer-about">ফ্রি ডকুমেন্ট টেমপ্লেট ও ফরম — Word, Excel, PDF, PSD, AI, PLP আকারে। এক অ্যাকাউন্টেই সব টুল।</p>
                <p class="footer-legal">স্বতন্ত্র টুলস সাইট — সরকারি পোর্টাল নয়।</p>
                <ul class="social-links social-links--icons" aria-label="সোশ্যাল মিডিয়া">
                    <li><a href="https://github.com/Repdoc-BD" target="_blank" rel="noopener" aria-label="Repdoc on GitHub">
                        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 19c-4 1.5-4-2.5-6-3m12 5v-3.5c0-1 .1-1.4-.5-2 2.8-.3 5.5-1.4 5.5-6a4.6 4.6 0 0 0-1.3-3.2 4.2 4.2 0 0 0-.1-3.2s-1.1-.3-3.5 1.3a12 12 0 0 0-6.2 0C6.5 2.8 5.4 3.1 5.4 3.1a4.2 4.2 0 0 0-.1 3.2A4.6 4.6 0 0 0 4 9.5c0 4.6 2.7 5.7 5.5 6-.6.6-.6 1.2-.5 2V21"/></svg>
                    </a></li>
                    <li><a href="https://www.facebook.com/Repdocbdofficial" target="_blank" rel="noopener" aria-label="Repdoc on Facebook">
                        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                    </a></li>
                    <li><a href="https://www.youtube.com/@Repdocbdofficial" target="_blank" rel="noopener" aria-label="Repdoc on YouTube">
                        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M22.5 7.2a2.8 2.8 0 0 0-2-2C18.7 4.7 12 4.7 12 4.7s-6.7 0-8.5.5a2.8 2.8 0 0 0-2 2A29 29 0 0 0 1 12a29 29 0 0 0 .5 4.8 2.8 2.8 0 0 0 2 2c1.8.5 8.5.5 8.5.5s6.7 0 8.5-.5a2.8 2.8 0 0 0 2-2A29 29 0 0 0 23 12a29 29 0 0 0-.5-4.8z"/><polygon points="10 15 15 12 10 9"/></svg>
                    </a></li>
                    <li><a href="https://x.com/Repdocbdofficial" target="_blank" rel="noopener" aria-label="Repdoc on X">
                        <svg class="icon icon-brand" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" stroke="none" d="M17.5 3h3.1l-6.8 7.8L21.8 21h-6.3l-4.9-6.4L5 21H1.9l7.3-8.3L1.6 3h6.4l4.4 5.9L17.5 3zm-1.1 16.1h1.7L7.2 4.8H5.4l11 14.3z"/></svg>
                    </a></li>
                    <li><a href="https://t.me/Repdocbdofficial" target="_blank" rel="noopener" aria-label="Repdoc on Telegram">
                        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M22 3 2 10.4l6.1 2.3L10.4 19l4-4.6 5.1 3.8z"/><path d="m8.1 12.7 9.9-7.3-7.6 8.4"/></svg>
                    </a></li>
                </ul>
            </div>

            <nav class="footer-col" aria-label="দ্রুত লিংক">
                <h3 class="footer-head">
                    <span class="footer-head-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#i-link"/></svg></span>
                    দ্রুত লিংক
                </h3>
                <ul>
                    <li><a href="#top">হোম পেজ</a></li>
                    <li><a href="#tools">সব টুলস</a></li>
                    <li><a href="showcase/">টেমপ্লেট শোকেস</a></li>
                    <li><a href="software/">সফটওয়্যার ডাউনলোড</a></li>
                    <li><a href="#how">কীভাবে কাজ করে</a></li>
                    <li><a href="<?= e(repdoc_url('about/')) ?>">আমাদের সম্পর্কে</a></li>
                    <li><a href="<?= e($LOGIN_URL) ?>">লগ ইন</a></li>
                </ul>
            </nav>

            <nav class="footer-col" aria-label="সেবাসমূহ">
                <h3 class="footer-head">
                    <span class="footer-head-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#i-file"/></svg></span>
                    সেবাসমূহ
                </h3>
                <ul>
                    <?php foreach ($tools as $tool): ?>
                        <li><a href="<?= e($tool['url']) ?>"><?= e($tool['name']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </nav>

            <nav class="footer-col" aria-label="সহায়তা">
                <h3 class="footer-head">
                    <span class="footer-head-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#i-shield"/></svg></span>
                    সহায়তা
                </h3>
                <ul>
                    <li><a href="#how">কীভাবে কাজ করে</a></li>
                    <li><a href="privacy/">গোপনীয়তা নীতি</a></li>
                    <li><a href="<?= e($APPLY_URL) ?>">অ্যাকাউন্টের জন্য আবেদন</a></li>
                    <li><a href="#featured">জনপ্রিয় টুলস</a></li>
                    <li><a href="#tools">সব টুলস</a></li>
                </ul>
            </nav>

            <div class="footer-col footer-contact">
                <h3 class="footer-head">
                    <span class="footer-head-icon" aria-hidden="true"><svg class="icon" viewBox="0 0 24 24"><use href="#i-send"/></svg></span>
                    যোগাযোগ
                </h3>
                <ul>
                    <li><a href="https://t.me/Repdocbdofficial" target="_blank" rel="noopener">
                        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M22 3 2 10.4l6.1 2.3L10.4 19l4-4.6 5.1 3.8z"/><path d="m8.1 12.7 9.9-7.3-7.6 8.4"/></svg>
                        <span>টেলিগ্রাম সাপোর্ট</span>
                    </a></li>
                    <li><a href="https://www.facebook.com/Repdocbdofficial" target="_blank" rel="noopener">
                        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg>
                        <span>ফেসবুক পেজ</span>
                    </a></li>
                    <li><a href="https://github.com/Repdoc-BD" target="_blank" rel="noopener">
                        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 19c-4 1.5-4-2.5-6-3m12 5v-3.5c0-1 .1-1.4-.5-2 2.8-.3 5.5-1.4 5.5-6a4.6 4.6 0 0 0-1.3-3.2 4.2 4.2 0 0 0-.1-3.2s-1.1-.3-3.5 1.3a12 12 0 0 0-6.2 0C6.5 2.8 5.4 3.1 5.4 3.1a4.2 4.2 0 0 0-.1 3.2A4.6 4.6 0 0 0 4 9.5c0 4.6 2.7 5.7 5.5 6-.6.6-.6 1.2-.5 2V21"/></svg>
                        <span>গিটহাব রিপো</span>
                    </a></li>
                    <li><a href="https://www.youtube.com/@Repdocbdofficial" target="_blank" rel="noopener">
                        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M22.5 7.2a2.8 2.8 0 0 0-2-2C18.7 4.7 12 4.7 12 4.7s-6.7 0-8.5.5a2.8 2.8 0 0 0-2 2A29 29 0 0 0 1 12a29 29 0 0 0 .5 4.8 2.8 2.8 0 0 0 2 2c1.8.5 8.5.5 8.5.5s6.7 0 8.5-.5a2.8 2.8 0 0 0 2-2A29 29 0 0 0 23 12a29 29 0 0 0-.5-4.8z"/><polygon points="10 15 15 12 10 9"/></svg>
                        <span>ইউটিউব চ্যানেল</span>
                    </a></li>
                </ul>
            </div>
        </div>

        <div class="footer-base">
            <div class="container footer-base-inner">
                <p>&copy; <?= date('Y') ?> <?= e($BRAND) ?>. সর্বস্বত্ব সংরক্ষিত — বাংলাদেশ থেকে।</p>
                <a class="back-top" href="#top">
                    <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><polyline points="6 15 12 9 18 15"/></svg>
                    <span>উপরে যান</span>
                </a>
            </div>
        </div>
    </footer>

    <script src="src/assets/js/home.js?v=1" defer></script>
</body>
</html>
