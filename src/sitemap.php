<?php
declare(strict_types=1);

/**
 * Repdoc — dynamic XML sitemap.
 *
 * Merges the static page registry with the live template catalogue, so new
 * files appear in /sitemap.xml as soon as they are published. Gated tools and
 * admin pages are excluded by the registry's `sitemap` flag.
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/seo.php';
require_once __DIR__ . '/filemeta.php';
require_once __DIR__ . '/templates.php';

if (!function_exists('repdoc_sitemap_entries')) {
    /**
     * @return array<int,array{loc:string,lastmod:string,changefreq:string,priority:string,images:array<int,array{loc:string,title:string,caption:string}>}>
     */
    function repdoc_sitemap_entries(): array
    {
        $entries = [];

        foreach (repdoc_page_registry() as $page) {
            if (empty($page['sitemap'])) continue;

            $file = REPDOC_ROOT . '/' . ltrim((string) $page['file'], '/');
            $lastmod = is_file($file) ? date('Y-m-d', (int) filemtime($file)) : date('Y-m-d');
            $images = [];
            if (!empty($page['image'])) {
                $images[] = [
                    'loc'     => repdoc_canonical((string) $page['image']),
                    'title'   => (string) $page['label'] . ' — Repdoc',
                    'caption' => (string) $page['label'],
                ];
            }

            $entries[] = [
                'loc'        => repdoc_canonical((string) $page['path']),
                'lastmod'    => $lastmod,
                'changefreq' => (string) ($page['changefreq'] ?? 'monthly'),
                'priority'   => (string) ($page['priority'] ?? '0.5'),
                'images'     => $images,
            ];
        }

        foreach (repdoc_template_index()['files'] as $row) {
            $lastmod = (int) ($row['mtime'] ?? 0);
            $images = [];
            $social = repdoc_template_social_image($row);
            if (!empty($social['url']) && !str_ends_with((string) $social['url'], '/src/assets/image/og-cover.png')) {
                $images[] = [
                    'loc'     => (string) $social['url'],
                    'title'   => (string) ($row['title'] ?? $row['name']),
                    'caption' => repdoc_template_trim((string) ($row['description'] ?? ''), 160),
                ];
            }

            $entries[] = [
                'loc'        => (string) $row['canonical'],
                'lastmod'    => $lastmod > 0 ? date('Y-m-d', $lastmod) : date('Y-m-d'),
                'changefreq' => 'monthly',
                'priority'   => '0.6',
                'images'     => $images,
            ];
        }

        return $entries;
    }
}

if (!function_exists('repdoc_sitemap_xml')) {
    function repdoc_sitemap_xml(): string
    {
        $esc = static fn(string $value): string => htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        $out = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $out .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

        foreach (repdoc_sitemap_entries() as $entry) {
            $out .= "  <url>\n";
            $out .= '    <loc>' . $esc($entry['loc']) . "</loc>\n";
            $out .= '    <lastmod>' . $esc($entry['lastmod']) . "</lastmod>\n";
            $out .= '    <changefreq>' . $esc($entry['changefreq']) . "</changefreq>\n";
            $out .= '    <priority>' . $esc($entry['priority']) . "</priority>\n";
            foreach ($entry['images'] as $image) {
                $out .= "    <image:image>\n";
                $out .= '      <image:loc>' . $esc($image['loc']) . "</image:loc>\n";
                if ($image['title'] !== '') {
                    $out .= '      <image:title>' . $esc($image['title']) . "</image:title>\n";
                }
                if ($image['caption'] !== '') {
                    $out .= '      <image:caption>' . $esc($image['caption']) . "</image:caption>\n";
                }
                $out .= "    </image:image>\n";
            }
            $out .= "  </url>\n";
        }

        $out .= "</urlset>\n";
        return $out;
    }
}
