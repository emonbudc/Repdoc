<?php
declare(strict_types=1);

/**
 * Repdoc — template catalogue data layer.
 *
 * Single source of truth for the six downloadable template formats, their
 * files, descriptions, slugs and canonical URLs. Used by the public listing
 * pages, the per-template landing pages, the sitemap and the SEO audit.
 *
 * Descriptions resolve in this order: admin database row → bundled
 * officefiles/descriptions.json → format note. Every published file therefore
 * has indexable copy without waiting for an admin edit.
 */

require_once __DIR__ . '/seo.php';
require_once __DIR__ . '/filemeta.php';

if (!function_exists('repdoc_template_formats')) {
    /** @return array<string,array<string,mixed>> */
    function repdoc_template_formats(): array
    {
        return [
            'word' => [
                'label' => 'MS Word',
                'full'  => 'Microsoft Word',
                'hub'   => 'officefiles',
                'group' => 'office',
                'brand' => '#2B579A',
                'soft'  => '#EEF3FB',
                'exts'  => ['doc', 'docx', 'rtf', 'odt', 'zip'],
                'mime'  => [
                    'doc'  => 'application/msword',
                    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'rtf'  => 'application/rtf',
                    'odt'  => 'application/vnd.oasis.opendocument.text',
                    'zip'  => 'application/zip',
                ],
                'note'  => 'চিঠি, আবেদন, নোটিশ ও অফিসের দৈনন্দিন কাজের জন্য রেডি Microsoft Word টেমপ্লেট — নাম, ঠিকানা ও তারিখ বদলে সরাসরি ব্যবহার করুন।',
            ],
            'excel' => [
                'label' => 'MS Excel',
                'full'  => 'Microsoft Excel',
                'hub'   => 'officefiles',
                'group' => 'office',
                'brand' => '#217346',
                'soft'  => '#EDF6F1',
                'exts'  => ['xls', 'xlsx', 'csv', 'zip'],
                'mime'  => [
                    'xls'  => 'application/vnd.ms-excel',
                    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'csv'  => 'text/csv',
                    'zip'  => 'application/zip',
                ],
                'note'  => 'হিসাব, তালিকা, বাজেট ও রিপোর্টের জন্য ফর্মুলা-সহ Microsoft Excel স্প্রেডশিট — নিজের ডেটা বসিয়ে সাথে সাথে ব্যবহারযোগ্য।',
            ],
            'ppt' => [
                'label' => 'PowerPoint',
                'full'  => 'Microsoft PowerPoint',
                'hub'   => 'officefiles',
                'group' => 'office',
                'brand' => '#C43E1C',
                'soft'  => '#FBEEE9',
                'exts'  => ['ppt', 'pptx', 'zip'],
                'mime'  => [
                    'ppt'  => 'application/vnd.ms-powerpoint',
                    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                    'zip'  => 'application/zip',
                ],
                'note'  => 'মিটিং, প্রেজেন্টেশন ও অফিসিয়াল প্রেজেন্টেশনের জন্য প্রস্তুত Microsoft PowerPoint স্লাইড — নিজের তথ্য বসিয়ে ব্যবহার করুন।',
            ],
            'psd' => [
                'label' => 'PSD Files',
                'full'  => 'Adobe Photoshop',
                'hub'   => 'offlinefiles',
                'group' => 'psd',
                'brand' => '#1D4ED8',
                'soft'  => '#E9EFFD',
                'exts'  => ['psd', 'psb', 'zip'],
                'mime'  => [
                    'psd' => 'image/vnd.adobe.photoshop',
                    'psb' => 'image/vnd.adobe.photoshop',
                    'zip' => 'application/zip',
                ],
                'note'  => 'লেয়ার-ভিত্তিক Adobe Photoshop টেমপ্লেট — পোস্টার, ব্যানার ও প্রোফাইল কভার নিজের মতো এডিট করার জন্য সম্পূর্ণ রেডি।',
            ],
            'ai' => [
                'label' => 'AI Files',
                'full'  => 'Adobe Illustrator',
                'hub'   => 'offlinefiles',
                'group' => 'ai',
                'brand' => '#B45309',
                'soft'  => '#FDF3E3',
                'exts'  => ['ai', 'eps', 'zip'],
                'mime'  => [
                    'ai'  => 'application/postscript',
                    'eps' => 'application/postscript',
                    'zip' => 'application/zip',
                ],
                'note'  => 'ভেক্টর Adobe Illustrator ফাইল — লোগো, লেটারহেড ও প্রিন্ট-রেডি ডিজাইন, যেকোনো সাইজে স্কেল করা যায়।',
            ],
            'plp' => [
                'label' => 'PLP Files',
                'full'  => 'PixelLab',
                'hub'   => 'offlinefiles',
                'group' => 'plp',
                'brand' => '#7C3AED',
                'soft'  => '#F2EEFD',
                'exts'  => ['plp', 'zip'],
                'mime'  => [
                    'plp' => 'application/octet-stream',
                    'zip' => 'application/zip',
                ],
                'note'  => 'মোবাইলে PixelLab দিয়ে খোলা ও এডিট করার জন্য প্রস্তুত PLP প্রজেক্ট ফাইল — টেক্সট, ছবি ও কালার বদলে ব্যবহার করুন।',
            ],
        ];
    }
}

