<?php
declare(strict_types=1);

/**
 * Repdoc — Sonod (certificate) Templates landing page.
 *
 * Public, indexable landing section for the homepage "Sonod Templates"
 * card (previously a 404 with no index file). Copy stays honest: it
 * describes the section and points at the working Prottoyon generator
 * (41 certificate types) instead of linking to empty category folders.
 */

require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
require_once dirname(__DIR__, 2) . '/src/seo.php';
require_once __DIR__ . '/../prottoyon/types.php';

$SEO_TITLE = 'সনদ টেমপ্লেট (Sonod Templates) — Word ও PDF — Repdoc';
$SEO_DESC  = 'সনদ (sonod) টেমপ্লেট ও ফরম — Word ডক ও PDF আকারে। প্রত্যয়ন জেনারেটর থেকে ৪১ ধরনের সনদ তৈরি করুন। UDC উদ্যোক্তাদের টুলকিট।';
$CANON     = repdoc_canonical('genaretor/certificate/');
$HOME      = repdoc_url('/');
$LOGIN_URL = repdoc_url('public/login/');
$APPLY_URL = repdoc_url('Entrepreneur/apply/');
$PROT_URL  = repdoc_url('genaretor/prottoyon/');
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($SEO_TITLE) ?></title>
    <meta name="description" content="<?= e($SEO_DESC) ?>">
    <meta name="theme-color" content="#F8FAFC">
    <?php
    repdoc_seo_head([
        'title'       => $SEO_TITLE,
        'description' => $SEO_DESC,
        'canonical'   => $CANON,
        'type'        => 'website',
        'image'       => repdoc_canonical('src/assets/image/logo.png'),
        'imageWidth'  => 480,
        'imageHeight' => 160,
        'imageAlt'    => 'Repdoc logo',
        'locale'      => 'bn_BD',
    ]);
    repdoc_jsonld([
        '@type'         => 'WebPage',
        '@id'           => $CANON . '#page',
        'name'          => 'সনদ টেমপ্লেট — Repdoc',
        'url'           => $CANON,
        'description'   => $SEO_DESC,
        'inLanguage'    => 'bn',
        'isPartOf'      => ['@id' => repdoc_canonical() . '#website'],
        'breadcrumb'    => ['@id' => $CANON . '#breadcrumb'],
    ]);
    repdoc_jsonld([
        '@type'           => 'BreadcrumbList',
        '@id'             => $CANON . '#breadcrumb',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => repdoc_canonical()],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'সনদ টেমপ্লেট', 'item' => $CANON],
        ],
    ]);
    ?>
    <link rel="icon" type="image/png" href="<?= e(repdoc_url('public/favicon.png')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Noto+Sans+Bengali:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/tokens.css?v=1')) ?>">
    <link rel="stylesheet" href="<?= e(repdoc_url('src/assets/css/app.css?v=1')) ?>">
    <style>
        .landing-wrap { max-width: 760px; margin: 0 auto; padding: 40px 20px 64px; }
        .crumbs { font-size: .85rem; margin: 0 0 16px; color: var(--text-muted); }
        .crumbs a { color: inherit; }
        .landing-card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 32px; }
        .landing-card h1 { margin: 0 0 12px; }
        .landing-card h2 { margin: 28px 0 8px; font-size: 1.1rem; }
        .landing-card p, .landing-card li { line-height: 1.7; }
        .landing-actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 24px; }
    </style>
</head>
<body class="auth-body">
    <main class="landing-wrap">
        <p class="crumbs" aria-label="Breadcrumb"><a href="<?= e($HOME) ?>">হোম</a> › সনদ টেমপ্লেট</p>
        <div class="landing-card">
            <a class="auth-brand" href="<?= e($HOME) ?>">
                <img src="<?= e(repdoc_url('src/assets/image/logo.png')) ?>" alt="" width="96" height="32" loading="lazy" decoding="async">
                <span><strong>Repdoc</strong><small>repdoc.freedev.app · union toolkit</small></span>
            </a>

            <h1>সনদ টেমপ্লেট</h1>
            <p>সার্টিফিকেট (সনদ) টেমপ্লেট ও ফরম — Word ডক ও PDF আকারে। ইউনিয়ন পরিষদের
                UDC উদ্যোক্তাদের জন্য রেডি টেমপ্লেট থেকে তৈরি ডকুমেন্ট।</p>

            <h2>প্রত্যয়ন জেনারেটর থেকে তৈরি করুন</h2>
            <p>সনদ তৈরি করতে <a href="<?= e($PROT_URL) ?>">প্রত্যয়ন টেমপ্লেট</a> ব্যবহার
                করুন — ৪১ ধরনের সনদ, নিজের অফিস প্রোফাইল থেকে ইস্যু। তথ্য দিন, তৈরি
                ডকুমেন্ট Word ও PDF আকারে ডাউনলোড করুন।</p>
            <p>আলাদা শ্রেণিভিত্তিক সনদ সংগ্রহ প্রকাশ হলে এখানে তালিকাভুক্ত হবে।</p>

            <h2>কোন কোন সনদ পাওয়া যায় (নমুনা তালিকা)</h2>
            <p>নিচের তালিকাটি প্রত্যয়ন জেনারেটরের আসল রেজিস্ট্রি থেকে নেওয়া —
                প্রতিটি সনদ তৈরি করতে <a href="<?= e($LOGIN_URL) ?>">লগ ইন</a> করে
                জেনারেটরটি খুলুন:</p>
            <ul>
                <?php foreach (prottoyon_types() as $type): ?>
                    <li><?= e($type['title']) ?></li>
                <?php endforeach; ?>
            </ul>

            <h2>কীভাবে শুরু করবেন</h2>
            <ol>
                <li><a href="<?= e($APPLY_URL) ?>">উদ্যোক্তা অ্যাকাউন্টের জন্য আবেদন করুন</a> (শুধু UDC উদ্যোক্তা)।</li>
                <li><a href="<?= e($LOGIN_URL) ?>">লগ ইন করুন</a> — একটি অ্যাকাউন্টেই সব টুল।</li>
                <li>টুল খুলে তথ্য দিন, তৈরি ডকুমেন্ট ডাউনলোড করুন।</li>
            </ol>

            <div class="landing-actions">
                <a class="btn btn-primary" href="<?= e($PROT_URL) ?>">প্রত্যয়ন টেমপ্লেট খুলুন</a>
                <a class="btn btn-secondary" href="<?= e($LOGIN_URL) ?>">লগ ইন</a>
            </div>
        </div>
        <p class="auth-sub" style="text-align:center">Independent tools site — not a government portal.</p>
    </main>
</body>
</html>
