<?php
declare(strict_types=1);

/**
 * Repdoc — UDC document archive store.
 *
 * The archive landing page (Entrepreneur/panel/landing.php) and the admin
 * editor (admin/document-archive.php) read and write the same file:
 * love/archive_items.json. When the file is missing the page falls back to the
 * built-in default set below, so a fresh install and the live site always
 * render something sensible.
 */

if (!function_exists('repdoc_archive_file')) {
    function repdoc_archive_file(): string
    {
        return dirname(__DIR__) . '/love/archive_items.json';
    }
}

if (!function_exists('repdoc_archive_data_dir')) {
    /** Folder that holds the actual blank templates (downloads). */
    function repdoc_archive_data_dir(): string
    {
        return dirname(__DIR__) . '/Entrepreneur/data';
    }
}

if (!function_exists('repdoc_archive_categories')) {
    function repdoc_archive_categories(): array
    {
        return [
            'certificate' => ['bn' => 'সার্টিফিকেট', 'admin' => 'Certificate'],
            'prottoyon'   => ['bn' => 'প্রত্যয়ন',    'admin' => 'Prottoyon'],
        ];
    }
}

if (!function_exists('repdoc_archive_tints')) {
    function repdoc_archive_tints(): array
    {
        return [
            'tint-blue'   => 'Blue',
            'tint-green'  => 'Green',
            'tint-violet' => 'Violet',
            'tint-amber'  => 'Amber',
            'tint-red'    => 'Red',
        ];
    }
}

if (!function_exists('repdoc_archive_icons')) {
    function repdoc_archive_icons(): array
    {
        return [
            'i-file'       => 'File',
            'i-certificate'=> 'Certificate',
            'i-graduation' => 'Graduation',
            'i-cap'        => 'Cap',
            'i-scroll'     => 'Scroll',
            'i-birth'      => 'Birth',
            'i-passport'   => 'Passport',
            'i-user-check' => 'Character',
            'i-income'     => 'Income',
            'i-flag'       => 'Nationality',
            'i-marriage'   => 'Marriage',
            'i-syringe'    => 'Health',
        ];
    }
}

if (!function_exists('repdoc_archive_kinds')) {
    function repdoc_archive_kinds(): array
    {
        return [
            'download'  => 'File download (Entrepreneur/data)',
            'external'  => 'External link (Google Drive / Docs)',
            'generator' => 'Live generator (prottoyon)',
            'page'      => 'Another page on this site',
        ];
    }
}

if (!function_exists('repdoc_archive_generator_types')) {
    /**
     * Generator types a card can point at, mirroring the prottoyon registry
     * (23 live types + the legacy certificates) so the admin dropdown never
     * offers a type the form cannot open.
     *
     * @return array<string, array{label:string,group:string,legacy:bool}>
     */
    function repdoc_archive_generator_types(): array
    {
        if (function_exists('prottoyon_all_types')) {
            $out = [];
            foreach (prottoyon_all_types() as $key => $row) {
                $out[(string) $key] = [
                    'label'  => (string) ($row['title'] ?? $key),
                    'group'  => (string) ($row['group'] ?? 'অন্যান্য'),
                    'legacy' => !empty($row['legacy']),
                ];
            }
            if ($out !== []) return $out;
        }

        return [
            'birth'         => ['label' => 'জন্ম প্রত্যয়নপত্র',   'group' => 'পুরাতন সনদ', 'legacy' => true],
            'character'     => ['label' => 'চারিত্রিক সনদপত্র',     'group' => 'পুরাতন সনদ', 'legacy' => true],
            'annual-income' => ['label' => 'বার্ষিক আয় প্রত্যয়নপত্র', 'group' => 'পুরাতন সনদ', 'legacy' => true],
            'nationality'   => ['label' => 'জাতীয়তা সনদপত্র',       'group' => 'পুরাতন সনদ', 'legacy' => true],
        ];
    }
}

