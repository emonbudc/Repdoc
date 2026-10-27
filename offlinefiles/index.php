<?php
declare(strict_types=1);

/**
 * Repdoc — offline design file library (PSD / AI / PLP).
 *
 * Public, indexable landing page. Files auto-list from
 * offlinefiles/{psd,ai,plp}/; admin-written descriptions
 * (admin/files.php) fall back to the format note when none is set.
 * Listing is public; the bytes stay login-gated via download.php.
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';
require_once dirname(__DIR__) . '/src/seo.php';
require_once dirname(__DIR__) . '/src/filemeta.php';
require_once dirname(__DIR__) . '/src/catalog.php';

$SEO_TITLE = 'PSD, AI ও PLP ফাইল শোকেস — Repdoc';
$SEO_DESC  = 'Repdoc অফলাইন ফাইল: Adobe Photoshop (PSD), Illustrator (AI) ও PixelLab (PLP) টেমপ্লেট — প্রতিটি ফাইলের বিবরণসহ। লগ ইন করে ডাউনলোড করুন।';
$CANON     = repdoc_canonical('offlinefiles/');
$HOME      = repdoc_url('/');
$LOGIN_URL = repdoc_url('public/login/');
$APPLY_URL = repdoc_url('Entrepreneur/apply/');

$currentUser = current_user();
$dashUrl     = $currentUser !== null ? repdoc_panel_home() : $LOGIN_URL;
$userBalance = $currentUser !== null ? (int) user_balance((int) $currentUser['id']) : 0;

$FORMATS = [
    'psd' => [
        'label' => 'PSD Files',
        'full'  => 'Adobe Photoshop',
        'hub'   => 'offlinefiles',
        'group' => 'psd',
        'brand' => '#1D4ED8',
        'soft'  => '#E9EFFD',
        'exts'  => ['psd', 'psb', 'zip'],
        'note'  => 'লেয়ার-ভিত্তিক Adobe Photoshop টেমপ্লেট — পোস্টার, ব্যানার ও প্রোফাইল কভার নিজের মতো এডিট করার জন্য সম্পূর্ণ রেডি।',
    ],
    'ai' => [
        'label' => 'AI Files',
        'full'  => 'Adobe Illustrator',
        'hub'   => 'offlinefiles',
        'group' => 'ai',
        'brand' => '#B45309',
        'soft'  => '#FDF3E3',
        'exts'  => ['ai', 'eps', 'zip'],
        'note'  => 'ভেক্টর Adobe Illustrator ফাইল — লোগো, লেটারহেড ও প্রিন্ট-রেডি ডিজাইন, যেকোনো সাইজে স্কেল করা যায়।',
    ],
    'plp' => [
        'label' => 'PLP Files',
        'full'  => 'PixelLab',
        'hub'   => 'offlinefiles',
        'group' => 'plp',
        'brand' => '#7C3AED',
        'soft'  => '#F2EEFD',
        'exts'  => ['plp', 'zip'],
        'note'  => 'মোবাইলে PixelLab দিয়ে খোলা ও এডিট করার জন্য প্রস্তুত PLP প্রজেক্ট ফাইল — টেক্সট, ছবি ও কালার বদলে ব্যবহার করুন।',
    ],
];

$GROUPS = [
    'psd' => ['label' => 'PSD Files', 'sub' => 'Photoshop Design'],
    'ai'  => ['label' => 'AI Files',  'sub' => 'Illustrator Vector'],
    'plp' => ['label' => 'PLP Files', 'sub' => 'Poster / Flex Design'],
];

$root  = dirname(__DIR__);
$files = [];
foreach ($FORMATS as $type => $meta) {
    $dir = $root . DIRECTORY_SEPARATOR . $meta['hub'] . DIRECTORY_SEPARATOR . $type;
    foreach (repdoc_catalog_scan($dir, $type, $meta['exts']) as $row) {
        $files[] = $row;
    }
}
usort($files, static fn(array $a, array $b): int => $b['mtime'] <=> $a['mtime']);

$counts       = ['all' => count($files), 'psd' => 0, 'ai' => 0, 'plp' => 0];
$typeCounts   = array_fill_keys(array_keys($FORMATS), 0);
$descriptions = repdoc_file_descriptions_all();

foreach ($files as $i => $file) {
    $meta = $FORMATS[$file['type']];
    $custom = $descriptions[repdoc_file_description_key($file['type'], $file['name'])] ?? '';
    $files[$i]['description'] = $custom !== '' ? $custom : $meta['note'];
    $files[$i]['thumb'] = repdoc_file_thumb_url($file['type'], $file['name']);
    $files[$i]['search'] = repdoc_catalog_search_index($files[$i], $FORMATS, $GROUPS);
    $counts[$meta['group']] = ($counts[$meta['group']] ?? 0) + 1;
    $typeCounts[$file['type']] = ($typeCounts[$file['type']] ?? 0) + 1;
}
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
        'image'       => repdoc_canonical('src/assets/image/officefiles-hero.png'),
        'imageWidth'  => 600,
        'imageHeight' => 378,
        'imageAlt'    => 'Repdoc PSD, AI and PLP file showcase',
        'locale'      => 'bn_BD',
    ]);
    repdoc_jsonld([
        '@type'       => 'WebPage',
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
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'PSD / AI / PLP ফাইল', 'item' => $CANON],
        ],
    ]);
    if ($files !== []) {
        $items = [];
        foreach ($files as $i => $file) {
            $items[] = [
                '@type'       => 'ListItem',
                'position'    => $i + 1,
                'name'        => $FORMATS[$file['type']]['full'] . ' — ' . $file['name'],
                'description' => $file['description'],
            ];
        }
        repdoc_jsonld([
            '@type'           => 'ItemList',
            '@id'             => $CANON . '#files',
            'name'            => 'Repdoc offline design files',
            'numberOfItems'   => count($files),
            'itemListElement' => $items,
        ]);
    }
    ?>
    <link rel="icon" type="image/png" href="<?= e(repdoc_url('public/favicon.png')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="image" href="<?= e(repdoc_url('src/assets/image/officefiles-hero.png')) ?>" fetchpriority="high">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&amp;family=Noto+Sans+Bengali:wght@400;500;600;700;800&amp;family=JetBrains+Mono:wght@400;500;600&amp;display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/tokens.css?v=2')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/shell.css?v=2')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/home.css?v=4')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/catalog.css?v=3')) ?>">
</head>
<body class="catalog-page cat-offlinefiles">
    <a class="skip-link" href="#library">ফাইল লাইব্রেরিতে যান</a>
    <?php
    repdoc_catalog_render([
        'active'      => 'offlinefiles',
        'home'        => $HOME,
        'login'       => $LOGIN_URL,
        'apply'       => $APPLY_URL,
        'dashboard'   => $dashUrl,
        'user'        => $currentUser,
        'balance'     => $userBalance,
        'formats'     => $FORMATS,
        'groups'      => $GROUPS,
        'files'       => $files,
        'counts'      => $counts,
        'type_counts' => $typeCounts,
        'downloads'   => true,
        'next'        => '/offlinefiles/',
        'filter_mode' => 'type',
        'heading'     => 'PSD / AI / PLP ফাইল',
        'hero'        => [
            'kicker'             => 'ডিজাইন ফাইল লাইব্রেরি',
            'kicker_no'          => 'Design',
            'title'              => 'PSD, AI ও PLP',
            'accent'             => 'ডিজাইন ফাইল, এডিট-রেডি',
            'lead'               => 'Photoshop, Illustrator ও PixelLab-এর লেয়ার-রেডি ডিজাইন টেমপ্লেট — পোস্টার, ব্যানার, কার্ড ও ব্র্যান্ডিং ফাইল এক জায়গায়।',
            'image_caption'      => 'PSD · AI · PLP',
            'search_placeholder' => 'PSD, AI বা PLP ডিজাইন ফাইল খুঁজুন…',
        ],
        'empty_title' => 'ডিজাইন ফাইল কালেকশন প্রস্তুত হচ্ছে',
        'empty_text'  => 'খুব শীঘ্রই PSD, AI ও PLP ডিজাইন ফাইল এখানে যুক্ত হবে। অ্যাকাউন্ট নিলে নতুন ফাইল প্রকাশের সাথে সাথেই ডাউনলোড করতে পারবেন।',
        'guide'       => [
            ['icon' => 'cat-i-search',   'title' => 'ডিজাইন বেছে নিন', 'text' => 'PSD, AI বা PLP ফিল্টার করে পছন্দের ডিজাইনের প্রিভিউ দেখে নিন।'],
            ['icon' => 'cat-i-lock',     'title' => 'লগ ইন করুন',      'text' => 'একটি অপারেটর অ্যাকাউন্টেই সব ডিজাইন ফাইল ও সব টুল আনলক হয়।'],
            ['icon' => 'cat-i-download', 'title' => 'ডাউনলোড করুন',    'text' => 'ফাইল ডাউনলোড করে Photoshop, Illustrator বা PixelLab-এ খুলে এডিট করুন।'],
        ],
        'closing_title' => 'ডিজাইন শুরু হোক রেডি ফাইল দিয়ে',
        'closing_text'  => 'পোস্টার, ব্যানার, বিজনেস কার্ড বা ব্র্যান্ড লোগো — প্রস্তুত ফাইল ডাউনলোড করে শুধু নিজের তথ্য বসান।',
    ]);
    ?>
    <script src="<?= e(repdoc_url('src/assets/js/catalog.js?v=1')) ?>" defer></script>
</body>
</html>
