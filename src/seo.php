<?php
declare(strict_types=1);

/**
 * Repdoc — central SEO helpers.
 *
 * Single source of truth for canonical URLs, social-sharing metadata, and
 * JSON-LD structured data. Every public page should build its <head> through
 * these helpers so titles, descriptions, canonicals, and Open Graph tags stay
 * consistent. Gated (login-required) and administrative pages must NOT use
 * the indexable defaults here — they carry `noindex` instead (see each page).
 *
 * Canonical base URL: override with the REPDOC_PUBLIC_BASE environment
 * variable on the host when the production domain ever changes. The default
 * is the production domain (repdoc.freedev.app). Local/dev
 * hosts are never used as canonicals.
 */

if (!defined('REPDOC_PUBLIC_BASE')) {
    $seoEnvBase = getenv('REPDOC_PUBLIC_BASE');
    define(
        'REPDOC_PUBLIC_BASE',
        $seoEnvBase !== false && trim((string) $seoEnvBase) !== ''
            ? rtrim(trim((string) $seoEnvBase), '/')
            : 'https://repdoc.freedev.app'
    );
}

if (!function_exists('repdoc_canonical')) {
    /**
     * Absolute canonical URL for a project-relative path.
     * Example: repdoc_canonical('showcase/') → https://repdoc.freedev.app/showcase/
     */
    function repdoc_canonical(string $path = ''): string
    {
        $path = ltrim($path, '/');
        return $path === '' ? REPDOC_PUBLIC_BASE . '/' : REPDOC_PUBLIC_BASE . '/' . $path;
    }
}

if (!function_exists('repdoc_page_registry')) {
    /**
     * Every public page the SEO audit and sitemap know about.
     *
     * `indexable` is the product decision (login/register stay indexable;
     * gated tools and the apply/verify forms carry noindex). `sitemap` is
     * separate because a page can be indexable but intentionally omitted.
     * `image` is a project-relative image used by the image sitemap and as
     * the page's default social card when a page does not pass its own.
     */
    function repdoc_page_registry(): array
    {
        return [
            ['path' => '', 'file' => 'index.php', 'label' => 'Home', 'indexable' => true, 'sitemap' => true, 'changefreq' => 'weekly', 'priority' => '1.0', 'image' => 'src/assets/image/hero.webp'],
            ['path' => 'about/', 'file' => 'about/index.php', 'label' => 'About', 'indexable' => true, 'sitemap' => true, 'changefreq' => 'monthly', 'priority' => '0.5', 'image' => 'src/assets/image/hero.webp'],
            ['path' => 'showcase/', 'file' => 'showcase/index.php', 'label' => 'Template Showcase', 'indexable' => true, 'sitemap' => true, 'changefreq' => 'weekly', 'priority' => '0.8', 'image' => 'src/assets/image/officefiles-hero.png'],
            ['path' => 'officefiles/', 'file' => 'officefiles/index.php', 'label' => 'Office Files', 'indexable' => true, 'sitemap' => true, 'changefreq' => 'weekly', 'priority' => '0.8', 'image' => 'src/assets/image/officefiles-hero.png'],
            ['path' => 'offlinefiles/', 'file' => 'offlinefiles/index.php', 'label' => 'Offline Files', 'indexable' => true, 'sitemap' => true, 'changefreq' => 'weekly', 'priority' => '0.8', 'image' => 'src/assets/image/officefiles-hero.png'],
            ['path' => 'software/', 'file' => 'software/index.php', 'label' => 'Software', 'indexable' => true, 'sitemap' => true, 'changefreq' => 'weekly', 'priority' => '0.8', 'image' => 'src/assets/image/hero.webp'],
            ['path' => 'importantlinks/', 'file' => 'importantlinks/index.php', 'label' => 'Important Links', 'indexable' => true, 'sitemap' => true, 'changefreq' => 'monthly', 'priority' => '0.6', 'image' => 'src/assets/image/hero.webp'],
            ['path' => 'privacy/', 'file' => 'privacy/index.php', 'label' => 'Privacy', 'indexable' => true, 'sitemap' => true, 'changefreq' => 'yearly', 'priority' => '0.3', 'image' => 'src/assets/image/hero.webp'],
            ['path' => 'genaretor/certificate/', 'file' => 'genaretor/certificate/index.php', 'label' => 'Certificate Generator', 'indexable' => true, 'sitemap' => true, 'changefreq' => 'monthly', 'priority' => '0.8', 'image' => 'src/assets/image/hero.webp'],
            ['path' => 'public/login/', 'file' => 'public/login/index.php', 'label' => 'Login', 'indexable' => true, 'sitemap' => true, 'changefreq' => 'monthly', 'priority' => '0.5', 'image' => 'src/assets/image/hero.webp'],
            ['path' => 'public/register/', 'file' => 'public/register/index.php', 'label' => 'Register', 'indexable' => true, 'sitemap' => true, 'changefreq' => 'monthly', 'priority' => '0.5', 'image' => 'src/assets/image/hero.webp'],
            ['path' => 'Entrepreneur/apply/', 'file' => 'Entrepreneur/apply/index.php', 'label' => 'Entrepreneur Apply', 'indexable' => false, 'sitemap' => false, 'changefreq' => 'monthly', 'priority' => '0.3', 'image' => ''],
            ['path' => 'verify/', 'file' => 'verify/index.php', 'label' => 'Certificate Verify', 'indexable' => false, 'sitemap' => false, 'changefreq' => 'monthly', 'priority' => '0.3', 'image' => ''],
            ['path' => 'genaretor/birth/', 'file' => 'genaretor/birth/index.php', 'label' => 'Birth Generator', 'indexable' => false, 'sitemap' => false, 'changefreq' => 'monthly', 'priority' => '0.3', 'image' => ''],
            ['path' => 'genaretor/cvmaker/', 'file' => 'genaretor/cvmaker/index.php', 'label' => 'CV Maker', 'indexable' => false, 'sitemap' => false, 'changefreq' => 'monthly', 'priority' => '0.3', 'image' => ''],
            ['path' => 'genaretor/prottoyon/', 'file' => 'genaretor/prottoyon/index.php', 'label' => 'Attestation Generator', 'indexable' => false, 'sitemap' => false, 'changefreq' => 'monthly', 'priority' => '0.3', 'image' => ''],
            ['path' => 'tools/land/', 'file' => 'tools/land/index.php', 'label' => 'Land Records', 'indexable' => false, 'sitemap' => false, 'changefreq' => 'monthly', 'priority' => '0.3', 'image' => ''],
            ['path' => 'tools/passport/', 'file' => 'tools/passport/index.php', 'label' => 'Passport Photo', 'indexable' => false, 'sitemap' => false, 'changefreq' => 'monthly', 'priority' => '0.3', 'image' => ''],
        ];
    }
}

