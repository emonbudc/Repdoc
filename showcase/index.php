<?php
declare(strict_types=1);

/**
 * Repdoc — template showcase: all public formats in one index.
 *
 * Public, indexable landing page for downloadable template packs.
 * Files are auto-listed from officefiles/{word,excel,ppt}/ and
 * offlinefiles/{psd,ai,plp}/ through src/templates.php. Each card links
 * to its own /showcase/{format}/{slug}/ landing page; the download bytes
 * stay gated behind an approved login via each hub's download.php.
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';
require_once dirname(__DIR__) . '/src/seo.php';
require_once dirname(__DIR__) . '/src/filemeta.php';
require_once dirname(__DIR__) . '/src/catalog.php';

$SEO_TITLE = 'টেমপ্লেট শোকেস — ফ্রি Word, Excel, PDF টেমপ্লেট | Repdoc';
$SEO_DESC  = 'Repdoc টেমপ্লেট শোকেস: Word, PDF, Excel, PSD, AI ও PLP ফরম্যাটের রেডি-টু-ইউজ টেমপ্লেট ও ফরম — প্রতিটির প্রিভিউ ও বিবরণসহ, এক অ্যাকাউন্টে সব আনলক।';
$CANON     = repdoc_canonical('showcase/');
$HOME      = repdoc_url('/');
$LOGIN_URL = repdoc_url('public/login/');
$APPLY_URL = repdoc_url('Entrepreneur/apply/');

$currentUser = current_user();
$dashUrl     = $currentUser !== null ? repdoc_panel_home() : $LOGIN_URL;
$userBalance = $currentUser !== null ? (int) user_balance((int) $currentUser['id']) : 0;

/** One entry per format folder. `hub` owns the download endpoint. */
$FORMATS = repdoc_template_formats();
$GROUPS  = repdoc_template_groups();

$files = repdoc_template_files();

$counts     = ['all' => count($files), 'office' => 0, 'psd' => 0, 'ai' => 0, 'plp' => 0];
$typeCounts = array_fill_keys(array_keys($FORMATS), 0);

foreach ($files as $i => $file) {
    $meta = $FORMATS[$file['type']];
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
        'imageAlt'    => 'Repdoc template showcase',
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
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'টেমপ্লেট শোকেস', 'item' => $CANON],
        ],
    ]);
    if ($files !== []) {
        $items = [];
        foreach ($files as $i => $file) {
            $items[] = [
                '@type'       => 'ListItem',
                'position'    => $i + 1,
                'name'        => $FORMATS[$file['type']]['full'] . ' — ' . ($file['title'] ?? $file['name']),
                'description' => $file['description'],
                'url'         => $file['canonical'] ?? repdoc_template_canonical($file['type'], $file['name']),
            ];
        }
        repdoc_jsonld([
            '@type'           => 'ItemList',
            '@id'             => $CANON . '#files',
            'name'            => 'Repdoc template showcase',
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
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/catalog.css?v=4')) ?>">
</head>
<body class="catalog-page cat-showcase">
    <a class="skip-link" href="#library">ফাইল লাইব্রেরিতে যান</a>
    <?php
    repdoc_catalog_render([
        'active'      => 'showcase',
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
        'downloads'   => false,
        'filter_mode' => 'group',
        'heading'     => 'সব টেমপ্লেট ফাইল',
        'hero'        => [
            'kicker'             => 'টেমপ্লেট ও ফরম কালেকশন',
            'kicker_no'          => 'Showcase',
            'title'              => 'সব ফরম্যাট, এক শোকেসে',
            'accent'             => 'রেডি টু ইউজ',
            'lead'               => 'Word, PDF, Excel, Photoshop, Illustrator ও PixelLab — প্রতিটি টেমপ্লেটের প্রিভিউ দেখুন, ফরম্যাট বেছে নিন, তারপর ডাউনলোডের জন্য হাব খুলুন।',
            'image_caption'      => 'এক অ্যাকাউন্টে সব ফরম্যাট',
            'search_placeholder' => 'ফাইলের নাম, ফরম্যাট বা কীওয়ার্ড লিখুন…',
        ],
        'empty_title' => 'ফাইল কালেকশন প্রস্তুত হচ্ছে',
        'empty_text'  => 'খুব শীঘ্রই Word, Excel, PowerPoint, PSD, AI ও PLP ফাইল এখানে যুক্ত হবে। অ্যাকাউন্ট নিলে নতুন ফাইল প্রকাশের সাথে সাথেই ডাউনলোড করতে পারবেন।',
        'guide'       => [
            ['icon' => 'cat-i-search',   'title' => 'ফাইল খুঁজুন',       'text' => 'নাম, ফরম্যাট বা কীওয়ার্ড লিখে লাইভ ফিল্টার করুন — প্রিভিউসহ পছন্দের টেমপ্লেট বেছে নিন।'],
            ['icon' => 'cat-i-file',     'title' => 'হাব খুলুন',        'text' => 'Microsoft Office ফাইল হলে Office হাব, আর PSD/AI/PLP হলে ডিজাইন হাব থেকে ফাইলটি বেছে নিন।'],
            ['icon' => 'cat-i-download', 'title' => 'ডাউনলোড করুন',    'text' => 'লগ ইন করে এক ক্লিকে ডাউনলোড করুন। এক অ্যাকাউন্টেই সব ফরম্যাট ও সব টুল আনলক।'],
        ],
        'closing_title' => 'সব ফরম্যাটের টেমপ্লেট, এক অ্যাকাউন্টে',
        'closing_text'  => 'টেমপ্লেট দেখতে অ্যাকাউন্ট লাগে না — ডাউনলোড করতে লগ ইন করুন অথবা নতুন অপারেটর অ্যাকাউন্টের জন্য আবেদন করুন।',
    ]);
    ?>
    <script src="<?= e(repdoc_url('src/assets/js/catalog.js?v=1')) ?>" defer></script>
</body>
</html>
