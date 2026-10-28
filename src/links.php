<?php
declare(strict_types=1);

/**
 * Repdoc — important-links directory store.
 *
 * Links are kept in love/important_links.json so the public directory and
 * the admin editor always read the same list. The public page only renders
 * valid rows; invalid rows stay visible in admin so they can be corrected.
 */

if (!function_exists('repdoc_links_file')) {
    function repdoc_links_file(): string
    {
        return dirname(__DIR__) . '/love/important_links.json';
    }
}

if (!function_exists('repdoc_link_categories')) {
    function repdoc_link_categories(): array
    {
        return [
            'government' => [
                'label' => 'সরকারি সেবা',
                'admin' => 'Government',
                'sub'   => 'মন্ত্রণালয় ও সেবা',
                'color' => '#0E6B4A',
                'soft'  => '#EAF6EF',
            ],
            'education' => [
                'label' => 'শিক্ষা',
                'admin' => 'Education',
                'sub'   => 'ভর্তি, ফলাফল, বৃত্তি',
                'color' => '#1D4ED8',
                'soft'  => '#E9EFFD',
            ],
            'jobs' => [
                'label' => 'চাকরি',
                'admin' => 'Jobs',
                'sub'   => 'নিয়োগ ও ক্যারিয়ার',
                'color' => '#B45309',
                'soft'  => '#FDF3E3',
            ],
            'utility' => [
                'label' => 'ইউটিলিটি',
                'admin' => 'Utility',
                'sub'   => 'বিদ্যুৎ, পানি, ইন্টারনেট',
                'color' => '#7C3AED',
                'soft'  => '#F2EEFD',
            ],
            'health' => [
                'label' => 'স্বাস্থ্য',
                'admin' => 'Health',
                'sub'   => 'হাসপাতাল ও স্বাস্থ্যসেবা',
                'color' => '#C1272D',
                'soft'  => '#FDEEEE',
            ],
            'other' => [
                'label' => 'অন্যান্য',
                'admin' => 'Other',
                'sub'   => 'বিবিধ দরকারি লিংক',
                'color' => '#475569',
                'soft'  => '#EEF2F6',
            ],
        ];
    }
}

if (!function_exists('repdoc_link_category')) {
    function repdoc_link_category(string $key): string
    {
        return array_key_exists($key, repdoc_link_categories()) ? $key : 'other';
    }
}

if (!function_exists('repdoc_link_category_meta')) {
    function repdoc_link_category_meta(string $key): array
    {
        $categories = repdoc_link_categories();
        return $categories[repdoc_link_category($key)];
    }
}

if (!function_exists('repdoc_link_text')) {
    function repdoc_link_text(string $value, int $max): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", trim($value));
        $value = strip_tags($value);
        $value = (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
        $value = trim((string) preg_replace('/[ \t]+/u', ' ', $value));
        return mb_substr($value, 0, $max, 'UTF-8');
    }
}

if (!function_exists('repdoc_link_raw_url')) {
    function repdoc_link_raw_url(string $value): string
    {
        $value = trim($value);
        $value = (string) preg_replace('/[\x00-\x1F\x7F\s]+/u', '', $value);
        return mb_substr($value, 0, 1000, 'UTF-8');
    }
}

if (!function_exists('repdoc_link_normalize_url')) {
    /** Return a safe http(s) URL or site path; '' when the value is unusable. */
    function repdoc_link_normalize_url(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '' || preg_match('/[\x00-\x20\x7F]/u', $raw)) return '';

        if ($raw[0] === '/') {
            if (str_starts_with($raw, '//')) return '';
            return $raw;
        }

        if (!preg_match('~^[a-z][a-z0-9+.-]*://~i', $raw) && !preg_match('~^[a-z][a-z0-9+.-]*:~i', $raw)) {
            if (!preg_match('~^[\p{L}\p{N}][\p{L}\p{N}.-]*\.[\p{L}]{2,}(?::\d+)?(?:[/?#].*)?$~u', $raw)) {
                return '';
            }
            $raw = 'https://' . $raw;
        }

        $parts = parse_url($raw);
        if (!is_array($parts)) return '';
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (!in_array($scheme, ['http', 'https'], true)) return '';
        if (empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) return '';
        return $raw;
    }
}

