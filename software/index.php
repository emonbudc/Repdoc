<?php
declare(strict_types=1);

/**
 * Repdoc — public software downloads library.
 *
 * Public, indexable and login-free. Entries are managed from
 * admin/software.php and stored in love/software.json. Every card links
 * directly to the publisher's external download URL.
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';
require_once dirname(__DIR__) . '/src/seo.php';
require_once dirname(__DIR__) . '/src/filemeta.php';
require_once dirname(__DIR__) . '/src/catalog.php';
require_once dirname(__DIR__) . '/src/software.php';

$SEO_TITLE = 'সফটওয়্যার ডাউনলোড — Repdoc';
$SEO_DESC  = 'Windows, Android, macOS ও Linux-এর নির্বাচিত সফটওয়্যার — লগ ইন ছাড়াই সরাসরি ডাউনলোড লিংকসহ এক জায়গায়।';
$CANON     = repdoc_canonical('software/');
$HOME      = repdoc_url('/');
$LOGIN_URL = repdoc_url('public/login/');
$APPLY_URL = repdoc_url('Entrepreneur/apply/');

$currentUser = current_user();
$dashUrl     = $currentUser !== null ? repdoc_panel_home() : $LOGIN_URL;
$userBalance = $currentUser !== null ? (int) user_balance((int) $currentUser['id']) : 0;

$platforms = repdoc_software_platforms();
$entries = repdoc_software_load_public();

$FORMATS = [];
foreach ($platforms as $key => $meta) {
    $FORMATS[$key] = [
        'label' => $meta['label'],
        'full'  => $meta['full'],
        'group' => 'software',
        'brand' => $meta['brand'],
        'soft'  => $meta['soft'],
        'exts'  => [$meta['ext']],
        'note'  => $meta['note'],
        'logo'  => 'software',
    ];
}

$GROUPS = [
    'software' => ['label' => 'সব সফটওয়্যার', 'sub' => 'Windows, Android, macOS, Linux'],
];

$files = [];
$counts = ['all' => 0, 'software' => 0];
$typeCounts = array_fill_keys(array_keys($FORMATS), 0);
$activePlatforms = [];
$lastUpdated = 0;

foreach ($entries as $entry) {
    $type = (string) $entry['platform'];
    $meta = $FORMATS[$type] ?? $FORMATS['other'];
    $sizeBytes = repdoc_software_size_bytes((string) $entry['size']);
    $sizeLabel = (string) $entry['size'];
    if ($sizeLabel === '' && $sizeBytes > 0) {
        $sizeLabel = repdoc_catalog_human_size($sizeBytes);
    }
    $description = (string) $entry['description'];
    if ($description === '') $description = (string) $meta['note'];

    $mtime = max((int) $entry['updated_at'], (int) $entry['created_at']);
    if ($mtime > $lastUpdated) $lastUpdated = $mtime;

    $host = repdoc_software_host((string) $entry['url']);
    $files[] = [
        'name'          => (string) $entry['name'],
        'title'         => (string) $entry['name'],
        'type'          => $type,
        'ext'           => (string) $meta['exts'][0],
        'ext_label'     => strtoupper((string) $meta['exts'][0]),
        'size'          => $sizeBytes,
        'size_label'    => $sizeLabel,
        'version'       => (string) $entry['version'],
        'mtime'         => $mtime,
        'description'   => $description,
        'thumb'         => repdoc_file_thumb_url('software', (string) $entry['id']),
        'search'        => mb_strtolower(
            $entry['name'] . ' ' . $entry['version'] . ' ' . $entry['description'] . ' '
            . $meta['label'] . ' ' . $meta['full'] . ' ' . $host . ' software download সফটওয়্যার',
            'UTF-8'
        ),
        'host'          => $host,
        'action_url'    => (string) $entry['url'],
        'action_label'  => 'ডাউনলোড করুন',
        'action_target' => '_blank',
        'action_rel'    => 'noopener noreferrer nofollow',
    ];

    $counts['all']++;
    $counts['software']++;
    $typeCounts[$type] = ($typeCounts[$type] ?? 0) + 1;
    $activePlatforms[$type] = true;
}

$bn = 'repdoc_catalog_bn_digits';
$totalSoftware = count($files);
$platformCount = count($activePlatforms);

$heroStats = [
    ['value' => $bn((string) $totalSoftware), 'label' => 'সফটওয়্যার'],
    ['value' => $bn((string) $platformCount), 'label' => 'প্ল্যাটফর্ম'],
    ['value' => repdoc_catalog_date($lastUpdated), 'label' => 'হালনাগাদ'],
];

$sidebarStats = [
    ['label' => 'সফটওয়্যার', 'value' => $bn((string) $totalSoftware) . 'টি'],
    ['label' => 'প্ল্যাটফর্ম', 'value' => $bn((string) $platformCount) . 'টি'],
    ['label' => 'সর্বশেষ যোগ', 'value' => repdoc_catalog_date($lastUpdated)],
];

$emptyActions = [
    ['label' => 'সব টুল দেখুন', 'url' => $HOME . '#tools', 'class' => 'btn btn-primary'],
    ['label' => 'গুরুত্বপূর্ণ লিংক', 'url' => repdoc_url('importantlinks/'), 'class' => 'btn btn-secondary'],
];

$closingActions = [
    ['label' => 'সব টুল দেখুন', 'url' => $HOME . '#tools', 'class' => 'btn btn-ledger'],
    ['label' => 'গুরুত্বপূর্ণ লিংক', 'url' => repdoc_url('importantlinks/'), 'class' => 'btn btn-ledger-outline'],
];
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($SEO_TITLE) ?></title>
    <meta name="description" content="<?= e($SEO_DESC) ?>">
    <meta name="theme-color" content="#FFFEFB">
    <?php
    repdoc_seo_head([
        'title'       => $SEO_TITLE,
        'description' => $SEO_DESC,
        'canonical'   => $CANON,
        'type'        => 'website',
        'image'       => repdoc_canonical('src/assets/image/hero.webp'),
        'imageWidth'  => 900,
        'imageHeight' => 600,
        'imageAlt'    => 'Repdoc software downloads library',
        'locale'      => 'bn_BD',
    ]);
    repdoc_jsonld([
        '@type'       => 'CollectionPage',
        '@id'         => $CANON . '#page',
        'name'        => $SEO_TITLE,
        'url'         => $CANON,
        'description' => $SEO_DESC,
        'inLanguage'  => 'bn',
        'isPartOf'    => ['@id' => repdoc_canonical() . '#website'],
        'breadcrumb'  => ['@id' => $CANON . '#breadcrumb'],
    ]);
    repdoc_jsonld([
        '@type'           => 'BreadcrumbList',
        '@id'             => $CANON . '#breadcrumb',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => repdoc_canonical()],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'সফটওয়্যার ডাউনলোড', 'item' => $CANON],
        ],
    ]);
    if ($files !== []) {
        $items = [];
        foreach ($files as $i => $file) {
            $items[] = [
                '@type'       => 'ListItem',
                'position'    => $i + 1,
                'name'        => $file['name'] . ($file['version'] !== '' ? ' ' . $file['version'] : ''),
                'description' => $file['description'],
                'url'         => $file['action_url'],
            ];
        }
        repdoc_jsonld([
            '@type'           => 'ItemList',
            '@id'             => $CANON . '#software',
            'name'            => 'Repdoc software downloads',
            'numberOfItems'   => $totalSoftware,
            'itemListOrder'   => 'https://schema.org/ItemListUnordered',
            'itemListElement' => $items,
        ]);
    }
    ?>
    <link rel="icon" type="image/png" href="<?= e(repdoc_url('public/favicon.png')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="image" href="<?= e(repdoc_url('src/assets/image/hero.webp')) ?>" fetchpriority="high">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&amp;family=Noto+Sans+Bengali:wght@400;500;600;700;800&amp;family=JetBrains+Mono:wght@400;500;600&amp;display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/tokens.css?v=2')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/shell.css?v=2')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/home.css?v=4')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/catalog.css?v=4')) ?>">
</head>
<body class="catalog-page cat-software">
    <a class="skip-link" href="#library">সফটওয়্যার লাইব্রেরিতে যান</a>
    <?php
    repdoc_catalog_render([
        'active'          => 'software',
        'home'            => $HOME,
        'login'           => $LOGIN_URL,
        'apply'           => $APPLY_URL,
        'dashboard'       => $dashUrl,
        'user'            => $currentUser,
        'balance'         => $userBalance,
        'formats'         => $FORMATS,
        'groups'          => $GROUPS,
        'files'           => $files,
        'counts'          => $counts,
        'type_counts'     => $typeCounts,
        'downloads'       => false,
        'public_downloads'=> true,
        'next'            => '/software/',
        'filter_mode'     => 'type',
        'heading'         => 'সব সফটওয়্যার',
        'hero'            => [
            'kicker'             => 'সফটওয়্যার ডাউনলোড',
            'kicker_no'          => 'Software',
            'title'              => 'দরকারি সফটওয়্যার',
            'accent'             => 'লগ ইন ছাড়াই ডাউনলোড',
            'lead'               => 'Windows, Android, macOS ও Linux-এর নির্বাচিত সফটওয়্যার — এক জায়গায় খুঁজুন, প্ল্যাটফর্ম বেছে নিন এবং সরাসরি ডাউনলোড করুন।',
            'image'              => repdoc_url('src/assets/image/hero.webp'),
            'image_alt'          => 'Repdoc সফটওয়্যার ডাউনলোড লাইব্রেরি',
            'image_caption'      => 'Windows · Android · macOS · Linux',
            'search_placeholder' => 'সফটওয়্যারের নাম, প্ল্যাটফর্ম বা ভার্সন খুঁজুন…',
        ],
        'hero_stats'      => $heroStats,
        'sidebar_stats'   => $sidebarStats,
        'sidebar_title'   => 'সফটওয়্যার এক নজরে',
        'sidebar_heading' => 'প্ল্যাটফর্ম অনুযায়ী',
        'filter_all_label'=> 'সব সফটওয়্যার',
        'filter_all_sub'  => 'সব প্ল্যাটফর্ম',
        'result_noun'     => 'সফটওয়্যার',
        'join_card'       => false,
        'empty_title'     => 'সফটওয়্যার কালেকশন প্রস্তুত হচ্ছে',
        'empty_text'      => 'খুব শীঘ্রই Windows, Android, macOS ও Linux-এর নির্বাচিত সফটওয়্যার এখানে যুক্ত হবে।',
        'empty_actions'   => $emptyActions,
        'empty_logos'     => ['software'],
        'noresult_title'  => 'কোনো সফটওয়্যার পাওয়া যায়নি',
        'noresult_text'   => 'অন্য নাম, প্ল্যাটফর্ম বা ভার্সন দিয়ে আবার খুঁজুন।',
        'noresult_reset'  => 'সব সফটওয়্যার দেখুন',
        'guide_kicker'    => 'ডাউনলোড প্রক্রিয়া',
        'guide_title'     => 'সফটওয়্যার ডাউনলোড করবেন যেভাবে',
        'guide_sub'       => 'তিন ধাপে পছন্দের সফটওয়্যার খুঁজে প্রকাশকের সাইট থেকে ডাউনলোড করুন।',
        'guide'           => [
            ['icon' => 'cat-i-search',   'title' => 'সফটওয়্যার খুঁজুন',  'text' => 'নাম, প্ল্যাটফর্ম বা ভার্সন দিয়ে লাইভ সার্চ করুন অথবা Windows, Android, macOS ও Linux ফিল্টার বেছে নিন।'],
            ['icon' => 'cat-i-shield',   'title' => 'যাচাই করুন',        'text' => 'কার্ডে দেওয়া প্রকাশকের ঠিকানা ও ভার্সন মিলিয়ে নিন — ডাউনলোড লিংক সরাসরি প্রকাশকের সাইটে খুলবে।'],
            ['icon' => 'cat-i-download', 'title' => 'ডাউনলোড করুন',     'text' => 'পছন্দের সফটওয়্যারে ক্লিক করুন — লগ ইন ছাড়াই নতুন ট্যাবে ডাউনলোড পেজ খুলবে।'],
        ],
        'closing_kicker'  => 'লগ ইন ছাড়াই, সরাসরি',
        'closing_title'   => 'সফটওয়্যার ডাউনলোড, লগ ইন ছাড়াই',
        'closing_text'    => 'প্ল্যাটফর্ম বেছে নিন, নাম দিয়ে খুঁজুন — প্রতিটি ডাউনলোড লিংক সরাসরি প্রকাশক বা ডেভেলপারের সাইটে খুলবে।',
        'closing_actions' => $closingActions,
    ]);
    ?>
    <script src="<?= e(repdoc_url('src/assets/js/catalog.js?v=1')) ?>" defer></script>
</body>
</html>
