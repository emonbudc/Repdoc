<?php
declare(strict_types=1);

/**
 * Repdoc — template showcase: all public formats in one index.
 *
 * Public, indexable landing page for downloadable template packs.
 * Files are auto-listed from officefiles/{word,excel,ppt}/ and
 * offlinefiles/{psd,ai,plp}/; each card shows the admin-written
 * description (admin/files.php) and falls back to a format note when
 * none is set. Listing is public; the bytes stay gated behind an
 * approved login via each hub's download.php — this page links to the
 * hubs instead of exposing download links.
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';
require_once dirname(__DIR__) . '/src/seo.php';
require_once dirname(__DIR__) . '/src/filemeta.php';
require_once dirname(__DIR__) . '/src/catalog.php';

$SEO_TITLE = 'টেমপ্লেট শোকেস — Repdoc';
$SEO_DESC  = 'Repdoc টেমপ্লেট শোকেস: Word, PDF, Excel, PSD, AI ও PLP ফরম্যাটের রেডি-টু-ইউজ টেমপ্লেট ও ফরম — এক অ্যাকাউন্টে সব আনলক।';
$CANON     = repdoc_canonical('showcase/');
$HOME      = repdoc_url('/');
$LOGIN_URL = repdoc_url('public/login/');
$APPLY_URL = repdoc_url('Entrepreneur/apply/');

$currentUser = current_user();
$dashUrl     = $currentUser !== null ? repdoc_panel_home() : $LOGIN_URL;
$userBalance = $currentUser !== null ? (int) user_balance((int) $currentUser['id']) : 0;

/** One entry per format folder. `hub` owns the download endpoint. */
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
    'office' => ['label' => 'MS Office', 'sub' => 'Word, Excel, PowerPoint'],
    'psd'    => ['label' => 'PSD Files', 'sub' => 'Photoshop Design'],
    'ai'     => ['label' => 'AI Files',  'sub' => 'Illustrator Vector'],
    'plp'    => ['label' => 'PLP Files', 'sub' => 'Poster / Flex Design'],
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

$counts     = ['all' => count($files), 'office' => 0, 'psd' => 0, 'ai' => 0, 'plp' => 0];
$typeCounts = array_fill_keys(array_keys($FORMATS), 0);
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
                'name'        => $FORMATS[$file['type']]['full'] . ' — ' . $file['name'],
                'description' => $file['description'],
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
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/tokens.css?v=1')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/shell.css?v=1')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/home.css?v=3')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/catalog.css?v=1')) ?>">
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
