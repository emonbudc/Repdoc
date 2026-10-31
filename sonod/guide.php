<?php
declare(strict_types=1);

/**
 * Repdoc — one public Bangla certificate guide.
 *
 * The guide is informational and must not imply government affiliation.
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';
require_once dirname(__DIR__) . '/src/seo.php';
require_once dirname(__DIR__) . '/src/catalog.php';
require_once dirname(__DIR__) . '/src/sonod_guides.php';
require_once __DIR__ . '/_ui.php';

$slug  = isset($_GET['slug']) && is_string($_GET['slug']) ? trim($_GET['slug']) : '';
$slug  = preg_replace('/[^a-z0-9-]/', '', strtolower($slug)) ?? '';
$guide = $slug !== '' ? repdoc_sonod_guide($slug) : null;

$HOME      = repdoc_url('/');
$LOGIN_URL = repdoc_url('public/login/');
$APPLY_URL = repdoc_url('Entrepreneur/apply/');
$HUB       = repdoc_url('sonod/');
$VERIFY    = repdoc_url('verify/');
$CERT      = repdoc_url('genaretor/certificate/');

$currentUser = current_user();
$dashUrl     = $currentUser !== null ? repdoc_panel_home() : $LOGIN_URL;
$userBalance = $currentUser !== null ? (int) user_balance((int) $currentUser['id']) : 0;

if ($guide === null) {
    http_response_code(404);
    $SEO_TITLE = 'গাইড পাওয়া যায়নি — Repdoc';
    $SEO_DESC  = 'এই সনদ গাইডটি পাওয়া যায়নি। সনদ গাইডের তালিকা থেকে প্রয়োজনীয় গাইড বেছে নিন।';
    $CANON     = repdoc_canonical('sonod/');
    ?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($SEO_TITLE) ?></title>
    <meta name="description" content="<?= e($SEO_DESC) ?>">
    <meta name="robots" content="noindex,follow">
    <link rel="icon" type="image/png" href="<?= e(repdoc_url('public/favicon.png')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/tokens.css?v=2')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/shell.css?v=2')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/catalog.css?v=4')) ?>">
    <?php repdoc_sonod_styles(); ?>
</head>
<body class="catalog-page sonod-page">
    <?php
    repdoc_catalog_sprite();
    repdoc_catalog_header([
        'home' => $HOME, 'login' => $LOGIN_URL, 'apply' => $APPLY_URL,
        'dashboard' => $dashUrl, 'user' => $currentUser, 'balance' => $userBalance, 'active' => 'sonod',
    ]);
    ?>
    <main class="sonod-wrap">
        <nav class="sonod-crumbs" aria-label="Breadcrumb"><a href="<?= e($HOME) ?>">হোম</a> › <a href="<?= e($HUB) ?>">সনদ গাইড</a></nav>
        <h1>গাইডটি পাওয়া যায়নি</h1>
        <p class="sonod-lead">ঠিকানাটি ভুল বা গাইডটি সরানো হয়েছে। সনদ গাইডের তালিকা থেকে প্রয়োজনীয় গাইড বেছে নিন।</p>
        <div class="sonod-cta"><a class="primary" href="<?= e($HUB) ?>">সব সনদ গাইড দেখুন</a></div>
    </main>
    <?php repdoc_catalog_footer(['home' => $HOME, 'login' => $LOGIN_URL, 'apply' => $APPLY_URL]); ?>
</body>
</html>
    <?php
    exit;
}

$CANON     = repdoc_canonical('sonod/' . $slug . '/');
$SEO_TITLE = (string) $guide['seo_title'];
$SEO_DESC  = (string) $guide['desc'];
$label     = (string) $guide['label'];
$updated   = '2026-10-31';
$faqs      = $guide['faqs'];
$related   = [];
foreach ((array) $guide['related'] as $relatedSlug) {
    $relatedGuide = repdoc_sonod_guide((string) $relatedSlug);
    if ($relatedGuide !== null) $related[(string) $relatedSlug] = $relatedGuide;
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
        'type'        => 'article',
        'modified'    => $updated . 'T00:00:00+06:00',
        'image'       => repdoc_canonical('src/assets/image/hero.webp'),
        'imageWidth'  => 900,
        'imageHeight' => 600,
        'imageAlt'    => $label . ' — বাংলা গাইড ও চেকলিস্ট',
        'twitterCard' => 'summary_large_image',
        'locale'      => 'bn_BD',
    ]);
    repdoc_jsonld([
        '@type'            => 'Article',
        '@id'              => $CANON . '#article',
        'headline'         => (string) $guide['title'],
        'description'      => $SEO_DESC,
        'url'              => $CANON,
        'mainEntityOfPage' => $CANON,
        'image'            => repdoc_canonical('src/assets/image/og-cover.png'),
        'inLanguage'       => 'bn',
        'datePublished'    => $updated,
        'dateModified'     => $updated,
        'author'           => ['@id' => repdoc_canonical() . '#org'],
        'publisher'        => ['@id' => repdoc_canonical() . '#org'],
        'isPartOf'         => ['@id' => repdoc_canonical() . '#website'],
        'about'            => ['@type' => 'Thing', 'name' => $label],
        'breadcrumb'       => ['@id' => $CANON . '#breadcrumb'],
    ]);
    repdoc_jsonld([
        '@type'           => 'BreadcrumbList',
        '@id'             => $CANON . '#breadcrumb',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => repdoc_canonical()],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'সনদ গাইড', 'item' => repdoc_canonical('sonod/')],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $label, 'item' => $CANON],
        ],
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
            <a href="<?= e($HOME) ?>">হোম</a> › <a href="<?= e($HUB) ?>">সনদ গাইড</a> › <span aria-current="page"><?= e($label) ?></span>
        </nav>

        <article>
            <header class="sonod-hero">
                <span class="sonod-kicker"><?= e($label) ?></span>
                <h1><?= e((string) $guide['title']) ?></h1>
                <p class="sonod-lead"><?= e((string) $guide['intro']) ?></p>
                <?php if (!empty($guide['aka'])): ?>
                    <p class="sonod-aka"><strong>যেভাবেও খোঁজা হয়:</strong> <?= e((string) $guide['aka']) ?></p>
                <?php endif; ?>
                <p class="sonod-meta"><span>আপডেট: ৩১ অক্টোবর ২০২৬</span><span>পড়ার সময়: প্রায় ৪ মিনিট</span><span>সরকারি সনদ নয় — সাধারণ তথ্য</span></p>
            </header>

            <?php repdoc_sonod_disclaimer(); ?>

            <section class="sonod-section" aria-labelledby="guide-definition">
                <h2 id="guide-definition"><?= e($label) ?> কী</h2>
                <p><?= e((string) $guide['definition']) ?></p>
            </section>

            <section class="sonod-section" aria-labelledby="guide-uses">
                <h2 id="guide-uses">কোথায় কাজে লাগে</h2>
                <ul>
                    <?php foreach ((array) $guide['uses'] as $item): ?>
                        <li><?= e((string) $item) ?></li>
                    <?php endforeach; ?>
                </ul>
            </section>

            <section class="sonod-section" aria-labelledby="guide-docs">
                <h2 id="guide-docs">সাধারণভাবে কী কী কাগজ লাগতে পারে</h2>
                <div class="sonod-panel">
                    <ul>
                        <?php foreach ((array) $guide['docs'] as $item): ?>
                            <li><?= e((string) $item) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="sonod-source">এটি সাধারণ চেকলিস্ট। অফিসভেদে অতিরিক্ত কাগজ, ফি বা ফরম্যাট চাওয়া হতে পারে—আবেদনের আগে সংশ্লিষ্ট অফিসে নিশ্চিত করুন।</p>
                </div>
            </section>

            <section class="sonod-section" aria-labelledby="guide-steps">
                <h2 id="guide-steps">আবেদনের সাধারণ ধাপ</h2>
                <ol>
                    <?php foreach ((array) $guide['steps'] as $item): ?>
                        <li><?= e((string) $item) ?></li>
                    <?php endforeach; ?>
                </ol>
            </section>

            <section class="sonod-section" aria-labelledby="guide-repdoc">
                <h2 id="guide-repdoc">Repdoc দিয়ে ড্রাফট বা প্রিন্ট কপি</h2>
                <p><?= e((string) $guide['repdoc']) ?></p>
                <div class="sonod-cta">
                    <a class="primary" href="<?= e($CERT) ?>">প্রত্যয়ন টেমপ্লেট দেখুন</a>
                    <a class="secondary" href="<?= e($VERIFY) ?>">সনদ যাচাই করুন</a>
                </div>
            </section>

            <section class="sonod-section sonod-faq" aria-labelledby="guide-faq">
                <h2 id="guide-faq">সচরাচর জিজ্ঞাসা</h2>
                <?php foreach ($faqs as $faq): ?>
                    <details>
                        <summary><?= e((string) $faq['q']) ?></summary>
                        <p><?= e((string) $faq['a']) ?></p>
                    </details>
                <?php endforeach; ?>
            </section>

            <?php if ($related !== []): ?>
                <section class="sonod-section" aria-labelledby="guide-related">
                    <h2 id="guide-related">সম্পর্কিত গাইড</h2>
                    <div class="sonod-related">
                        <?php foreach ($related as $relatedSlug => $relatedGuide): ?>
                            <a href="<?= e(repdoc_url('sonod/' . $relatedSlug . '/')) ?>"><?= e((string) $relatedGuide['label']) ?></a>
                        <?php endforeach; ?>
                        <a href="<?= e($HUB) ?>">সব সনদ গাইড</a>
                    </div>
                </section>
            <?php endif; ?>
        </article>

        <p class="sonod-source">তথ্যসূত্র: সংশ্লিষ্ট স্থানীয় সরকার প্রতিষ্ঠান ও দপ্তরের প্রকাশিত সাধারণ প্রক্রিয়া। এই গাইড কোনো সরকারি অফিসের প্রতিনিধিত্ব করে না এবং আইনি পরামর্শ নয়।</p>
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