if (!function_exists('repdoc_archive_text')) {
    /** Single-line-safe text: strip markup/control chars, collapse spaces, cap length. */
    function repdoc_archive_text(string $value, int $max = 200): string
    {
        $value = str_replace(["\r\n", "\r"], ' ', trim($value));
        $value = strip_tags($value);
        $value = (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
        $value = trim((string) preg_replace('/\s+/u', ' ', $value));
        return mb_substr($value, 0, $max, 'UTF-8');
    }
}

if (!function_exists('repdoc_archive_basename')) {
    /** A plain file name inside Entrepreneur/data — never a path. */
    function repdoc_archive_basename(string $file): string
    {
        $file = trim(str_replace('\\', '/', $file));
        if ($file === '' || str_contains($file, '..') || str_contains($file, '/')) {
            return '';
        }
        return mb_substr($file, 0, 160, 'UTF-8');
    }
}

if (!function_exists('repdoc_archive_file_ok')) {
    function repdoc_archive_file_ok(string $file): bool
    {
        $name = repdoc_archive_basename($file);
        return $name !== '' && is_file(repdoc_archive_data_dir() . '/' . $name);
    }
}

if (!function_exists('repdoc_archive_file_label')) {
    /** "PNG · 1.2 MB" for a file in Entrepreneur/data. */
    function repdoc_archive_file_label(string $file): string
    {
        $name = repdoc_archive_basename($file);
        $path = repdoc_archive_data_dir() . '/' . $name;
        if ($name === '' || !is_file($path)) return 'ফাইল নেই';

        $bytes = (int) @filesize($path);
        if ($bytes < 1024) {
            $size = $bytes . ' B';
        } elseif ($bytes < 1024 * 1024) {
            $size = number_format($bytes / 1024, 1) . ' KB';
        } else {
            $size = number_format($bytes / (1024 * 1024), 1) . ' MB';
        }
        return strtoupper((string) pathinfo($name, PATHINFO_EXTENSION)) . ' · ' . $size;
    }
}

if (!function_exists('repdoc_archive_files')) {
    /** Every template file available for download cards (for the admin picker). */
    function repdoc_archive_files(): array
    {
        $out = [];
        $dir = repdoc_archive_data_dir();
        if (!is_dir($dir)) return $out;

        foreach ((array) @scandir($dir) as $entry) {
            $entry = (string) $entry;
            if ($entry === '' || $entry[0] === '.') continue;
            if (!is_file($dir . '/' . $entry)) continue;
            $out[] = $entry;
        }
        sort($out, SORT_NATURAL | SORT_FLAG_CASE);
        return $out;
    }
}

if (!function_exists('repdoc_archive_url')) {
    /** Normalize a page/external URL: '' when unusable. */
    function repdoc_archive_url(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '' || preg_match('/[\x00-\x20\x7F]/u', $raw)) return '';

        if (str_starts_with($raw, '//')) return '';
        if (preg_match('~^https?://~i', $raw)) {
            $parts = parse_url($raw);
            if (!is_array($parts) || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
                return '';
            }
            return $raw;
        }
        // Site path (no scheme): keep it project-relative.
        if (str_contains($raw, ':')) return '';
        return $raw;
    }
}

