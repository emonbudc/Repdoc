<?php
declare(strict_types=1);

/**
 * Repdoc — mouza map browser helpers (admin only).
 *
 * The map collection lives on a public Google Drive folder as
 * "<JL>-<Mouza>-<sheet>.pdf" files. Nothing is copied into this repo: the
 * manifest (maps/data/manifest.php) stores the Drive file id per sheet, and
 * downloads are handed off to Drive through admin/maps-download.php.
 */

if (!function_exists('repdoc_maps_manifest')) {
    /** Parsed manifest, cached for the request. */
    function repdoc_maps_manifest(): array
    {
        static $manifest = null;
        if (is_array($manifest)) return $manifest;

        $path = REPDOC_ROOT . '/maps/data/manifest.php';
        $data = is_file($path) ? require $path : [];
        $manifest = is_array($data) ? $data : [];
        return $manifest;
    }
}

if (!function_exists('repdoc_maps_bn_digits')) {
    /** 12 → ১২ (Bengali numerals for display only). */
    function repdoc_maps_bn_digits(string|int $value): string
    {
        return strtr((string) $value, [
            '0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪',
            '5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯',
        ]);
    }
}

if (!function_exists('repdoc_maps_districts')) {
    /** @return array<int,array> district rows from the manifest. */
    function repdoc_maps_districts(): array
    {
        $rows = repdoc_maps_manifest()['districts'] ?? [];
        return is_array($rows) ? array_values(array_filter($rows, 'is_array')) : [];
    }
}

if (!function_exists('repdoc_maps_district')) {
    function repdoc_maps_district(int $index = 0): ?array
    {
        $districts = repdoc_maps_districts();
        return $districts[$index] ?? null;
    }
}

if (!function_exists('repdoc_maps_upazilas')) {
    /** @return array<int,array> upazila rows of one district. */
    function repdoc_maps_upazilas(array $district): array
    {
        $rows = $district['upazilas'] ?? [];
        return is_array($rows) ? array_values(array_filter($rows, 'is_array')) : [];
    }
}

if (!function_exists('repdoc_maps_search_key')) {
    /**
     * Fold a name/JL into a comparable key.
     *
     * Drive titles are transliterated ("Dokkin Atkoriya"), the collection also
     * has Bengali names, and Bengali has two encodings for য় — comparing raw
     * bytes would miss half the matches.
     */
    function repdoc_maps_search_key(string $value): string
    {
        $value = trim($value);
        if ($value === '') return '';
        $value = mb_strtolower($value, 'UTF-8');
        $value = strtr($value, [
            'য়' => 'য', 'য়' => 'য', 'ড়' => 'র', 'ঢ়' => 'ঢ',
            '০' => '0', '১' => '1', '২' => '2', '৩' => '3', '৪' => '4',
            '৫' => '5', '৬' => '6', '৭' => '7', '৮' => '8', '৯' => '9',
        ]);
        $value = (string) preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value);
        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }
}

if (!function_exists('repdoc_maps_stats')) {
    /** @return array{mouzas:int,files:int,upazilas:int,upazilas_with_files:int} */
    function repdoc_maps_stats(array $district): array
    {
        $mouzas = 0;
        $files = 0;
        $withFiles = 0;
        foreach (repdoc_maps_upazilas($district) as $upazila) {
            $rows = is_array($upazila['mouzas'] ?? null) ? $upazila['mouzas'] : [];
            $mouzas += count($rows);
            $sheetCount = 0;
            foreach ($rows as $mouza) {
                $sheetCount += count(is_array($mouza['sheets'] ?? null) ? $mouza['sheets'] : []);
            }
            $files += $sheetCount;
            if ($sheetCount > 0) $withFiles++;
        }
        return [
            'mouzas' => $mouzas,
            'files' => $files,
            'upazilas' => count(repdoc_maps_upazilas($district)),
            'upazilas_with_files' => $withFiles,
        ];
    }
}

