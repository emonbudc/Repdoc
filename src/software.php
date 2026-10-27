<?php
declare(strict_types=1);

/**
 * Repdoc — software downloads directory store.
 *
 * Entries live in love/software.json, the same file-backed pattern used by
 * important links. The public page reads only active, valid rows; the admin
 * editor reads every row so a bad manual edit can still be corrected.
 *
 * Thumbnails are not stored in the JSON. They use the existing thumbnail
 * helper with the format key "software" and the stable entry id, so renaming
 * an entry never orphans its image.
 */

if (!function_exists('repdoc_software_file')) {
    function repdoc_software_file(): string
    {
        return dirname(__DIR__) . '/love/software.json';
    }
}

if (!function_exists('repdoc_software_platforms')) {
    /** Platform key => display metadata used by the admin and public pages. */
    function repdoc_software_platforms(): array
    {
        return [
            'windows' => [
                'label' => 'Windows',
                'full'  => 'Windows Software',
                'sub'   => 'Windows 10 / 11',
                'admin' => 'Windows',
                'brand' => '#0F6CBD',
                'soft'  => '#EAF3FB',
                'ext'   => 'exe',
                'note'  => 'Windows 10 ও 11-এর জন্য নির্বাচিত সফটওয়্যার — ডাউনলোড করে ইনস্টল করুন।',
            ],
            'android' => [
                'label' => 'Android',
                'full'  => 'Android App',
                'sub'   => 'APK / Play Store',
                'admin' => 'Android',
                'brand' => '#1F8A4C',
                'soft'  => '#EAFBF1',
                'ext'   => 'apk',
                'note'  => 'Android ডিভাইসের জন্য নির্বাচিত অ্যাপ — APK ইনস্টল করে ব্যবহার করুন।',
            ],
            'macos' => [
                'label' => 'macOS',
                'full'  => 'Mac Software',
                'sub'   => 'Apple Silicon / Intel',
                'admin' => 'macOS',
                'brand' => '#475569',
                'soft'  => '#EEF2F6',
                'ext'   => 'dmg',
                'note'  => 'Mac-এর জন্য নির্বাচিত সফটওয়্যার — Apple Silicon ও Intel উভয়ের জন্য যাচাই করে নিন।',
            ],
            'linux' => [
                'label' => 'Linux',
                'full'  => 'Linux Software',
                'sub'   => 'DEB / RPM / AppImage',
                'admin' => 'Linux',
                'brand' => '#B45309',
                'soft'  => '#FDF3E3',
                'ext'   => 'deb',
                'note'  => 'Linux ডিস্ট্রিবিউশনের জন্য নির্বাচিত সফটওয়্যার — প্যাকেজ ফরম্যাট যাচাই করে নিন।',
            ],
            'other' => [
                'label' => 'অন্যান্য',
                'full'  => 'Other Software',
                'sub'   => 'Portable / Web / অন্যান্য',
                'admin' => 'Other',
                'brand' => '#7C3AED',
                'soft'  => '#F2EEFD',
                'ext'   => 'zip',
                'note'  => 'প্ল্যাটফর্ম-নিরপেক্ষ, পোর্টেবল বা অন্যান্য সফটওয়্যার — প্রকাশকের নির্দেশনা অনুসরণ করুন।',
            ],
        ];
    }
}

if (!function_exists('repdoc_software_platform')) {
    function repdoc_software_platform(string $key): string
    {
        $key = strtolower(trim($key));
        return array_key_exists($key, repdoc_software_platforms()) ? $key : 'other';
    }
}

if (!function_exists('repdoc_software_platform_meta')) {
    function repdoc_software_platform_meta(string $key): array
    {
        $platforms = repdoc_software_platforms();
        return $platforms[repdoc_software_platform($key)];
    }
}

if (!function_exists('repdoc_software_text')) {
    /** Strip markup/control characters and trim a text field. */
    function repdoc_software_text(string $value, int $max): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", trim($value));
        $value = strip_tags($value);
        $value = (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
        $value = trim((string) preg_replace('/[ \t]+/u', ' ', $value));
        return mb_substr($value, 0, $max, 'UTF-8');
    }
}

if (!function_exists('repdoc_software_download_url')) {
    /** Require an absolute http(s) download URL. Returns '' when unsafe. */
    function repdoc_software_download_url(string $value): string
    {
        $value = trim($value);
        if ($value === '' || mb_strlen($value, 'UTF-8') > 1000) return '';
        if (preg_match('/[\x00-\x20\x7F]/u', $value)) return '';

        $parts = parse_url($value);
        if (!is_array($parts)) return '';
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (!in_array($scheme, ['http', 'https'], true)) return '';
        if (empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) return '';
        return $value;
    }
}

if (!function_exists('repdoc_software_host')) {
    function repdoc_software_host(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (is_string($host) && $host !== '') {
            return (string) preg_replace('/^www\./i', '', $host);
        }
        return 'external';
    }
}

