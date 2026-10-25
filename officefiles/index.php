<?php
declare(strict_types=1);

/**
 * Repdoc — Microsoft Office file library (Word / Excel / PowerPoint).
 *
 * Public, indexable landing page. Files auto-list from
 * officefiles/{word,excel,ppt}/; admin-written descriptions
 * (admin/files.php) fall back to the format note when none is set.
 * Listing is public; the bytes stay login-gated via download.php.
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';
require_once dirname(__DIR__) . '/src/seo.php';
require_once dirname(__DIR__) . '/src/filemeta.php';
require_once dirname(__DIR__) . '/src/catalog.php';

$SEO_TITLE = 'MS Office ফাইল শোকেস — Repdoc';
$SEO_DESC  = 'Repdoc ফাইল শোকেস: Microsoft Word, Excel ও PowerPoint টেমপ্লেট — প্রতিটি ফাইলের বিবরণসহ। লগ ইন করে ডাউনলোড করুন।';
$CANON     = repdoc_canonical('officefiles/');
$HOME      = repdoc_url('/');
$LOGIN_URL = repdoc_url('public/login/');
$APPLY_URL = repdoc_url('Entrepreneur/apply/');

$currentUser = current_user();
$dashUrl     = $currentUser !== null ? repdoc_panel_home() : $LOGIN_URL;
$userBalance = $currentUser !== null ? (int) user_balance((int) $currentUser['id']) : 0;

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

$root  = dirname(__DIR__);
$files = [];
foreach ($FORMATS as $type => $meta) {
    $dir = $root . DIRECTORY_SEPARATOR . $meta['hub'] . DIRECTORY_SEPARATOR . $type;
    foreach (repdoc_catalog_scan($dir, $type, $meta['exts']) as $row) {
        $files[] = $row;
    }
}
usort($files, static fn(array $a, array $b): int => $b['mtime'] <=> $a['mtime']);

$counts       = ['all' => count($files), 'office' => 0];
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
        'imageAlt'    => 'Repdoc MS Office file showcase',
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
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'MS Office ফাইল', 'item' => $CANON],
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
            'name'            => 'Repdoc MS Office files',
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
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/tokens.css?v=1')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/shell.css?v=1')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/home.css?v=3')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/catalog.css?v=1')) ?>">
</head>
<body class="catalog-page cat-officefiles">
    <a class="skip-link" href="#library">ফাইল লাইব্রেরিতে যান</a>
    <?php
    repdoc_catalog_render([
        'active'      => 'officefiles',
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
        'next'        => '/officefiles/',
        'filter_mode' => 'type',
        'heading'     => 'MS Office ফাইল',
        'hero'        => [
            'kicker'             => 'MS Office ফাইল লাইব্রেরি',
            'kicker_no'          => 'Office',
            'title'              => 'Word, Excel, PowerPoint',
            'accent'             => 'অফিস ফাইল, প্রস্তুত',
            'lead'               => 'চিঠি, রিপোর্ট, বাজেট ও প্রেজেন্টেশনের জন্য রেডি Microsoft Office টেমপ্লেট — পছন্দের ফাইল খুঁজে লগ ইন করে ডাউনলোড করুন।',
            'image_caption'      => 'Word · Excel · PowerPoint',
            'search_placeholder' => 'Word, Excel বা PowerPoint ফাইল খুঁজুন…',
        ],
        'empty_title' => 'Office ফাইল কালেকশন প্রস্তুত হচ্ছে',
        'empty_text'  => 'খুব শীঘ্রই Word, Excel ও PowerPoint টেমপ্লেট এখানে যুক্ত হবে। অ্যাকাউন্ট নিলে নতুন ফাইল প্রকাশের সাথে সাথেই ডাউনলোড করতে পারবেন।',
        'guide'       => [
            ['icon' => 'cat-i-search',   'title' => 'ফাইল খুঁজুন',   'text' => 'Word, Excel বা PowerPoint বেছে নিন — নাম বা কীওয়ার্ড দিয়েও লাইভ সার্চ করতে পারেন।'],
            ['icon' => 'cat-i-lock',     'title' => 'লগ ইন করুন',   'text' => 'একটি অপারেটর অ্যাকাউন্টেই সব Office ফাইল ও সব টুল আনলক হয়।'],
            ['icon' => 'cat-i-download', 'title' => 'ডাউনলোড করুন', 'text' => 'পছন্দের ফাইলে ক্লিক করুন — ফাইলটি সরাসরি আপনার ডিভাইসে ডাউনলোড হবে।'],
        ],
        'closing_title' => 'আপনার অফিস ফাইল, আরও দ্রুত',
        'closing_text'  => 'অ্যাকাউন্ট খুলে Word, Excel ও PowerPoint টেমপ্লেট এক ক্লিকে ডাউনলোড করুন — একই অ্যাকাউন্টে সব টুল।',
    ]);
    ?>
    <script src="<?= e(repdoc_url('src/assets/js/catalog.js?v=1')) ?>" defer></script>
</body>
</html>