if (!function_exists('repdoc_jsonld_site')) {
    /**
     * Emit the site-wide Organization + WebSite entities once per request.
     * Every page uses the same @id values, so Google can resolve the graph
     * regardless of which page it lands on first.
     */
    function repdoc_jsonld_site(): void
    {
        static $done = false;
        if ($done) return;
        $done = true;

        $base = repdoc_canonical();
        repdoc_jsonld([
            '@type'       => 'Organization',
            '@id'         => $base . '#org',
            'name'        => 'Repdoc',
            'url'         => $base,
            'logo'        => [
                '@type'  => 'ImageObject',
                '@id'    => $base . '#logo',
                'url'    => repdoc_canonical('src/assets/image/logo.png'),
                'width'  => 480,
                'height' => 160,
            ],
            'description' => 'বাংলাদেশের কম্পিউটার অপারেটরদের জন্য স্বতন্ত্র ডকুমেন্ট-টেমপ্লেট টুলকিট।',
            'areaServed'  => ['@type' => 'Country', 'name' => 'Bangladesh'],
            'audience'    => ['@type' => 'Audience', 'audienceType' => 'Computer operators with a business or job, Bangladesh'],
            'sameAs'      => [
                'https://github.com/Repdoc-BD',
                'https://t.me/Repdocbdofficial',
                'https://www.youtube.com/@Repdocbdofficial',
                'https://x.com/Repdocbdofficial',
                'https://www.facebook.com/Repdocbdofficial',
            ],
        ]);
        repdoc_jsonld([
            '@type'       => 'WebSite',
            '@id'         => $base . '#website',
            'name'        => 'Repdoc',
            'url'         => $base,
            'publisher'   => ['@id' => $base . '#org'],
            'inLanguage'  => ['bn'],
            'description' => 'ফ্রি ডকুমেন্ট টেমপ্লেট ও ফরম — Word, Excel, PDF, PSD, AI, PLP।',
        ]);
    }
}

