<?php
declare(strict_types=1);

/**
 * Repdoc — important links directory.
 *
 * Public, indexable directory of useful government, education, jobs,
 * utility, health and other links. Links are managed from
 * admin/important-links.php and stored in love/important_links.json.
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';
require_once dirname(__DIR__) . '/src/seo.php';
require_once dirname(__DIR__) . '/src/catalog.php';
require_once dirname(__DIR__) . '/src/links.php';

$SEO_TITLE = 'গুরুত্বপূর্ণ লিংক — Repdoc';
$SEO_DESC  = 'সরকারি সেবা, শিক্ষা, চাকরি, ইউটিলিটি ও স্বাস্থ্যসেবার দরকারি লিংক এক জায়গায়। ক্যাটাগরি বা নাম দিয়ে খুঁজুন এবং সরাসরি নতুন ট্যাবে খুলুন।';
$CANON     = repdoc_canonical('importantlinks/');
$HOME      = repdoc_url('/');
$LOGIN_URL = repdoc_url('public/login/');
$APPLY_URL = repdoc_url('Entrepreneur/apply/');
$SHOWCASE_URL = repdoc_url('showcase/');

$currentUser = current_user();
$dashUrl     = $currentUser !== null ? repdoc_panel_home() : $LOGIN_URL;
$userBalance = $currentUser !== null ? (int) user_balance((int) $currentUser['id']) : 0;
$adminEdit   = is_admin() ? repdoc_url('admin/important-links.php') : '';

$links = array_values(array_filter(
    repdoc_links_load(),
    static fn(array $link): bool => !empty($link['valid'])
));
$categories = repdoc_link_categories();
$categoryCounts = array_fill_keys(array_keys($categories), 0);
foreach ($links as $link) {
    $categoryCounts[$link['category']] = ($categoryCounts[$link['category']] ?? 0) + 1;
}
$totalLinks = count($links);
$activeCategories = count(array_filter($categoryCounts, static fn(int $count): bool => $count > 0));
$lastUpdated = repdoc_links_last_updated();
$bn = static fn(int $value): string => repdoc_catalog_bn_digits((string) $value);

$linkItems = [];
foreach ($links as $i => $link) {
    $category = repdoc_link_category_meta((string) $link['category']);
    $host = repdoc_link_host((string) $link['url']);
    $linkItems[] = [
        'title'    => (string) $link['title'],
        'url'      => (string) $link['url'],
        'desc'     => (string) $link['desc'],
        'host'     => $host,
        'key'      => (string) $link['category'],
        'category' => $category,
        'search'   => repdoc_link_search_index($link),
        'index'    => $i,
    ];
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
        'image'       => repdoc_canonical('src/assets/image/hero.webp'),
        'imageWidth'  => 900,
        'imageHeight' => 600,
        'imageAlt'    => 'Repdoc গুরুত্বপূর্ণ লিংক ডিরেক্টরি',
        'twitterCard' => 'summary_large_image',
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
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'গুরুত্বপূর্ণ লিংক', 'item' => $CANON],
        ],
    ]);
    if ($linkItems !== []) {
        $items = [];
        foreach ($linkItems as $i => $item) {
            $items[] = [
                '@type'       => 'ListItem',
                'position'    => $i + 1,
                'name'        => $item['title'],
                'description' => $item['desc'],
                'url'         => $item['url'],
            ];
        }
        repdoc_jsonld([
            '@type'           => 'ItemList',
            '@id'             => $CANON . '#links',
            'name'            => 'Repdoc গুরুত্বপূর্ণ লিংক',
            'numberOfItems'   => $totalLinks,
            'itemListOrder'   => 'https://schema.org/ItemListUnordered',
            'itemListElement' => $items,
        ]);
    }
    ?>
    <link rel="icon" type="image/png" href="<?= e(repdoc_url('public/favicon.png')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&amp;family=Noto+Sans+Bengali:wght@400;500;600;700;800&amp;family=JetBrains+Mono:wght@400;500;600&amp;display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/tokens.css?v=2')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/shell.css?v=2')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/home.css?v=4')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/catalog.css?v=3')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/links.css?v=1')) ?>">
</head>
<body class="catalog-page cat-importantlinks">
    <a class="skip-link" href="#library">লিংক লাইব্রেরিতে যান</a>
    <?php
    repdoc_catalog_sprite();
    repdoc_catalog_header([
        'home'      => $HOME,
        'login'     => $LOGIN_URL,
        'apply'     => $APPLY_URL,
        'dashboard' => $dashUrl,
        'user'      => $currentUser,
        'balance'   => $userBalance,
        'active'    => 'importantlinks',
    ]);
    ?>

    <main>
        <section class="cat-hero link-hero" aria-labelledby="linkHeroTitle">
            <div class="container cat-hero-inner">
                <div class="cat-hero-copy">
                    <p class="reg-kicker cat-kicker"><span class="reg-no" aria-hidden="true">Links</span>দরকারি লিংক ডিরেক্টরি</p>
                    <h1 class="reg-title cat-title" id="linkHeroTitle">গুরুত্বপূর্ণ লিংক<span class="reg-title-accent">এক জায়গায়</span></h1>
                    <span class="reg-rule cat-rule" aria-hidden="true"></span>
                    <p class="cat-lead">সরকারি সেবা, শিক্ষা, চাকরি, ইউটিলিটি ও স্বাস্থ্যসেবার দরকারি ঠিকানা — ক্যাটাগরি বেছে নিন, নাম দিয়ে খুঁজুন, এক ক্লিকে নতুন ট্যাবে খুলুন।</p>

                    <form class="cat-search" id="catSearchForm" role="search">
                        <svg class="icon cat-search-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#cat-i-search"/></svg>
                        <label class="sr-only" for="catFileSearch">লিংকের নাম, ক্যাটাগরি বা ঠিকানা লিখুন</label>
                        <input id="catFileSearch" type="search" placeholder="লিংকের নাম, ক্যাটাগরি বা ঠিকানা লিখুন…" autocomplete="off" enterkeyhint="search"<?= $totalLinks === 0 ? ' disabled' : '' ?>>
                        <button type="button" class="cat-search-clear" id="catSearchClear" aria-label="সার্চ মুছুন" hidden>
                            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#cat-i-close"/></svg>
                        </button>
                        <button type="submit" class="btn btn-ledger cat-search-btn"<?= $totalLinks === 0 ? ' disabled' : '' ?>>খুঁজুন</button>
                    </form>

                    <div class="cat-hero-stats" aria-label="লিংক ডিরেক্টরির সারসংক্ষেপ">
                        <div class="cat-hero-stat"><strong><?= e($bn($totalLinks)) ?></strong><span>লিংক</span></div>
                        <div class="cat-hero-stat"><strong><?= e($bn($activeCategories)) ?></strong><span>ক্যাটাগরি</span></div>
                        <div class="cat-hero-stat"><strong><?= e(repdoc_catalog_date($lastUpdated)) ?></strong><span>হালনাগাদ</span></div>
                    </div>

                    <?php if ($totalLinks === 0 && $adminEdit !== ''): ?>
                        <p class="cat-hero-note">
                            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#cat-i-link"/></svg>
                            ডিরেক্টরি এখন খালি — <a href="<?= e($adminEdit) ?>">অ্যাডমিন প্যানেল থেকে প্রথম লিংক যোগ করুন</a>।
                        </p>
                    <?php else: ?>
                        <p class="cat-hero-note">
                            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#cat-i-shield"/></svg>
                            সব লিংক নতুন ট্যাবে খুলবে — ঠিকানা যাচাই করে ব্যবহার করুন।
                        </p>
                    <?php endif; ?>
                </div>

                <figure class="cat-hero-art link-hero-art">
                    <span class="cat-art-frame link-board-frame">
                        <span class="link-board" aria-hidden="true">
                            <span class="link-board-bar"><i></i><i></i><i></i><b>repdoc · links</b></span>
                            <span class="link-board-search">
                                <svg class="icon" viewBox="0 0 24 24"><use href="#cat-i-search"/></svg>
                                <span>গুরুত্বপূর্ণ লিংক খুঁজুন…</span>
                            </span>
                            <span class="link-board-list">
                                <span class="link-board-row">
                                    <span class="link-board-mark" style="--mark:#0E6B4A;--mark-soft:#EAF6EF">স</span>
                                    <span class="link-board-text"><span class="link-board-name">সরকারি সেবা</span><i></i><i></i></span>
                                    <span class="link-board-go">↗</span>
                                </span>
                                <span class="link-board-row">
                                    <span class="link-board-mark" style="--mark:#1D4ED8;--mark-soft:#E9EFFD">শ</span>
                                    <span class="link-board-text"><span class="link-board-name">শিক্ষা</span><i></i><i></i></span>
                                    <span class="link-board-go">↗</span>
                                </span>
                                <span class="link-board-row">
                                    <span class="link-board-mark" style="--mark:#B45309;--mark-soft:#FDF3E3">চ</span>
                                    <span class="link-board-text"><span class="link-board-name">চাকরি</span><i></i><i></i></span>
                                    <span class="link-board-go">↗</span>
                                </span>
                                <span class="link-board-row">
                                    <span class="link-board-mark" style="--mark:#7C3AED;--mark-soft:#F2EEFD">ই</span>
                                    <span class="link-board-text"><span class="link-board-name">ইউটিলিটি</span><i></i><i></i></span>
                                    <span class="link-board-go">↗</span>
                                </span>
                            </span>
                            <span class="link-board-stamp"><svg class="icon" viewBox="0 0 24 24"><use href="#cat-i-check"/></svg>ভেরিফায়েড লিংক</span>
                        </span>
                    </span>
                    <figcaption class="cat-art-tag">খুঁজুন · যাচাই করুন · নতুন ট্যাবে খুলুন</figcaption>
                </figure>
            </div>
        </section>

        <section class="cat-formats" aria-labelledby="linkFormatsHeading">
            <h2 class="sr-only" id="linkFormatsHeading">লিংকের ক্যাটাগরি বেছে নিন</h2>
            <div class="container cat-format-grid">
                <button type="button" class="cat-format is-active" data-filter="all" aria-pressed="true"<?= $totalLinks === 0 ? ' disabled' : '' ?>>
                    <span class="cat-format-logo cat-format-all" aria-hidden="true">
                        <svg class="icon" viewBox="0 0 24 24"><use href="#cat-i-link"/></svg>
                    </span>
                    <span class="cat-format-text"><strong>সব লিংক</strong><small>সব ক্যাটাগরি</small></span>
                    <span class="cat-format-count"><?= e($bn($totalLinks)) ?></span>
                </button>
                <?php foreach ($categories as $key => $category): ?>
                    <?php $count = $categoryCounts[$key] ?? 0; ?>
                    <button type="button" class="cat-format" data-filter="<?= e($key) ?>" aria-pressed="false"<?= $totalLinks === 0 || $count === 0 ? ' disabled' : '' ?>>
                        <span class="cat-format-logo link-filter-mark" style="background:<?= e($category['soft']) ?>;color:<?= e($category['color']) ?>" aria-hidden="true"><?= e(mb_substr($category['label'], 0, 1, 'UTF-8')) ?></span>
                        <span class="cat-format-text"><strong><?= e($category['label']) ?></strong><small><?= e($category['sub']) ?></small></span>
                        <span class="cat-format-count"><?= e($bn($count)) ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="cat-library" id="library" aria-labelledby="linkLibraryHeading">
            <div class="container cat-layout">
                <div class="cat-main">
                    <div class="cat-toolbar">
                        <div>
                            <p class="kicker">দরকারি ঠিকানা</p>
                            <h2 id="linkLibraryHeading">লিংক লাইব্রেরি</h2>
                            <p class="cat-result" id="catResult" role="status" aria-live="polite">
                                <strong id="catResultCount"><?= e($bn($totalLinks)) ?></strong>টি লিংক দেখানো হচ্ছে
                            </p>
                        </div>
                        <div class="cat-toolbar-actions">
                            <span class="link-toolbar-hint">
                                <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#cat-i-external"/></svg>
                                নতুন ট্যাবে খুলবে
                            </span>
                            <button type="button" class="cat-reset" id="catReset" hidden>
                                <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#cat-i-close"/></svg>
                                রিসেট
                            </button>
                        </div>
                    </div>

                    <?php if ($linkItems === []): ?>
                        <div class="cat-empty link-empty" role="status">
                            <div class="cat-empty-logos link-empty-logos" aria-hidden="true">
                                <span class="cat-format-logo link-filter-mark" style="background:#EAF6EF;color:#0E6B4A">স</span>
                                <span class="cat-format-logo link-filter-mark" style="background:#E9EFFD;color:#1D4ED8">শ</span>
                                <span class="cat-format-logo link-filter-mark" style="background:#FDF3E3;color:#B45309">চ</span>
                            </div>
                            <h3>লিংক ডিরেক্টরি এখন খালি</h3>
                            <p>অ্যাডমিন প্যানেল থেকে দরকারি লিংক যোগ করা হলে এখানেই ক্যাটাগরি অনুযায়ী দেখা যাবে।</p>
                            <div class="cat-empty-actions">
                                <?php if ($adminEdit !== ''): ?>
                                    <a class="btn btn-primary" href="<?= e($adminEdit) ?>">লিংক যোগ করুন</a>
                                <?php endif; ?>
                                <a class="btn btn-secondary" href="<?= e($HOME) ?>#tools">সব টুল দেখুন</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="cat-file-grid link-grid" id="catFileGrid">
                            <?php foreach ($linkItems as $item): ?>
                                <a class="cat-file-card link-card"
                                   href="<?= e($item['url']) ?>"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   data-type="<?= e($item['key']) ?>"
                                   data-group="<?= e($item['key']) ?>"
                                   data-search="<?= e($item['search']) ?>"
                                   data-name="<?= e(mb_strtolower($item['title'], 'UTF-8')) ?>"
                                   style="--i:<?= (int) ($item['index'] % 12) ?>;--link-color:<?= e($item['category']['color']) ?>;--link-soft:<?= e($item['category']['soft']) ?>"
                                   aria-label="<?= e($item['title'] . ' — ' . $item['host'] . ', নতুন ট্যাবে খুলবে') ?>">
                                    <span class="link-card-top">
                                        <span class="link-cat-chip">
                                            <span class="link-chip-dot" aria-hidden="true"></span>
                                            <?= e($item['category']['label']) ?>
                                        </span>
                                        <span class="link-external" aria-hidden="true">
                                            <svg class="icon" viewBox="0 0 24 24"><use href="#cat-i-external"/></svg>
                                        </span>
                                    </span>
                                    <span class="link-card-title"><?= e($item['title']) ?></span>
                                    <span class="link-host">
                                        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#cat-i-link"/></svg>
                                        <span><?= e($item['host']) ?></span>
                                    </span>
                                    <span class="link-card-desc"><?= e($item['desc'] !== '' ? $item['desc'] : 'বিস্তারিত দেখতে লিংকটি খুলুন।') ?></span>
                                    <span class="link-card-foot">
                                        <span class="link-open">লিংক খুলুন <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#cat-i-arrow"/></svg></span>
                                        <span class="link-newtab">নতুন ট্যাব</span>
                                    </span>
                                </a>
                            <?php endforeach; ?>
                        </div>

                        <div class="cat-noresult" id="catNoResult" role="status" hidden>
                            <img class="cat-noresult-art" src="<?= e(repdoc_url('src/assets/image/boyfromwhole.png')) ?>" alt="" width="180" height="180" loading="lazy" decoding="async">
                            <h3>কোনো লিংক পাওয়া যায়নি</h3>
                            <p>অন্য নাম, ক্যাটাগরি বা কীওয়ার্ড দিয়ে আবার খুঁজুন।</p>
                            <button type="button" class="btn btn-secondary" id="catNoReset">সব লিংক দেখুন</button>
                        </div>
                    <?php endif; ?>
                </div>

                <aside class="cat-sidebar" aria-label="লিংক ডিরেক্টরি তথ্য">
                    <div class="cat-side-card">
                        <h3 class="cat-side-title">
                            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#cat-i-link"/></svg>
                            ডিরেক্টরি এক নজরে
                        </h3>
                        <ul class="cat-stat-list">
                            <li><span>মোট লিংক</span><strong><?= e($bn($totalLinks)) ?>টি</strong></li>
                            <li><span>সক্রিয় ক্যাটাগরি</span><strong><?= e($bn($activeCategories)) ?>টি</strong></li>
                            <li><span>সর্বশেষ হালনাগাদ</span><strong><?= e(repdoc_catalog_date($lastUpdated)) ?></strong></li>
                            <li><span>খোলার ধরন</span><strong>নতুন ট্যাব</strong></li>
                        </ul>
                    </div>

                    <div class="cat-side-card">
                        <h3 class="cat-side-title">
                            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#cat-i-grid"/></svg>
                            ক্যাটাগরি অনুযায়ী
                        </h3>
                        <div class="cat-side-formats">
                            <button type="button" class="cat-side-format is-active" data-filter="all" aria-pressed="true"<?= $totalLinks === 0 ? ' disabled' : '' ?>>
                                <span class="link-side-dot" style="background:var(--primary)" aria-hidden="true"></span>
                                <span class="cat-side-format-name">সব লিংক</span>
                                <span class="cat-side-count"><?= e($bn($totalLinks)) ?></span>
                            </button>
                            <?php foreach ($categories as $key => $category): ?>
                                <?php $count = $categoryCounts[$key] ?? 0; ?>
                                <button type="button" class="cat-side-format" data-filter="<?= e($key) ?>" aria-pressed="false"<?= $totalLinks === 0 || $count === 0 ? ' disabled' : '' ?>>
                                    <span class="link-side-dot" style="background:<?= e($category['color']) ?>" aria-hidden="true"></span>
                                    <span class="cat-side-format-name"><?= e($category['label']) ?></span>
                                    <span class="cat-side-count"><?= e($bn($count)) ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="cat-side-card cat-ad" aria-label="বিজ্ঞাপনের স্থান">
                        <span>Ad Space</span>
                        <small>বিজ্ঞাপনের জন্য যোগাযোগ করুন</small>
                    </div>

                    <div class="cat-side-card cat-join">
                        <span class="cat-join-icon" aria-hidden="true">
                            <svg class="icon" viewBox="0 0 24 24"><use href="#cat-i-link"/></svg>
                        </span>
                        <h3>লিংক হালনাগাদ দরকার?</h3>
                        <p>কোনো লিংক কাজ না করলে বা নতুন দরকারি লিংক যোগ করতে চাইলে আমাদের জানান।</p>
                        <a class="btn btn-primary" href="https://t.me/Repdocbdofficial" target="_blank" rel="noopener noreferrer">টেলিগ্রামে জানান</a>
                        <a class="cat-join-link" href="<?= e($HOME) ?>#tools">সব টুল দেখুন</a>
                    </div>
                </aside>
            </div>
        </section>

        <section class="cat-guide" id="guide" aria-labelledby="linkGuideHeading">
            <div class="container">
                <div class="section-head cat-guide-head">
                    <div>
                        <p class="kicker">প্রক্রিয়া</p>
                        <h2 id="linkGuideHeading">লিংক ব্যবহার করবেন যেভাবে</h2>
                        <p class="section-sub">তিন ধাপে দরকারি ঠিকানাটি খুঁজে নতুন ট্যাবে খুলুন।</p>
                    </div>
                </div>
                <ol class="cat-steps">
                    <li class="cat-step">
                        <div class="cat-step-top">
                            <span class="cat-step-num" aria-hidden="true">১</span>
                            <span class="cat-step-icon" aria-hidden="true">
                                <svg class="icon" viewBox="0 0 24 24"><use href="#cat-i-search"/></svg>
                            </span>
                        </div>
                        <h3>ক্যাটাগরি বা নাম দিয়ে খুঁজুন</h3>
                        <p>উপরের ক্যাটাগরি বাটনে ক্লিক করুন অথবা সার্চ বক্সে সেবার নাম বা কীওয়ার্ড লিখুন।</p>
                    </li>
                    <li class="cat-step">
                        <div class="cat-step-top">
                            <span class="cat-step-num" aria-hidden="true">২</span>
                            <span class="cat-step-icon" aria-hidden="true">
                                <svg class="icon" viewBox="0 0 24 24"><use href="#cat-i-shield"/></svg>
                            </span>
                        </div>
                        <h3>ঠিকানা যাচাই করুন</h3>
                        <p>লিংক খোলার আগে ডোমেইন ও সেবার নাম মিলিয়ে নিন। প্রযোজ্য ক্ষেত্রে সরকারি সাইটের অফিসিয়াল ঠিকানা ব্যবহার করুন।</p>
                    </li>
                    <li class="cat-step">
                        <div class="cat-step-top">
                            <span class="cat-step-num" aria-hidden="true">৩</span>
                            <span class="cat-step-icon" aria-hidden="true">
                                <svg class="icon" viewBox="0 0 24 24"><use href="#cat-i-external"/></svg>
                            </span>
                        </div>
                        <h3>নতুন ট্যাবে খুলুন</h3>
                        <p>লিংকে ক্লিক করলে নতুন ট্যাবে খুলবে — Repdoc-এর বর্তমান পেজটি আপনার কাছে খোলা থাকবে।</p>
                    </li>
                </ol>
            </div>
        </section>

        <section class="cat-closing-band" aria-labelledby="linkClosingHeading">
            <div class="container">
                <div class="cat-closing">
                    <div class="cat-closing-copy">
                        <p class="kicker">দরকারি লিংক, এক জায়গায়</p>
                        <h2 id="linkClosingHeading">ঠিকানা খুঁজতে আর সময় নষ্ট নয়</h2>
                        <p>সরকারি সেবা থেকে ইউটিলিটি — দরকারি সব লিংক এক ডিরেক্টরিতে। পাশাপাশি ব্যবহার করুন Repdoc-এর ফাইল ও ডকুমেন্ট টুলকিট।</p>
                    </div>
                    <div class="cat-closing-actions">
                        <a class="btn btn-ledger" href="<?= e($HOME) ?>#tools">সব টুল দেখুন</a>
                        <a class="btn btn-ledger-outline" href="<?= e($SHOWCASE_URL) ?>">টেমপ্লেট শোকেস</a>
                    </div>
                </div>
            </div>
        </section>
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