if (!function_exists('repdoc_archive_defaults')) {
    /** The original hand-written card list, used until the store is saved once. */
    function repdoc_archive_defaults(): array
    {
        return [
            [
                'title' => 'High School Old', 'desc' => 'OLD High School Certificate blank template',
                'cat' => 'certificate', 'tag' => 'Certificate', 'icon' => 'i-graduation', 'tint' => 'tint-blue',
                'kind' => 'download', 'file' => 'oldblank.PNG', 'url' => '', 'type' => '', 'meta' => '',
                'action' => 'ডাউনলোড', 'bn' => 'মাধ্যমিক সনদ স্কুল সার্টিফিকেট',
            ],
            [
                'title' => 'Girls Old', 'desc' => 'Certificate template for grade 8 girls',
                'cat' => 'certificate', 'tag' => 'Certificate', 'icon' => 'i-certificate', 'tint' => 'tint-green',
                'kind' => 'download', 'file' => '8mgirls.jpg', 'url' => '', 'type' => '', 'meta' => '',
                'action' => 'ডাউনলোড', 'bn' => 'বালিকা সনদ অষ্টম শ্রেণি',
            ],
            [
                'title' => 'OLD Birth Certificate', 'desc' => 'Old Birth Certificate blank template',
                'cat' => 'certificate', 'tag' => 'Birth Cert', 'icon' => 'i-birth', 'tint' => 'tint-amber',
                'kind' => 'download', 'file' => 'oldbc.jpg', 'url' => '', 'type' => '', 'meta' => '',
                'action' => 'ডাউনলোড', 'bn' => 'জন্ম সনদ জন্মনিবন্ধন',
            ],
            [
                'title' => 'PSC Certificate', 'desc' => 'PSC Certificate blank form',
                'cat' => 'certificate', 'tag' => 'Certificate', 'icon' => 'i-file', 'tint' => 'tint-red',
                'kind' => 'download', 'file' => 'PSC.jpg', 'url' => '', 'type' => '', 'meta' => '',
                'action' => 'ডাউনলোড', 'bn' => 'পিএসসি প্রাথমিক সমাপনী সনদ',
            ],
            [
                'title' => 'Oxford KG Prottoyon', 'desc' => 'Prottoyon blank for birth proof (Oxford Kindergarten)',
                'cat' => 'prottoyon', 'tag' => 'Prottoyon', 'icon' => 'i-file', 'tint' => 'tint-violet',
                'kind' => 'download', 'file' => 'Oxford_Prottoyon.JPG', 'url' => '', 'type' => '', 'meta' => '',
                'action' => 'ডাউনলোড', 'bn' => 'অক্সফোর্ড কেজি প্রত্যয়ন জন্ম',
            ],
            [
                'title' => 'Gobarpara Primary Prottoyon', 'desc' => 'Gobarpara Primary School prottoyon blank for birth proof',
                'cat' => 'prottoyon', 'tag' => 'Prottoyon', 'icon' => 'i-file', 'tint' => 'tint-violet',
                'kind' => 'download', 'file' => 'Gobarpara_Primary.jpg', 'url' => '', 'type' => '', 'meta' => '',
                'action' => 'ডাউনলোড', 'bn' => 'গোবরপাড়া প্রাথমিক প্রত্যয়ন জন্ম',
            ],
            [
                'title' => 'Tika Card (EPI)', 'desc' => 'EPI Vaccination Card blank — Black & White',
                'cat' => 'certificate', 'tag' => 'Health Card', 'icon' => 'i-syringe', 'tint' => 'tint-green',
                'kind' => 'external', 'file' => '', 'type' => '', 'meta' => 'Google Docs', 'action' => 'খুলুন',
                'url' => 'https://docs.google.com/document/d/1v9rU5lP_wjHwexDzvcwfhhBAyNRzfYoN/edit?usp=sharing&ouid=108834765163028255543&rtpof=true&sd=true',
                'bn' => 'টিকা কার্ড ইপিআই স্বাস্থ্য',
            ],
            [
                'title' => 'Kabin Nama / Nikahnama', 'desc' => 'Marriage Certificate (Kabin/Nikahnama) PSD template',
                'cat' => 'certificate', 'tag' => 'PSD File', 'icon' => 'i-marriage', 'tint' => 'tint-amber',
                'kind' => 'external', 'file' => '', 'type' => '', 'meta' => 'PSD · Google Drive', 'action' => 'খুলুন',
                'url' => 'https://drive.google.com/file/d/1NWN_NM5OAmeSymwHGOQl5AMv5U4vyNgW/view?usp=sharing',
                'bn' => 'কবিন নামা নিকাহনামা বিবাহ',
            ],
            [
                'title' => 'Birth Registration Prottoyon', 'desc' => 'Official Birth Registration Certificate (Prottoyon Form)',
                'cat' => 'prottoyon', 'tag' => 'প্রত্যয়ন', 'icon' => 'i-passport', 'tint' => 'tint-blue',
                'kind' => 'generator', 'file' => '', 'url' => '', 'type' => 'birth',
                'meta' => 'Word · PDF · লাইভ জেনারেটর', 'action' => 'তৈরি করুন', 'bn' => 'জন্ম নিবন্ধন প্রত্যয়ন',
            ],
            [
                'title' => 'Character Certificate', 'desc' => 'School Character Certificate / Prottoyon for students',
                'cat' => 'prottoyon', 'tag' => 'Prottoyon', 'icon' => 'i-user-check', 'tint' => 'tint-green',
                'kind' => 'generator', 'file' => '', 'url' => '', 'type' => 'character',
                'meta' => 'Word · PDF · লাইভ জেনারেটর', 'action' => 'তৈরি করুন', 'bn' => 'চারিত্রিক সনদ',
            ],
            [
                'title' => 'Income Certificate', 'desc' => 'Union Parishad Income Certificate / Prottoyon format',
                'cat' => 'prottoyon', 'tag' => 'প্রত্যয়নপত্র', 'icon' => 'i-income', 'tint' => 'tint-amber',
                'kind' => 'generator', 'file' => '', 'url' => '', 'type' => 'annual-income',
                'meta' => 'Word · PDF · লাইভ জেনারেটর', 'action' => 'তৈরি করুন', 'bn' => 'আয় সনদ প্রত্যয়ন',
            ],
            [
                'title' => 'Nationality Certificate', 'desc' => 'Bangladesh Nationality Certificate template (প্রত্যয়নপত্র)',
                'cat' => 'certificate', 'tag' => 'Certificate', 'icon' => 'i-flag', 'tint' => 'tint-red',
                'kind' => 'generator', 'file' => '', 'url' => '', 'type' => 'nationality',
                'meta' => 'Word · PDF · লাইভ জেনারেটর', 'action' => 'তৈরি করুন', 'bn' => 'জাতীয়তা সনদ',
            ],
            [
                'title' => 'JSC Certificate', 'desc' => 'Junior School Certificate (JSC) blank template',
                'cat' => 'certificate', 'tag' => 'Certificate', 'icon' => 'i-scroll', 'tint' => 'tint-blue',
                'kind' => 'page', 'file' => '', 'type' => '', 'meta' => 'সনদ তালিকা', 'action' => 'খুলুন',
                'url' => 'genaretor/certificate/', 'bn' => 'জেএসসি সনদ',
            ],
            [
                'title' => 'SSC Certificate', 'desc' => 'Secondary School Certificate (SSC) blank template',
                'cat' => 'certificate', 'tag' => 'Certificate', 'icon' => 'i-cap', 'tint' => 'tint-green',
                'kind' => 'page', 'file' => '', 'type' => '', 'meta' => 'সনদ তালিকা', 'action' => 'খুলুন',
                'url' => 'genaretor/certificate/', 'bn' => 'এসএসসি সনদ',
            ],
        ];
    }
}