if (!function_exists('repdoc_template_groups')) {
    /** @return array<string,array{label:string,sub:string}> */
    function repdoc_template_groups(): array
    {
        return [
            'office' => ['label' => 'MS Office', 'sub' => 'Word, Excel, PowerPoint'],
            'psd'    => ['label' => 'PSD Files', 'sub' => 'Photoshop Design'],
            'ai'     => ['label' => 'AI Files',  'sub' => 'Illustrator Vector'],
            'plp'    => ['label' => 'PLP Files', 'sub' => 'Poster / Flex Design'],
        ];
    }
}

if (!function_exists('repdoc_template_title')) {
    /** Human-readable title from a filename (keeps Bengali stems intact). */
    function repdoc_template_title(string $name): string
    {
        $stem = (string) pathinfo($name, PATHINFO_FILENAME);
        $stem = str_replace(['-', '_'], ' ', $stem);
        $stem = (string) preg_replace('/\s+/u', ' ', trim($stem));
        if ($stem !== '' && preg_match('/^[\x20-\x7E]+$/', $stem)) {
            $words = explode(' ', $stem);
            foreach ($words as $i => $word) {
                if ($word !== '' && !preg_match('/^[A-Z0-9]{2,}$/', $word)) {
                    $words[$i] = mb_convert_case($word, MB_CASE_TITLE, 'UTF-8');
                }
            }
            $stem = implode(' ', $words);
        }
        return $stem !== '' ? $stem : $name;
    }
}

if (!function_exists('repdoc_template_bundled_descriptions')) {
    /** @return array<string,string> keyed by "type:filename". */
    function repdoc_template_bundled_descriptions(): array
    {
        static $cache = null;
        if (is_array($cache)) return $cache;

        $cache = [];
        $path = REPDOC_ROOT . DIRECTORY_SEPARATOR . 'officefiles' . DIRECTORY_SEPARATOR . 'descriptions.json';
        if (!is_file($path)) return $cache;

        $raw = @file_get_contents($path);
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        if (is_array($decoded)) {
            foreach ($decoded as $key => $value) {
                $text = trim((string) $value);
                if ($text !== '') $cache[(string) $key] = $text;
            }
        }
        return $cache;
    }
}

if (!function_exists('repdoc_template_description')) {
    /** Best available description for one published file. */
    function repdoc_template_description(string $type, string $name): string
    {
        $type = strtolower(trim($type));
        $name = trim($name);
        $key = $type . ':' . $name;

        if (function_exists('repdoc_file_descriptions_all')) {
            $rows = repdoc_file_descriptions_all();
            $custom = trim((string) ($rows[$key] ?? ''));
            if ($custom !== '') return $custom;
        }

        $bundled = repdoc_template_bundled_descriptions();
        $packaged = trim((string) ($bundled[$key] ?? ''));
        if ($packaged !== '') return $packaged;

        $formats = repdoc_template_formats();
        return (string) ($formats[$type]['note'] ?? '');
    }
}

if (!function_exists('repdoc_template_mime')) {
    function repdoc_template_mime(string $type, string $ext): string
    {
        $formats = repdoc_template_formats();
        return (string) ($formats[strtolower($type)]['mime'][strtolower($ext)] ?? 'application/octet-stream');
    }
}