if (!function_exists('repdoc_link_host')) {
    function repdoc_link_host(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (is_string($host) && $host !== '') {
            return (string) preg_replace('/^www\./i', '', $host);
        }
        $path = parse_url($url, PHP_URL_PATH);
        return is_string($path) && $path !== '' ? $path : 'repdoc.rf.gd';
    }
}

if (!function_exists('repdoc_links_load')) {
    /**
     * Load every row, including invalid rows (for the admin editor).
     * Public callers must filter on $row['valid'].
     */
    function repdoc_links_load(): array
    {
        $file = repdoc_links_file();
        if (!is_file($file)) return [];

        $decoded = json_decode((string) @file_get_contents($file), true);
        if (!is_array($decoded)) return [];
        if (isset($decoded['links']) && is_array($decoded['links'])) {
            $decoded = $decoded['links'];
        }

        $out = [];
        foreach ($decoded as $row) {
            if (!is_array($row)) continue;

            $title = repdoc_link_text((string) ($row['title'] ?? ''), 120);
            $rawUrl = repdoc_link_raw_url((string) ($row['url'] ?? ''));
            $url = repdoc_link_normalize_url($rawUrl);
            $desc = repdoc_link_text((string) ($row['desc'] ?? ''), 400);
            if ($title === '' && $rawUrl === '' && $desc === '') continue;

            $error = '';
            if ($title === '') {
                $error = 'Title is required.';
            } elseif ($rawUrl === '') {
                $error = 'URL is required.';
            } elseif ($url === '') {
                $error = 'Use a full http(s) URL or a site path starting with /.';
            }

            $out[] = [
                'title'    => $title,
                'url'      => $url !== '' ? $url : $rawUrl,
                'desc'     => $desc,
                'category' => repdoc_link_category((string) ($row['category'] ?? 'other')),
                'valid'    => $error === '',
                'error'    => $error,
            ];
        }
        return $out;
    }
}

if (!function_exists('repdoc_links_save')) {
    /**
     * Normalize and persist rows. Invalid rows are kept so the editor can
     * correct them instead of silently losing work.
     *
     * @return array{ok:bool,saved:int,invalid:int}
     */
    function repdoc_links_save(array $rows): array
    {
        $out = [];
        $invalid = 0;

        foreach ($rows as $row) {
            if (!is_array($row)) continue;

            $title = repdoc_link_text((string) ($row['title'] ?? ''), 120);
            $rawUrl = repdoc_link_raw_url((string) ($row['url'] ?? ''));
            $url = repdoc_link_normalize_url($rawUrl);
            $desc = repdoc_link_text((string) ($row['desc'] ?? ''), 400);
            if ($title === '' && $rawUrl === '' && $desc === '') continue;

            if ($url === '' || $title === '') $invalid++;
            $out[] = [
                'title'    => $title,
                'url'      => $url !== '' ? $url : $rawUrl,
                'desc'     => $desc,
                'category' => repdoc_link_category((string) ($row['category'] ?? 'other')),
            ];
        }

        $json = json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return ['ok' => false, 'saved' => 0, 'invalid' => $invalid];
        }
        $json .= "\n";

        $file = repdoc_links_file();
        $dir = dirname($file);
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            return ['ok' => false, 'saved' => 0, 'invalid' => $invalid];
        }

        $tmp = $file . '.tmp';
        if (@file_put_contents($tmp, $json) === false) {
            return ['ok' => false, 'saved' => 0, 'invalid' => $invalid];
        }
        if (!@rename($tmp, $file)) {
            @unlink($tmp);
            return ['ok' => false, 'saved' => 0, 'invalid' => $invalid];
        }
        @chmod($file, 0644);

        return ['ok' => true, 'saved' => count($out), 'invalid' => $invalid];
    }
}

if (!function_exists('repdoc_links_last_updated')) {
    function repdoc_links_last_updated(): int
    {
        $file = repdoc_links_file();
        return is_file($file) ? (int) @filemtime($file) : 0;
    }
}

if (!function_exists('repdoc_link_search_index')) {
    function repdoc_link_search_index(array $link): string
    {
        $category = repdoc_link_category_meta((string) ($link['category'] ?? 'other'));
        return mb_strtolower(
            ($link['title'] ?? '') . ' ' . ($link['desc'] ?? '') . ' ' . ($link['url'] ?? '') . ' '
            . repdoc_link_host((string) ($link['url'] ?? '')) . ' '
            . $category['label'] . ' ' . $category['admin'],
            'UTF-8'
        );
    }
}