if (!function_exists('repdoc_seo_head')) {
    /**
     * Echo the indexable-page <head> tags: canonical, robots, Open Graph,
     * Twitter cards, and the site-wide JSON-LD graph. Title + description
     * meta tags stay in each page's own <head> so copy remains page-specific.
     *
     * $opts: title, description, canonical (abs URL), type (website|article),
     *        image (abs URL), imageWidth, imageHeight, imageAlt, noindex (bool),
     *        twitterCard (summary|summary_large_image), locale (e.g. 'bn_BD'),
     *        modified (ISO 8601 for article pages), siteGraph (bool).
     */
    function repdoc_seo_head(array $opts): void
    {
        $title       = (string) ($opts['title'] ?? 'Repdoc');
        $desc        = (string) ($opts['description'] ?? '');
        $canonical   = (string) ($opts['canonical'] ?? repdoc_canonical());
        $type        = (string) ($opts['type'] ?? 'website');
        $imageGiven  = isset($opts['image']);
        $image       = (string) ($opts['image'] ?? repdoc_canonical('src/assets/image/og-cover.png'));
        $imageAlt    = (string) ($opts['imageAlt'] ?? $title);
        $imageWidth  = $opts['imageWidth'] ?? ($imageGiven ? null : 1200);
        $imageHeight = $opts['imageHeight'] ?? ($imageGiven ? null : 630);
        $twitterCard = (string) ($opts['twitterCard'] ?? (($imageWidth !== null && (int) $imageWidth >= 600) ? 'summary_large_image' : 'summary'));
        $locale      = (string) ($opts['locale'] ?? 'bn_BD');
        $noindex     = !empty($opts['noindex']);

        echo '    <link rel="canonical" href="' . e($canonical) . '">' . "\n";
        echo '    <meta name="robots" content="' . ($noindex
            ? 'noindex,follow'
            : 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1') . '">' . "\n";
        echo '    <meta property="og:title" content="' . e($title) . '">' . "\n";
        if ($desc !== '') {
            echo '    <meta property="og:description" content="' . e($desc) . '">' . "\n";
        }
        echo '    <meta property="og:type" content="' . e($type) . '">' . "\n";
        echo '    <meta property="og:url" content="' . e($canonical) . '">' . "\n";
        echo '    <meta property="og:site_name" content="Repdoc">' . "\n";
        echo '    <meta property="og:locale" content="' . e($locale) . '">' . "\n";
        echo '    <meta property="og:image" content="' . e($image) . '">' . "\n";
        echo '    <meta property="og:image:alt" content="' . e($imageAlt) . '">' . "\n";
        if ($imageWidth !== null) {
            echo '    <meta property="og:image:width" content="' . (int) $imageWidth . '">' . "\n";
        }
        if ($imageHeight !== null) {
            echo '    <meta property="og:image:height" content="' . (int) $imageHeight . '">' . "\n";
        }
        if ($type === 'article' && !empty($opts['modified'])) {
            echo '    <meta property="article:modified_time" content="' . e((string) $opts['modified']) . '">' . "\n";
        }
        echo '    <meta name="twitter:card" content="' . e($twitterCard) . '">' . "\n";
        echo '    <meta name="twitter:title" content="' . e($title) . '">' . "\n";
        if ($desc !== '') {
            echo '    <meta name="twitter:description" content="' . e($desc) . '">' . "\n";
        }
        echo '    <meta name="twitter:image" content="' . e($image) . '">' . "\n";
        echo '    <meta name="twitter:image:alt" content="' . e($imageAlt) . '">' . "\n";

        if (($opts['siteGraph'] ?? true) !== false) {
            repdoc_jsonld_site();
        }
    }
}

if (!function_exists('repdoc_jsonld')) {
    /**
     * Echo a JSON-LD block. $data must only describe content that is visible
     * on the page — never fabricate ratings, addresses, or authors.
     */
    function repdoc_jsonld(array $data): void
    {
        if (!isset($data['@context'])) {
            $data = ['@context' => 'https://schema.org'] + $data;
        }
        echo '    <script type="application/ld+json">'
            . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            . '</script>' . "\n";
    }
}
