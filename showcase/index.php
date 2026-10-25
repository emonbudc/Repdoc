<?php
declare(strict_types=1);

/**
 * Repdoc — Template Showcase landing page.
 *
 * Public, indexable landing section for the homepage "Template Showcase"
 * card (previously a 404 with no index file). Copy stays honest: it
 * describes the formats and points at the working generators instead of
 * claiming downloadable files that are not published yet.
 */

require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/seo.php';

$SEO_TITLE = 'Template Showcase — Free Templates & Forms (Word, PDF) — Repdoc';
$SEO_DESC  = 'Browse the Repdoc template showcase: free templates and forms as Word docs and PDF. Start from a generator — CV Maker and Prottoyon produce finished documents from ready templates.';
$CANON     = repdoc_canonical('showcase/');
$HOME      = repdoc_url('/');
$LOGIN_URL = repdoc_url('public/login/');
$APPLY_URL = repdoc_url('Entrepreneur/apply/');
$CV_URL    = repdoc_url('genaretor/cvmaker/');
$PROT_URL  = repdoc_url('genaretor/prottoyon/');
?>
<!DOCTYPE html>
<html lang="en">
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
    ]);
    repdoc_jsonld([
        '@type'         => 'WebPage',
        '@id'           => $CANON . '#page',
        'name'          => 'Template Showcase — Repdoc',
        'url'           => $CANON,
        'description'   => $SEO_DESC,
        'inLanguage'    => 'en',
        'isPartOf'      => ['@id' => repdoc_canonical() . '#website'],
        'breadcrumb'    => ['@id' => $CANON . '#breadcrumb'],
    ]);
    repdoc_jsonld([
        '@type'           => 'BreadcrumbList',
        '@id'             => $CANON . '#breadcrumb',
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => repdoc_canonical()],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Template Showcase', 'item' => $CANON],
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
        <p class="crumbs" aria-label="Breadcrumb"><a href="<?= e($HOME) ?>">Home</a> › Template Showcase</p>
        <div class="landing-card">
            <a class="auth-brand" href="<?= e($HOME) ?>">
                <img src="<?= e(repdoc_url('src/assets/image/logo.png')) ?>" alt="" width="96" height="32" loading="lazy" decoding="async">
                <span><strong>Repdoc</strong><small>repdoc.freedev.app · union toolkit</small></span>
            </a>

            <h1>Template Showcase</h1>
            <p>Free templates and forms as Word docs and PDF — all in one place. Repdoc is a
                document <em>generator</em>: instead of downloading a blank file and retyping
                everything, you fill in the details and get a finished document.</p>

            <h2>Start from a working generator</h2>
            <ul>
                <li><a href="<?= e($CV_URL) ?>">CV Maker</a> — build a CV / resume from a free template and download it.</li>
                <li><a href="<?= e($PROT_URL) ?>">Prottoyon Templates</a> — attestation (prottoyon) templates and forms as Word docs and PDF.</li>
            </ul>
            <p>More downloadable template packs (Word, PDF, PSD) will be listed here as they are published.</p>

            <h2>Every format, one subscription</h2>
            <p>One Repdoc account unlocks every format below. Word and PDF are
                generated on-site today — the rest publish here as packs land:</p>
            <ul>
                <li><strong>Word</strong> — generated from the tool, download today.</li>
                <li><strong>PDF</strong> — generated from the tool, download today.</li>
                <li><strong>Excel</strong> — template pack via <a href="../officefiles/">Office Files</a>.</li>
                <li><strong>PSD (Photoshop)</strong> — template pack via <a href="../offlinefiles/">Offline Files</a>.</li>
                <li><strong>AI (Illustrator)</strong> — template pack via <a href="../offlinefiles/">Offline Files</a>.</li>
                <li><strong>PLP (PixelLab)</strong> — template pack via <a href="../offlinefiles/">Offline Files</a>.</li>
            </ul>

            <h2>CV templates inside (sample list)</h2>
            <p>The <a href="<?= e($CV_URL) ?>">CV Maker</a> ships with these ready
                resume templates — <a href="<?= e($LOGIN_URL) ?>">log in</a> to open
                the generator and fill one in:</p>
            <ul>
                <li>Modern, Classic, Minimal, Bangla</li>
                <li>Executive, Creative, Corporate, Portfolio</li>
                <li>Slate, Elite, Bold, Standard, Dynamic</li>
                <li>Word Style, Insight</li>
            </ul>

            <h2>How it works</h2>
            <ol>
                <li><a href="<?= e($APPLY_URL) ?>">Apply for an entrepreneur account</a> (UDC entrepreneurs of the Union Parishad).</li>
                <li><a href="<?= e($LOGIN_URL) ?>">Log in</a> — one account unlocks every tool.</li>
                <li>Open a tool, enter the details, and download the finished Word doc or PDF.</li>
            </ol>

            <div class="landing-actions">
                <a class="btn btn-primary" href="<?= e($CV_URL) ?>">Open CV Maker</a>
                <a class="btn btn-secondary" href="<?= e($LOGIN_URL) ?>">Log in</a>
            </div>
        </div>
        <p class="auth-sub" style="text-align:center">Independent tools site — not a government portal.</p>
    </main>
</body>
</html>