if (!function_exists('repdoc_software_size_bytes')) {
    /** Convert labels like "40 MB", "1.5 GB" or plain "40" into bytes. */
    function repdoc_software_size_bytes(string $value): int
    {
        $value = strtoupper(str_replace([',', ' '], '', trim($value)));
        if ($value === '') return 0;
        if (!preg_match('/^([0-9]+(?:\.[0-9]+)?)(B|KB|MB|GB|TB)?$/', $value, $m)) return 0;

        $number = (float) $m[1];
        $unit = ($m[2] ?? '') !== '' ? $m[2] : 'MB';
        $multipliers = [
            'B'  => 1,
            'KB' => 1024,
            'MB' => 1024 * 1024,
            'GB' => 1024 * 1024 * 1024,
            'TB' => 1024 * 1024 * 1024 * 1024,
        ];
        return (int) round($number * ($multipliers[$unit] ?? $multipliers['MB']));
    }
}

if (!function_exists('repdoc_software_new_id')) {
    function repdoc_software_new_id(): string
    {
        return bin2hex(random_bytes(8));
    }
}

if (!function_exists('repdoc_software_valid_id')) {
    function repdoc_software_valid_id(string $id): bool
    {
        return (bool) preg_match('/^[a-f0-9]{16}$/', $id);
    }
}

if (!function_exists('repdoc_software_prepare')) {
    /**
     * Normalize one row. Returns [entry, error]; the entry is still useful
     * for showing invalid values in the admin editor.
     *
     * @return array{0:array,1:string}
     */
    function repdoc_software_prepare(array $row, bool $newId = false): array
    {
        $id = strtolower(trim((string) ($row['id'] ?? '')));
        if (!repdoc_software_valid_id($id)) $id = '';

        $platform = repdoc_software_platform((string) ($row['platform'] ?? 'other'));
        $name = repdoc_software_text((string) ($row['name'] ?? ''), 120);
        $version = repdoc_software_text((string) ($row['version'] ?? ''), 40);
        $size = repdoc_software_text((string) ($row['size'] ?? ''), 40);
        $description = repdoc_software_text((string) ($row['description'] ?? ''), 800);
        $rawUrl = trim((string) ($row['url'] ?? ''));
        $url = repdoc_software_download_url($rawUrl);
        $active = !empty($row['active']) && (string) $row['active'] !== '0';

        $created = max(0, (int) ($row['created_at'] ?? 0));
        $updated = max(0, (int) ($row['updated_at'] ?? 0));
        if ($created === 0) $created = time();
        if ($updated === 0) $updated = $created;

        if ($id === '') {
            $id = $newId
                ? repdoc_software_new_id()
                : substr(sha1($platform . ':' . $name . ':' . $rawUrl), 0, 16);
        }

        $entry = [
            'id'          => $id,
            'name'        => $name,
            'platform'    => $platform,
            'version'     => $version,
            'size'        => $size,
            'description' => $description,
            'url'         => $url !== '' ? $url : $rawUrl,
            'active'      => $active,
            'created_at'  => $created,
            'updated_at'  => $updated,
        ];

        $error = '';
        if ($name === '') {
            $error = 'Name is required.';
        } elseif ($rawUrl === '') {
            $error = 'Download URL is required.';
        } elseif ($url === '') {
            $error = 'Use a full http(s) download URL.';
        }

        return [$entry, $error];
    }
}

if (!function_exists('repdoc_software_load_all')) {
    /**
     * Load every stored row, including invalid manual edits.
     * Each row gets "valid" and "error" keys for the admin editor.
     */
    function repdoc_software_load_all(): array
    {
        $file = repdoc_software_file();
        if (!is_file($file)) return [];

        $decoded = json_decode((string) @file_get_contents($file), true);
        if (!is_array($decoded)) return [];
        if (isset($decoded['items']) && is_array($decoded['items'])) {
            $decoded = $decoded['items'];
        }

        $out = [];
        foreach ($decoded as $row) {
            if (!is_array($row)) continue;
            [$entry, $error] = repdoc_software_prepare($row);
            if ($entry['name'] === '' && $entry['url'] === '' && $entry['description'] === '') {
                continue;
            }
            $entry['valid'] = $error === '';
            $entry['error'] = $error;
            $out[] = $entry;
        }
        return $out;
    }
}

if (!function_exists('repdoc_software_load_public')) {
    /** Active + valid rows only, in the admin-controlled order. */
    function repdoc_software_load_public(): array
    {
        return array_values(array_filter(
            repdoc_software_load_all(),
            static fn(array $row): bool => !empty($row['valid']) && !empty($row['active'])
        ));
    }
}