if (!function_exists('repdoc_template_base_slug')) {
    /** ASCII slug from a filename; non-ASCII stems get a stable short hash. */
    function repdoc_template_base_slug(string $type, string $name): string
    {
        $stem = (string) pathinfo($name, PATHINFO_FILENAME);
        $slug = strtolower($stem);
        $slug = (string) preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        if ($slug === '') {
            $slug = 'tpl-' . substr(sha1(strtolower(trim($type)) . ':' . trim($name)), 0, 10);
        }
        return $slug;
    }
}

if (!function_exists('repdoc_template_scan_type')) {
    /** Scan one format folder, newest first. */
    function repdoc_template_scan_type(string $type): array
    {
        $formats = repdoc_template_formats();
        $type = strtolower(trim($type));
        $meta = $formats[$type] ?? null;
        if ($meta === null) return [];

        $dir = REPDOC_ROOT . DIRECTORY_SEPARATOR . $meta['hub'] . DIRECTORY_SEPARATOR . $type;
        if (!is_dir($dir)) return [];

        $out = [];
        foreach (@scandir($dir) ?: [] as $name) {
            if ($name === '.' || $name === '..' || str_starts_with($name, '.')) continue;
            $ext = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, $meta['exts'], true)) continue;
            $path = $dir . DIRECTORY_SEPARATOR . $name;
            if (!is_file($path)) continue;
            $out[] = [
                'type'  => $type,
                'name'  => $name,
                'ext'   => $ext,
                'size'  => (int) @filesize($path),
                'mtime' => (int) @filemtime($path),
            ];
        }
        usort($out, static fn(array $a, array $b): int => $b['mtime'] <=> $a['mtime']);
        return $out;
    }
}

if (!function_exists('repdoc_template_index')) {
    /**
     * Build the complete catalogue index once per request.
     *
     * @return array{files:array<string,array<string,mixed>>,by_slug:array<string,array<string,string>>}
     */
    function repdoc_template_index(bool $refresh = false): array
    {
        static $cache = null;
        if (!$refresh && is_array($cache)) return $cache;

        $formats = repdoc_template_formats();
        $rows = [];
        foreach ($formats as $type => $meta) {
            foreach (repdoc_template_scan_type($type) as $row) {
                $row['title'] = repdoc_template_title((string) $row['name']);
                $row['description'] = repdoc_template_description($type, (string) $row['name']);
                $row['thumb'] = repdoc_file_thumb_url($type, (string) $row['name']);
                $row['slug'] = repdoc_template_base_slug($type, (string) $row['name']);
                $row['key'] = $type . ':' . $row['name'];
                $rows[] = $row;
            }
        }

        $groups = [];
        foreach ($rows as $i => $row) {
            $groups[$row['type']][$row['slug']][] = $i;
        }
        foreach ($groups as $type => $slugs) {
            foreach ($slugs as $base => $idxs) {
                if (count($idxs) === 1) continue;
                foreach ($idxs as $idx) {
                    $rows[$idx]['slug'] = $base . '-' . $rows[$idx]['ext'];
                }
            }
        }

        usort($rows, static fn(array $a, array $b): int => $b['mtime'] <=> $a['mtime']);

        $files = [];
        $bySlug = [];
        foreach ($rows as $row) {
            $path = 'showcase/' . $row['type'] . '/' . $row['slug'] . '/';
            $row['path'] = $path;
            $row['href'] = repdoc_url($path);
            $row['canonical'] = repdoc_canonical($path);
            $files[$row['key']] = $row;
            $bySlug[$row['type']][$row['slug']] = $row['key'];
        }

        $cache = ['files' => $files, 'by_slug' => $bySlug];
        return $cache;
    }
}

if (!function_exists('repdoc_template_find')) {
    function repdoc_template_find(string $type, string $name): ?array
    {
        $index = repdoc_template_index();
        return $index['files'][strtolower(trim($type)) . ':' . trim($name)] ?? null;
    }
}