if (!function_exists('repdoc_archive_normalize')) {
    /**
     * Normalize one row. Returns the stored shape plus `valid`/`error` so the
     * admin editor can flag a broken row instead of dropping the admin's work.
     */
    function repdoc_archive_normalize(array $row): array
    {
        $cats  = repdoc_archive_categories();
        $tints = repdoc_archive_tints();
        $icons = repdoc_archive_icons();
        $kinds = repdoc_archive_kinds();
        $types = repdoc_archive_generator_types();

        $cat  = (string) ($row['cat'] ?? '');
        $kind = (string) ($row['kind'] ?? '');
        $icon = (string) ($row['icon'] ?? '');
        $tint = (string) ($row['tint'] ?? '');

        $item = [
            'title'  => repdoc_archive_text((string) ($row['title'] ?? ''), 120),
            'desc'   => repdoc_archive_text((string) ($row['desc'] ?? ''), 300),
            'cat'    => isset($cats[$cat]) ? $cat : 'certificate',
            'tag'    => repdoc_archive_text((string) ($row['tag'] ?? ''), 40),
            'icon'   => isset($icons[$icon]) ? $icon : 'i-file',
            'tint'   => isset($tints[$tint]) ? $tint : 'tint-blue',
            'kind'   => isset($kinds[$kind]) ? $kind : 'download',
            'file'   => repdoc_archive_basename((string) ($row['file'] ?? '')),
            'url'    => repdoc_archive_url((string) ($row['url'] ?? '')),
            'type'   => (string) ($row['type'] ?? ''),
            'meta'   => repdoc_archive_text((string) ($row['meta'] ?? ''), 80),
            'action' => repdoc_archive_text((string) ($row['action'] ?? ''), 40),
            'bn'     => repdoc_archive_text((string) ($row['bn'] ?? ''), 200),
        ];

        // Unknown generator type: keep a known one so the card still opens.
        if ($item['kind'] === 'generator' && !isset($types[$item['type']])) {
            $item['type'] = (string) (array_key_first($types) ?: 'birth');
        }
        if ($item['kind'] !== 'generator') $item['type'] = '';
        if ($item['kind'] !== 'download')  $item['file'] = '';
        if (!in_array($item['kind'], ['external', 'page'], true)) $item['url'] = '';
        if ($item['action'] === '') {
            $item['action'] = match ($item['kind']) {
                'download'  => 'ডাউনলোড',
                'generator' => 'তৈরি করুন',
                default     => 'খুলুন',
            };
        }
        if ($item['tag'] === '') {
            $item['tag'] = $item['cat'] === 'prottoyon' ? 'Prottoyon' : 'Certificate';
        }

        $error = '';
        if ($item['title'] === '') {
            $error = 'শিরোনাম দরকার।';
        } elseif ($item['kind'] === 'download' && $item['file'] === '') {
            $error = 'ডাউনলোডের জন্য ফাইল নাম দরকার।';
        } elseif ($item['kind'] === 'download' && !repdoc_archive_file_ok($item['file'])) {
            $error = 'ফাইলটা Entrepreneur/data-তে নেই — কার্ডে “ফাইল নেই” দেখাবে।';
        } elseif (in_array($item['kind'], ['external', 'page'], true) && $item['url'] === '') {
            $error = 'এই ধরনের কার্ডে লিংক দরকার।';
        }

        $item['valid'] = $error === '';
        $item['error'] = $error;
        return $item;
    }
}