if (!function_exists('repdoc_maps_filter')) {
    /**
     * Mouzas of one upazila filtered by a free-text query.
     *
     * Matches the mouza name (Bengali or transliterated), any alias, and the
     * JL number, so "১২", "12" and "atkoriya" all work.
     *
     * @return array<int,array>
     */
    function repdoc_maps_filter(array $upazila, string $query = ''): array
    {
        $rows = is_array($upazila['mouzas'] ?? null) ? array_values(array_filter($upazila['mouzas'], 'is_array')) : [];
        $needle = repdoc_maps_search_key($query);
        if ($needle === '') return $rows;

        $out = [];
        foreach ($rows as $mouza) {
            $haystack = [(string) ($mouza['name'] ?? ''), (string) ($mouza['jl'] ?? '')];
            foreach ((array) ($mouza['aliases'] ?? []) as $alias) {
                $haystack[] = (string) $alias;
            }
            foreach ($haystack as $candidate) {
                if (str_contains(repdoc_maps_search_key($candidate), $needle)) {
                    $out[] = $mouza;
                    break;
                }
            }
        }
        return $out;
    }
}

if (!function_exists('repdoc_maps_file_index')) {
    /**
     * Flat Drive-id → sheet index, used to validate every download request.
     *
     * @return array<string,array{id:string,file:string,sheet:int,rs:bool,jl:int,mouza:string,upazila_bn:string,upazila_en:string,district_bn:string}>
     */
    function repdoc_maps_file_index(): array
    {
        static $index = null;
        if (is_array($index)) return $index;

        $index = [];
        foreach (repdoc_maps_districts() as $district) {
            foreach (repdoc_maps_upazilas($district) as $upazila) {
                foreach ((array) ($upazila['mouzas'] ?? []) as $mouza) {
                    if (!is_array($mouza)) continue;
                    foreach ((array) ($mouza['sheets'] ?? []) as $sheet) {
                        if (!is_array($sheet) || empty($sheet['id'])) continue;
                        $index[(string) $sheet['id']] = [
                            'id'          => (string) $sheet['id'],
                            'file'        => (string) ($sheet['file'] ?? ''),
                            'sheet'       => (int) ($sheet['sheet'] ?? 1),
                            'rs'          => !empty($sheet['rs']),
                            'jl'          => (int) ($mouza['jl'] ?? 0),
                            'mouza'       => (string) ($mouza['name'] ?? ''),
                            'upazila_bn'  => (string) ($upazila['bn'] ?? ''),
                            'upazila_en'  => (string) ($upazila['en'] ?? ''),
                            'district_bn' => (string) ($district['bn'] ?? ''),
                        ];
                    }
                }
            }
        }
        return $index;
    }
}

if (!function_exists('repdoc_maps_file')) {
    function repdoc_maps_file(string $driveId): ?array
    {
        if (!preg_match('/^[A-Za-z0-9_-]{20,}$/', $driveId)) return null;
        return repdoc_maps_file_index()[$driveId] ?? null;
    }
}

if (!function_exists('repdoc_maps_download_url')) {
    /** Our gated endpoint — it logs the admin download and forwards to Drive. */
    function repdoc_maps_download_url(string $driveId): string
    {
        return repdoc_url('admin/maps-download.php') . '?id=' . rawurlencode($driveId);
    }
}

if (!function_exists('repdoc_maps_drive_url')) {
    /** Direct Drive download (large files get Drive's confirm page). */
    function repdoc_maps_drive_url(string $driveId): string
    {
        return 'https://drive.google.com/uc?export=download&confirm=t&id=' . rawurlencode($driveId);
    }
}

if (!function_exists('repdoc_maps_folder_url')) {
    function repdoc_maps_folder_url(string $folderId): string
    {
        return 'https://drive.google.com/drive/folders/' . rawurlencode($folderId);
    }
}