if (!function_exists('repdoc_template_find_by_slug')) {
    function repdoc_template_find_by_slug(string $type, string $slug): ?array
    {
        $type = strtolower(trim($type));
        $slug = strtolower(trim(rawurldecode($slug)));
        $index = repdoc_template_index();
        $key = $index['by_slug'][$type][$slug] ?? null;
        return $key !== null ? ($index['files'][$key] ?? null) : null;
    }
}

if (!function_exists('repdoc_template_href')) {
    function repdoc_template_href(string $type, string $name): string
    {
        $row = repdoc_template_find($type, $name);
        return $row !== null ? (string) $row['href'] : '';
    }
}

if (!function_exists('repdoc_template_canonical')) {
    function repdoc_template_canonical(string $type, string $name): string
    {
        $row = repdoc_template_find($type, $name);
        return $row !== null ? (string) $row['canonical'] : '';
    }
}

if (!function_exists('repdoc_template_files')) {
    /**
     * Published files, newest first.
     * @param string|string[] $types Empty = every format.
     * @return array<int,array<string,mixed>>
     */
    function repdoc_template_files(string|array $types = []): array
    {
        $index = repdoc_template_index();
        $wanted = is_array($types) ? array_map('strtolower', $types) : ($types !== '' ? [strtolower($types)] : []);
        $out = [];
        foreach ($index['files'] as $row) {
            if ($wanted !== [] && !in_array($row['type'], $wanted, true)) continue;
            $out[] = $row;
        }
        usort($out, static fn(array $a, array $b): int => $b['mtime'] <=> $a['mtime']);
        return $out;
    }
}

if (!function_exists('repdoc_template_related')) {
    /** Same-format templates first, then other formats as filler. */
    function repdoc_template_related(string $type, string $name, int $limit = 6): array
    {
        $index = repdoc_template_index();
        $formats = repdoc_template_formats();
        $type = strtolower(trim($type));
        $group = (string) ($formats[$type]['group'] ?? '');

        $same = [];
        $groupOther = [];
        $rest = [];
        foreach ($index['files'] as $row) {
            if ($row['type'] === $type && $row['name'] === $name) continue;
            if ($row['type'] === $type) {
                $same[] = $row;
            } elseif ($group !== '' && (string) ($formats[$row['type']]['group'] ?? '') === $group) {
                $groupOther[] = $row;
            } else {
                $rest[] = $row;
            }
        }

        $pick = array_slice(array_merge($same, $groupOther, $rest), 0, max(1, $limit));
        return $pick;
    }
}

if (!function_exists('repdoc_template_thumb_canonical')) {
    function repdoc_template_thumb_canonical(array $row): string
    {
        $type = (string) ($row['type'] ?? '');
        $name = (string) ($row['name'] ?? '');
        if ($type === '' || $name === '') return '';
        $path = repdoc_file_thumb_path($type, $name);
        if ($path === '') return '';
        return repdoc_canonical('file-thumbs/' . strtolower($type) . '/' . basename($path));
    }
}

if (!function_exists('repdoc_template_social_image')) {
    /** @return array{url:string,width:?int,height:?int,alt:string} */
    function repdoc_template_social_image(array $row): array
    {
        $type = (string) ($row['type'] ?? '');
        $name = (string) ($row['name'] ?? '');
        $title = (string) ($row['title'] ?? repdoc_template_title($name));
        $formats = repdoc_template_formats();
        $full = (string) ($formats[$type]['full'] ?? 'Repdoc');

        $path = $type !== '' && $name !== '' ? repdoc_file_thumb_path($type, $name) : '';
        if ($path !== '') {
            $info = @getimagesize($path);
            return [
                'url'    => repdoc_canonical('file-thumbs/' . strtolower($type) . '/' . basename($path)),
                'width'  => $info !== false ? (int) $info[0] : null,
                'height' => $info !== false ? (int) $info[1] : null,
                'alt'    => $title . ' — ' . $full . ' টেমপ্লেট প্রিভিউ',
            ];
        }

        return [
            'url'    => repdoc_canonical('src/assets/image/og-cover.png'),
            'width'  => 1200,
            'height' => 630,
            'alt'    => $title . ' — ' . $full . ' টেমপ্লেট',
        ];
    }
}