if (!function_exists('repdoc_archive_load_raw')) {
    /** Rows exactly as stored (empty when the file is absent/invalid). */
    function repdoc_archive_load_raw(): array
    {
        $file = repdoc_archive_file();
        if (!is_file($file)) return [];

        $decoded = json_decode((string) @file_get_contents($file), true);
        if (!is_array($decoded)) return [];
        if (isset($decoded['items']) && is_array($decoded['items'])) {
            $decoded = $decoded['items'];
        }

        $rows = [];
        foreach ($decoded as $row) {
            if (is_array($row)) $rows[] = $row;
        }
        return $rows;
    }
}

if (!function_exists('repdoc_archive_items')) {
    /**
     * The card list used by the landing page and the admin editor.
     * Falls back to the built-in defaults until the store has been saved.
     */
    function repdoc_archive_items(): array
    {
        $raw = repdoc_archive_load_raw();
        if ($raw === []) {
            $raw = repdoc_archive_defaults();
        }

        $out = [];
        foreach ($raw as $row) {
            $out[] = repdoc_archive_normalize($row);
        }
        return $out;
    }
}

if (!function_exists('repdoc_archive_save')) {
    /**
     * Normalize + persist the card list (atomic write, same pattern as links).
     *
     * @return array{ok:bool,saved:int,invalid:int}
     */
    function repdoc_archive_save(array $rows): array
    {
        $out = [];
        $invalid = 0;
        foreach ($rows as $row) {
            if (!is_array($row)) continue;
            $item = repdoc_archive_normalize($row);
            if ($item['title'] === '' && $item['desc'] === '') continue;
            if (!$item['valid']) $invalid++;
            unset($item['valid'], $item['error']);
            $out[] = $item;
        }

        $payload = [
            'updated_at' => date('Y-m-d H:i:s'),
            'items'      => $out,
        ];
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return ['ok' => false, 'saved' => 0, 'invalid' => $invalid];
        }

        $file = repdoc_archive_file();
        $dir  = dirname($file);
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            return ['ok' => false, 'saved' => 0, 'invalid' => $invalid];
        }

        $tmp = $file . '.tmp';
        if (@file_put_contents($tmp, $json . "\n") === false) {
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

if (!function_exists('repdoc_archive_last_updated')) {
    function repdoc_archive_last_updated(): int
    {
        $file = repdoc_archive_file();
        return is_file($file) ? (int) @filemtime($file) : 0;
    }
}

if (!function_exists('repdoc_archive_href')) {
    /** Where one card points, resolved for the public page. */
    function repdoc_archive_href(array $item): string
    {
        return match ((string) ($item['kind'] ?? 'download')) {
            'generator' => repdoc_url('genaretor/prottoyon/') . '?type=' . rawurlencode((string) ($item['type'] ?? 'birth')),
            'external'  => (string) ($item['url'] ?? ''),
            'page'      => preg_match('~^https?://~i', (string) ($item['url'] ?? ''))
                ? (string) $item['url']
                : repdoc_url(ltrim((string) ($item['url'] ?? ''), '/')),
            default     => repdoc_url('Entrepreneur/panel/download.php') . '?file=' . rawurlencode((string) ($item['file'] ?? '')),
        };
    }
}