if (!function_exists('repdoc_software_find')) {
    function repdoc_software_find(string $id): ?array
    {
        if (!repdoc_software_valid_id($id)) return null;
        foreach (repdoc_software_load_all() as $row) {
            if ($row['id'] === $id) return $row;
        }
        return null;
    }
}

if (!function_exists('repdoc_software_export_row')) {
    function repdoc_software_export_row(array $row): array
    {
        return [
            'id'          => (string) $row['id'],
            'name'        => (string) $row['name'],
            'platform'    => (string) $row['platform'],
            'version'     => (string) $row['version'],
            'size'        => (string) $row['size'],
            'description' => (string) $row['description'],
            'url'         => (string) $row['url'],
            'active'      => !empty($row['active']),
            'created_at'  => (int) $row['created_at'],
            'updated_at'  => (int) $row['updated_at'],
        ];
    }
}

if (!function_exists('repdoc_software_write')) {
    function repdoc_software_write(array $rows): bool
    {
        $clean = [];
        foreach ($rows as $row) {
            if (is_array($row)) $clean[] = repdoc_software_export_row($row);
        }

        $json = json_encode($clean, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) return false;
        $json .= "\n";

        $file = repdoc_software_file();
        $dir = dirname($file);
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) return false;

        $tmp = $file . '.tmp';
        if (@file_put_contents($tmp, $json) === false) {
            @unlink($tmp);
            return false;
        }
        if (!@rename($tmp, $file)) {
            @unlink($tmp);
            return false;
        }
        @chmod($file, 0644);
        return true;
    }
}

if (!function_exists('repdoc_software_save_entry')) {
    /**
     * Create or update one entry.
     *
     * @return array{ok:bool,id:string,error:string,entry:?array}
     */
    function repdoc_software_save_entry(array $input, ?string $id = null): array
    {
        $rows = repdoc_software_load_all();
        $index = null;
        $existing = null;

        if ($id !== null && $id !== '') {
            if (!repdoc_software_valid_id($id)) {
                return ['ok' => false, 'id' => '', 'error' => 'Unknown software entry.', 'entry' => null];
            }
            foreach ($rows as $i => $row) {
                if ($row['id'] === $id) {
                    $index = $i;
                    $existing = $row;
                    break;
                }
            }
            if ($existing === null) {
                return ['ok' => false, 'id' => '', 'error' => 'Software entry not found.', 'entry' => null];
            }
            $input['id'] = $id;
            $input['created_at'] = $existing['created_at'];
            $input['updated_at'] = time();
        } else {
            $input['created_at'] = time();
            $input['updated_at'] = time();
        }

        [$entry, $error] = repdoc_software_prepare($input, $index === null);
        if ($error !== '') {
            return ['ok' => false, 'id' => $entry['id'], 'error' => $error, 'entry' => $entry];
        }

        if ($index !== null) {
            $rows[$index] = $entry;
        } else {
            array_unshift($rows, $entry);
        }

        if (!repdoc_software_write($rows)) {
            return ['ok' => false, 'id' => '', 'error' => 'Could not write love/software.json. Check folder permissions.', 'entry' => null];
        }
        return ['ok' => true, 'id' => $entry['id'], 'error' => '', 'entry' => $entry];
    }
}

if (!function_exists('repdoc_software_delete_entry')) {
    function repdoc_software_delete_entry(string $id): bool
    {
        if (!repdoc_software_valid_id($id)) return false;

        $rows = repdoc_software_load_all();
        $kept = array_values(array_filter($rows, static fn(array $row): bool => $row['id'] !== $id));
        if (count($kept) === count($rows)) return false;
        return repdoc_software_write($kept);
    }
}

if (!function_exists('repdoc_software_move_entry')) {
    function repdoc_software_move_entry(string $id, string $direction): bool
    {
        if (!repdoc_software_valid_id($id) || !in_array($direction, ['up', 'down'], true)) return false;

        $rows = repdoc_software_load_all();
        $index = null;
        foreach ($rows as $i => $row) {
            if ($row['id'] === $id) {
                $index = $i;
                break;
            }
        }
        if ($index === null) return false;

        $swap = $direction === 'up' ? $index - 1 : $index + 1;
        if ($swap < 0 || $swap >= count($rows)) return false;

        $tmp = $rows[$index];
        $rows[$index] = $rows[$swap];
        $rows[$swap] = $tmp;
        return repdoc_software_write($rows);
    }
}

if (!function_exists('repdoc_software_toggle_entry')) {
    function repdoc_software_toggle_entry(string $id): bool
    {
        if (!repdoc_software_valid_id($id)) return false;

        $rows = repdoc_software_load_all();
        $found = false;
        foreach ($rows as $i => $row) {
            if ($row['id'] !== $id) continue;
            $rows[$i]['active'] = empty($row['active']);
            $rows[$i]['updated_at'] = time();
            $found = true;
            break;
        }
        if (!$found) return false;
        return repdoc_software_write($rows);
    }
}

