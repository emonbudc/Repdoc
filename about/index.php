<?php
declare(strict_types=1);

/**
 * Repdoc — dedicated About page (about/).
 *
 * Public, indexable page. Explains what Repdoc is, who it is for, how the
 * toolkit works, and the independent/non-government disclaimer. Reuses the
 * shared catalogue chrome so the header, footer and visual language stay
 * identical to the rest of the public site.
 */

require_once dirname(__DIR__) . '/src/bootstrap.php';
require_once dirname(__DIR__) . '/src/seo.php';
require_once dirname(__DIR__) . '/src/catalog.php';
require_once dirname(__DIR__) . '/src/links.php';

$SEO_TITLE = 'সম্পর্কে — Repdoc';
$SEO_DESC  = 'Repdoc কী, কাদের জন্য এবং কীভাবে কাজ করে জানুন। বাংলাদেশের কম্পিউটার অপারেটরদের জন্য স্বতন্ত্র ডকুমেন্ট টুলকিট — ফরম পূরণ করে প্রত্যয়ন, সনদ, সিভি ও দরকারি ফাইল তৈরি করুন।';

$CANON     = repdoc_canonical('about/');
$HOME      = repdoc_url('/');
$LOGIN_URL = repdoc_url('public/login/');
$APPLY_URL = repdoc_url('Entrepreneur/apply/');

$currentUser = current_user();
$dashUrl     = $currentUser !== null ? repdoc_panel_home() : $LOGIN_URL;
$userBalance = $currentUser !== null ? (int) user_balance((int) $currentUser['id']) : 0;

$linkCount = 0;
foreach (repdoc_links_load() as $link) {
    if (!empty($link['valid'])) $linkCount++;
}
$bn = static fn(int $value): string => repdoc_catalog_bn_digits((string) $value);

$faqs = [
    [
        'q' => 'Repdoc কি কোনো সরকারি ওয়েবসাইট?',
        'a' => 'না। Repdoc একটি স্বতন্ত্র ডকুমেন্ট-টেমপ্লেট টুলকিট, সরকারি পোর্টাল নয়। সরকারি ফি, আবেদন বা সনদ সংক্রান্ত কোনো প্রক্রিয়ার জন্য সংশ্লিষ্ট সরকারি দপ্তরের নির্দেশনা ও ওয়েবসাইট অনুসরণ করুন।',
    ],
    [
        'q' => 'সব টুল ব্যবহার করতে কি টাকা লাগে?',
        'a' => 'টেমপ্লেট দেখতে এবং গুরুত্বপূর্ণ লিংক ব্যবহার করতে অ্যাকাউন্ট বা পেমেন্ট লাগে না। ডাউনলোড ও ডকুমেন্ট তৈরির জন্য অপারেটর অ্যাকাউন্টে লগ ইন করতে হয়; কিছু টুলের জন্য ব্যালেন্স প্রয়োজন হতে পারে, আবার অনেক টুল ফ্রি।',
    ],
    [
        'q' => 'এক অ্যাকাউন্টে কী কী পাওয়া যায়?',
        'a' => 'একটি অপারেটর অ্যাকাউন্টে সব ডকুমেন্ট টুল, সব টেমপ্লেট ফরম্যাট (Word, Excel, PowerPoint, PSD, AI, PLP) এবং গুরুত্বপূর্ণ লিংক ডিরেক্টরি — এক লগইনেই ব্যবহার করা যায়।',
    ],
    [
        'q' => 'আমার তথ্য কীভাবে সংরক্ষণ করা হয়?',
        'a' => 'ফরমের তথ্য সার্ভারে সংরক্ষিত হতে পারে এবং পাসওয়ার্ড হ্যাশ করে রাখা হয়, প্লেইন টেক্সটে নয়। বিস্তারিত জানতে গোপনীয়তা নীতি পড়ুন।',
    ],
    [
        'q' => 'নতুন অ্যাকাউন্ট কীভাবে খুলব?',
        'a' => 'অপারেটর অ্যাকাউন্টের জন্য অনলাইনে আবেদন করুন। অনুমোদনের পর লগ ইন করে সব টুল, টেমপ্লেট ও সুবিধা ব্যবহার করতে পারবেন।',
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
        'imageAlt'    => 'Repdoc ডকুমেন্ট টুলকিট',
        'locale'      => 'bn_BD',
    ]);
    repdoc_jsonld([
        '@type'       => 'AboutPage',
        '@id'         => $CANON . '#page',
        'name'        => $SEO_TITLE,
        'url'         => $CANON,
        'description' => $SEO_DESC,
        'inLanguage'  => 'bn',
        'isPartOf'    => ['@id' => repdoc_canonical() . '#website'],
        'about'       => [
            '@type'       => 'Organization',
            'name'        => 'Repdoc',
            'url'         => repdoc_canonical(),
            'logo'        => repdoc_canonical('src/assets/image/logo.png'),
            'description' => 'বাংলাদেশের কম্পিউটার অপারেটরদের জন্য স্বতন্ত্র ডকুমেন্ট-টেমপ্লেট টুলকিট।',
            'areaServed'  => ['@type' => 'Country', 'name' => 'Bangladesh'],
        ],
        'breadcrumb'  => ['@id' => $CANON . '#breadcrumb'],
    ]);
    repdoc_jsonld([
        '@type'           => 'BreadcrumbList',
        '@id'             => $CANON . '#breadcrumb',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => repdoc_canonical()],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'সম্পর্কে', 'item' => $CANON],
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
    <link rel="preload" as="image" href="<?= e(repdoc_url('src/assets/image/hero.webp')) ?>" fetchpriority="high">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&amp;family=Noto+Sans+Bengali:wght@400;500;600;700;800&amp;family=JetBrains+Mono:wght@400;500;600&amp;display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/tokens.css?v=2')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/shell.css?v=2')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/home.css?v=4')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/catalog.css?v=3')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/about.css?v=1')) ?>">
