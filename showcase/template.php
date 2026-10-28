<?php
declare(strict_types=1);

/**
 * Repdoc — single template landing page.
 *
 * Route: /showcase/{format}/{slug}/ → showcase/template.php?type=…&slug=…
 * (see .htaccess). Public and indexable: the preview, description and file
 * facts are visible; the download bytes stay behind the hub's login gate.
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';
require_once dirname(__DIR__) . '/src/seo.php';
require_once dirname(__DIR__) . '/src/filemeta.php';
require_once dirname(__DIR__) . '/src/catalog.php';

$type   = is_string($_GET['type'] ?? null) ? strtolower(trim($_GET['type'])) : '';
$slug   = is_string($_GET['slug'] ?? null) ? strtolower(trim($_GET['slug'])) : '';
$FORMATS = repdoc_template_formats();

if (!isset($FORMATS[$type]) || $slug === '') {
    require REPDOC_ROOT . '/404.php';
    exit;
}

$row = repdoc_template_find_by_slug($type, $slug);
if ($row === null) {
    require REPDOC_ROOT . '/404.php';
    exit;
}

$meta        = $FORMATS[$type];
$title       = (string) $row['title'];
$description = (string) $row['description'];
$canonical   = (string) $row['canonical'];
$showcaseUrl = repdoc_url('showcase/');
$hubUrl      = repdoc_url($meta['hub'] . '/');
$homeUrl     = repdoc_url('/');
$loginUrl    = repdoc_url('public/login/');
$applyUrl    = repdoc_url('Entrepreneur/apply/');
$nextPath    = '/' . (string) $row['path'];

$currentUser = current_user();
$dashUrl     = $currentUser !== null ? repdoc_panel_home() : $loginUrl;
$userBalance = $currentUser !== null ? (int) user_balance((int) $currentUser['id']) : 0;

$seoTitle = repdoc_template_meta_title($title, (string) $meta['full']);
$seoDesc  = repdoc_template_meta_description($description, (string) $meta['full']);
$social   = repdoc_template_social_image($row);

$extLabel  = strtoupper((string) $row['ext']);
$mime      = repdoc_template_mime($type, (string) $row['ext']);
$sizeLabel = repdoc_catalog_human_size((int) $row['size']);
$dateLabel = repdoc_catalog_date((int) $row['mtime']);
$modified  = date('c', (int) $row['mtime']);
$steps     = repdoc_template_usage_steps($type);
$related   = repdoc_template_related($type, (string) $row['name'], 6);

$downloadUrl = $currentUser !== null
    ? repdoc_url($meta['hub'] . '/download.php') . '?type=' . rawurlencode($type) . '&file=' . rawurlencode((string) $row['name'])
    : $loginUrl . '?next=' . rawurlencode($nextPath);

$crumbFormat = $type === 'word' || $type === 'excel' || $type === 'ppt' ? 'MS Office ফাইল' : 'PSD / AI / PLP ফাইল';

$ogImage = $social['url'];
$ogWidth = $social['width'];
$ogHeight = $social['height'];
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($seoTitle) ?></title>
    <meta name="description" content="<?= e($seoDesc) ?>">
    <meta name="theme-color" content="#FFFEFB">
    <?php
    repdoc_seo_head([
        'title'       => $seoTitle,
        'description' => $seoDesc,
        'canonical'   => $canonical,
        'type'        => 'article',
        'image'       => $ogImage,
        'imageWidth'  => $ogWidth,
        'imageHeight' => $ogHeight,
        'imageAlt'    => $social['alt'],
        'twitterCard' => ($ogWidth !== null && $ogWidth >= 600) ? 'summary_large_image' : 'summary',
        'modified'    => $modified,
        'locale'      => 'bn_BD',
    ]);
    repdoc_jsonld([
        '@type'       => 'ItemPage',
        '@id'         => $canonical . '#page',
        'name'        => $seoTitle,
        'url'         => $canonical,
        'description' => $seoDesc,
        'inLanguage'  => 'bn',
        'isPartOf'    => ['@id' => repdoc_canonical() . '#website'],
        'breadcrumb'  => ['@id' => $canonical . '#breadcrumb'],
        'mainEntity'  => ['@id' => $canonical . '#document'],
    ]);
    repdoc_jsonld([
        '@type'               => 'DigitalDocument',
        '@id'                 => $canonical . '#document',
        'name'                => $title,
        'description'         => $description,
        'url'                 => $canonical,
        'encodingFormat'      => $mime,
        'fileFormat'          => $mime,
        'contentSize'         => (int) $row['size'],
        'dateModified'        => $modified,
        'thumbnailUrl'        => $ogImage,
        'image'               => $ogImage,
        'inLanguage'          => 'bn',
        'isAccessibleForFree' => false,
        'isPartOf'            => ['@id' => repdoc_canonical($meta['hub'] . '/') . '#files'],
        'provider'            => ['@id' => repdoc_canonical() . '#org'],
    ]);
    repdoc_jsonld([
        '@type'           => 'BreadcrumbList',
        '@id'             => $canonical . '#breadcrumb',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => repdoc_canonical()],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'টেমপ্লেট শোকেস', 'item' => repdoc_canonical('showcase/')],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $crumbFormat, 'item' => repdoc_canonical($meta['hub'] . '/')],
            ['@type' => 'ListItem', 'position' => 4, 'name' => $title, 'item' => $canonical],
        ],
    ]);
    ?>
    <link rel="icon" type="image/png" href="<?= e(repdoc_url('public/favicon.png')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&amp;family=Noto+Sans+Bengali:wght@400;500;600;700;800&amp;family=JetBrains+Mono:wght@400;500;600&amp;display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/tokens.css?v=2')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/shell.css?v=2')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/home.css?v=4')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/catalog.css?v=4')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/template.css?v=1')) ?>">
    <script src="<?= e(repdoc_url('src/assets/js/catalog.js?v=1')) ?>" defer></script>
</head>
<body class="catalog-page tpl-page">
    <a class="skip-link" href="#tpl-main">টেমপ্লেটের বিবরণে যান</a>
    <?php
    repdoc_catalog_sprite();
    repdoc_catalog_header([
        'home'      => $homeUrl,
        'login'     => $loginUrl,
        'apply'     => $applyUrl,
        'dashboard' => $dashUrl,
        'user'      => $currentUser,
        'balance'   => $userBalance,
        'active'    => 'showcase',
    ]);
    ?>

    <main id="tpl-main" class="tpl-main-wrap">
        <div class="container">
            <nav class="tpl-crumbs" aria-label="ব্রেডক্রাম্ব">
                <ol>
                    <li><a href="<?= e($homeUrl) ?>">হোম</a></li>
                    <li><a href="<?= e($showcaseUrl) ?>">টেমপ্লেট শোকেস</a></li>
                    <li><a href="<?= e($hubUrl) ?>"><?= e($crumbFormat) ?></a></li>
                    <li aria-current="page"><?= e($title) ?></li>
                </ol>
            </nav>

            <div class="tpl-layout">
                <article class="tpl-content">
                    <p class="tpl-kicker"><?= e((string) $meta['full']) ?> · <?= e($extLabel) ?></p>
                    <h1 class="tpl-title"><?= e($title) ?></h1>
                    <p class="tpl-lead"><?= e($description) ?></p>

                    <div class="tpl-chips" aria-label="ফাইল সংক্ষেপে">
                        <span class="tpl-chip"><?= e((string) $meta['label']) ?></span>
                        <span class="tpl-chip"><?= e($extLabel) ?></span>
                        <span class="tpl-chip"><?= e($sizeLabel) ?></span>
                        <span class="tpl-chip"><?= e($dateLabel) ?></span>
                    </div>

                    <figure class="tpl-preview<?= empty($row['thumb']) ? ' tpl-preview-art' : '' ?>" style="--tpl-soft:<?= e((string) $meta['soft']) ?>">
                        <?php if (!empty($row['thumb'])): ?>
                            <img src="<?= e((string) $row['thumb']) ?>" alt="<?= e($title) ?> — <?= e((string) $meta['full']) ?> টেমপ্লেট প্রিভিউ" width="480" height="360" fetchpriority="high" decoding="async">
                        <?php else: ?>
                            <?= repdoc_catalog_preview_art($type) ?>
                        <?php endif; ?>
                        <figcaption><?= e($title) ?> — <?= e((string) $meta['full']) ?> টেমপ্লেট</figcaption>
                    </figure>

                    <section class="tpl-section" aria-labelledby="tplAbout">
                        <h2 id="tplAbout">এই টেমপ্লেট সম্পর্কে</h2>
                        <p><?= e($description) ?></p>
                        <p>
                            <?= e($title) ?> হলো <?= e((string) $meta['full']) ?>-এর জন্য প্রস্তুত একটি <?= e($extLabel) ?> টেমপ্লেট।
                            ফাইলটি ডাউনলোড করে নিজের নাম, প্রতিষ্ঠান, তারিখ ও প্রয়োজনীয় তথ্য বসিয়ে সরাসরি ব্যবহার করতে পারবেন।
                            <?= e((string) $meta['note']) ?>
                        </p>
                    </section>

                    <section class="tpl-section" aria-labelledby="tplHow">
                        <h2 id="tplHow">কীভাবে ব্যবহার করবেন</h2>
                        <ol class="tpl-steps">
                            <?php foreach ($steps as $i => $step): ?>
                                <li>
                                    <span class="tpl-step-no" aria-hidden="true"><?= e(repdoc_catalog_bn_digits((string) ($i + 1))) ?></span>
                                    <span><?= e($step) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </section>

                    <?php if ($related !== []): ?>
                    <section class="tpl-section" aria-labelledby="tplRelated">
                        <h2 id="tplRelated">একই ধরনের আরও টেমপ্লেট</h2>
                        <ul class="tpl-related">
                            <?php foreach ($related as $item): ?>
                                <?php
                                $relatedFormat = $FORMATS[$item['type']] ?? null;
                                if ($relatedFormat === null) continue;
                                ?>
                                <li>
                                    <a href="<?= e((string) $item['href']) ?>">
                                        <span class="tpl-related-format"><?= e((string) $relatedFormat['label']) ?> · <?= e(strtoupper((string) $item['ext'])) ?></span>
                                        <strong><?= e((string) $item['title']) ?></strong>
                                        <span class="tpl-related-desc"><?= e(repdoc_template_trim((string) $item['description'], 110)) ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                    <?php endif; ?>
                </article>

                <aside class="tpl-side" aria-label="ডাউনলোড ও ফাইল তথ্য">
                    <div class="tpl-card tpl-download-card">
                        <p class="tpl-side-kicker">ডাউনলোড</p>
                        <h2>ফাইলটি নিন</h2>
                        <p class="tpl-side-text">
                            <?= $currentUser !== null
                                ? 'নিচের বাটনে ক্লিক করে ফাইলটি সরাসরি ডাউনলোড করুন।'
                                : 'ডাউনলোড করতে অপারেটর অ্যাকাউন্টে লগ ইন করুন। এক অ্যাকাউন্টেই সব টেমপ্লেট ও টুল।' ?>
                        </p>
                        <a class="btn btn-primary tpl-cta" href="<?= e($downloadUrl) ?>">
                            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#cat-i-download"/></svg>
                            <span><?= $currentUser !== null ? 'ডাউনলোড করুন' : 'লগইন করে ডাউনলোড' ?></span>
                        </a>
                        <a class="tpl-text-link" href="<?= e($hubUrl) ?>">সব <?= e((string) $meta['label']) ?> ফাইল দেখুন</a>
                        <?php if ($currentUser === null): ?>
                            <a class="tpl-text-link" href="<?= e($applyUrl) ?>">অ্যাকাউন্টের জন্য আবেদন করুন</a>
                        <?php endif; ?>
                    </div>

                    <div class="tpl-card">
                        <h2>ফাইল তথ্য</h2>
                        <dl class="tpl-facts">
                            <div><dt>ফরম্যাট</dt><dd><?= e((string) $meta['full']) ?></dd></div>
                            <div><dt>এক্সটেনশন</dt><dd><?= e($extLabel) ?></dd></div>
                            <div><dt>সাইজ</dt><dd><?= e($sizeLabel) ?></dd></div>
                            <div><dt>হালনাগাদ</dt><dd><?= e($dateLabel) ?></dd></div>
                            <div><dt>অ্যাক্সেস</dt><dd>লগ ইন প্রয়োজন</dd></div>
                        </dl>
                    </div>

                    <div class="tpl-card">
                        <h2>আরও ফরম্যাট</h2>
                        <ul class="tpl-format-links">
                            <li><a href="<?= e(repdoc_url('officefiles/')) ?>">MS Office ফাইল <span>Word · Excel · PowerPoint</span></a></li>
                            <li><a href="<?= e(repdoc_url('offlinefiles/')) ?>">ডিজাইন ফাইল <span>PSD · AI · PLP</span></a></li>
                            <li><a href="<?= e($showcaseUrl) ?>">সব টেমপ্লেট শোকেস <span><?= e(repdoc_catalog_bn_digits('204')) ?>টি ফাইল</span></a></li>
                        </ul>
                    </div>
                </aside>
            </div>
        </div>
    </main>

    <?php
    repdoc_catalog_footer([
        'home'  => $homeUrl,
        'login' => $loginUrl,
        'apply' => $applyUrl,
    ]);
    ?>
</body>
</html>
