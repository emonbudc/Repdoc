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

if (!function_exists('repdoc_seo_head')) {
    /**
     * Echo the indexable-page <head> tags: canonical, robots (when told),
     * Open Graph, and Twitter cards. Title + description meta tags stay in
     * each page's own <head> so copy remains page-specific.
     *
     * $opts: title, description, canonical (abs URL), type (website|article),
     *        image (abs URL), imageWidth, imageHeight, imageAlt, noindex (bool),
     *        twitterCard (summary|summary_large_image), locale (e.g. 'bn_BD').
     */
    function repdoc_seo_head(array $opts): void
    {
        $title       = (string) ($opts['title'] ?? 'Repdoc');
        $desc        = (string) ($opts['description'] ?? '');
        $canonical   = (string) ($opts['canonical'] ?? repdoc_canonical());
        $type        = (string) ($opts['type'] ?? 'website');
        $image       = (string) ($opts['image'] ?? repdoc_canonical('public/favicon.png'));
        $imageAlt    = (string) ($opts['imageAlt'] ?? $title);
        $twitterCard = (string) ($opts['twitterCard'] ?? 'summary');
        $noindex     = !empty($opts['noindex']);

        echo '    <link rel="canonical" href="' . e($canonical) . '">' . "\n";
        if ($noindex) {
            echo '    <meta name="robots" content="noindex">' . "\n";
        }
        echo '    <meta property="og:title" content="' . e($title) . '">' . "\n";
        if ($desc !== '') {
            echo '    <meta property="og:description" content="' . e($desc) . '">' . "\n";
        }
        echo '    <meta property="og:type" content="' . e($type) . '">' . "\n";
        echo '    <meta property="og:url" content="' . e($canonical) . '">' . "\n";
        echo '    <meta property="og:site_name" content="Repdoc">' . "\n";
        if (!empty($opts['locale'])) {
            echo '    <meta property="og:locale" content="' . e((string) $opts['locale']) . '">' . "\n";
        }
        echo '    <meta property="og:image" content="' . e($image) . '">' . "\n";
        echo '    <meta property="og:image:alt" content="' . e($imageAlt) . '">' . "\n";
        if (isset($opts['imageWidth'])) {
            echo '    <meta property="og:image:width" content="' . (int) $opts['imageWidth'] . '">' . "\n";
        }
        if (isset($opts['imageHeight'])) {
            echo '    <meta property="og:image:height" content="' . (int) $opts['imageHeight'] . '">' . "\n";
        }
        echo '    <meta name="twitter:card" content="' . e($twitterCard) . '">' . "\n";
        echo '    <meta name="twitter:title" content="' . e($title) . '">' . "\n";
        if ($desc !== '') {
            echo '    <meta name="twitter:description" content="' . e($desc) . '">' . "\n";
        }
        echo '    <meta name="twitter:image" content="' . e($image) . '">' . "\n";
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