</head>
<body class="catalog-page cat-about">
    <a class="skip-link" href="#story">আমাদের গল্পে যান</a>
    <?php
    repdoc_catalog_sprite();
    repdoc_catalog_header([
        'home'      => $HOME,
        'login'     => $LOGIN_URL,
        'apply'     => $APPLY_URL,
        'dashboard' => $dashUrl,
        'user'      => $currentUser,
        'balance'   => $userBalance,
        'active'    => 'about',
    ]);
    ?>

    <main>
        <section class="cat-hero about-hero" aria-labelledby="aboutHeroTitle">
            <div class="container cat-hero-inner">
                <div class="cat-hero-copy">
                    <p class="reg-kicker cat-kicker"><span class="reg-no" aria-hidden="true">About</span>আমাদের সম্পর্কে</p>
                    <h1 class="reg-title cat-title" id="aboutHeroTitle">ডকুমেন্টের কাজ<span class="reg-title-accent">এখন আর ঝামেলার নয়</span></h1>
                    <span class="reg-rule cat-rule" aria-hidden="true"></span>
                    <p class="cat-lead">
                        Repdoc বাংলাদেশের কম্পিউটার অপারেটর, দোকান ও ছোট ব্যবসার জন্য একটি স্বতন্ত্র
                        ডকুমেন্ট টুলকিট। ফরম পূরণ করেই প্রত্যয়ন, সনদ, সিভি ও দরকারি ফাইল তৈরি করুন
                        Word, Excel ও PDF-এ — সঙ্গে রেডি টেমপ্লেট আর দরকারি লিংকের ডিরেক্টরি।
                    </p>

                    <div class="cat-hero-stats" role="list" aria-label="Repdoc এক নজরে">
                        <div class="cat-hero-stat" role="listitem">
                            <strong><?= e($bn(7)) ?>+</strong>
                            <span>প্রধান টুল</span>
                        </div>
                        <div class="cat-hero-stat" role="listitem">
                            <strong><?= e($bn(6)) ?></strong>
                            <span>ফাইল ফরম্যাট</span>
                        </div>
                        <div class="cat-hero-stat" role="listitem">
                            <strong><?= e($bn($linkCount)) ?></strong>
                            <span>দরকারি লিংক</span>
                        </div>
                        <div class="cat-hero-stat" role="listitem">
                            <strong><?= e($bn(1)) ?></strong>
                            <span>অ্যাকাউন্টেই সব</span>
                        </div>
                    </div>

                    <p class="cat-hero-note">
                        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#cat-i-shield"/></svg>
                        স্বতন্ত্র টুলস সাইট — সরকারি পোর্টাল নয়।
                    </p>
                </div>

                <figure class="cat-hero-art about-hero-figure">
                    <span class="cat-art-frame">
                        <img src="<?= e(repdoc_url('src/assets/image/hero.webp')) ?>" alt="Repdoc-এর ডকুমেন্ট টুলকিট — ফরম, সনদ ও প্রত্যয়ন" width="900" height="600" fetchpriority="high" decoding="async">
                    </span>
                    <span class="cat-art-tag">প্রস্তুত · প্রিন্ট · ডেলিভারি</span>
                </figure>
            </div>
        </section>

        <section class="section" id="story" aria-labelledby="storyHeading">
            <div class="container">
                <div class="about-story">
                    <div class="about-story-copy">
                        <p class="kicker">আমাদের গল্প</p>
                        <h2 id="storyHeading">কম সময়ে, বেশি কাজ</h2>
                        <p>
                            দোকানের কাগজের স্তূপ, একই তথ্য বারবার টাইপ করা, প্রিন্টারের সামনে অপেক্ষা —
                            ডকুমেন্টের কাজে সবচেয়ে বেশি সময় নষ্ট হয় এই পুনরাবৃত্তিতে। Repdoc শুরু
                            হয়েছিল সেই ঝামেলা কমানোর জন্য: একবার তথ্য দিন, পছন্দের টেমপ্লেট বেছে নিন,
                            আর ফাইল প্রস্তুত।
                        </p>
                        <p>
                            আমরা চেয়েছি টুলগুলো হোক সহজ, দ্রুত এবং বাংলাদেশের দৈনন্দিন কাজের সঙ্গে
                            মানানসই। তাই টেমপ্লেটগুলো বাংলা-বান্ধব, মোবাইলেও ব্যবহারযোগ্য, আর সব
                            সুবিধা এক অ্যাকাউন্টে — বাড়তি সফটওয়্যার বা জটিল সেটআপ ছাড়াই।
                        </p>
                        <ul class="about-points" aria-label="Repdoc-এর বৈশিষ্ট্য">
                            <li><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#cat-i-check"/></svg>বাংলা-বান্ধব টেমপ্লেট</li>
                            <li><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#cat-i-check"/></svg>মোবাইলে ব্যবহারযোগ্য</li>
                            <li><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#cat-i-check"/></svg>এক অ্যাকাউন্টে সব</li>
                        </ul>
                    </div>

                    <figure class="about-story-figure">
                        <span class="about-shot">
                            <img src="<?= e(repdoc_url('src/assets/image/about1.webp')) ?>" alt="এলোমেলো কাগজের স্তূপের পাশে গোছানো ডিজিটাল ডকুমেন্ট" width="760" height="316" loading="lazy" decoding="async">
                        </span>
                        <figcaption>এলোমেলো কাগজ থেকে গোছানো ফাইল</figcaption>
                    </figure>
                </div>
            </div>
        </section>

        <section class="section section-alt" id="what-we-do" aria-labelledby="whatHeading">
            <div class="container">
                <div class="section-head">
                    <div>
                        <p class="kicker">আমরা কী করি</p>
                        <h2 id="whatHeading">এক টুলকিট, তিন ধরনের সুবিধা</h2>
                        <p class="section-sub">ডকুমেন্ট তৈরি, রেডি টেমপ্লেট ডাউনলোড আর দরকারি ওয়েবসাইট — সব এক জায়গায় সাজানো।</p>
                    </div>
                </div>

                <div class="about-do-grid">
                    <article class="about-do-card">
                        <span class="about-do-icon" aria-hidden="true">
                            <svg class="icon" viewBox="0 0 24 24"><use href="#cat-i-user"/></svg>
                        </span>
                        <h3>ডকুমেন্ট জেনারেটর</h3>
                        <p>প্রত্যয়ন, সনদ, সিভি, জন্ম ও এনআইডি ফরম — ফরম পূরণ করে সরাসরি Word ও PDF আকারে ডাউনলোড করুন।</p>
                        <a class="about-do-link" href="<?= e($HOME) ?>#tools">
                            টুল দেখুন
                            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#cat-i-arrow"/></svg>
                        </a>
                    </article>

                    <article class="about-do-card">
                        <span class="about-do-icon" aria-hidden="true">
                            <svg class="icon" viewBox="0 0 24 24"><use href="#cat-i-file"/></svg>
                        </span>
                        <h3>রেডি টেমপ্লেট লাইব্রেরি</h3>
                        <p>Word, Excel, PowerPoint, PSD, AI ও PLP — অফিস, ব্যবসা ও ডিজাইনের জন্য প্রস্তুত টেমপ্লেট, প্রিভিউসহ।</p>
                        <a class="about-do-link" href="<?= e(repdoc_url('showcase/')) ?>">
                            শোকেস দেখুন
                            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#cat-i-arrow"/></svg>
                        </a>
                    </article>

                    <article class="about-do-card">
                        <span class="about-do-icon" aria-hidden="true">
                            <svg class="icon" viewBox="0 0 24 24"><use href="#cat-i-link"/></svg>
                        </span>
                        <h3>গুরুত্বপূর্ণ লিংক ডিরেক্টরি</h3>
                        <p>সরকারি সেবা, শিক্ষা, চাকরি, ইউটিলিটি ও স্বাস্থ্যসেবার দরকারি ওয়েবসাইট — ক্যাটাগরি করে এক জায়গায়।</p>
                        <a class="about-do-link" href="<?= e(repdoc_url('importantlinks/')) ?>">
                            লিংক দেখুন
                            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#cat-i-arrow"/></svg>
                        </a>
                    </article>
                </div>
            </div>
        </section>

        <section class="section" id="how" aria-labelledby="howHeading">
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
                            <img src="<?= e(repdoc_url('src/assets/image/step1.webp')) ?>" alt="ফোন থেকে ডকুমেন্ট টেমপ্লেট বেছে নিচ্ছেন একজন অপারেটর" width="675" height="567" loading="lazy" decoding="async">
                        </figure>
                        <span class="step-num" aria-hidden="true">১</span>
                        <h3>টুল বা টেমপ্লেট বেছে নিন</h3>
                        <p>দরকারি ডকুমেন্টের টুল খুলুন, অথবা টেমপ্লেট শোকেস থেকে পছন্দের ফরম্যাট বেছে নিন।</p>
                    </li>
                    <li class="how-card">
                        <figure class="how-figure">
                            <img src="<?= e(repdoc_url('src/assets/image/step2.webp')) ?>" alt="ফরমে তথ্য পূরণ ও যাচাইয়ের ধাপ" width="760" height="1266" loading="lazy" decoding="async">
                        </figure>
                        <span class="step-num" aria-hidden="true">২</span>
                        <h3>তথ্য দিন</h3>
                        <p>ফরমে নাম, ঠিকানা ও প্রয়োজনীয় তথ্য পূরণ করুন — লাইভ প্রিভিউয়ে মিলিয়ে নিন।</p>
                    </li>
                    <li class="how-card">
                        <figure class="how-figure">
                            <img src="<?= e(repdoc_url('src/assets/image/step3.webp')) ?>" alt="প্রিন্টার ও ল্যাপটপে Word ও PDF ডকুমেন্ট তৈরি" width="760" height="1026" loading="lazy" decoding="async">
                        </figure>
                        <span class="step-num" aria-hidden="true">৩</span>
                        <h3>ডাউনলোড করুন</h3>
                        <p>Word ও PDF আকারে ফাইল নিন — প্রিন্ট করুন, ডেলিভারি দিন, আর কাজ এগিয়ে নিন।</p>
                    </li>
                </ol>
            </div>
        </section>

        <section class="section section-alt" aria-labelledby="whyHeading">
            <div class="container">
                <div class="section-head">
                    <div>
                        <p class="kicker">আস্থা</p>
                        <h2 id="whyHeading">কেন Repdoc</h2>
                    </div>
                </div>
                <figure class="why-banner">
                    <img src="<?= e(repdoc_url('src/assets/image/why-us.webp')) ?>" alt="PDF, Word, Excel, PSD, AI ও PLP ফরম্যাট এবং Repdoc-এর মোবাইল ও ডেস্কটপ ইন্টারফেস" width="1264" height="519" loading="lazy" decoding="async">
                </figure>
                <ul class="trust-strip">
                    <li class="trust-tile">
                        <span class="trust-icon" aria-hidden="true">
                            <svg class="icon" viewBox="0 0 24 24"><use href="#cat-i-grid"/></svg>
                        </span>
                        <span><strong>সব ফরম্যাট এক জায়গায়</strong>Word, Excel, PowerPoint, PSD, AI ও PLP — রেডি-টু-ইউজ থেকে প্রিন্ট-রেডি, সব টেমপ্লেট।</span>
                    </li>
                    <li class="trust-tile">
                        <span class="trust-icon" aria-hidden="true">
                            <svg class="icon" viewBox="0 0 24 24"><use href="#cat-i-shield"/></svg>
                        </span>
                        <span><strong>এক অ্যাকাউন্টেই সব</strong>একটি লগইনে সব টুল, সব টেমপ্লেট আর দরকারি লিংক — আলাদা সাবস্ক্রিপশনের ঝামেলা নেই।</span>
                    </li>
                    <li class="trust-tile">
                        <span class="trust-icon" aria-hidden="true">
                            <svg class="icon" viewBox="0 0 24 24"><use href="#cat-i-mobile"/></svg>
                        </span>
                        <span><strong>যেকোনো ডিভাইসে</strong>ফোন, ট্যাবলেট বা ডেস্কটপ — যেখান থেকে খুশি কাজ করুন, ফাইল একই থাকে।</span>
                    </li>
                </ul>
            </div>
        </section>

        <section class="section" aria-labelledby="audienceHeading">
            <div class="container">
                <div class="section-head">
                    <div>
                        <p class="kicker">কাদের জন্য</p>
                        <h2 id="audienceHeading">যারা প্রতিদিন ডকুমেন্ট নিয়ে কাজ করেন</h2>
                    </div>
                </div>
                <div class="about-audience">
                    <article class="about-audience-card">
                        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#cat-i-user"/></svg>
                        <h3>কম্পিউটার অপারেটর</h3>
                        <p>প্রত্যয়ন, সনদ, আবেদন ও ফরম — প্রতিদিনের কাজ দ্রুত শেষ করুন।</p>
                    </article>
                    <article class="about-audience-card">
                        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#cat-i-folder"/></svg>
                        <h3>দোকান ও ছোট ব্যবসা</h3>
                        <p>চিঠি, নোটিশ, হিসাব ও লেটারহেড — গ্রাহককে সময়মতো ডেলিভারি দিন।</p>
                    </article>
                    <article class="about-audience-card">
                        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#cat-i-file"/></svg>
                        <h3>শিক্ষার্থী ও চাকরিপ্রার্থী</h3>
                        <p>সিভি, সনদ ও আবেদনের রেডি টেমপ্লেট — নিজের তথ্য বসিয়ে ব্যবহার করুন।</p>
                    </article>
                    <article class="about-audience-card">
                        <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#cat-i-link"/></svg>
                        <h3>সাধারণ ব্যবহারকারী</h3>
                        <p>সরকারি সেবা, শিক্ষা, চাকরি ও স্বাস্থ্যের দরকারি লিংক — এক ক্লিকে।</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="section section-alt" id="faq" aria-labelledby="faqHeading">
            <div class="container">
                <div class="section-head">
                    <div>
                        <p class="kicker">প্রশ্নোত্তর</p>
                        <h2 id="faqHeading">সাধারণ প্রশ্ন</h2>
                    </div>
                </div>
                <div class="about-faq">
                    <?php foreach ($faqs as $faq): ?>
                        <details>
                            <summary><?= e($faq['q']) ?></summary>
                            <p><?= e($faq['a']) ?></p>
                        </details>
                    <?php endforeach; ?>
                </div>
                <p class="about-disclaimer">
                    <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#cat-i-shield"/></svg>
                    <span>
                        Repdoc স্বতন্ত্রভাবে পরিচালিত একটি টুলস সাইট। আমরা কোনো সরকারি দপ্তর নই এবং কোনো
                        সরকারি আবেদন, ফি বা সনদ প্রক্রিয়া সম্পন্ন করি না। আইনি বা সরকারি বিষয়ে
                        সংশ্লিষ্ট দপ্তরের নির্দেশনা অনুসরণ করুন। তথ্য সংরক্ষণ সম্পর্কে জানুন
                        <a href="<?= e(repdoc_url('privacy/')) ?>">গোপনীয়তা নীতিতে</a>।
                    </span>
                </p>
            </div>
        </section>

        <section class="section cta-band" aria-labelledby="aboutCtaHeading">
            <div class="container">
                <div class="cta-start">
                    <div class="cta-start-copy">
                        <p class="cta-eyebrow">
                            <span class="cta-eyebrow-mark" aria-hidden="true">
                                <svg class="icon" viewBox="0 0 24 24"><use href="#cat-i-check"/></svg>
                            </span>
                            সহজ · দ্রুত · নির্ভরযোগ্য
                        </p>
                        <h2 id="aboutCtaHeading">শুরু করতে প্রস্তুত?</h2>
                        <p class="cta-lead">অপারেটর অ্যাকাউন্টের জন্য আবেদন করুন, লগ ইন করে যেকোনো টুল খুলুন।</p>
                        <p class="cta-note">টেমপ্লেট দেখতে অ্যাকাউন্ট লাগে না — ডাউনলোড ও ডকুমেন্ট তৈরির জন্য লগ ইন করুন।</p>
                        <ul class="cta-perks">
                            <li><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#cat-i-file"/></svg>রেডি টেমপ্লেট</li>
                            <li><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#cat-i-shield"/></svg>নিরাপদ লগইন</li>
                            <li><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#cat-i-mobile"/></svg>সব ডিভাইসে</li>
                            <li><svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#cat-i-bolt"/></svg>দ্রুত ডাউনলোড</li>
                        </ul>
                    </div>
                    <figure class="cta-start-art">
                        <img src="<?= e(repdoc_url('src/assets/image/cta-start.webp')) ?>" alt="ল্যাপটপে ডকুমেন্ট তৈরি করছেন একজন অপারেটর" width="960" height="480" loading="lazy" decoding="async">
                    </figure>
                    <div class="cta-start-actions">
                        <a class="btn btn-primary" href="<?= e($APPLY_URL) ?>">
                            <span>অপারেটর হিসেবে আবেদন</span>
                            <svg class="icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#cat-i-arrow"/></svg>
                        </a>
                        <a class="btn btn-secondary" href="<?= e($LOGIN_URL) ?>">লগ ইন</a>
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