if (!function_exists('repdoc_template_trim')) {
    /** Trim at a word boundary and append an ellipsis. */
    function repdoc_template_trim(string $text, int $max): string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));
        if (mb_strlen($text, 'UTF-8') <= $max) return $text;
        $cut = mb_substr($text, 0, max(1, $max - 1), 'UTF-8');
        $pos = mb_strrpos($cut, ' ', 0, 'UTF-8');
        if ($pos !== false && $pos > max(10, (int) ($max * 0.5))) {
            $cut = mb_substr($cut, 0, $pos, 'UTF-8');
        }
        return rtrim($cut, " -–—") . '…';
    }
}

if (!function_exists('repdoc_template_meta_title')) {
    function repdoc_template_meta_title(string $title, string $formatFull): string
    {
        $title = trim($title);
        $withFormat = $title . ' — ' . $formatFull . ' টেমপ্লেট | Repdoc';
        if (mb_strlen($withFormat, 'UTF-8') <= 60) return $withFormat;

        $suffix = ' — Repdoc';
        $room = 60 - mb_strlen($suffix, 'UTF-8');
        return repdoc_template_trim($title, $room) . $suffix;
    }
}

if (!function_exists('repdoc_template_meta_description')) {
    function repdoc_template_meta_description(string $description, string $formatFull): string
    {
        $description = trim((string) preg_replace('/\s+/u', ' ', $description));
        $suffix = ' — Repdoc-এর ফ্রি ' . $formatFull . ' টেমপ্লেট, লগ ইন করে ডাউনলোড করুন।';

        if ($description === '') $description = ltrim($suffix, ' —');
        if (mb_strlen($description, 'UTF-8') < 70) $description .= $suffix;

        return repdoc_template_trim($description, 160);
    }
}

if (!function_exists('repdoc_template_usage_steps')) {
    /** @return string[] */
    function repdoc_template_usage_steps(string $type): array
    {
        return match (strtolower($type)) {
            'word' => [
                'ডাউনলোড করা ফাইলটি Microsoft Word-এ খুলুন।',
                'নাম, ঠিকানা, তারিখ ও প্রতিষ্ঠানের তথ্য নিজের মতো বদলান।',
                'প্রিন্ট করুন অথবা PDF হিসেবে সংরক্ষণ করুন।',
            ],
            'excel' => [
                'ফাইলটি Microsoft Excel-এ খুলুন।',
                'নিজের হিসাব, তালিকা বা বাজেটের ডেটা বসান; ফর্মুলা ঠিক আছে কি না দেখে নিন।',
                'প্রয়োজন হলে PDF বা প্রিন্ট আউট নিন।',
            ],
            'ppt' => [
                'ফাইলটি Microsoft PowerPoint-এ খুলুন।',
                'স্লাইডের টেক্সট, ছবি ও ব্র্যান্ডের তথ্য নিজের মতো সাজান।',
                'প্রেজেন্টেশন হিসেবে সংরক্ষণ করুন বা PDF-এ এক্সপোর্ট করুন।',
            ],
            'psd' => [
                'ফাইলটি Adobe Photoshop-এ খুলুন।',
                'লেয়ার, টেক্সট ও ছবি এডিট করে নিজের ব্র্যান্ডের তথ্য বসান।',
                'প্রিন্ট বা ওয়েবের জন্য PNG/JPG হিসেবে এক্সপোর্ট করুন।',
            ],
            'ai' => [
                'ফাইলটি Adobe Illustrator-এ খুলুন।',
                'ভেক্টর লোগো, টেক্সট ও রঙ নিজের প্রয়োজন অনুযায়ী এডিট করুন।',
                'প্রিন্ট-রেডি PDF বা SVG হিসেবে এক্সপোর্ট করুন।',
            ],
            'plp' => [
                'PLP ফাইলটি PixelLab-এ ইমপোর্ট করুন।',
                'টেক্সট, ছবি ও কালার বদলে নিজের ডিজাইন তৈরি করুন।',
                'প্রজেক্টটি সংরক্ষণ করুন বা ছবি হিসেবে এক্সপোর্ট করুন।',
            ],
            default => [
                'ফাইলটি ডাউনলোড করুন।',
                'নিজের তথ্য বসিয়ে প্রয়োজনে এডিট করুন।',
                'প্রিন্ট করুন বা সংরক্ষণ করুন।',
            ],
        };
    }
}
