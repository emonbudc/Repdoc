<?php
declare(strict_types=1);

/**
 * Repdoc — public Bangla certificate guide hub.
 *
 * Informational pages only. Repdoc is not a government office and these guides
 * do not replace the issuing authority's process.
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';
require_once dirname(__DIR__) . '/src/seo.php';
require_once dirname(__DIR__) . '/src/catalog.php';
require_once dirname(__DIR__) . '/src/sonod_guides.php';
require_once __DIR__ . '/_ui.php';

$guides = repdoc_sonod_guides();
$groups = repdoc_sonod_groups();

$SEO_TITLE = 'সনদ গাইড — নাগরিক, ওয়ারিশ, চারিত্রিক ও অন্যান্য সনদ | Repdoc';
$SEO_DESC  = 'নাগরিক, ওয়ারিশ, চারিত্রিক, আয়, ভূমিহীন, প্রতিবন্ধী ও ট্রেড লাইসেন্সসহ সাধারণ সনদের বাংলা গাইড — কী লাগে, কোথায় আবেদন করবেন ও কীভাবে যাচাই করবেন।';
$CANON     = repdoc_canonical('sonod/');
$HOME      = repdoc_url('/');
$LOGIN_URL = repdoc_url('public/login/');
$APPLY_URL = repdoc_url('Entrepreneur/apply/');
$VERIFY    = repdoc_url('verify/');

$currentUser = current_user();
$dashUrl     = $currentUser !== null ? repdoc_panel_home() : $LOGIN_URL;
$userBalance = $currentUser !== null ? (int) user_balance((int) $currentUser['id']) : 0;

$faqs = [
    [
        'q' => 'এই সনদ গাইডগুলো কি সরকারি তথ্য?',
        'a' => 'না। এগুলো Repdoc-এর সাধারণ তথ্যভিত্তিক বাংলা গাইড। সরকারি সনদ, ফি, আবেদন ও সময়সীমার জন্য সংশ্লিষ্ট ইউনিয়ন পরিষদ, পৌরসভা, সিটি কর্পোরেশন বা দপ্তরের সর্বশেষ নির্দেশনা অনুসরণ করুন।',
    ],
    [
        'q' => 'গাইড পড়ে কি সরাসরি সনদ পাওয়া যাবে?',
        'a' => 'না। গাইডে সাধারণ প্রক্রিয়া ও কাগজপত্রের ধারণা দেওয়া হয়। সনদ ইস্যু করে কেবল সংশ্লিষ্ট কর্তৃপক্ষ; Repdoc-এ শুধু প্রত্যয়নের ড্রাফট বা প্রিন্ট-রেডি কপি তৈরি করা যায়।',
    ],
    [
        'q' => 'সনদ যাচাই কোথায় করবেন?',
        'a' => 'সরকারি সনদ সংশ্লিষ্ট ইস্যুকারী কর্তৃপক্ষের মাধ্যমে যাচাই করুন। Repdoc-এ তৈরি প্রত্যয়নের জন্য Repdoc-এর সনদ যাচাই পেজে সনদ নম্বর দিন।',
    ],
    [
        'q' => 'কোন সনদের জন্য কোন কাগজ লাগে?',
        'a' => 'সনদের ধরন ও এলাকাভেদে কাগজপত্র ভিন্ন। প্রতিটি গাইডে সাধারণ চেকলিস্ট দেওয়া আছে; চূড়ান্ত তালিকা সংশ্লিষ্ট অফিস থেকেই নিশ্চিত করুন।',
    ],
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
        'imageAlt'    => 'Repdoc সনদ গাইড — বাংলা চেকলিস্ট ও আবেদন তথ্য',
        'twitterCard' => 'summary_large_image',
        'locale'      => 'bn_BD',
    ]);
    repdoc_jsonld([
        '@type'       => 'CollectionPage',
        '@id'         => $CANON . '#page',
        'name'        => 'সনদ গাইড — Repdoc',
        'url'         => $CANON,
        'description' => $SEO_DESC,
        'inLanguage'  => 'bn',
        'isPartOf'    => ['@id' => repdoc_canonical() . '#website'],
        'about'       => ['@id' => repdoc_canonical() . '#org'],
        'breadcrumb'  => ['@id' => $CANON . '#breadcrumb'],
    ]);
    repdoc_jsonld([
        '@type'           => 'BreadcrumbList',
        '@id'             => $CANON . '#breadcrumb',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => repdoc_canonical()],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'সনদ গাইড', 'item' => $CANON],
        ],
    ]);
    repdoc_jsonld([
        '@type'           => 'ItemList',
        '@id'             => $CANON . '#guides',
        'name'            => 'সনদ গাইডের তালিকা',
        'itemListElement' => array_map(
            static fn(string $slug, array $guide, int $index): array => [
                '@type'    => 'ListItem',
                'position' => $index + 1,
                'name'     => (string) $guide['label'],
                'url'      => repdoc_canonical('sonod/' . $slug . '/'),
            ],
            array_keys($guides),
            array_values($guides),
            range(1, count($guides))
        ),
    ]);
    repdoc_jsonld([
        '@type'      => 'FAQPage',
        '@id'        => $CANON . '#faq',
        'mainEntity' => array_map(
            static fn(array $item): array => [
                '@type'          => 'Question',
                'name'           => $item['q'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['a']],
            ],
            $faqs
        ),
    ]);
    ?>
    <link rel="icon" type="image/png" href="<?= e(repdoc_url('public/favicon.png')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&amp;family=Noto+Sans+Bengali:wght@400;500;600;700;800&amp;display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/tokens.css?v=2')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/shell.css?v=2')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/home.css?v=4')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/catalog.css?v=4')) ?>">
    <?php repdoc_sonod_styles(); ?>
</head>
<body class="catalog-page sonod-page">
    <?php
    repdoc_catalog_sprite();
    repdoc_catalog_header([
        'home'      => $HOME,
        'login'     => $LOGIN_URL,
        'apply'     => $APPLY_URL,
        'dashboard' => $dashUrl,
        'user'      => $currentUser,
        'balance'   => $userBalance,
        'active'    => 'sonod',
    ]);
    ?>

    <main class="sonod-wrap">
        <nav class="sonod-crumbs" aria-label="Breadcrumb">
            <a href="<?= e($HOME) ?>">হোম</a> › <span aria-current="page">সনদ গাইড</span>
        </nav>

        <section class="sonod-hero" aria-labelledby="sonodHubTitle">
            <span class="sonod-kicker">Bangla certificate guides</span>
            <h1 id="sonodHubTitle">সনদ গাইড: কী লাগে, কোথায় আবেদন করবেন, কীভাবে যাচাই করবেন</h1>
            <p class="sonod-lead">নাগরিক, ওয়ারিশ, চারিত্রিক, আয়, ভূমিহীন, প্রতিবন্ধী, মৃত্যু ও ট্রেড লাইসেন্সসহ সাধারণ সনদের জন্য বাংলা চেকলিস্ট। প্রতিটি গাইডে সাধারণ কাগজপত্র, আবেদনের ধাপ এবং সচরাচর জিজ্ঞাসা এক জায়গায় সাজানো হয়েছে।</p>
            <p class="sonod-meta"><span><?= e((string) count($guides)) ?>টি গাইড</span><span>সর্বশেষ হালনাগাদ: ৩১ অক্টোবর ২০২৬</span><span>পড়তে কোনো লগইন লাগে না</span></p>
        </section>

        <?php repdoc_sonod_disclaimer(); ?>

        <?php foreach ($groups as $groupKey => $group): ?>
            <?php
            $groupGuides = array_filter($guides, static fn(array $guide): bool => ($guide['group'] ?? '') === $groupKey);
            if ($groupGuides === []) continue;
            ?>
            <section class="sonod-group" aria-labelledby="group-<?= e($groupKey) ?>">
                <h2 id="group-<?= e($groupKey) ?>"><?= e((string) $group['label']) ?></h2>
                <p><?= e((string) $group['sub']) ?></p>
                <div class="sonod-grid">
                    <?php foreach ($groupGuides as $slug => $guide): ?>
                        <?php repdoc_sonod_card((string) $slug, $guide); ?>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>

        <section class="sonod-section" aria-labelledby="sonodHow">
            <h2 id="sonodHow">সঠিক সনদ খোঁজার সহজ ধাপ</h2>
            <ol>
                <li><strong>প্রয়োজনটি পরিষ্কার করুন:</strong> কোন প্রতিষ্ঠান সনদটি চাইছে এবং তারা কোন নাম/ফরম্যাট চায়, আগে জেনে নিন।</li>
                <li><strong>কর্তৃপক্ষ শনাক্ত করুন:</strong> আপনার ঠিকানার ইউনিয়ন পরিষদ, পৌরসভা, সিটি কর্পোরেশন বা জেলা প্রশাসন—কোনটি প্রযোজ্য তা নিশ্চিত করুন।</li>
                <li><strong>কাগজপত্র মিলিয়ে নিন:</strong> গাইডের সাধারণ চেকলিস্ট দেখুন, তারপর অফিসের সর্বশেষ তালিকা অনুযায়ী কাগজ জমা দিন।</li>
                <li><strong>যাচাই করুন:</strong> সনদ হাতে পাওয়ার পর ইস্যুকারী কর্তৃপক্ষের নির্দেশনা অনুযায়ী বৈধতা যাচাই করুন।</li>
            </ol>
            <div class="sonod-cta">
                <a class="primary" href="<?= e(repdoc_url('genaretor/certificate/')) ?>">প্রত্যয়ন টেমপ্লেট দেখুন</a>
                <a class="secondary" href="<?= e($VERIFY) ?>">সনদ যাচাই করুন</a>
            </div>
        </section>

        <section class="sonod-section sonod-faq" aria-labelledby="sonodFaq">
            <h2 id="sonodFaq">সচরাচর জিজ্ঞাসা</h2>
            <?php foreach ($faqs as $faq): ?>
                <details>
                    <summary><?= e($faq['q']) ?></summary>
                    <p><?= e($faq['a']) ?></p>
                </details>
            <?php endforeach; ?>
        </section>

        <p class="sonod-source">তথ্যসূত্র: স্থানীয় সরকার প্রতিষ্ঠান ও সংশ্লিষ্ট দপ্তরের প্রকাশিত সাধারণ প্রক্রিয়া। নির্দিষ্ট ফি, সময় ও ফরম্যাট পরিবর্তন হতে পারে—আবেদনের আগে অফিসে যাচাই করুন।</p>
    </main>

    <?php
    repdoc_catalog_footer([
        'home'  => $HOME,
        'login' => $LOGIN_URL,
        'apply' => $APPLY_URL,
    ]);
    ?>
    <script src="<?= e(repdoc_url('src/assets/js/catalog.js?v=1')) ?>" defer></script>
</body>
</html>
